<?php

namespace Database\Seeders;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Enums\UserStatus;
use App\Models\Organization;
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
 * dicetak ke konsol dan tidak disimpan di mana pun. Login Super Admin bisa
 * memakai ZAKAT_BOOTSTRAP_EMAIL atau ZAKAT_BOOTSTRAP_USERNAME (PRD 01 §9).
 */
class BootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([CodeRegistrySeeder::class, PermissionSeeder::class, RoleSeeder::class]);

        $organization = Organization::query()->where('code', env('ZAKAT_BOOTSTRAP_ORG_CODE', 'ZETRA'))->first();

        if ($organization === null) {
            $organization = new Organization;
            $organization->fill([
                'code' => env('ZAKAT_BOOTSTRAP_ORG_CODE', 'ZETRA'),
                'name' => env('ZAKAT_BOOTSTRAP_ORG_NAME', 'ZETRA'),
                'organization_type' => OrganizationType::Platform->value,
            ]);
            $organization->status = OrganizationStatus::Active;
            $organization->saveQuietly();
        }

        $email = env('ZAKAT_BOOTSTRAP_EMAIL', 'admin@zakat.test');
        $username = env('ZAKAT_BOOTSTRAP_USERNAME', 'superadmin');
        $password = env('ZAKAT_BOOTSTRAP_PASSWORD') ?: Str::password(16);

        $user = User::findByLoginIdentifier($email) ?? User::findByLoginIdentifier($username);

        if ($user === null) {
            $user = new User;
            $user->fill([
                'name' => 'Super Admin',
                'email' => $email,
                'username' => $username,
                'password' => $password,
            ]);
            // Dibiarkan null: SUPER_ADMIN adalah role platform, tidak bernaung
            // pada organisasi mana pun (PRD 01 §24). Organisasi kerja dipilih
            // lewat organization switcher saat dibutuhkan.
            $user->organization_id = null;
            $user->status = UserStatus::Active;
            $user->email_verified_at = now();
            $user->saveQuietly();

            $this->command?->warn("Super Admin dibuat: {$email} atau {$username} / {$password}");
        } elseif ($user->username === null) {
            $user->forceFill(['username' => $username])->saveQuietly();
            $this->command?->info("Username Super Admin dilengkapi: {$username}");
        }

        // organization_id NULL pada pivot menandai assignment platform-level.
        $superAdmin = Role::query()->whereNull('organization_id')->where('code', Role::SUPER_ADMIN)->firstOrFail();

        if (! $user->roles()->wherePivotNull('organization_id')->where('roles.id', $superAdmin->getKey())->exists()) {
            $user->roles()->attach($superAdmin->getKey(), ['organization_id' => null]);
        }
    }
}
