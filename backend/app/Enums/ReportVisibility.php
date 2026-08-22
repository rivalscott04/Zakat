<?php

namespace App\Enums;

/** PRD 19N §38 dan §39. */
enum ReportVisibility: string
{
    case Public = 'PUBLIC';
    case Internal = 'INTERNAL';
    case Restricted = 'RESTRICTED';
    case Confidential = 'CONFIDENTIAL';

    /** PRD 19Z §27 — laporan rahasia butuh permission eksplisit. */
    public function extraPermission(): ?string
    {
        return $this === self::Confidential ? 'report.confidential.view' : null;
    }
}
