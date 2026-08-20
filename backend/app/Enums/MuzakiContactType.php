<?php

namespace App\Enums;

/** PRD 03 §18 — channel kontak Muzaki. */
enum MuzakiContactType: string
{
    case Email = 'email';
    case Phone = 'phone';
    case Whatsapp = 'whatsapp';
    case Other = 'other';
}
