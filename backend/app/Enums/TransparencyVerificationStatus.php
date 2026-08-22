<?php

namespace App\Enums;

/** PRD 18R §29. */
enum TransparencyVerificationStatus: string
{
    case Valid = 'VALID';
    case Warning = 'WARNING';
    case Invalid = 'INVALID';

    /** PRD 18Z §11 dan §23 — snapshot invalid tidak boleh maju ke approval. */
    public function allowsPublication(): bool
    {
        return $this !== self::Invalid;
    }
}
