<?php

namespace App\Support;

use App\Models\Organization;

/**
 * PRD 02 §15 — active organization context. Diisi hanya oleh middleware
 * ResolveOrganizationContext setelah membership diverifikasi di backend.
 * Frontend tidak pernah menulis nilai ini secara langsung.
 */
final class OrganizationContext
{
    private static ?Organization $organization = null;

    public static function set(?Organization $organization): void
    {
        self::$organization = $organization;
    }

    public static function current(): ?Organization
    {
        return self::$organization;
    }

    public static function id(): ?string
    {
        return self::$organization?->getKey();
    }

    public static function requireId(): string
    {
        return self::id() ?? throw \App\Exceptions\ZakatException::forbidden('Organization context belum dipilih.');
    }
}
