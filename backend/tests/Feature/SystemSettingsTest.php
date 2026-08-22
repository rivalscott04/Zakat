<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Services\SettingService;
use App\Support\SettingRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** PRD 20 dan PRD 02 §24 — resolusi System Default -> Organization Setting. */
class SystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tanpa_setting_tersimpan_nilai_berasal_dari_config(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);

        $response = $this->getJson('/api/v1/settings')->assertOk();

        $minLength = collect($response->json('data'))->firstWhere('key', 'password.min_length');

        $this->assertSame(config('zakat.password.min_length'), $minLength['value']);
        $this->assertSame('DEFAULT', $minLength['source']);
    }

    public function test_setting_organisasi_menimpa_default_dan_tercatat_di_audit(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);

        $this->putJson('/api/v1/settings', [
            'scope' => SettingRegistry::ORGANIZATION,
            'values' => ['pagination.per_page' => 40],
        ])->assertOk();

        $setting = collect($this->getJson('/api/v1/settings')->json('data'))->firstWhere('key', 'pagination.per_page');

        $this->assertSame(40, $setting['value']);
        $this->assertSame('ORGANIZATION', $setting['source']);
        $this->assertSame(config('zakat.pagination.per_page'), $setting['default_value']);

        $log = AuditLog::withoutGlobalScopes()->where('action', 'setting_updated')->firstOrFail();
        $this->assertSame('CONFIGURATION', $log->event_category);
        $this->assertSame($organization->getKey(), $log->organization_id);
    }

    /** PRD 02 §24 dan §25 — organisasi tidak boleh menyentuh konfigurasi keamanan. */
    public function test_organisasi_tidak_dapat_mengubah_setting_keamanan(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);

        $this->putJson('/api/v1/settings', [
            'scope' => SettingRegistry::ORGANIZATION,
            'values' => ['password.min_length' => 8],
        ])->assertForbidden();

        $this->putJson('/api/v1/settings', [
            'scope' => SettingRegistry::GLOBAL,
            'values' => ['password.min_length' => 20],
        ])->assertForbidden();

        $this->assertDatabaseCount('settings', 0);
    }

    public function test_platform_admin_dapat_mengubah_setting_global(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->platformAdmin($organization), $organization);

        $this->putJson('/api/v1/settings', [
            'scope' => SettingRegistry::GLOBAL,
            'values' => ['password.min_length' => 14],
        ])->assertOk();

        $this->assertDatabaseHas('settings', ['organization_id' => null, 'key' => 'password.min_length']);
        $this->assertSame(14, app(SettingService::class)->effective($organization->getKey())['password.min_length']);
    }

    /** Nilai efektif harus benar-benar dipakai policy password, bukan hanya tersimpan. */
    public function test_setting_global_mengubah_policy_password_yang_berlaku(): void
    {
        $organization = $this->organization();
        $admin = $this->platformAdmin($organization);
        $this->loginAs($admin, $organization);

        $this->putJson('/api/v1/settings', [
            'scope' => SettingRegistry::GLOBAL,
            'values' => ['password.min_length' => 16],
        ])->assertOk();

        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'password',
            'password' => 'Rahasia12345',
            'password_confirmation' => 'Rahasia12345',
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_key_tidak_terdaftar_ditolak(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);

        $this->putJson('/api/v1/settings', [
            'scope' => SettingRegistry::ORGANIZATION,
            'values' => ['app.debug' => true],
        ])->assertStatus(422);

        $this->assertDatabaseCount('settings', 0);
    }

    public function test_nilai_di_luar_rentang_ditolak(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->platformAdmin($organization), $organization);

        $this->putJson('/api/v1/settings', [
            'scope' => SettingRegistry::GLOBAL,
            'values' => ['password.min_length' => 4],
        ])->assertStatus(422)->assertJsonValidationErrors('values.password.min_length');
    }

    public function test_reset_mengembalikan_nilai_bawaan(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);

        $this->putJson('/api/v1/settings', [
            'scope' => SettingRegistry::ORGANIZATION,
            'values' => ['pagination.per_page' => 40],
        ])->assertOk();

        $this->deleteJson('/api/v1/settings/pagination.per_page')->assertOk();

        $setting = collect($this->getJson('/api/v1/settings')->json('data'))->firstWhere('key', 'pagination.per_page');

        $this->assertSame('DEFAULT', $setting['source']);
        $this->assertSame(config('zakat.pagination.per_page'), $setting['value']);
    }

    /** Setting satu organisasi tidak boleh bocor ke organisasi lain. */
    public function test_setting_organisasi_terisolasi(): void
    {
        $first = $this->organization();
        $second = $this->organization();

        $this->loginAs($this->member($first), $first);
        $this->putJson('/api/v1/settings', [
            'scope' => SettingRegistry::ORGANIZATION,
            'values' => ['pagination.per_page' => 40],
        ])->assertOk();

        $this->assertSame(
            config('zakat.pagination.per_page'),
            app(SettingService::class)->effective($second->getKey())['pagination.per_page']
        );
    }

    public function test_satu_key_hanya_punya_satu_baris_per_scope(): void
    {
        $organization = $this->organization();
        $this->loginAs($this->member($organization), $organization);

        foreach ([30, 45] as $value) {
            $this->putJson('/api/v1/settings', [
                'scope' => SettingRegistry::ORGANIZATION,
                'values' => ['pagination.per_page' => $value],
            ])->assertOk();
        }

        $this->assertSame(1, Setting::query()->where('key', 'pagination.per_page')->count());
    }
}
