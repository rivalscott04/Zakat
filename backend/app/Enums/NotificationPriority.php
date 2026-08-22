<?php

namespace App\Enums;

/** PRD 16N §30. */
enum NotificationPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';

    /** PRD 16Y §12 — URGENT boleh melewati preference user. */
    public function overridesPreference(): bool
    {
        return $this === self::Urgent;
    }
}
