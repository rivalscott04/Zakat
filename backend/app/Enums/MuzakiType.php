<?php

namespace App\Enums;

/** PRD 03 §7 — tipe entitas Muzaki. */
enum MuzakiType: string
{
    case Individual = 'individual';
    case Family = 'family';
    case Company = 'company';
    case Organization = 'organization';
    case Institution = 'institution';

    public function usesIndividualProfile(): bool
    {
        return $this === self::Individual;
    }

    public function usesOrganizationProfile(): bool
    {
        return ! $this->usesIndividualProfile() && $this !== self::Family;
    }
}
