<?php

namespace App\Services;

use App\Enums\EntityStatus;
use App\Models\NotificationEmailConfig;
use App\Models\NotificationWebhook;
use App\Support\OrganizationContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * PRD 16H §17 dan PRD 16I §20 — konfigurasi tujuan channel eksternal.
 *
 * Secret dan credential disimpan terenkripsi oleh cast model dan tidak pernah
 * dikembalikan penuh (PRD 16H §18, PRD 16Y §16 dan §17).
 */
class NotificationEndpointService
{
    public function __construct(private readonly AuditService $audits) {}

    // ---------------------------------------------------------- webhook

    public function webhooks(): Collection
    {
        return NotificationWebhook::query()->orderBy('name')->get();
    }

    public function createWebhook(array $data): array
    {
        $webhook = new NotificationWebhook;
        $webhook->fill($data);
        $webhook->status = EntityStatus::Active;

        // Secret dibuat backend dan hanya ditampilkan sekali di response ini.
        $secret = Str::random(48);
        $webhook->secret_encrypted = $secret;
        $webhook->save();

        $this->audits->record('notification_webhook_created', $webhook, after: $webhook->only(['name', 'url', 'events']));

        return ['webhook' => $webhook, 'secret' => $secret];
    }

    public function updateWebhook(NotificationWebhook $webhook, array $data): NotificationWebhook
    {
        $before = $webhook->only(['name', 'url', 'events', 'status']);

        $webhook->fill($data);

        if (isset($data['status'])) {
            $webhook->status = EntityStatus::from($data['status']);
        }

        $webhook->save();

        $this->audits->record('notification_webhook_updated', $webhook, $before, $webhook->only(['name', 'url', 'events', 'status']));

        return $webhook;
    }

    public function rotateWebhookSecret(NotificationWebhook $webhook): array
    {
        $secret = Str::random(48);
        $webhook->secret_encrypted = $secret;
        $webhook->save();

        $this->audits->record('notification_webhook_updated', $webhook, context: ['secret_rotated' => true]);

        return ['webhook' => $webhook, 'secret' => $secret];
    }

    // ------------------------------------------------------ email config

    public function emailConfig(): ?NotificationEmailConfig
    {
        return NotificationEmailConfig::query()->first();
    }

    public function saveEmailConfig(array $data): NotificationEmailConfig
    {
        $config = $this->emailConfig() ?? new NotificationEmailConfig;
        $before = $config->exists ? $config->only(['driver', 'host', 'port', 'from_email', 'encryption', 'status']) : null;

        $config->fill($data);
        $config->organization_id = OrganizationContext::requireId();
        $config->status = EntityStatus::Active;

        if (array_key_exists('username', $data)) {
            $config->username_encrypted = $data['username'];
        }

        // Password kosong berarti tidak diganti, bukan dikosongkan.
        if (filled($data['password'] ?? null)) {
            $config->password_encrypted = $data['password'];
        }

        $config->save();

        $this->audits->record(
            'notification_email_config_updated',
            $config,
            $before,
            $config->only(['driver', 'host', 'port', 'from_email', 'encryption', 'status']),
        );

        return $config;
    }
}
