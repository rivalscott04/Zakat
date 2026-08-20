<?php

namespace App\Enums;

/** PRD 02 §23 — tipe kontak organisasi. */
enum ContactType: string
{
    case Email = 'email';
    case Phone = 'phone';
    case Whatsapp = 'whatsapp';
    case Website = 'website';
}
