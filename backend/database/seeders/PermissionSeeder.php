<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

/**
 * PRD 01 §25 — katalog permission. Format `resource.action` sesuai keputusan
 * user 2026-08-20. Kolom module hanya untuk pengelompokan di UI.
 */
class PermissionSeeder extends Seeder
{
    /** @var array<string, array<string, array<int, string>>> */
    public const CATALOG = [
        'auth' => [
            'users' => ['view', 'create', 'update', 'impersonate'],
            'roles' => ['view', 'create', 'update'],
            'permissions' => ['view'],
        ],
        'organization' => [
            'organizations' => ['view', 'create', 'update'],
            'members' => ['view', 'create', 'update'],
            'amils' => ['view', 'create', 'update'],
            'assignments' => ['view', 'create', 'update'],
        ],
        'muzaki' => [
            'muzaki' => [
                'view', 'create', 'update', 'activate', 'deactivate', 'archive',
                'view_sensitive', 'verify_identity', 'manage_contacts', 'manage_addresses',
                'manage_family', 'manage_preferences', 'manage_tags', 'manage_notes',
                'merge', 'export', 'view_audit',
            ],
        ],
        'audit' => [
            'audit' => ['view'],
        ],
        'zakat' => [
            'zakat' => ['view', 'category.manage', 'type.create', 'type.update', 'type.activate', 'type.deactivate', 'rule.create', 'rule.update', 'rule.activate', 'rule.expire', 'rule.archive', 'nisab.manage', 'haul.manage', 'rate.manage', 'rule.resolve'],
        ],
    ];

    public function run(): void
    {
        foreach (self::CATALOG as $module => $resources) {
            foreach ($resources as $resource => $actions) {
                foreach ($actions as $action) {
                    Permission::query()->updateOrCreate(
                        ['name' => "{$resource}.{$action}"],
                        [
                            'module' => $module,
                            'resource' => $resource,
                            'action' => $action,
                            'description' => ucfirst($action).' '.str_replace('_', ' ', $resource),
                        ]
                    );
                }
            }
        }
    }
}
