<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\NotificationPriority;
use App\Enums\NotificationRecipientType;
use App\Enums\NotificationStatus;
use App\Exceptions\ZakatException;
use App\Jobs\SendNotificationDelivery;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\NotificationPreference;
use App\Models\NotificationRule;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\NotificationChannelManager;
use App\Notifications\RecipientResolver;
use App\Support\OrganizationContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * PRD 16 — satu-satunya pintu masuk notifikasi (PRD 16B §3).
 *
 * Modul lain tidak memanggil channel atau mailer sendiri; cukup memanggil
 * dispatchEvent() dengan nama event, lalu rule organisasi yang menentukan
 * template, channel, penerima, dan prioritasnya.
 */
class NotificationService
{
    public function __construct(
        private readonly AuditService $audits,
        private readonly NotificationTemplateService $templates,
        private readonly RecipientResolver $recipients,
        private readonly NotificationChannelManager $channels,
    ) {}

    // ------------------------------------------------------------- pemicu

    /**
     * PRD 16L §27 — domain event dari modul lain.
     *
     * @param  array<string, mixed>  $payload
     * @return Collection<int, Notification>
     */
    public function dispatchEvent(
        string $eventName,
        array $payload = [],
        ?string $organizationId = null,
        ?string $idempotencyKey = null,
    ): Collection {
        $organizationId ??= OrganizationContext::id();

        if ($organizationId === null) {
            return collect();
        }

        $rules = NotificationRule::query()
            ->acrossOrganizations()
            ->with('template')
            ->where('organization_id', $organizationId)
            ->where('event_name', $eventName)
            ->where('enabled', true)
            ->get();

        return $rules->flatMap(fn (NotificationRule $rule) => $this->applyRule($rule, $organizationId, $payload, $idempotencyKey));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return Collection<int, Notification>
     */
    private function applyRule(NotificationRule $rule, string $organizationId, array $payload, ?string $idempotencyKey): Collection
    {
        $userIds = $this->recipients->resolve($rule, $organizationId, $payload);

        // PRD 16Y §2 — notification tanpa recipient tidak dibuat sama sekali.
        if ($userIds === []) {
            return collect();
        }

        $channels = collect($rule->channels ?? [])
            ->map(fn (string $channel) => NotificationChannel::from($channel))
            ->all();

        return collect($userIds)->map(fn (string $userId) => $this->send(
            organizationId: $organizationId,
            userId: $userId,
            content: $this->compose($rule, $organizationId, $userId, $payload),
            channels: $channels,
            priority: $rule->priority,
            eventName: $rule->event_name,
            rule: $rule,
            idempotencyKey: $idempotencyKey,
        ))->filter()->values();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{title: string, message: string}
     */
    private function compose(NotificationRule $rule, string $organizationId, string $userId, array $payload): array
    {
        $template = $rule->template;

        if ($template === null) {
            return [
                'title' => $payload['title'] ?? $rule->event_name,
                'message' => $payload['message'] ?? $rule->event_name,
            ];
        }

        $variables = $payload + [
            'recipient_name' => User::query()->whereKey($userId)->value('name'),
            'organization_name' => Organization::query()->withoutGlobalScopes()->whereKey($organizationId)->value('name'),
            'app_name' => config('app.name'),
            'notification_number' => null,
        ];

        return [
            'title' => $this->templates->render($template->subject ?? $rule->event_name, $variables),
            'message' => $this->templates->render($template->content, $variables),
        ];
    }

    // ------------------------------------------------------- pembuatan

    /**
     * @param  array{title: string, message: string}  $content
     * @param  array<int, NotificationChannel>  $channels
     */
    public function send(
        string $organizationId,
        string $userId,
        array $content,
        array $channels,
        NotificationPriority $priority = NotificationPriority::Normal,
        ?string $eventName = null,
        ?NotificationRule $rule = null,
        ?string $idempotencyKey = null,
        ?string $batchId = null,
        ?\DateTimeInterface $scheduledAt = null,
        array $data = [],
        bool $hold = false,
    ): ?Notification {
        // PRD 16Y §7 — event yang sama tidak boleh menghasilkan notification ganda.
        if ($idempotencyKey !== null && $this->alreadySent($organizationId, $userId, $idempotencyKey)) {
            return null;
        }

        $notification = DB::transaction(function () use (
            $organizationId, $userId, $content, $channels, $priority, $eventName, $rule, $idempotencyKey, $batchId, $scheduledAt, $data, $hold
        ) {
            $notification = new Notification;
            $notification->fill([
                'recipient_type' => NotificationRecipientType::User->value,
                'recipient_id' => $userId,
                'title' => $content['title'],
                'message' => $content['message'],
                'data' => $data === [] ? null : $data,
                'priority' => $priority->value,
            ]);
            $notification->organization_id = $organizationId;
            $notification->event_name = $eventName;
            $notification->rule_id = $rule?->getKey();
            $notification->template_id = $rule?->template_id;
            $notification->batch_id = $batchId;
            $notification->idempotency_key = $idempotencyKey;
            $notification->created_by = Auth::id();
            $notification->scheduled_at = $scheduledAt;
            // PRD 16Q §35 — yang berjadwal menunggu scheduler, sisanya masuk
            // antrean. Anggota batch ditahan sebagai draft sampai batch dikirim.
            $notification->status = match (true) {
                $hold => NotificationStatus::Draft,
                $scheduledAt !== null && $scheduledAt > now() => NotificationStatus::Scheduled,
                default => NotificationStatus::Queued,
            };
            $notification->save();

            $this->createDeliveries($notification, $channels);

            return $notification;
        });

        $this->audits->record(
            $notification->status === NotificationStatus::Scheduled ? 'notification_scheduled' : 'notification_created',
            $notification,
            after: $notification->only(['notification_number', 'event_name', 'recipient_id', 'priority', 'status']),
            organizationId: $organizationId,
        );

        if ($notification->status === NotificationStatus::Queued) {
            $this->queue($notification);
        }

        return $notification;
    }

    /** @param array<int, NotificationChannel> $channels */
    private function createDeliveries(Notification $notification, array $channels): void
    {
        foreach ($channels as $channel) {
            // PRD 16Y §11 — preference dicek sebelum notification non-critical dikirim.
            if (! $this->channelAllowed($notification, $channel)) {
                continue;
            }

            $driver = $this->channels->for($channel);

            $delivery = new NotificationDelivery;
            $delivery->notification_id = $notification->getKey();
            $delivery->channel = $channel;
            $delivery->recipient_address = $driver->resolveAddress($notification);
            $delivery->status = NotificationDeliveryStatus::Pending;
            $delivery->max_attempts = $channel->maxAttempts();
            $delivery->save();
        }
    }

    private function channelAllowed(Notification $notification, NotificationChannel $channel): bool
    {
        // PRD 16Y §12 — URGENT boleh melewati preference.
        if ($notification->priority->overridesPreference() || $notification->event_name === null) {
            return true;
        }

        $preference = NotificationPreference::query()
            ->where('user_id', $notification->recipient_id)
            ->where('organization_id', $notification->organization_id)
            ->where('event_name', $notification->event_name)
            ->where('channel', $channel)
            ->first();

        // Tanpa preference tersimpan, channel dianggap aktif.
        return $preference === null || $preference->enabled;
    }

    // -------------------------------------------------------- pengiriman

    /** PRD 16O §32 — channel eksternal lewat queue, in app langsung. */
    public function queue(Notification $notification): void
    {
        $notification->deliveries()
            ->where('status', NotificationDeliveryStatus::Pending)
            ->get()
            ->each(function (NotificationDelivery $delivery) {
                $delivery->status = NotificationDeliveryStatus::Queued;
                $delivery->save();

                if ($delivery->channel->isExternal()) {
                    SendNotificationDelivery::dispatch($delivery->getKey());

                    return;
                }

                $this->deliver($delivery);
            });

        $this->refreshStatus($notification);
    }

    /**
     * Dipanggil job maupun pengiriman langsung.
     *
     * Sengaja tidak melempar exception: kegagalan channel tidak boleh
     * menggagalkan transaksi bisnis yang memicunya. Job yang memutuskan perlu
     * tidaknya percobaan ulang, berdasarkan NotificationDelivery::canRetry().
     */
    public function deliver(NotificationDelivery $delivery): NotificationDelivery
    {
        if ($delivery->status->isFinal()) {
            return $delivery;
        }

        $delivery->status = NotificationDeliveryStatus::Processing;
        $delivery->attempt_count = $delivery->attempt_count + 1;
        $delivery->last_attempt_at = now();
        $delivery->save();

        try {
            $result = $this->channels->for($delivery->channel)->send($delivery);

            $delivery->provider = $result['provider'];
            $delivery->provider_reference = $result['provider_reference'];
            $delivery->status = $result['delivered'] ? NotificationDeliveryStatus::Delivered : NotificationDeliveryStatus::Sent;
            $delivery->sent_at = now();
            $delivery->delivered_at = $result['delivered'] ? now() : null;
            $delivery->error_message = null;
            $delivery->save();

            $this->audits->record(
                $result['delivered'] ? 'notification_delivered' : 'notification_sent',
                $delivery->notification,
                context: ['channel' => $delivery->channel->value],
                organizationId: $delivery->notification->organization_id,
            );
        } catch (Throwable $exception) {
            // PRD 16P §33 — kegagalan menjadi FAILED hanya setelah kuota retry habis.
            $exhausted = $delivery->attempt_count >= $delivery->max_attempts;

            $delivery->status = $exhausted ? NotificationDeliveryStatus::Failed : NotificationDeliveryStatus::Queued;
            $delivery->failed_at = $exhausted ? now() : null;
            $delivery->error_message = substr($exception->getMessage(), 0, 1000);
            $delivery->save();

            $this->refreshStatus($delivery->notification);

            if ($exhausted) {
                $this->audits->record(
                    'notification_failed',
                    $delivery->notification,
                    context: ['channel' => $delivery->channel->value, 'error' => $delivery->error_message],
                    organizationId: $delivery->notification->organization_id,
                );

                return $delivery;
            }
        }

        $this->refreshStatus($delivery->notification);

        return $delivery;
    }

    /** PRD 16F §12 — status notification adalah rangkuman status delivery-nya. */
    private function refreshStatus(Notification $notification): void
    {
        $deliveries = $notification->deliveries()->get();

        if ($deliveries->isEmpty() || $deliveries->contains(fn (NotificationDelivery $item) => ! $item->status->isFinal())) {
            return;
        }

        $succeeded = $deliveries->filter(fn (NotificationDelivery $item) => $item->status->isSuccessful())->count();

        $notification->status = match (true) {
            $succeeded === 0 => NotificationStatus::Failed,
            $succeeded === $deliveries->count() => NotificationStatus::Sent,
            default => NotificationStatus::PartiallySent,
        };
        $notification->sent_at = $succeeded > 0 ? ($notification->sent_at ?? now()) : null;
        $notification->save();
    }

    /** PRD 16Q §35 — dipanggil scheduler ketika waktunya tiba. */
    public function dispatchScheduled(): int
    {
        $due = Notification::query()
            ->acrossOrganizations()
            ->where('status', NotificationStatus::Scheduled)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($due as $notification) {
            $notification->status = NotificationStatus::Queued;
            $notification->save();

            $this->audits->record('notification_queued', $notification, organizationId: $notification->organization_id);
            $this->queue($notification);
        }

        return $due->count();
    }

    // ------------------------------------------------------ notification center

    public function listForCurrentUser(array $filters): LengthAwarePaginator
    {
        return Notification::query()
            ->where('recipient_type', NotificationRecipientType::User)
            ->where('recipient_id', $this->currentUserId())
            ->when(($filters['unread'] ?? null) !== null, fn ($query) => (bool) $filters['unread']
                ? $query->whereNull('read_at')
                : $query->whereNotNull('read_at'))
            ->when($filters['priority'] ?? null, fn ($query, $priority) => $query->where('priority', $priority))
            ->when($filters['event_name'] ?? null, fn ($query, $event) => $query->where('event_name', $event))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(
                fn ($inner) => $inner->where('title', 'ilike', "%{$search}%")->orWhere('notification_number', 'ilike', "%{$search}%")
            ))
            ->latest('created_at')
            ->paginate(min((int) ($filters['per_page'] ?? config('zakat.pagination.per_page')), config('zakat.pagination.max_per_page')));
    }

    public function unreadCount(): int
    {
        return Notification::query()
            ->where('recipient_type', NotificationRecipientType::User)
            ->where('recipient_id', $this->currentUserId())
            ->whereNull('read_at')
            ->count();
    }

    /** PRD 16Y §14 — notification hanya dapat diakses recipient-nya. */
    public function findForCurrentUser(string $id): Notification
    {
        $notification = Notification::query()
            ->with('deliveries')
            ->whereKey($id)
            ->where('recipient_type', NotificationRecipientType::User)
            ->where('recipient_id', $this->currentUserId())
            ->first();

        return $notification ?? throw ZakatException::notFound('Notifikasi tidak ditemukan.');
    }

    public function markRead(string $id): Notification
    {
        $notification = $this->findForCurrentUser($id);

        if (! $notification->isRead()) {
            $notification->read_at = now();
            $notification->save();

            $this->audits->record('notification_read', $notification, organizationId: $notification->organization_id);
        }

        return $notification;
    }

    public function markUnread(string $id): Notification
    {
        $notification = $this->findForCurrentUser($id);

        if ($notification->isRead()) {
            $notification->read_at = null;
            $notification->save();

            $this->audits->record('notification_unread', $notification, organizationId: $notification->organization_id);
        }

        return $notification;
    }

    public function markAllRead(): int
    {
        return Notification::query()
            ->where('recipient_type', NotificationRecipientType::User)
            ->where('recipient_id', $this->currentUserId())
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);
    }

    public function delete(string $id): void
    {
        $notification = $this->findForCurrentUser($id);

        $this->audits->record('notification_cancelled', $notification, organizationId: $notification->organization_id);

        $notification->delete();
    }

    private function alreadySent(string $organizationId, string $userId, string $idempotencyKey): bool
    {
        return Notification::query()
            ->acrossOrganizations()
            ->where('organization_id', $organizationId)
            ->where('recipient_id', $userId)
            ->where('idempotency_key', $idempotencyKey)
            ->exists();
    }

    private function currentUserId(): string
    {
        return Auth::id() ?? throw ZakatException::forbidden('Notifikasi hanya dapat dibaca oleh penggunanya.');
    }
}
