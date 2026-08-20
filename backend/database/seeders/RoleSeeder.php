<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * PRD 01 §24 — delapan default role sebagai role system (organization_id NULL).
 *
 * PRD hanya mendeskripsikan tanggung jawab tiap role, bukan matriks permission
 * per modul. Pemetaan di bawah adalah turunan langsung dari deskripsi tersebut
 * untuk cakupan modul 01 dan 02 saja, dan perlu ditinjau ulang ketika modul
 * keuangan, collection, dan distribution dikerjakan.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $all = Permission::query()->pluck('id', 'name');
        $readOnly = $all->filter(fn ($id, $name) => str_ends_with($name, '.view'))->values()->all();

        $definitions = [
            'SUPER_ADMIN' => ['Super Admin', 'Akses penuh terhadap platform.', $all->values()->all()],
            'ADMIN' => ['Administrator', 'Mengelola konfigurasi organisasi dan user.', $all->values()->all()],
            'AMIL' => ['Amil', 'Menjalankan operasional pengelolaan zakat.', $all->only([
                'organizations.view', 'members.view', 'amils.view', 'assignments.view',
            ])->values()->all()],
            'VERIFIER' => ['Verifier', 'Memverifikasi data dan transaksi.', $all->only(['organizations.view'])->values()->all()],
            'APPROVER' => ['Approver', 'Memberikan persetujuan pada aktivitas yang membutuhkan approval.', $all->only(['organizations.view'])->values()->all()],
            'FINANCE' => ['Finance', 'Mengelola aktivitas keuangan.', $all->only(['organizations.view'])->values()->all()],
            'AUDITOR' => ['Auditor', 'Akses baca terhadap data audit dan laporan.', $readOnly],
            'VIEWER' => ['Viewer', 'Akses baca terhadap resource tertentu.', $all->only([
                'organizations.view', 'members.view', 'amils.view', 'assignments.view', 'users.view',
            ])->values()->all()],
        ];

        foreach ($definitions as $code => [$name, $description, $permissionIds]) {
            $role = Role::query()->whereNull('organization_id')->where('code', $code)->first() ?? new Role;
            $role->fill(['name' => $name, 'description' => $description, 'is_active' => true]);
            $role->code = $code;
            $role->organization_id = null;
            $role->is_system = true;
            $role->saveQuietly();

            $role->permissions()->sync($permissionIds);
        }
    }
}
