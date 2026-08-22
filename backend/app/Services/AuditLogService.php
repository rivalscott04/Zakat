<?php

namespace App\Services;

use App\Exceptions\ZakatException;
use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * PRD 17P sampai 17S — sisi baca audit trail.
 *
 * Modul ini tidak pernah menulis maupun mengubah audit. Penulisan hanya lewat
 * AuditService, dan PRD 17B §3 serta §4 menegaskan catatan bersifat append only
 * dan tidak dapat diubah.
 */
class AuditLogService
{
    /**
     * PRD 17P §30 — penyaringan daftar audit.
     *
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters): LengthAwarePaginator
    {
        return $this->query($filters)
            ->orderByDesc('occurred_at')
            ->paginate(min((int) ($filters['per_page'] ?? 25), (int) config('zakat.pagination.max_per_page')));
    }

    public function find(string $id): AuditLog
    {
        return AuditLog::query()->find($id) ?? throw ZakatException::notFound('Catatan audit tidak ditemukan.');
    }

    /**
     * PRD 17R §33 — riwayat satu entitas.
     *
     * @return Collection<int, AuditLog>
     */
    public function forEntity(string $entityType, string $entityId): Collection
    {
        return AuditLog::query()
            // entity_type disimpan sebagai nama kelas penuh; penyebutan singkat
            // seperti "Distribution" tetap diterima agar URL enak dibaca.
            ->where(fn ($query) => $query->where('entity_type', $entityType)->orWhere('entity_type', 'like', '%\\\\'.$entityType))
            ->where('entity_id', $entityId)
            ->orderByDesc('occurred_at')
            ->get();
    }

    /**
     * PRD 17J §19 — seluruh peristiwa yang lahir dari satu request.
     *
     * @return Collection<int, AuditLog>
     */
    public function forRequest(string $requestId): Collection
    {
        return AuditLog::query()->where('request_id', $requestId)->orderBy('occurred_at')->get();
    }

    /**
     * PRD 17S §34 — ekspor sebagai baris siap unduh.
     *
     * PRD 17S §35 membatasi ekspor, jadi jumlah baris dipagari keras di sini.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function export(array $filters): array
    {
        return $this->query($filters)
            ->orderByDesc('occurred_at')
            ->limit(5000)
            ->get()
            ->map(fn (AuditLog $log) => [
                'audit_number' => $log->audit_number,
                'occurred_at' => $log->occurred_at?->toIso8601String(),
                'event_name' => $log->event_name,
                'event_category' => $log->event_category,
                'module_code' => $log->module_code,
                'severity' => $log->severity,
                'actor_name' => $log->actor_name,
                'actor_type' => $log->actor_type,
                'entity_type' => $log->entity_type ? class_basename($log->entity_type) : null,
                'entity_reference' => $log->entity_reference,
                'ip_address' => $log->ip_address,
                'request_id' => $log->request_id,
            ])
            ->all();
    }

    /** PRD 17V §40 — ringkasan untuk dasbor audit. */
    public function summary(array $filters): array
    {
        $base = fn () => $this->query($filters);

        return [
            'total' => $base()->count(),
            'by_severity' => $base()->selectRaw('severity, count(*) as total')->groupBy('severity')->pluck('total', 'severity'),
            'by_category' => $base()->selectRaw('event_category, count(*) as total')->groupBy('event_category')->orderByDesc('total')->limit(10)->pluck('total', 'event_category'),
            'by_module' => $base()->selectRaw('module_code, count(*) as total')->groupBy('module_code')->orderByDesc('total')->limit(10)->pluck('total', 'module_code'),
        ];
    }

    /**
     * PRD 17O §27 — pemeriksaan keutuhan sederhana.
     *
     * Belum memakai hash chain (PRD 17O §28 menyebutnya opsional). Yang diperiksa
     * adalah tanda-tanda catatan yang hilang atau cacat: penomoran ganda,
     * penomoran kosong, dan waktu kejadian yang tidak terisi.
     */
    public function integrityCheck(array $filters): array
    {
        $duplicates = AuditLog::query()
            ->selectRaw('audit_number, count(*) as total')
            ->whereNotNull('audit_number')
            ->groupBy('audit_number')
            ->havingRaw('count(*) > 1')
            ->pluck('total', 'audit_number');

        $missingNumber = AuditLog::query()->whereNull('audit_number')->count();
        $missingTime = AuditLog::query()->whereNull('occurred_at')->count();

        return [
            'checked_at' => now()->toIso8601String(),
            'total_records' => AuditLog::query()->count(),
            'duplicate_numbers' => $duplicates,
            'records_without_number' => $missingNumber,
            'records_without_occurred_at' => $missingTime,
            'healthy' => $duplicates->isEmpty() && $missingNumber === 0 && $missingTime === 0,
        ];
    }

    /** @param array<string, mixed> $filters */
    private function query(array $filters): Builder
    {
        return AuditLog::query()
            ->when($filters['event_category'] ?? null, fn ($q, $v) => $q->where('event_category', $v))
            ->when($filters['module_code'] ?? null, fn ($q, $v) => $q->where('module_code', $v))
            ->when($filters['severity'] ?? null, fn ($q, $v) => $q->where('severity', $v))
            ->when($filters['actor_id'] ?? null, fn ($q, $v) => $q->where('actor_id', $v))
            ->when($filters['entity_type'] ?? null, fn ($q, $v) => $q->where('entity_type', 'like', '%'.$v))
            ->when($filters['entity_id'] ?? null, fn ($q, $v) => $q->where('entity_id', $v))
            ->when($filters['request_id'] ?? null, fn ($q, $v) => $q->where('request_id', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('occurred_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('occurred_at', '<=', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where(
                fn ($x) => $x->where('event_name', 'ilike', "%{$v}%")
                    ->orWhere('audit_number', 'ilike', "%{$v}%")
                    ->orWhere('entity_reference', 'ilike', "%{$v}%")
                    ->orWhere('actor_name', 'ilike', "%{$v}%")
            ));
    }
}
