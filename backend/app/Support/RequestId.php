<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * PRD 00 §19 — request id wajib untuk tracing. Disimpan di container supaya
 * service dan audit log bisa membacanya tanpa harus dioper lewat parameter.
 */
final class RequestId
{
    private static ?string $id = null;

    public static function set(string $id): void
    {
        self::$id = $id;
    }

    public static function current(): string
    {
        return self::$id ??= (string) Str::ulid();
    }

    public static function reset(): void
    {
        self::$id = null;
    }
}
