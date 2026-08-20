<?php

namespace App\Enums;

/** PRD 03 §19 — status verifikasi identity/contact. */
enum VerificationStatus: string
{
    case Unverified = 'unverified';
    case Pending = 'pending';
    case Verified = 'verified';
    case Invalid = 'invalid';
}
