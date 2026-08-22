<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Exceptions\ZakatException;
use App\Models\Notification;
use App\Models\NotificationBatch;
use App\Support\OrganizationContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

/**
 * PRD 16R §36 — satu batch menampung banyak recipient.
 *
 * Batch dibuat dalam status draft; pengiriman baru terjadi saat send() dipanggil
 * supaya daftar penerima dapat diperiksa lebih dulu.
 */
class NotificationBatchService
{
    public function __construct(
        private readonly AuditService $audits,
        private readonly NotificationService $notifications,
    ) {}

    public function list(array $filters): LengthAwarePaginator
    {
        return NotificationBatch::query()
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('created_at')
            ->paginate(min((int) ($filters['per_page'] ?? config('zakat.pagination.per_page')), config('zakat.pagination.max_per_page')));
    }

    public function find(string $id): NotificationBatch
    {
        return NotificationBatch::query()->with('notifications')->findOrFail($id);
    }

    /**
     * @param  array{name: string, title: string, message: string, channels: array<int, string>, recipient_ids: array<int, string>, priority?: string}  $data
     */
    public function create(array $data): NotificationBatch
    {
        $organizationId = OrganizationContext::requireId();

        $batch = new NotificationBatch;
        $batch->fill(['name' => $data['name']]);
        $batch->organization_id = $organizationId;
        $batch->created_by = Auth::id();
        $batch->status = NotificationStatus::Draft->value;
        $batch->total_recipient = count($data['recipient_ids']);
        $batch->save();

        $this->audits->record('notification_batch_created', $batch, after: $batch->getAttributes());

        // Payload penerima tidak disimpan terpisah: notification dibuat langsung
        // dalam status draft agar tetap satu sumber kebenaran.
        foreach ($data['recipient_ids'] as $userId) {
            $this->notifications->send(
                organizationId: $organizationId,
                userId: $userId,
                content: ['title' => $data['title'], 'message' => $data['message']],
                channels: array_map(fn (string $channel) => NotificationChannel::from($channel), $data['channels']),
                priority: NotificationPriority::from($data['priority'] ?? NotificationPriority::Normal->value),
                batchId: $batch->getKey(),
                hold: true,
            );
        }

        return $batch->refresh()->load('notifications');
    }

    /** PRD 16R — pengiriman batch tetap lewat queue milik NotificationService. */
    public function send(NotificationBatch $batch): NotificationBatch
    {
        if ($batch->status !== NotificationStatus::Draft->value) {
            throw ZakatException::invalidTransition('Batch hanya dapat dikirim sekali dari status draft.');
        }

        $batch->notifications()->get()->each(function (Notification $notification) {
            $notification->status = NotificationStatus::Queued;
            $notification->save();

            $this->notifications->queue($notification);
        });

        return $this->settle($batch, 'notification_batch_sent');
    }

    public function cancel(NotificationBatch $batch): NotificationBatch
    {
        if ($batch->status !== NotificationStatus::Draft->value) {
            throw ZakatException::invalidTransition('Hanya batch draft yang dapat dibatalkan.');
        }

        $batch->notifications()->get()->each(function (Notification $notification) {
            $notification->status = NotificationStatus::Cancelled;
            $notification->save();
        });

        $batch->status = NotificationStatus::Cancelled->value;
        $batch->save();

        $this->audits->record('notification_batch_cancelled', $batch);

        return $batch->load('notifications');
    }

    private function settle(NotificationBatch $batch, string $action): NotificationBatch
    {
        $notifications = $batch->notifications()->get();

        $batch->total_success = $notifications
            ->filter(fn (Notification $item) => in_array($item->status, [NotificationStatus::Sent, NotificationStatus::PartiallySent], true))
            ->count();
        $batch->total_failed = $notifications->filter(fn (Notification $item) => $item->status === NotificationStatus::Failed)->count();
        $batch->status = NotificationStatus::Sent->value;
        $batch->save();

        $this->audits->record($action, $batch, after: $batch->only(['total_recipient', 'total_success', 'total_failed']));

        return $batch->load('notifications');
    }
}
