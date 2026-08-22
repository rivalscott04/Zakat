<?php

namespace App\Enums;

/** PRD 19D §8. */
enum ReportCategory: string
{
    case Collection = 'COLLECTION';
    case Fund = 'FUND';
    case Distribution = 'DISTRIBUTION';
    case Financial = 'FINANCIAL';
    case Accounting = 'ACCOUNTING';
    case Muzakki = 'MUZAKKI';
    case Mustahik = 'MUSTAHIK';
    case Assessment = 'ASSESSMENT';
    case Program = 'PROGRAM';
    case Banking = 'BANKING';
    case Reconciliation = 'RECONCILIATION';
    case Audit = 'AUDIT';
    case Transparency = 'TRANSPARENCY';
    case Organization = 'ORGANIZATION';
    case Operational = 'OPERATIONAL';
    case Custom = 'CUSTOM';

    /** PRD 19S §49 — permission kategori, di luar report.view yang umum. */
    public function permission(): ?string
    {
        return match ($this) {
            self::Collection => 'report.collection.view',
            self::Fund => 'report.fund.view',
            self::Distribution => 'report.distribution.view',
            self::Financial, self::Accounting => 'report.financial.view',
            self::Muzakki => 'report.muzakki.view',
            self::Mustahik => 'report.mustahik.view',
            self::Assessment => 'report.assessment.view',
            self::Program => 'report.program.view',
            self::Banking, self::Reconciliation => 'report.banking.view',
            self::Audit => 'report.audit.view',
            default => null,
        };
    }
}
