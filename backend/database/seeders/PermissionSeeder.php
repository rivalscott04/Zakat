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
        // PRD 16V §48.
        'notification' => [
            'notification' => ['view', 'create', 'send', 'delete', 'audit.view'],
            'notification.template' => ['view', 'create', 'update', 'manage'],
            'notification.rule' => ['view', 'create', 'update', 'manage'],
            'notification.preference' => ['manage'],
            'notification.batch' => ['view', 'create', 'send'],
            'notification.webhook' => ['view', 'manage'],
            'notification.email_config' => ['manage'],
        ],
        // PRD 20.
        'setting' => [
            'setting' => ['view', 'update'],
        ],
        // PRD 17U §39.
        'audit' => [
            'audit' => ['view', 'view_detail', 'view_sensitive', 'export', 'integrity_check', 'archive.view', 'retention.manage', 'system.view', 'security.view'],
        ],
        'zakat' => [
            'zakat' => ['view', 'category.manage', 'type.create', 'type.update', 'type.activate', 'type.deactivate', 'rule.create', 'rule.update', 'rule.activate', 'rule.expire', 'rule.archive', 'nisab.manage', 'haul.manage', 'rate.manage', 'rule.resolve', 'calculation.view', 'calculation.create', 'calculation.calculate', 'calculation.confirm', 'calculation.cancel', 'calculation.recalculate', 'calculation.adjust', 'calculation.approve', 'calculation.override_expired', 'calculation.convert', 'calculation.view_breakdown', 'calculation.view_snapshot', 'calculation.view_audit', 'formula.manage'],
        ],
        'collection' => [
            'collection' => ['view', 'create', 'update', 'confirm', 'cancel', 'reactivate', 'create_manual', 'verify', 'adjust', 'override', 'view_payment', 'view_receipt', 'export', 'view_audit', 'approve', 'manual.approve', 'overpayment.approve', 'refund.approve'],
        ],
        'fund' => [
            'fund' => ['view', 'create', 'update', 'balance.view', 'movement.view', 'movement.create', 'allocation.create', 'allocation.approve', 'allocation.cancel', 'reservation.create', 'reservation.release', 'transfer.create', 'transfer.approve', 'adjustment.create', 'adjustment.approve', 'reconciliation.create', 'reconciliation.review', 'report.view', 'export', 'audit.view'],
        ],
        'accounting' => [
            'accounting' => ['view', 'account.view', 'account.create', 'account.update', 'journal.view', 'journal.create', 'journal.submit', 'journal.approve', 'journal.post', 'journal.reverse', 'adjustment.create', 'adjustment.approve', 'period.view', 'period.create', 'period.lock', 'period.close', 'ledger.view', 'trial_balance.view', 'reconciliation.view', 'reconciliation.create', 'export', 'audit.view'],
        ],
        'mustahik' => [
            'mustahik' => ['view', 'create', 'update', 'delete', 'merge', 'identity.view', 'identity.verify', 'assessment.view', 'assessment.create', 'assessment.submit', 'assessment.approve', 'eligibility.view', 'eligibility.determine', 'verification.perform', 'household.view', 'household.manage', 'distribution_history.view', 'export', 'audit.view'],
        ],
        'assessment' => [
            'assessment' => ['view', 'create', 'update', 'submit', 'review', 'approve', 'reject', 'return', 'reassess', 'score.override', 'export', 'audit.view'],
            'assessment.request' => ['view', 'create', 'assign', 'cancel'],
            'assessment.template' => ['view', 'create', 'update', 'publish'],
        ],
        'program' => [
            'program' => ['view', 'create', 'update', 'delete', 'submit', 'approve', 'activate', 'suspend', 'complete', 'close', 'cancel', 'archive', 'category.manage', 'budget.view', 'budget.create', 'budget.update', 'budget.approve', 'eligibility.view', 'eligibility.manage', 'enrollment.view', 'enrollment.create', 'enrollment.approve', 'enrollment.reject', 'enrollment.withdraw', 'activity.view', 'activity.create', 'activity.update', 'activity.manage', 'target.view', 'target.manage', 'output.manage', 'outcome.manage', 'export', 'audit.view'],
        ],
        'distribution' => [
            'distribution' => ['view', 'create', 'update', 'submit', 'approve', 'reject', 'reserve', 'schedule', 'process', 'complete', 'cancel', 'reverse', 'confirm', 'export', 'audit.view'],
            'distribution.request' => ['view', 'create', 'approve', 'reject'],
            'distribution.batch' => ['view', 'create', 'update', 'approve', 'process'],
            'distribution.proof' => ['view', 'upload', 'verify'],
        ],
        // PRD 13R §35.
        'payment' => [
            'payment' => ['view', 'create', 'verify', 'cancel', 'refresh', 'audit.view'],
            'payment.refund' => ['request', 'approve', 'reject'],
            'payment.provider' => ['view', 'manage'],
            'payment.webhook' => ['view'],
            'payment.reconciliation' => ['view', 'manage'],
        ],
        'bank_reconciliation' => [
            'bank_account' => ['view', 'create', 'update', 'manage'], 'bank_statement' => ['view', 'import', 'process'], 'bank_transaction' => ['view', 'match', 'unmatch', 'exclude'], 'bank_reconciliation' => ['view', 'create', 'start', 'auto_match', 'complete', 'close', 'adjustment.create', 'adjustment.approve', 'audit.view'],
        ],
        'document_management' => [
            'document' => ['view', 'create', 'update', 'delete', 'restore', 'download', 'preview', 'replace', 'verify', 'reject', 'relation.manage', 'archive', 'access_log.view', 'manage'], 'document.version' => ['view', 'create', 'restore'],
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
