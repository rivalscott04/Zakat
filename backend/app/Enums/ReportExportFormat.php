<?php

namespace App\Enums;

/** PRD 19I §26. */
enum ReportExportFormat: string
{
    case Csv = 'CSV';
    case Xlsx = 'XLSX';
    case Pdf = 'PDF';

    public function extension(): string
    {
        return strtolower($this->value);
    }

    public function mimeType(): string
    {
        return match ($this) {
            self::Csv => 'text/csv',
            self::Xlsx => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::Pdf => 'application/pdf',
        };
    }
}
