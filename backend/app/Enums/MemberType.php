<?php

namespace App\Enums;

/** PRD 02 §12 — member type menjelaskan hubungan operasional, bukan role. */
enum MemberType: string
{
    case Employee = 'employee';
    case Amil = 'amil';
    case Volunteer = 'volunteer';
    case Auditor = 'auditor';
    case External = 'external';
}
