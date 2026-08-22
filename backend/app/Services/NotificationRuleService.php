<?php

namespace App\Services;

use App\Enums\EntityStatus;
use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Exceptions\ZakatException;
use App\Models\NotificationRule;
use App\Models\NotificationTemplate;
use App\Notifications\NotificationEventBridge;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/** PRD 16L §28 — aturan yang memetakan event ke template, channel, dan penerima. */
class NotificationRuleService
{
    public function __construct(private readonly AuditService $audits) {}

    public function list(array $filters): LengthAwarePaginator
    {
        return NotificationRule::query()
            ->with('template')
            ->when($filters['event_name'] ?? null, fn ($query, $event) => $query->where('event_name', $event))
            ->when(($filters['enabled'] ?? null) !== null, fn ($query) => $query->where('enabled', (bool) $filters['enabled']))
            ->orderBy('event_name')
            ->paginate(min((int) ($filters['per_page'] ?? config('zakat.pagination.per_page')), config('zakat.pagination.max_per_page')));
    }

    public function create(array $data): NotificationRule
    {
        $this->assertUsable($data);

        $rule = new NotificationRule;
        $rule->fill($data);
        // Default kolom hanya berlaku di database; model in memory tetap perlu
        // nilainya supaya resource tidak membaca null.
        $rule->priority = $data['priority'] ?? NotificationPriority::Normal->value;
        $rule->enabled = true;
        $rule->save();

        $this->forgetEventCache($rule);
        $this->audits->record('notification_rule_created', $rule, after: $rule->getAttributes());

        return $rule->load('template');
    }

    public function update(NotificationRule $rule, array $data): NotificationRule
    {
        $before = $rule->getOriginal();
        $this->assertUsable($data + $rule->only(['template_id', 'channels']));

        $rule->fill($data);
        $rule->save();

        $this->forgetEventCache($rule);
        $this->audits->record('notification_rule_updated', $rule, $before, $rule->getAttributes());

        return $rule->load('template');
    }

    public function setEnabled(NotificationRule $rule, bool $enabled): NotificationRule
    {
        $rule->enabled = $enabled;
        $rule->save();

        $this->forgetEventCache($rule);
        $this->audits->record($enabled ? 'notification_rule_enabled' : 'notification_rule_disabled', $rule);

        return $rule->load('template');
    }

    private function forgetEventCache(NotificationRule $rule): void
    {
        Cache::forget(NotificationEventBridge::cacheKey($rule->organization_id));
    }

    /**
     * Rule tidak boleh aktif dengan template yang belum aktif atau channel yang
     * tidak dilayani template tersebut, karena kegagalannya baru terlihat saat
     * event terjadi.
     */
    private function assertUsable(array $data): void
    {
        $templateId = $data['template_id'] ?? null;

        if ($templateId === null) {
            return;
        }

        $template = NotificationTemplate::query()->find($templateId);

        if ($template === null) {
            throw ZakatException::notFound('Template notifikasi tidak ditemukan.');
        }

        if ($template->status !== EntityStatus::Active) {
            throw ZakatException::conflict('Template harus aktif sebelum dipakai pada rule.');
        }

        $channels = array_map(fn (string $channel) => NotificationChannel::from($channel), (array) ($data['channels'] ?? []));

        if ($channels === []) {
            throw ZakatException::conflict('Rule harus memiliki minimal satu channel.');
        }
    }
}
