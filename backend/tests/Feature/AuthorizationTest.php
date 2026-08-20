<?php

namespace Tests\Feature;

use App\Enums\MembershipStatus;
use App\Models\Amil;
use App\Models\OrganizationMember;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** PRD 01 §27 dan §29, PRD 22 — permission enforcement, IDOR, dan BOLA. */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_kurang_ditolak_dengan_403(): void
    {
        $organization = $this->organization();
        // VIEWER tidak memiliki users.create.
        $viewer = $this->member($organization, 'VIEWER');

        $this->loginAs($viewer, $organization)
            ->postJson('/api/v1/users', ['name' => 'X', 'email' => 'x@example.test', 'role_ids' => []])
            ->assertStatus(403)
            ->assertJsonPath('code', 'FORBIDDEN');
    }

    public function test_permission_yang_dimiliki_diizinkan(): void
    {
        $organization = $this->organization();
        $viewer = $this->member($organization, 'VIEWER');

        $this->loginAs($viewer, $organization)->getJson('/api/v1/users')->assertOk();
    }

    public function test_permission_role_organisasi_lain_tidak_berlaku(): void
    {
        $organizationA = $this->organization();
        $organizationB = $this->organization();

        // Role ADMIN dipegang di organisasi A saja; di organisasi B user hanya
        // punya membership tanpa role.
        $user = $this->member($organizationA, 'ADMIN');

        $member = new OrganizationMember;
        $member->fill(['member_type' => 'employee']);
        $member->organization_id = $organizationB->getKey();
        $member->user_id = $user->getKey();
        $member->status = MembershipStatus::Active;
        $member->joined_at = now();
        $member->save();

        $this->loginAs($user, $organizationB)
            ->postJson('/api/v1/users', ['name' => 'X', 'email' => 'x@example.test', 'role_ids' => []])
            ->assertStatus(403);
    }

    public function test_user_organisasi_lain_tidak_dapat_diakses_lewat_ulid(): void
    {
        $organizationA = $this->organization();
        $organizationB = $this->organization();

        $admin = $this->member($organizationA, 'ADMIN');
        $asing = $this->member($organizationB, 'ADMIN');

        $this->loginAs($admin, $organizationA)
            ->getJson("/api/v1/users/{$asing->getKey()}")
            ->assertStatus(404)
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    public function test_amil_organisasi_lain_tidak_terlihat(): void
    {
        $organizationA = $this->organization();
        $organizationB = $this->organization();

        $adminA = $this->member($organizationA, 'ADMIN');
        $adminB = $this->member($organizationB, 'ADMIN');

        $amilB = $this->loginAs($adminB, $organizationB)
            ->postJson('/api/v1/amils', ['name' => 'Amil B'])
            ->assertStatus(201)
            ->json('data.id');

        $this->loginAs($adminA, $organizationA)
            ->getJson("/api/v1/amils/{$amilB}")
            ->assertStatus(404);

        $this->getJson('/api/v1/amils')->assertOk()->assertJsonCount(0, 'data');
        $this->assertSame(1, Amil::query()->acrossOrganizations()->count());
    }

    public function test_organization_id_dari_payload_diabaikan(): void
    {
        $organizationA = $this->organization();
        $organizationB = $this->organization();
        $admin = $this->member($organizationA, 'ADMIN');

        $id = $this->loginAs($admin, $organizationA)
            ->postJson('/api/v1/amils', [
                'name' => 'Amil Titipan',
                'organization_id' => $organizationB->getKey(),
            ])
            ->assertStatus(201)
            ->json('data.id');

        $amil = Amil::query()->acrossOrganizations()->findOrFail($id);

        $this->assertSame($organizationA->getKey(), $amil->organization_id);
    }

    public function test_switch_organization_menolak_organisasi_tanpa_membership(): void
    {
        $organizationA = $this->organization();
        $organizationB = $this->organization();
        $user = $this->member($organizationA, 'ADMIN');

        $this->loginAs($user, $organizationA)
            ->postJson('/api/v1/auth/switch-organization', ['organization_id' => $organizationB->getKey()])
            ->assertStatus(403);
    }

    public function test_role_system_tidak_dapat_diubah(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization, 'ADMIN');

        $roleId = Role::query()->whereNull('organization_id')->where('code', 'VIEWER')->value('id');

        $this->loginAs($admin, $organization)
            ->patchJson("/api/v1/roles/{$roleId}", ['name' => 'Diubah'])
            ->assertStatus(403);
    }

    public function test_user_tidak_dapat_menonaktifkan_dirinya_sendiri(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization, 'ADMIN');

        $this->loginAs($admin, $organization)
            ->postJson("/api/v1/users/{$admin->getKey()}/deactivate")
            ->assertStatus(403);
    }
}
