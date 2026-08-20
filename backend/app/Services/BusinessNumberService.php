<?php

namespace App\Services;

use App\Models\CodeRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/** PRD 00 §11 — generator business number `{CODE}{YEAR}{SEQUENCE}`. */
class BusinessNumberService
{
    /** @var array<string, bool> */
    private array $registered = [];

    public function next(string $code, ?int $year = null): string
    {
        $code = strtoupper($code);
        $this->assertRegistered($code);

        $year ??= (int) now()->format('Y');

        // ponytail: satu statement atomic ON CONFLICT, tanpa row lock terpisah.
        // Rollback transaksi pemanggil bisa menyisakan gap nomor; itu diterima
        // karena PRD 00 §11 melarang nomor dipakai ulang, bukan melarang gap.
        $row = DB::selectOne(
            'INSERT INTO business_number_sequences (id, code, year, last_number, created_at, updated_at)
             VALUES (?, ?, ?, 1, now(), now())
             ON CONFLICT (code, year)
             DO UPDATE SET last_number = business_number_sequences.last_number + 1, updated_at = now()
             RETURNING last_number',
            [(string) Str::ulid(), $code, $year]
        );

        return sprintf('%s%d%06d', $code, $year, (int) $row->last_number);
    }

    private function assertRegistered(string $code): void
    {
        if ($this->registered[$code] ?? false) {
            return;
        }

        $exists = CodeRegistry::query()->where('code', $code)->where('is_active', true)->exists();

        if (! $exists) {
            throw new RuntimeException("Business code [{$code}] belum terdaftar atau tidak aktif pada code registry.");
        }

        $this->registered[$code] = true;
    }
}
