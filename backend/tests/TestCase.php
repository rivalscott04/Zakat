<?php

namespace Tests;

use App\Enums\MembershipStatus;
use App\Enums\MemberType;
use App\Http\Middleware\ResolveOrganizationContext;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Role;
use App\Models\User;
use App\Support\OrganizationContext;
use Database\Seeders\CodeRegistrySeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReportCatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\RateLimiter;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CodeRegistrySeeder::class, PermissionSeeder::class, RoleSeeder::class, ReportCatalogSeeder::class]);

        OrganizationContext::set(null);
        RateLimiter::clear('test');

        // Sanctum hanya mengaktifkan session untuk request dari stateful domain,
        // jadi test harus meniru Origin yang dikirim SPA.
        $this->withHeader('Origin', 'http://localhost');
    }

    protected function tearDown(): void
    {
        OrganizationContext::set(null);

        parent::tearDown();
    }

    protected function organization(array $attributes = []): Organization
    {
        return Organization::factory()->create($attributes);
    }

    /** Buat user dengan membership aktif dan role tertentu pada satu organisasi. */
    protected function member(Organization $organization, string $roleCode = 'ADMIN', array $attributes = []): User
    {
        $user = User::factory()->create($attributes + ['organization_id' => $organization->getKey()]);

        $member = new OrganizationMember;
        $member->fill(['member_type' => MemberType::Employee->value]);
        $member->organization_id = $organization->getKey();
        $member->user_id = $user->getKey();
        $member->status = MembershipStatus::Active;
        $member->joined_at = now();
        $member->save();

        $role = Role::query()->whereNull('organization_id')->where('code', $roleCode)->firstOrFail();
        $user->roles()->attach($role->getKey(), ['organization_id' => $organization->getKey()]);

        return $user->fresh();
    }

    /** Super Admin platform: role di-assign tanpa organization scope. */
    protected function platformAdmin(Organization $organization): User
    {
        $user = $this->member($organization, 'ADMIN');

        $superAdmin = Role::query()->whereNull('organization_id')->where('code', Role::SUPER_ADMIN)->firstOrFail();
        $user->roles()->attach($superAdmin->getKey(), ['organization_id' => null]);

        return $user->fresh();
    }

    /** Login lewat session, sekaligus menetapkan active organization context. */
    protected function loginAs(User $user, ?Organization $organization = null): static
    {
        $this->actingAs($user);

        if ($organization !== null) {
            $this->withSession([ResolveOrganizationContext::SESSION_KEY => $organization->getKey()]);
        }

        return $this;
    }
}
