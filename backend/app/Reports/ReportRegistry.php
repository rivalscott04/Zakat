<?php

namespace App\Reports;

use App\Enums\ReportCategory;
use App\Enums\ReportParameterType;
use App\Enums\ReportVisibility;

/**
 * PRD 19C §7 — katalog laporan bawaan sistem.
 *
 * Definisinya tinggal di kode, bukan di database, supaya report code tetap
 * stabil (PRD 19C §7) dan tidak bisa diubah lewat API. Baris `reports` untuk
 * tiap kode dibuat oleh ReportCatalogSeeder dengan organization_id NULL.
 */
final class ReportRegistry
{
    private const PERIOD = [
        ['parameter_code' => 'date_from', 'label' => 'Tanggal mulai', 'type' => ReportParameterType::Date, 'required' => true],
        ['parameter_code' => 'date_to', 'label' => 'Tanggal akhir', 'type' => ReportParameterType::Date, 'required' => true],
    ];

    /**
     * @var array<string, array{name: string, description: string, category: ReportCategory, visibility: ReportVisibility, data_source: string, parameters: array<int, array<string, mixed>>}>
     */
    public const REPORTS = [
        'ZAKATCOLLECTION' => [
            'name' => 'Ringkasan Penerimaan Zakat',
            'description' => 'Penerimaan per jenis zakat beserta sisa tagihan pada satu periode.',
            'category' => ReportCategory::Collection,
            'visibility' => ReportVisibility::Internal,
            'data_source' => 'collections',
            'parameters' => self::PERIOD,
        ],
        'FUNDPOSITION' => [
            'name' => 'Posisi Dana',
            'description' => 'Saldo tiap dana beserta bagian yang dipesan, dialokasikan, dan tersalurkan.',
            'category' => ReportCategory::Fund,
            'visibility' => ReportVisibility::Internal,
            'data_source' => 'funds',
            'parameters' => [],
        ],
        'DISTRIBUTIONSUMMARY' => [
            'name' => 'Ringkasan Penyaluran',
            'description' => 'Penyaluran per status dan jenis pada satu periode.',
            'category' => ReportCategory::Distribution,
            'visibility' => ReportVisibility::Internal,
            'data_source' => 'distributions',
            'parameters' => self::PERIOD,
        ],
        'ASNAFDISTRIBUTION' => [
            'name' => 'Penyaluran per Asnaf',
            'description' => 'Nilai penyaluran yang diterima tiap golongan asnaf.',
            'category' => ReportCategory::Distribution,
            'visibility' => ReportVisibility::Internal,
            'data_source' => 'distributions',
            'parameters' => self::PERIOD,
        ],
        'PROGRAMPERFORMANCE' => [
            'name' => 'Kinerja Program',
            'description' => 'Anggaran, penerima manfaat, dan realisasi penyaluran tiap program.',
            'category' => ReportCategory::Program,
            'visibility' => ReportVisibility::Internal,
            'data_source' => 'programs',
            'parameters' => [],
        ],
        'MUZAKKISUMMARY' => [
            'name' => 'Ringkasan Muzaki',
            'description' => 'Jumlah muzaki per jenis dan status berikut kontribusinya.',
            'category' => ReportCategory::Muzakki,
            'visibility' => ReportVisibility::Internal,
            'data_source' => 'muzakis',
            'parameters' => [],
        ],
        'MUSTAHIKSUMMARY' => [
            'name' => 'Ringkasan Mustahik',
            'description' => 'Jumlah mustahik per jenis, status verifikasi, dan status kelayakan.',
            'category' => ReportCategory::Mustahik,
            'visibility' => ReportVisibility::Internal,
            'data_source' => 'mustahiks',
            'parameters' => [],
        ],
        'BANKRECONCILIATION' => [
            'name' => 'Ringkasan Rekonsiliasi Bank',
            'description' => 'Sesi rekonsiliasi beserta nilai cocok, belum cocok, dan selisihnya.',
            'category' => ReportCategory::Banking,
            'visibility' => ReportVisibility::Internal,
            'data_source' => 'reconciliation_sessions',
            'parameters' => self::PERIOD,
        ],
        'FINANCIALPOSITION' => [
            'name' => 'Posisi Keuangan',
            'description' => 'Saldo tiap akun dari jurnal yang sudah diposting.',
            'category' => ReportCategory::Financial,
            'visibility' => ReportVisibility::Confidential,
            'data_source' => 'journal_lines',
            'parameters' => self::PERIOD,
        ],
        'ACTIVITYSUMMARY' => [
            'name' => 'Ringkasan Aktivitas',
            'description' => 'Jumlah aktivitas utama pada satu periode.',
            'category' => ReportCategory::Operational,
            'visibility' => ReportVisibility::Internal,
            'data_source' => 'multiple',
            'parameters' => self::PERIOD,
        ],
        'AUDITSUMMARY' => [
            'name' => 'Ringkasan Audit',
            'description' => 'Peristiwa audit per modul, kategori, dan tingkat kegentingan.',
            'category' => ReportCategory::Audit,
            'visibility' => ReportVisibility::Restricted,
            'data_source' => 'audit_logs',
            'parameters' => self::PERIOD,
        ],
    ];

    public static function has(string $code): bool
    {
        return isset(self::REPORTS[$code]);
    }

    /** @return array<int, string> */
    public static function codes(): array
    {
        return array_keys(self::REPORTS);
    }
}
