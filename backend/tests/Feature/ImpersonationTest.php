<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Impersonate platform admin via lab404/laravel-impersonate. */
class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_dapat_impersonate_user_aktif(): void
    {
        $organization = $this->organization();
        $admin = $this->platformAdmin($organization);
        $target = $this->member($organization, 'VIEWER');

        $this->loginAs($admin, $organization)
            ->postJson("/api/v1/users/{$target->getKey()}/impersonate")
            ->assertOk()
            ->assertJsonPath('data.message', 'Impersonate dimulai.');

        $this->assertAuthenticatedAs($target);

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $target->getKey())
            ->assertJsonPath('data.impersonation.active', true)
            ->assertJsonPath('data.impersonation.impersonator.id', $admin->getKey());

        $this->assertDatabaseHas('audit_logs', ['action' => 'impersonation_started', 'actor_id' => $admin->getKey()]);
    }

    public function test_admin_organisasi_tidak_dapat_impersonate(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization, 'ADMIN');
        $target = $this->member($organization, 'VIEWER');

        $this->loginAs($admin, $organization)
            ->postJson("/api/v1/users/{$target->getKey()}/impersonate")
            ->assertStatus(403);

        $this->assertAuthenticatedAs($admin);
    }

    public function test_tidak_dapat_impersonate_super_admin_lain(): void
    {
        $organization = $this->organization();
        $admin = $this->platformAdmin($organization);
        $otherSuperAdmin = $this->platformAdmin($organization);

        $this->loginAs($admin, $organization)
            ->postJson("/api/v1/users/{$otherSuperAdmin->getKey()}/impersonate")
            ->assertStatus(403);

        $this->assertAuthenticatedAs($admin);
    }

    public function test_leave_impersonation_mengembalikan_admin_asli(): void
    {
        $organization = $this->organization();
        $admin = $this->platformAdmin($organization);
        $target = $this->member($organization, 'VIEWER');

        $this->loginAs($admin, $organization)
            ->postJson("/api/v1/users/{$target->getKey()}/impersonate")
            ->assertOk();

        $this->postJson('/api/v1/auth/leave-impersonation')
            ->assertOk()
            ->assertJsonPath('data.message', 'Impersonate diakhiri.');

        $this->assertAuthenticatedAs($admin);

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $admin->getKey())
            ->assertJsonPath('data.impersonation.active', false);

        $this->assertDatabaseHas('audit_logs', ['action' => 'impersonation_ended', 'actor_id' => $admin->getKey()]);
    }
}
