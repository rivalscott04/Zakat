<?php

namespace Tests\Feature;

use App\Enums\TransparencySnapshotStatus;
use App\Enums\TransparencyVerificationStatus;
use App\Models\Organization;
use App\Models\Setting;
use App\Models\TransparencySnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/** PRD 18 — snapshot, alur publikasi, privasi data publik, dan API publik. */
class TransparencyTest extends TestCase
{
    use RefreshDatabase;

    private function enablePublic(Organization $organization): void
    {
        Setting::query()->create([
            'organization_id' => $organization->getKey(),
            'key' => 'transparency.public_enabled',
            'value' => true,
        ]);

        Cache::forget('settings:org:'.$organization->getKey());
    }

    /** Bawa snapshot sampai status tertentu lewat API. */
    private function snapshotUpTo(Organization $organization, string $target = 'PUBLISHED'): string
    {
        $id = $this->postJson('/api/v1/transparency/snapshots', [
            'snapshot_type' => 'MONTHLY',
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
        ])->assertCreated()->json('data.id');

        $steps = ['GENERATED' => 'generate', 'PENDING_APPROVAL' => 'submit', 'APPROVED' => 'approve', 'PUBLISHED' => 'publish'];

        foreach ($steps as $status => $action) {
            $this->postJson("/api/v1/transparency/snapshots/{$id}/{$action}")->assertOk();

            if ($status === $target) {
                break;
            }
        }

        return $id;
    }

    // -------------------------------------------------------------- snapshot

    public function test_snapshot_mendapat_nomor_dan_agregat(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);

        $id = $this->snapshotUpTo($organization, 'GENERATED');
        $snapshot = $this->getJson("/api/v1/transparency/snapshots/{$id}")->assertOk();

        $this->assertMatchesRegularExpression('/^TRP\d{4}\d{6}$/', $snapshot->json('data.snapshot_number'));
        $this->assertNotNull($snapshot->json('data.snapshot_data.collection.total_collection'));
        $this->assertNotNull($snapshot->json('data.snapshot_data.metrics.distribution_rate'));
    }

    /** PRD 18B §3 dan PRD 18Z §4 sampai §10 — data publik tanpa identitas. */
    public function test_snapshot_tidak_memuat_data_identitas(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);

        $id = $this->snapshotUpTo($organization, 'GENERATED');
        $data = json_encode(TransparencySnapshot::query()->findOrFail($id)->data);

        foreach (['identity_number', 'nik', 'full_name', 'phone', 'email', 'account_number', 'address_line', 'password', 'token'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, strtolower($data));
        }
    }

    /** PRD 18Z §11 dan §12 — urutan publikasi tidak boleh dilompati. */
    public function test_snapshot_tidak_dapat_langsung_dipublikasikan(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);

        $id = $this->postJson('/api/v1/transparency/snapshots', [
            'snapshot_type' => 'MONTHLY',
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/transparency/snapshots/{$id}/publish")->assertStatus(409);
        $this->postJson("/api/v1/transparency/snapshots/{$id}/generate")->assertOk();
        $this->postJson("/api/v1/transparency/snapshots/{$id}/publish")->assertStatus(409);
    }

    /** PRD 18Z §23 — snapshot yang gagal verifikasi tidak dapat diajukan. */
    public function test_snapshot_invalid_tidak_dapat_diajukan(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);

        $id = $this->snapshotUpTo($organization, 'GENERATED');

        $snapshot = TransparencySnapshot::query()->findOrFail($id);
        $snapshot->verification_status = TransparencyVerificationStatus::Invalid;
        $snapshot->save();

        $this->postJson("/api/v1/transparency/snapshots/{$id}/submit")->assertStatus(409);
    }

    public function test_verifikasi_menandai_saldo_yang_tidak_konsisten(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);

        $id = $this->snapshotUpTo($organization, 'GENERATED');

        $snapshot = TransparencySnapshot::query()->findOrFail($id);
        $data = $snapshot->data;
        $data['fund']['available_balance'] = '999999.00';
        $snapshot->data = $data;
        $snapshot->save();

        $this->postJson("/api/v1/transparency/snapshots/{$id}/validate")
            ->assertOk()
            ->assertJsonPath('data.verification_status', 'INVALID');
    }

    /** PRD 18Z §14 — snapshot terbit tidak boleh diubah lagi. */
    public function test_snapshot_terbit_tidak_dapat_dibuat_ulang(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);

        $id = $this->snapshotUpTo($organization);

        $this->postJson("/api/v1/transparency/snapshots/{$id}/generate")->assertStatus(409);
    }

    /** PRD 18Z §16 — pencabutan wajib beralasan. */
    public function test_pencabutan_wajib_menyertakan_alasan(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);

        $id = $this->snapshotUpTo($organization);

        $this->postJson("/api/v1/transparency/snapshots/{$id}/revoke", [])->assertStatus(422);

        $this->postJson("/api/v1/transparency/snapshots/{$id}/revoke", ['reason' => 'Terdapat koreksi angka penyaluran periode ini.'])
            ->assertOk()
            ->assertJsonPath('data.status', TransparencySnapshotStatus::Revoked->value);

        $this->assertDatabaseHas('audit_logs', ['action' => 'transparency_snapshot_revoked']);
    }

    // ---------------------------------------------------------------- publik

    public function test_dashboard_publik_tertutup_bila_organisasi_belum_mengizinkan(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);
        $this->snapshotUpTo($organization);

        $this->getJson("/api/public/transparency/{$organization->code}")->assertNotFound();
    }

    public function test_dashboard_publik_hanya_menampilkan_snapshot_terbit(): void
    {
        $organization = $this->organization();
        $this->enablePublic($organization);
        $this->loginAs($this->member($organization), $organization);

        // Snapshot yang belum terbit tidak boleh muncul.
        $this->snapshotUpTo($organization, 'APPROVED');
        $this->app['auth']->forgetGuards();

        $this->getJson("/api/public/transparency/{$organization->code}")
            ->assertOk()
            ->assertJsonPath('data.snapshot_number', null);

        $this->loginAs($this->member($organization), $organization);
        $published = $this->snapshotUpTo($organization);
        $this->app['auth']->forgetGuards();

        $this->getJson("/api/public/transparency/{$organization->code}")
            ->assertOk()
            ->assertJsonPath(
                'data.snapshot_number',
                TransparencySnapshot::query()->withoutGlobalScopes()->findOrFail($published)->snapshot_number
            );
    }

    public function test_ringkasan_publik_dapat_diakses_tanpa_login(): void
    {
        $organization = $this->organization();
        $this->enablePublic($organization);
        $this->loginAs($this->member($organization), $organization);
        $this->snapshotUpTo($organization);
        $this->app['auth']->forgetGuards();

        $summary = $this->getJson("/api/public/transparency/{$organization->code}/summary")->assertOk();

        $this->assertSame($organization->code, $summary->json('data.organization.code'));
        $this->assertArrayHasKey('distribution_rate', $summary->json('data.metrics'));
    }

    /** PRD 18T §34 — verifikasi publik membuktikan transaksi tanpa membuka identitas. */
    public function test_verifikasi_referensi_tidak_membuka_identitas(): void
    {
        $organization = $this->organization();
        $this->enablePublic($organization);

        $this->getJson('/api/public/transparency/verify/TIDAKADA123')->assertNotFound();
    }

    public function test_snapshot_organisasi_lain_tidak_terlihat(): void
    {
        $first = $this->organization();
        $second = $this->organization();

        $this->loginAs($this->member($second), $second);
        $id = $this->snapshotUpTo($second, 'GENERATED');

        $this->loginAs($this->member($first), $first);
        $this->getJson("/api/v1/transparency/snapshots/{$id}")->assertNotFound();
    }

    // --------------------------------------------------------------- laporan

    public function test_laporan_hanya_terbit_bila_snapshotnya_terbit(): void
    {
        $organization = $this->organization();
        $this->enablePublic($organization);
        $this->loginAs($this->member($organization), $organization);

        $draftSnapshot = $this->snapshotUpTo($organization, 'APPROVED');

        $report = $this->postJson('/api/v1/transparency/reports', [
            'title' => 'Laporan Transparansi Bulanan',
            'report_type' => 'MONTHLY',
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'snapshot_id' => $draftSnapshot,
        ])->assertCreated();

        $reportId = $report->json('data.id');
        $this->assertMatchesRegularExpression('/^RPT\d{4}\d{6}$/', $report->json('data.report_number'));

        $this->postJson("/api/v1/transparency/reports/{$reportId}/publish")->assertStatus(409);

        $this->postJson("/api/v1/transparency/snapshots/{$draftSnapshot}/publish")->assertOk();
        $this->postJson("/api/v1/transparency/reports/{$reportId}/publish")->assertOk()->assertJsonPath('data.status', 'PUBLISHED');

        $this->app['auth']->forgetGuards();
        $this->getJson("/api/public/transparency/{$organization->code}/reports")
            ->assertOk()
            ->assertJsonPath('data.0.report_number', $report->json('data.report_number'));
    }

    /** PRD 18Z §21 — akses internal tetap tunduk pada permission. */
    public function test_user_tanpa_izin_tidak_dapat_mempublikasikan(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);
        $id = $this->snapshotUpTo($organization, 'APPROVED');

        $this->loginAs($this->member($organization, 'VIEWER'), $organization);
        $this->postJson("/api/v1/transparency/snapshots/{$id}/publish")->assertForbidden();
    }

    public function test_periode_bulanan_dirapikan_otomatis(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);

        $response = $this->postJson('/api/v1/transparency/snapshots', [
            'snapshot_type' => 'MONTHLY',
            'period_start' => '2026-03-17',
            'period_end' => '2026-03-20',
        ])->assertCreated();

        $this->assertSame('2026-03-01', $response->json('data.period_start'));
        $this->assertSame('2026-03-31', $response->json('data.period_end'));
    }

    /** PRD 18Z §19 — dashboard publik memakai cache dan dibuang saat publikasi berubah. */
    public function test_cache_publik_dibuang_saat_publikasi_berubah(): void
    {
        $organization = $this->organization();
        $this->enablePublic($organization);
        $this->loginAs($this->member($organization), $organization);
        $id = $this->snapshotUpTo($organization);
        $this->app['auth']->forgetGuards();

        $this->getJson("/api/public/transparency/{$organization->code}")->assertOk();
        $this->assertNotNull(Cache::get('transparency:public:'.$organization->getKey()));

        $this->loginAs($this->member($organization), $organization);
        $this->postJson("/api/v1/transparency/snapshots/{$id}/revoke", ['reason' => 'Koreksi angka penerimaan periode berjalan.'])->assertOk();

        $this->assertNull(Cache::get('transparency:public:'.$organization->getKey()));
    }
}
