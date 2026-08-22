<?php

namespace App\Support;

/**
 * PRD 01 §14 dan §20 — password policy, login threshold, dan lock duration
 * harus configurable. PRD 02 §24 menetapkan urutan resolusi:
 * System Default (config/zakat.php) -> Organization Setting.
 *
 * Hanya key yang terdaftar di sini yang boleh dibaca atau ditulis lewat API,
 * supaya endpoint settings tidak menjadi jalan menulis config sembarangan.
 *
 * PRD 02 §24 dan §25 juga melarang organisasi mengubah konfigurasi keamanan
 * global, jadi key keamanan bertahan pada scope GLOBAL.
 */
final class SettingRegistry
{
    public const GLOBAL = 'GLOBAL';

    public const ORGANIZATION = 'ORGANIZATION';

    /**
     * Kunci array adalah path di config/zakat.php.
     *
     * @var array<string, array{scope: string, group: string, label: string, type: string, rules: array<int, mixed>}>
     */
    public const KEYS = [
        'password.min_length' => [
            'scope' => self::GLOBAL,
            'group' => 'security',
            'label' => 'Panjang minimum password',
            'type' => 'integer',
            'rules' => ['integer', 'min:8', 'max:128'],
        ],
        'password.reject_compromised' => [
            'scope' => self::GLOBAL,
            'group' => 'security',
            'label' => 'Tolak password yang pernah bocor',
            'type' => 'boolean',
            'rules' => ['boolean'],
        ],
        'login.max_attempts' => [
            'scope' => self::GLOBAL,
            'group' => 'security',
            'label' => 'Batas percobaan login sebelum rate limit',
            'type' => 'integer',
            'rules' => ['integer', 'min:1', 'max:20'],
        ],
        'login.decay_seconds' => [
            'scope' => self::GLOBAL,
            'group' => 'security',
            'label' => 'Jendela rate limit login (detik)',
            'type' => 'integer',
            'rules' => ['integer', 'min:30', 'max:3600'],
        ],
        'login.lock_threshold' => [
            'scope' => self::GLOBAL,
            'group' => 'security',
            'label' => 'Gagal login sebelum akun terkunci',
            'type' => 'integer',
            'rules' => ['integer', 'min:3', 'max:50'],
        ],
        'login.lock_minutes' => [
            'scope' => self::GLOBAL,
            'group' => 'security',
            'label' => 'Durasi kunci akun (menit)',
            'type' => 'integer',
            'rules' => ['integer', 'min:5', 'max:1440'],
        ],
        'invitation.expires_hours' => [
            'scope' => self::GLOBAL,
            'group' => 'security',
            'label' => 'Masa berlaku undangan (jam)',
            'type' => 'integer',
            'rules' => ['integer', 'min:1', 'max:720'],
        ],
        'password_reset.expires_minutes' => [
            'scope' => self::GLOBAL,
            'group' => 'security',
            'label' => 'Masa berlaku tautan reset password (menit)',
            'type' => 'integer',
            'rules' => ['integer', 'min:5', 'max:1440'],
        ],
        'display_timezone' => [
            'scope' => self::ORGANIZATION,
            'group' => 'locale',
            'label' => 'Zona waktu tampilan',
            'type' => 'string',
            'rules' => ['string', 'timezone'],
        ],
        'default_currency' => [
            'scope' => self::ORGANIZATION,
            'group' => 'locale',
            'label' => 'Mata uang default',
            'type' => 'string',
            'rules' => ['string', 'size:3', 'uppercase'],
        ],
        // PRD 18P §25 — dashboard publik hanya aktif bila organisasi mengizinkan.
        'transparency.public_enabled' => [
            'scope' => self::ORGANIZATION,
            'group' => 'transparency',
            'label' => 'Aktifkan dashboard transparansi publik',
            'type' => 'boolean',
            'rules' => ['boolean'],
        ],
        'pagination.per_page' => [
            'scope' => self::ORGANIZATION,
            'group' => 'locale',
            'label' => 'Jumlah baris per halaman',
            'type' => 'integer',
            'rules' => ['integer', 'min:5', 'max:100'],
        ],
    ];

    /** @return array<int, string> */
    public static function keysFor(string $scope): array
    {
        return array_keys(array_filter(self::KEYS, fn (array $item) => $item['scope'] === $scope));
    }

    public static function scopeOf(string $key): ?string
    {
        return self::KEYS[$key]['scope'] ?? null;
    }

    /** Nilai default berasal dari config/zakat.php (System Default, PRD 02 §24). */
    public static function default(string $key): mixed
    {
        return config('zakat.'.$key);
    }

    public static function cast(string $key, mixed $value): mixed
    {
        return match (self::KEYS[$key]['type'] ?? 'string') {
            'integer' => (int) $value,
            'boolean' => (bool) $value,
            default => (string) $value,
        };
    }
}
