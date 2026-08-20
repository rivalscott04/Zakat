<?php

namespace App\Enums;

/** PRD 03 §15 — jenis identitas. */
enum IdentityType: string
{
    case Nik = 'nik';
    case Passport = 'passport';
    case TaxId = 'tax_id';
    case EmployeeId = 'employee_id';
    case Other = 'other';
}
