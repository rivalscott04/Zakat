<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** PRD 17 — penomoran, klasifikasi, penelusuran, dan pembatasan akses audit. */
class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    private function scenario(): array
    {
        $organization = $this->organization();
        $admin = $this->member($organization);
        $this->loginAs($admin, $organization);

        return compact('organization', 'admin');
    }

    // ---------------------------------------------------------- pencatatan

    public function test_setiap_peristiwa_mendapat_nomor_kategori_dan_severity(): void
    {
        $s = $this->scenario();

        $this->postJson('/api/v1/amils', ['name' => 'Amil Audit'])->assertCreated();

        $log = AuditLog::withoutGlobalScopes()->where('action', 'amil_created')->firstOrFail();

        $this->assertMatchesRegularExpression('/^AUD\d{4}\d{6}$/', $log->audit_number);
        $this->assertSame('organization.amil.created', $log->event_name);
        $this->assertSame('CREATE', $log->event_category);
        $this->assertSame('organization', $log->module_code);
        $this->assertSame('INFO', $log->severity);
        $this->assertSame('USER', $log->actor_type);
        $this->assertNotNull($log->occurred_at);
        // PRD 17G §14 — penanda yang tetap bermakna walau entitasnya hilang.
        $this->assertMatchesRegularExpression('/^AML\d+$/', $log->entity_reference);
    }

    /** PRD 17M §23 — kegagalan keamanan tidak boleh berseverity INFO. */
    public function test_peristiwa_keamanan_berseverity_lebih_tinggi(): void
    {
        $s = $this->scenario();

        $this->postJson('/api/v1/auth/logout');
        $this->postJson('/api/v1/auth/login', ['login' => $s['admin']->email, 'password' => 'salah']);

        $log = AuditLog::withoutGlobalScopes()->where('action', 'login_failed')->firstOrFail();

        $this->assertSame('WARNING', $log->severity);
        $this->assertSame('SECURITY', $log->event_category);
        $this->assertSame('auth', $log->module_code);
    }

    /** PRD 17F §11 — peristiwa tanpa pengguna tercatat sebagai SYSTEM. */
    public function test_peristiwa_tanpa_pengguna_tercatat_sebagai_system(): void
    {
        $this->scenario();
        $this->postJson('/api/v1/auth/logout');

        $this->postJson('/api/v1/auth/login', ['login' => 'entah@example.test', 'password' => 'salah']);

        $log = AuditLog::withoutGlobalScopes()->where('action', 'login_failed')->latest('occurred_at')->firstOrFail();
        $this->assertSame('SYSTEM', $log->actor_type);
    }

    /** PRD 17H §17 — kredensial tidak pernah masuk audit. */
    public function test_field_rahasia_tidak_tersimpan(): void
    {
        $s = $this->scenario();

        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'password',
            'password' => 'RahasiaBaru123',
            'password_confirmation' => 'RahasiaBaru123',
        ])->assertOk();

        $dump = AuditLog::withoutGlobalScopes()->get()->toJson();
        $this->assertStringNotContainsString('RahasiaBaru123', $dump);
    }

    // ------------------------------------------------------------ pembacaan

    public function test_daftar_audit_dapat_disaring(): void
    {
        $s = $this->scenario();
        $this->postJson('/api/v1/amils', ['name' => 'Amil Filter'])->assertCreated();

        $this->getJson('/api/v1/audit-logs?event_category=CREATE&module_code=organization')
            ->assertOk()
            ->assertJsonPath('data.0.event_category', 'CREATE');

        $this->getJson('/api/v1/audit-logs?severity=TIDAKADA')->assertStatus(422);
    }

    public function test_riwayat_per_entitas_dan_per_request(): void
    {
        $this->scenario();
        $amil = $this->postJson('/api/v1/amils', ['name' => 'Amil Riwayat'])->assertCreated()->json('data.id');
        $this->patchJson("/api/v1/amils/{$amil}", ['name' => 'Amil Riwayat Diubah'])->assertOk();

        $timeline = $this->getJson("/api/v1/audit-logs/entity/Amil/{$amil}")->assertOk()->json('data');
        $this->assertGreaterThanOrEqual(2, count($timeline));

        $requestId = $timeline[0]['request_id'];
        $this->getJson("/api/v1/audit-logs/request/{$requestId}")->assertOk()->assertJsonPath('data.0.request_id', $requestId);
    }

    public function test_ringkasan_dan_pemeriksaan_keutuhan(): void
    {
        $this->scenario();
        $this->postJson('/api/v1/amils', ['name' => 'Amil Ringkas'])->assertCreated();

        $summary = $this->getJson('/api/v1/audit-logs/summary')->assertOk()->json('data');
        $this->assertGreaterThan(0, $summary['total']);

        $this->postJson('/api/v1/audit-logs/integrity-check')
            ->assertOk()
            ->assertJsonPath('data.healthy', true)
            ->assertJsonPath('data.records_without_number', 0);
    }

    // ------------------------------------------------------------- keamanan

    /** PRD 17U §39 — nilai lama dan baru hanya untuk pemegang audit.view_sensitive. */
    public function test_nilai_perubahan_hanya_untuk_pemegang_izin_khusus(): void
    {
        $s = $this->scenario();
        $amil = $this->postJson('/api/v1/amils', ['name' => 'Amil Rahasia'])->assertCreated()->json('data.id');

        $log = AuditLog::withoutGlobalScopes()->where('entity_id', $amil)->firstOrFail();

        // Admin memegang seluruh permission termasuk audit.view_sensitive.
        $this->getJson("/api/v1/audit-logs/{$log->id}")->assertOk()->assertJsonStructure(['data' => ['new_values']]);

        // Role tanpa izin itu hanya melihat penanda bahwa ada perubahan.
        $auditor = $this->member($s['organization'], 'AUDITOR', ['email' => 'auditor@example.test']);
        $role = Role::whereNull('organization_id')->where('code', 'AUDITOR')->firstOrFail();
        $role->permissions()->syncWithoutDetaching(Permission::whereIn('name', ['audit.view', 'audit.view_detail'])->pluck('id'));

        $this->loginAs($auditor, $s['organization']);
        $response = $this->getJson("/api/v1/audit-logs/{$log->id}")->assertOk();

        $this->assertTrue($response->json('data.has_changes'));
        $this->assertArrayNotHasKey('new_values', $response->json('data'));
    }

    public function test_audit_organisasi_lain_tidak_terlihat(): void
    {
        $this->scenario();
        $this->postJson('/api/v1/amils', ['name' => 'Amil Milik A'])->assertCreated();

        $organizationB = $this->organization();
        $adminB = $this->member($organizationB, 'ADMIN', ['email' => 'lain.audit@example.test']);

        $this->loginAs($adminB, $organizationB);
        $rows = $this->getJson('/api/v1/audit-logs')->assertOk()->json('data');

        foreach ($rows as $row) {
            $this->assertNotSame('Amil Milik A', $row['entity_reference']);
        }
    }

    public function test_permission_kurang_ditolak(): void
    {
        $s = $this->scenario();

        $viewer = $this->member($s['organization'], 'VIEWER', ['email' => 'viewer.audit@example.test']);
        $this->loginAs($viewer, $s['organization']);

        $this->getJson('/api/v1/audit-logs')->assertForbidden();
        $this->postJson('/api/v1/audit-logs/export')->assertForbidden();
        $this->postJson('/api/v1/audit-logs/integrity-check')->assertForbidden();
    }

    /** PRD 17B §3 dan §4 — audit hanya dapat dibaca, tidak ada endpoint tulis. */
    public function test_tidak_ada_endpoint_yang_mengubah_audit(): void
    {
        $this->scenario();

        foreach (Route::getRoutes() as $route) {
            if (str_starts_with($route->uri(), 'api/v1/audit-logs')) {
                $writes = array_intersect($route->methods(), ['PUT', 'PATCH', 'DELETE']);
                $this->assertSame([], $writes, "Route {$route->uri()} tidak boleh mengubah audit.");
            }
        }
    }
}
