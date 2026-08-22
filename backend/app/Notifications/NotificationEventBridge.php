<?php

namespace App\Notifications;

use App\Models\AuditLog;
use App\Models\NotificationRule;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * PRD 16B §3 dan PRD 16L §27 — jembatan domain event ke modul notification.
 *
 * Nama aksi audit sudah identik dengan nama event pada PRD 16L (`payment_paid`,
 * `distribution_request_approved`, `document_uploaded`, dan seterusnya), jadi
 * satu titik sambung di AuditService membuat seluruh modul memancarkan event
 * tanpa perlu memanggil notification sendiri. Itu sekaligus menegakkan larangan
 * PRD 16B §3: modul lain memang tidak punya jalur notifikasi sendiri.
 *
 * ponytail: pengecekan rule memakai cache daftar event per organisasi. Kalau
 * nanti jumlah rule membesar, ganti dengan indeks event yang dimuat sekali per
 * request.
 */
class NotificationEventBridge
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handle(AuditLog $log, ?Model $entity, ?array $after): void
    {
        $eventName = $log->action;

        // Notifikasi tentang notifikasi akan memicu dirinya sendiri.
        if (str_starts_with($eventName, 'notification_') || $log->organization_id === null) {
            return;
        }

        if (! in_array($eventName, $this->eventsWithRules($log->organization_id), true)) {
            return;
        }

        try {
            $this->notifications->dispatchEvent(
                eventName: $eventName,
                payload: $this->payload($log, $entity, $after),
                organizationId: $log->organization_id,
                // Satu peristiwa audit menghasilkan paling banyak satu notification
                // per penerima (PRD 16Y §7).
                idempotencyKey: 'audit:'.$log->getKey(),
            );
        } catch (Throwable $exception) {
            // Kegagalan notifikasi tidak boleh membatalkan aksi bisnis yang sudah terjadi.
            Log::warning('Gagal mengirim notifikasi untuk event '.$eventName, ['error' => $exception->getMessage()]);
        }
    }

    /** @return array<string, mixed> */
    private function payload(AuditLog $log, ?Model $entity, ?array $after): array
    {
        $attributes = $entity?->getAttributes() ?? [];

        return array_filter(
            ($after ?? []) + $attributes + [
                'owner_id' => $attributes['created_by'] ?? $log->actor_id,
                'entity_reference' => $log->entity_reference,
                'title' => $log->description ?? $log->event_name,
                'message' => $log->description ?? $log->event_name,
            ],
            fn ($value) => is_scalar($value) || $value === null,
        );
    }

    /** @return array<int, string> */
    private function eventsWithRules(string $organizationId): array
    {
        return Cache::remember(
            self::cacheKey($organizationId),
            now()->addMinutes(10),
            fn () => NotificationRule::query()
                ->acrossOrganizations()
                ->where('organization_id', $organizationId)
                ->where('enabled', true)
                ->distinct()
                ->pluck('event_name')
                ->all(),
        );
    }

    public static function cacheKey(string $organizationId): string
    {
        return 'notification:events:'.$organizationId;
    }
}
