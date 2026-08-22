<?php

namespace App\Support;

use App\Enums\AuditEventCategory;
use App\Enums\AuditSeverity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * PRD 17D, 17L, dan 17M.
 *
 * Seluruh modul memanggil AuditService dengan nama aksi datar seperti
 * `payment_created`. Kelas ini yang menurunkannya menjadi event_name berformat
 * `module.entity.action`, kategori, kode modul, dan severity, sehingga 150-an
 * pemanggil tidak perlu diubah satu per satu.
 */
final class AuditEventClassifier
{
    /** Kata kerja penutup nama aksi yang menentukan kategorinya. */
    private const BY_SUFFIX = [
        'created' => AuditEventCategory::Create,
        'uploaded' => AuditEventCategory::Create,
        'imported' => AuditEventCategory::Create,
        'updated' => AuditEventCategory::Update,
        'replaced' => AuditEventCategory::Update,
        'assigned' => AuditEventCategory::Update,
        'switched' => AuditEventCategory::Update,
        'deleted' => AuditEventCategory::Delete,
        'removed' => AuditEventCategory::Delete,
        'purged' => AuditEventCategory::Delete,
        'restored' => AuditEventCategory::Restore,
        'reactivated' => AuditEventCategory::Restore,
        'approved' => AuditEventCategory::Approval,
        'verified' => AuditEventCategory::Approval,
        'activated' => AuditEventCategory::Approval,
        'completed' => AuditEventCategory::Approval,
        'rejected' => AuditEventCategory::Rejection,
        'cancelled' => AuditEventCategory::Rejection,
        'reversed' => AuditEventCategory::Rejection,
        'suspended' => AuditEventCategory::Rejection,
        'failed' => AuditEventCategory::Security,
        'locked' => AuditEventCategory::Security,
        'unlocked' => AuditEventCategory::Security,
        'revoked' => AuditEventCategory::Security,
    ];

    /**
     * Modul diturunkan dari kelas entitasnya lebih dulu.
     *
     * Awalan nama aksi saja tidak cukup: `account_created` milik akuntansi
     * sedangkan `account_locked` milik autentikasi, keduanya berawalan sama.
     *
     * @var array<string, string>
     */
    private const BY_ENTITY = [
        'user' => 'auth', 'role' => 'auth', 'permission' => 'auth', 'user_invitation' => 'auth',
        'organization' => 'organization', 'organization_member' => 'organization',
        'organization_address' => 'organization', 'organization_contact' => 'organization',
        'amil' => 'organization', 'amil_assignment' => 'organization',
        'muzaki' => 'muzaki',
        'zakat_type' => 'zakat', 'zakat_category' => 'zakat', 'zakat_calculation' => 'zakat',
        'collection' => 'collection', 'collection_payment' => 'collection', 'payment_allocation' => 'collection',
        'fund' => 'fund', 'fund_movement' => 'fund', 'fund_allocation' => 'fund',
        'fund_reservation' => 'fund', 'fund_transfer' => 'fund', 'fund_reconciliation' => 'fund',
        'chart_of_account' => 'accounting', 'journal_entry' => 'accounting', 'journal_line' => 'accounting',
        'accounting_period' => 'accounting', 'accounting_event' => 'accounting',
        'mustahik' => 'mustahik',
        'assessment' => 'assessment', 'assessment_request' => 'assessment',
        'program' => 'program', 'program_budget' => 'program', 'program_enrollment' => 'program',
        'program_budget_commitment' => 'program',
        'distribution' => 'distribution', 'distribution_batch' => 'distribution',
        'distribution_request' => 'distribution', 'distribution_proof' => 'distribution',
        'payment' => 'payment', 'payment_provider' => 'payment', 'payment_refund' => 'payment',
        'payment_webhook' => 'payment', 'payment_reconciliation' => 'payment',
        'bank_account' => 'bank', 'bank_statement' => 'bank', 'bank_transaction' => 'bank',
        'reconciliation_session' => 'bank', 'reconciliation_adjustment' => 'bank',
        'reconciliation_transaction' => 'bank', 'reconciliation_match' => 'bank',
        'document' => 'document', 'document_version' => 'document', 'document_relation' => 'document',
        'audit_log' => 'audit',
    ];

    /** Dipakai hanya bila peristiwa tidak membawa entitas, misalnya login gagal. */
    private const BY_PREFIX = [
        'login' => 'auth', 'logout' => 'auth', 'password' => 'auth', 'session' => 'auth',
        'account' => 'auth', 'impersonation' => 'auth', 'user' => 'auth', 'role' => 'auth',
        'organization' => 'organization', 'amil' => 'organization',
        'muzaki' => 'muzaki', 'zakat' => 'zakat', 'calculation' => 'zakat',
        'collection' => 'collection', 'fund' => 'fund',
        'journal' => 'accounting', 'period' => 'accounting',
        'mustahik' => 'mustahik', 'assessment' => 'assessment', 'program' => 'program',
        'distribution' => 'distribution', 'payment' => 'payment',
        'bank' => 'bank', 'reconciliation' => 'bank',
        'document' => 'document', 'notification' => 'notification',
    ];

    /** Modul yang menentukan kategori ketika aksinya tidak menyiratkan apa pun. */
    private const MODULE_CATEGORY = [
        'auth' => AuditEventCategory::Authentication,
        'organization' => AuditEventCategory::Configuration,
        'collection' => AuditEventCategory::Collection,
        'fund' => AuditEventCategory::Accounting,
        'accounting' => AuditEventCategory::Accounting,
        'assessment' => AuditEventCategory::Assessment,
        'program' => AuditEventCategory::Program,
        'distribution' => AuditEventCategory::Distribution,
        'payment' => AuditEventCategory::Payment,
        'bank' => AuditEventCategory::Banking,
        'document' => AuditEventCategory::Document,
        'notification' => AuditEventCategory::Notification,
        'audit' => AuditEventCategory::System,
    ];

    /**
     * @return array{event_name: string, event_category: string, module_code: string, severity: string}
     */
    public static function classify(string $action, ?Model $entity): array
    {
        $segments = explode('_', $action);
        $suffix = end($segments) ?: $action;
        $prefix = $segments[0] ?? $action;

        $entityName = $entity !== null ? Str::snake(class_basename($entity)) : null;

        $module = ($entityName !== null ? (self::BY_ENTITY[$entityName] ?? null) : null)
            ?? self::BY_PREFIX[$prefix]
            ?? 'system';

        $category = self::BY_SUFFIX[$suffix]
            ?? self::MODULE_CATEGORY[$module]
            ?? AuditEventCategory::Other;

        // PRD 17L §21 — module.entity.action.
        $actionName = count($segments) > 1 ? implode('_', array_slice($segments, 1)) : $action;

        return [
            'event_name' => $module.'.'.($entityName ?? $prefix).'.'.$actionName,
            'event_category' => $category->value,
            'module_code' => $module,
            'severity' => self::severity($action, $category)->value,
        ];
    }

    /** PRD 17M §23. */
    private static function severity(string $action, AuditEventCategory $category): AuditSeverity
    {
        // Peristiwa yang menandakan kegagalan keamanan atau pembalikan nilai
        // finansial selalu perlu perhatian, apa pun modulnya.
        if (str_contains($action, 'failed') || str_contains($action, 'locked') || str_contains($action, 'mismatch')) {
            return AuditSeverity::Warning;
        }

        if (str_contains($action, 'reversed') || str_contains($action, 'impersonation') || str_contains($action, 'purged')) {
            return AuditSeverity::Critical;
        }

        return match ($category) {
            AuditEventCategory::Security => AuditSeverity::Warning,
            AuditEventCategory::Payment,
            AuditEventCategory::Distribution,
            AuditEventCategory::Accounting,
            AuditEventCategory::Banking,
            AuditEventCategory::Approval,
            AuditEventCategory::Rejection,
            AuditEventCategory::Delete,
            AuditEventCategory::Configuration => AuditSeverity::Notice,
            default => AuditSeverity::Info,
        };
    }
}
