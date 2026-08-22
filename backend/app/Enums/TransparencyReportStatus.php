<?php

namespace App\Enums;

/** PRD 18O §23. */
enum TransparencyReportStatus: string
{
    case Draft = 'DRAFT';
    case Published = 'PUBLISHED';
    case Archived = 'ARCHIVED';
}
