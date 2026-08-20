<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Models\UserInvitation;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/** PRD 01 §10, §15, §16, §20, §21 — alur autentikasi dan proteksi akun. */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_aktif_dapat_login(): void
    {
        $organization = $this->organization();
        $user = $this->member($organization);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()->assertJsonPath('data.authenticated', true);
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_user_aktif_dapat_login_dengan_username(): void
    {
        $organization = $this->organization();
        $user = $this->member($organization, 'ADMIN', ['username' => 'amil_zetra']);

        $this->postJson('/api/v1/auth/login', [
            'login' => 'amil_zetra',
            'password' => 'password',
        ])->assertOk()->assertJsonPath('data.authenticated', true);

        $this->assertAuthenticatedAs($user);
    }

    public function test_payload_email_lama_masih_diterima_sebagai_login(): void
    {
        $user = $this->member($this->organization());

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()->assertJsonPath('data.authenticated', true);
    }

    public function test_login_gagal_tidak_membocorkan_apakah_identifier_terdaftar(): void
    {
        $user = $this->member($this->organization());

        $salahPassword = $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => 'password-salah',
        ]);

        $identifierTidakAda = $this->postJson('/api/v1/auth/login', [
            'login' => 'tidak-ada@example.test',
            'password' => 'password-salah',
        ]);

        $salahPassword->assertStatus(401);
        $identifierTidakAda->assertStatus(401);
        $this->assertSame($salahPassword->json('message'), $identifierTidakAda->json('message'));
        $this->assertGuest();
    }

    public function test_user_suspended_tidak_dapat_login(): void
    {
        $user = $this->member($this->organization(), 'ADMIN', ['status' => UserStatus::Suspended]);

        $this->postJson('/api/v1/auth/login', ['login' => $user->email, 'password' => 'password'])
            ->assertStatus(403);

        $this->assertGuest();
    }

    public function test_login_dibatasi_rate_limit(): void
    {
        $user = $this->member($this->organization());
        $max = (int) config('zakat.login.max_attempts');

        for ($i = 0; $i < $max; $i++) {
            $this->postJson('/api/v1/auth/login', ['login' => $user->email, 'password' => 'salah']);
        }

        $this->postJson('/api/v1/auth/login', ['login' => $user->email, 'password' => 'password'])
            ->assertStatus(429);
    }

    public function test_akun_terkunci_setelah_melewati_ambang_kegagalan(): void
    {
        $user = $this->member($this->organization());
        $threshold = (int) config('zakat.login.lock_threshold');

        for ($i = 0; $i < $threshold; $i++) {
            // Rate limiter dibersihkan agar yang diuji adalah penguncian akun,
            // bukan throttle per IP.
            RateLimiter::clear(strtolower($user->email).'|127.0.0.1');
            $this->postJson('/api/v1/auth/login', ['login' => $user->email, 'password' => 'salah']);
        }

        $user->refresh();
        $this->assertSame(UserStatus::Locked, $user->status);
        $this->assertNotNull($user->locked_until);

        $this->assertDatabaseHas('audit_logs', ['action' => 'account_locked', 'actor_id' => $user->getKey()]);
    }

    public function test_lock_yang_kedaluwarsa_dilepas_otomatis(): void
    {
        $user = $this->member($this->organization());
        $user->forceFill([
            'status' => UserStatus::Locked,
            'locked_until' => now()->subMinute(),
            'failed_login_attempts' => 10,
        ])->saveQuietly();

        $this->postJson('/api/v1/auth/login', ['login' => $user->email, 'password' => 'password'])
            ->assertOk();

        $this->assertSame(UserStatus::Active, $user->fresh()->status);
    }

    public function test_me_mengembalikan_identitas_tanpa_credential(): void
    {
        $organization = $this->organization();
        $user = $this->member($organization);

        $response = $this->loginAs($user, $organization)->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.organization.id', $organization->getKey())
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.remember_token');

        $this->assertContains('users.view', $response->json('data.permissions'));
    }

    public function test_ganti_password_mencabut_session_lain(): void
    {
        $organization = $this->organization();
        $user = $this->member($organization);

        DB::table('sessions')->insert([
            'id' => 'session-lama',
            'user_id' => $user->getKey(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $this->loginAs($user, $organization)
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'password',
                'password' => 'RahasiaBaru123',
                'password_confirmation' => 'RahasiaBaru123',
            ])->assertOk();

        $this->assertDatabaseMissing('sessions', ['id' => 'session-lama']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'password_changed', 'actor_id' => $user->getKey()]);
    }

    public function test_ganti_password_menolak_password_lama_yang_salah(): void
    {
        $organization = $this->organization();
        $user = $this->member($organization);

        $this->loginAs($user, $organization)
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'bukan-password',
                'password' => 'RahasiaBaru123',
                'password_confirmation' => 'RahasiaBaru123',
            ])->assertStatus(422)->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    public function test_forgot_password_selalu_menjawab_generik(): void
    {
        Notification::fake();
        $user = $this->member($this->organization());

        $terdaftar = $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email]);
        $tidakTerdaftar = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'entah@example.test']);

        $terdaftar->assertOk();
        $tidakTerdaftar->assertOk();
        $this->assertSame($terdaftar->json('data.message'), $tidakTerdaftar->json('data.message'));
    }

    public function test_password_tidak_pernah_masuk_audit_log(): void
    {
        $organization = $this->organization();
        $user = $this->member($organization);

        $this->loginAs($user, $organization)->postJson('/api/v1/auth/change-password', [
            'current_password' => 'password',
            'password' => 'RahasiaBaru123',
            'password_confirmation' => 'RahasiaBaru123',
        ])->assertOk();

        $dump = AuditLog::query()->get()->toJson();

        $this->assertStringNotContainsString('RahasiaBaru123', $dump);
        $this->assertStringNotContainsString('$2y$', $dump);
    }

    public function test_logout_mengakhiri_session(): void
    {
        $user = $this->member($this->organization());

        $this->postJson('/api/v1/auth/login', ['login' => $user->email, 'password' => 'password'])->assertOk();
        $this->postJson('/api/v1/auth/logout')->assertOk();

        // Session sudah di-invalidate, jadi request berikutnya kembali sebagai tamu.
        $this->flushHeaders();
        $this->withHeader('Origin', 'http://localhost');
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_endpoint_terproteksi_menolak_tamu(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('code', 'UNAUTHORIZED')
            ->assertJsonStructure(['message', 'errors', 'code', 'request_id']);
    }

    public function test_undangan_mengaktifkan_akun_dan_hanya_sekali_pakai(): void
    {
        Notification::fake();

        $organization = $this->organization();
        $admin = $this->member($organization);

        $this->loginAs($admin, $organization)->postJson('/api/v1/users', [
            'name' => 'Amil Baru',
            'email' => 'amil.baru@example.test',
            'role_ids' => [Role::query()->whereNull('organization_id')->where('code', 'AMIL')->value('id')],
        ])->assertStatus(201);

        $undangan = User::query()->where('email', 'amil.baru@example.test')->firstOrFail();
        $token = UserInvitation::query()->where('user_id', $undangan->getKey())->firstOrFail();

        // Token plaintext hanya dikirim lewat notifikasi, jadi diambil ulang dari service.
        $plain = app(UserService::class)->sendInvitation($undangan);

        $this->postJson('/api/v1/auth/accept-invitation', [
            'email' => $undangan->email,
            'token' => $plain,
            'password' => 'PasswordAmil123',
            'password_confirmation' => 'PasswordAmil123',
        ])->assertOk();

        $this->assertSame(UserStatus::Active, $undangan->fresh()->status);

        $this->postJson('/api/v1/auth/accept-invitation', [
            'email' => $undangan->email,
            'token' => $plain,
            'password' => 'PasswordLain123',
            'password_confirmation' => 'PasswordLain123',
        ])->assertStatus(422);

        $this->assertNotNull($token);
    }
}
