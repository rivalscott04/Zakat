<?php

namespace App\Enums;

/** PRD 19F §21. */
enum ReportParameterType: string
{
    case Date = 'DATE';
    case DateRange = 'DATE_RANGE';
    case Select = 'SELECT';
    case MultiSelect = 'MULTI_SELECT';
    case Text = 'TEXT';
    case Number = 'NUMBER';
    case Boolean = 'BOOLEAN';

    /** PRD 19W §9 — parameter wajib divalidasi sesuai tipenya. */
    public function rules(): array
    {
        return match ($this) {
            self::Date => ['date'],
            self::DateRange => ['array', 'size:2'],
            self::Select, self::Text => ['string', 'max:255'],
            self::MultiSelect => ['array'],
            self::Number => ['numeric'],
            self::Boolean => ['boolean'],
        };
    }
}
