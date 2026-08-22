<?php

namespace Database\Seeders;

use App\Models\CodeRegistry;
use Illuminate\Database\Seeder;

/** PRD 00 §9 dan §10 — business code wajib terdaftar sebelum dipakai. */
class CodeRegistrySeeder extends Seeder
{
    public function run(): void
    {
        $codes = [
            ['code' => 'ORG', 'name' => 'Organization', 'entity_type' => 'organizations', 'module' => 'organization'],
            ['code' => 'AML', 'name' => 'Amil', 'entity_type' => 'amils', 'module' => 'organization'],
            ['code' => 'MZK', 'name' => 'Muzaki', 'entity_type' => 'muzakis', 'module' => 'muzaki'],
            ['code' => 'ZKC', 'name' => 'Zakat Calculation', 'entity_type' => 'zakat_calculations', 'module' => 'zakat'],
            ['code' => 'COL', 'name' => 'Collection', 'entity_type' => 'collections', 'module' => 'collection'],
            ['code' => 'FND', 'name' => 'Fund Movement', 'entity_type' => 'fund_movements', 'module' => 'fund'],
            ['code' => 'ALC', 'name' => 'Fund Allocation', 'entity_type' => 'fund_allocations', 'module' => 'fund'],
            ['code' => 'RSV', 'name' => 'Fund Reservation', 'entity_type' => 'fund_reservations', 'module' => 'fund'],
            ['code' => 'FTR', 'name' => 'Fund Transfer', 'entity_type' => 'fund_transfers', 'module' => 'fund'],
            ['code' => 'REC', 'name' => 'Fund Reconciliation', 'entity_type' => 'fund_reconciliations', 'module' => 'fund'],
            ['code' => 'BNK', 'name' => 'Bank Account', 'entity_type' => 'bank_accounts', 'module' => 'bank_reconciliation'],
            ['code' => 'BST', 'name' => 'Bank Statement', 'entity_type' => 'bank_statements', 'module' => 'bank_reconciliation'],
            ['code' => 'RCS', 'name' => 'Reconciliation Session', 'entity_type' => 'reconciliation_sessions', 'module' => 'bank_reconciliation'],
            ['code' => 'DOC', 'name' => 'Document', 'entity_type' => 'documents', 'module' => 'document_management'],
            ['code' => 'JRN', 'name' => 'Journal Entry', 'entity_type' => 'journal_entries', 'module' => 'accounting'],
            ['code' => 'MSH', 'name' => 'Mustahik', 'entity_type' => 'mustahiks', 'module' => 'mustahik'],
            ['code' => 'ASR', 'name' => 'Assessment Request', 'entity_type' => 'assessment_requests', 'module' => 'assessment'],
            ['code' => 'ASM', 'name' => 'Assessment', 'entity_type' => 'assessments', 'module' => 'assessment'],
            ['code' => 'PRG', 'name' => 'Program', 'entity_type' => 'programs', 'module' => 'program'],
            ['code' => 'ENR', 'name' => 'Program Enrollment', 'entity_type' => 'program_enrollments', 'module' => 'program'],
            ['code' => 'DSR', 'name' => 'Distribution Request', 'entity_type' => 'distribution_requests', 'module' => 'distribution'],
            ['code' => 'DST', 'name' => 'Distribution', 'entity_type' => 'distributions', 'module' => 'distribution'],
            ['code' => 'DTB', 'name' => 'Distribution Batch', 'entity_type' => 'distribution_batches', 'module' => 'distribution'],
            ['code' => 'PAY', 'name' => 'Payment', 'entity_type' => 'payments', 'module' => 'payment'],
            ['code' => 'RFD', 'name' => 'Payment Refund', 'entity_type' => 'payment_refunds', 'module' => 'payment'],
            ['code' => 'NTF', 'name' => 'Notification', 'entity_type' => 'notifications', 'module' => 'notification'],
            ['code' => 'NFB', 'name' => 'Notification Batch', 'entity_type' => 'notification_batches', 'module' => 'notification'],
            ['code' => 'AUD', 'name' => 'Audit Log', 'entity_type' => 'audit_logs', 'module' => 'audit'],
        ];

        foreach ($codes as $code) {
            CodeRegistry::query()->updateOrCreate(['code' => $code['code']], $code + ['is_active' => true]);
        }
    }
}
