<?php

namespace Database\Seeders;

use App\Enums\MembershipStatus;
use App\Enums\MemberType;
use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Bootstrap organisasi dan Super Admin pertama.
 *
 * PRD 01 §7 hanya mengenal "Admin Created User", jadi user pertama harus lahir
 * dari luar API. Jalankan sekali saat instalasi:
 *
 *   php artisan db:seed --class=Database\\Seeders\\BootstrapSeeder
 *
 * Password diambil dari ZAKAT_BOOTSTRAP_PASSWORD; bila kosong, password acak
 * dicetak ke konsol dan tidak disimpan di mana pun.
 */
class BootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([CodeRegistrySeeder::class, PermissionSeeder::class, RoleSeeder::class]);

        $organization = Organization::query()->where('code', env('ZAKAT_BOOTSTRAP_ORG_CODE', 'ZAKATOS'))->first();

        if ($organization === null) {
            $organization = new Organization;
            $organization->fill([
                'code' => env('ZAKAT_BOOTSTRAP_ORG_CODE', 'ZAKATOS'),
                'name' => env('ZAKAT_BOOTSTRAP_ORG_NAME', 'Zakat OS'),
                'organization_type' => OrganizationType::Platform->value,
            ]);
            $organization->status = OrganizationStatus::Active;
            $organization->saveQuietly();
        }

        $email = env('ZAKAT_BOOTSTRAP_EMAIL', 'admin@zakat.test');
        $password = env('ZAKAT_BOOTSTRAP_PASSWORD') ?: Str::password(16);

        $user = User::query()->whereRaw('lower(email) = ?', [Str::lower($email)])->first();

        if ($user === null) {
            $user = new User;
            $user->fill(['name' => 'Super Admin', 'email' => $email, 'password' => $password]);
            $user->organization_id = $organization->getKey();
            $user->status = UserStatus::Active;
            $user->email_verified_at = now();
            $user->saveQuietly();

            $this->command?->warn("Super Admin dibuat: {$email} / {$password}");
        }

        $membership = OrganizationMember::query()->acrossOrganizations()
            ->where('organization_id', $organization->getKey())
            ->where('user_id', $user->getKey())
            ->first();

        if ($membership === null) {
            $membership = new OrganizationMember;
            $membership->fill(['member_type' => MemberType::Employee->value]);
            $membership->organization_id = $organization->getKey();
            $membership->user_id = $user->getKey();
            $membership->status = MembershipStatus::Active;
            $membership->joined_at = now();
            $membership->saveQuietly();
        }

        // organization_id NULL pada pivot menandai assignment platform-level.
        $superAdmin = Role::query()->whereNull('organization_id')->where('code', Role::SUPER_ADMIN)->firstOrFail();

        if (! $user->roles()->wherePivotNull('organization_id')->where('roles.id', $superAdmin->getKey())->exists()) {
            $user->roles()->attach($superAdmin->getKey(), ['organization_id' => null]);
        }
    }
}
