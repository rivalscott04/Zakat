<?php

namespace Tests\Feature;

use App\Enums\MembershipStatus;
use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\OrganizationMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** PRD 02B, 02C, 02G — hierarki, membership, dan konteks organisasi. */
class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_organisasi_baru_mendapat_business_number_dan_status_draft(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization, 'ADMIN');

        $response = $this->loginAs($admin, $organization)->postJson('/api/v1/organizations', [
            'code' => 'UPZMATARAM',
            'name' => 'UPZ Mataram',
            'organization_type' => 'upz',
            'parent_id' => $organization->getKey(),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.code', 'UPZMATARAM');

        $this->assertMatchesRegularExpression('/^ORG\d{4}\d{6}$/', $response->json('data.business_number'));
    }

    public function test_organisasi_tidak_boleh_menjadi_parent_dirinya_sendiri(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization, 'ADMIN');

        $this->loginAs($admin, $organization)
            ->patchJson("/api/v1/organizations/{$organization->getKey()}", [
                'parent_id' => $organization->getKey(),
            ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'CONFLICT');
    }

    public function test_hierarki_melingkar_ditolak(): void
    {
        $root = $this->organization();
        $admin = $this->platformAdmin($root);

        $child = Organization::factory()->create(['parent_id' => $root->getKey()]);
        $grandChild = Organization::factory()->create(['parent_id' => $child->getKey()]);

        // Menjadikan cucu sebagai parent root akan membentuk lingkaran.
        $this->loginAs($admin, $root)
            ->patchJson("/api/v1/organizations/{$root->getKey()}", ['parent_id' => $grandChild->getKey()])
            ->assertStatus(409);
    }

    public function test_organisasi_suspended_menolak_perubahan_data(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization, 'ADMIN');

        $organization->status = OrganizationStatus::Suspended;
        $organization->saveQuietly();

        $this->loginAs($admin, $organization)
            ->postJson('/api/v1/amils', ['name' => 'Amil Baru'])
            ->assertStatus(403);

        // Pembacaan tetap diizinkan.
        $this->getJson('/api/v1/amils')->assertOk();
    }

    public function test_platform_admin_tetap_dapat_mengubah_organisasi_suspended(): void
    {
        $organization = $this->organization();
        $admin = $this->platformAdmin($organization);

        $organization->status = OrganizationStatus::Suspended;
        $organization->saveQuietly();

        $this->loginAs($admin, $organization)
            ->postJson("/api/v1/organizations/{$organization->getKey()}/activate")
            ->assertOk()
            ->assertJsonPath('data.status', 'active');
    }

    public function test_switch_organization_mengganti_scope_data(): void
    {
        $organizationA = $this->organization();
        $organizationB = $this->organization();

        $user = $this->member($organizationA, 'ADMIN');

        $member = new OrganizationMember;
        $member->fill(['member_type' => 'employee']);
        $member->organization_id = $organizationB->getKey();
        $member->user_id = $user->getKey();
        $member->status = MembershipStatus::Active;
        $member->joined_at = now();
        $member->save();

        $this->loginAs($user, $organizationA);

        $this->getJson('/api/v1/organizations/available')->assertOk()->assertJsonCount(2, 'data');

        $this->postJson('/api/v1/auth/switch-organization', ['organization_id' => $organizationB->getKey()])
            ->assertOk()
            ->assertJsonPath('data.id', $organizationB->getKey());

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.organization.id', $organizationB->getKey());

        $this->assertDatabaseHas('audit_logs', ['action' => 'organization_switched']);
    }

    public function test_membership_dicabut_menghilangkan_akses_organisasi(): void
    {
        $organization = $this->organization();
        $user = $this->member($organization, 'ADMIN');

        $this->loginAs($user, $organization)->getJson('/api/v1/amils')->assertOk();

        OrganizationMember::query()->acrossOrganizations()
            ->where('user_id', $user->getKey())
            ->update(['status' => MembershipStatus::Terminated->value]);

        // Tanpa membership aktif, context kosong sehingga permission organisasi hilang.
        $this->getJson('/api/v1/amils')->assertStatus(403);
    }

    public function test_member_duplikat_ditolak(): void
    {
        $organization = $this->organization();
        $admin = $this->member($organization, 'ADMIN');

        $this->loginAs($admin, $organization)
            ->postJson("/api/v1/organizations/{$organization->getKey()}/members", [
                'user_id' => $admin->getKey(),
                'member_type' => 'amil',
            ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'DUPLICATE_RESOURCE');
    }

    public function test_organisasi_yang_tidak_terlihat_dilaporkan_404(): void
    {
        $organizationA = $this->organization();
        $organizationB = $this->organization();
        $admin = $this->member($organizationA, 'ADMIN');

        $this->loginAs($admin, $organizationA)
            ->getJson("/api/v1/organizations/{$organizationB->getKey()}")
            ->assertStatus(404);
    }

    /**
     * PRD 01 §24 dan PRD 02 §28 — SUPER_ADMIN adalah role platform.
     *
     * PRD 02 §27 menuntut membership aktif untuk semua akses, tetapi §28
     * mengecualikan administrator platform. Yang lebih khusus dimenangkan,
     * karena kalau tidak, platform admin tidak akan pernah bisa masuk untuk
     * investigasi seperti yang §28 sendiri wajibkan.
     */
    public function test_platform_admin_tidak_perlu_membership_untuk_masuk_organisasi(): void
    {
        $platform = $this->organization(['code' => 'PLATFORMX', 'name' => 'Platform']);
        $lembaga = $this->organization(['code' => 'LAZABCX', 'name' => 'LAZ ABC']);

        $superAdmin = $this->platformAdmin($platform);
        $this->loginAs($superAdmin, $platform);

        // Seluruh organisasi terlihat, bukan hanya tempat dia terdaftar.
        $available = $this->getJson('/api/v1/organizations/available')->assertOk()->json('data');
        $this->assertEqualsCanonicalizing(['PLATFORMX', 'LAZABCX'], array_column($available, 'code'));

        // Dan boleh masuk ke organisasi tanpa membership.
        $this->postJson('/api/v1/auth/switch-organization', ['organization_id' => $lembaga->getKey()])
            ->assertOk()
            ->assertJsonPath('data.code', 'LAZABCX');

        $this->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('data.organization.code', 'LAZABCX');
    }

    /** Pengecualian itu khusus platform admin; user biasa tetap tunduk PRD 02 §27. */
    public function test_admin_organisasi_tetap_butuh_membership(): void
    {
        $organizationA = $this->organization();
        $organizationB = $this->organization();
        $admin = $this->member($organizationA, 'ADMIN');

        $this->loginAs($admin, $organizationA);

        $this->getJson('/api/v1/organizations/available')->assertOk()->assertJsonCount(1, 'data');
        $this->postJson('/api/v1/auth/switch-organization', ['organization_id' => $organizationB->getKey()])
            ->assertStatus(403);
    }
}
