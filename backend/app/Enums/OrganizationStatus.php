<?php

namespace App\Enums;

/** PRD 02 §7 — status organisasi. */
enum OrganizationStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';
    case Archived = 'archived';

    /** PRD 02 §27 — hanya organisasi aktif yang bisa dipakai sebagai konteks kerja. */
    public function isOperational(): bool
    {
        return $this === self::Active;
    }

    /** PRD 02 §28 dan §38.5 — organisasi suspended tidak boleh membuat transaksi baru. */
    public function acceptsNewTransactions(): bool
    {
        return $this === self::Active;
    }
}
