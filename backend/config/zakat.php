<?php

/*
 | Zakat OS runtime policy.
 |
 | PRD 01 §14 dan §20 menyatakan password policy, login threshold, dan lock
 | duration harus configurable melalui System Settings (PRD 20). Modul System
 | Settings belum diimplementasikan, jadi nilainya tinggal di sini dulu.
 | Saat PRD 20 dikerjakan, service settings cukup meng-override key ini.
 */

return [

    // PRD 00 §14 — storage UTC, default display timezone Asia/Makassar.
    'display_timezone' => env('ZAKAT_DISPLAY_TIMEZONE', 'Asia/Makassar'),

    // PRD 00 §13 — ISO 4217, default IDR.
    'default_currency' => env('ZAKAT_DEFAULT_CURRENCY', 'IDR'),

    'password' => [
        // PRD 01 §14 — minimum 8, produksi disarankan 10-12.
        'min_length' => (int) env('ZAKAT_PASSWORD_MIN_LENGTH', 8),

        // Cek password bocor lewat HaveIBeenPwned. Butuh akses jaringan keluar,
        // jadi default mati dan sebaiknya dinyalakan di produksi.
        'reject_compromised' => (bool) env('ZAKAT_PASSWORD_REJECT_COMPROMISED', false),
    ],

    'login' => [
        // PRD 01 §20 — rate limiting per email+IP.
        'max_attempts' => (int) env('ZAKAT_LOGIN_MAX_ATTEMPTS', 5),
        'decay_seconds' => (int) env('ZAKAT_LOGIN_DECAY_SECONDS', 60),

        // PRD 01 §21 — auto lock setelah gagal berulang, dengan recovery otomatis.
        'lock_threshold' => (int) env('ZAKAT_LOGIN_LOCK_THRESHOLD', 10),
        'lock_minutes' => (int) env('ZAKAT_LOGIN_LOCK_MINUTES', 30),
    ],

    'invitation' => [
        'expires_hours' => (int) env('ZAKAT_INVITATION_EXPIRES_HOURS', 72),
    ],

    'password_reset' => [
        'expires_minutes' => (int) env('ZAKAT_PASSWORD_RESET_EXPIRES_MINUTES', 60),
    ],

    'pagination' => [
        'per_page' => (int) env('ZAKAT_PER_PAGE', 25),
        'max_per_page' => (int) env('ZAKAT_MAX_PER_PAGE', 100),
    ],
];
