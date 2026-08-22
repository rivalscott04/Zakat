<?php

namespace App\Services;

use App\Enums\EntityStatus;
use App\Enums\NotificationChannel;
use App\Exceptions\ZakatException;
use App\Models\NotificationTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** PRD 16J dan 16K — template beserta validasi variabelnya. */
class NotificationTemplateService
{
    /** Variabel yang selalu tersedia, apa pun eventnya (PRD 16K §25). */
    public const AMBIENT_VARIABLES = ['recipient_name', 'organization_name', 'notification_number', 'app_name'];

    public function __construct(private readonly AuditService $audits) {}

    public function list(array $filters): LengthAwarePaginator
    {
        return NotificationTemplate::query()
            ->when($filters['channel'] ?? null, fn ($query, $channel) => $query->where('channel', $channel))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(
                fn ($inner) => $inner->where('template_code', 'ilike', "%{$search}%")->orWhere('name', 'ilike', "%{$search}%")
            ))
            ->orderBy('template_code')
            ->paginate(min((int) ($filters['per_page'] ?? config('zakat.pagination.per_page')), config('zakat.pagination.max_per_page')));
    }

    public function create(array $data): NotificationTemplate
    {
        $data['template_code'] = $this->normalizeCode($data['template_code']);
        $this->assertRenderable($data['content'], $data['variables'] ?? []);

        $template = new NotificationTemplate;
        $template->fill($data);
        $template->status = EntityStatus::Draft;
        $template->save();

        $this->audits->record('notification_template_created', $template, after: $template->getAttributes());

        return $template;
    }

    public function update(NotificationTemplate $template, array $data): NotificationTemplate
    {
        if (isset($data['template_code'])) {
            $data['template_code'] = $this->normalizeCode($data['template_code']);
        }

        $before = $template->getOriginal();
        $this->assertRenderable($data['content'] ?? $template->content, $data['variables'] ?? $template->variables ?? []);

        $template->fill($data);
        $template->save();

        $this->audits->record('notification_template_updated', $template, $before, $template->getAttributes());

        return $template;
    }

    /** PRD 16K §26 — template harus lolos validasi sebelum diaktifkan. */
    public function activate(NotificationTemplate $template): NotificationTemplate
    {
        $this->assertRenderable($template->content, $template->variables ?? []);

        $template->status = EntityStatus::Active;
        $template->save();

        $this->audits->record('notification_template_activated', $template);

        return $template;
    }

    public function deactivate(NotificationTemplate $template): NotificationTemplate
    {
        $template->status = EntityStatus::Inactive;
        $template->save();

        $this->audits->record('notification_template_deactivated', $template);

        return $template;
    }

    /**
     * PRD 16K §24 — substitusi `{{variable}}`.
     *
     * PRD 16Y §9 melarang unknown variable terkirim, jadi placeholder yang tidak
     * punya nilai membuat render gagal, bukan menghasilkan teks kosong.
     *
     * @param  array<string, mixed>  $values
     */
    public function render(string $content, array $values): string
    {
        $missing = array_diff($this->placeholders($content), array_keys(array_filter($values, fn ($value) => $value !== null)));

        if ($missing !== []) {
            throw ZakatException::conflict('Variabel template tidak memiliki nilai: '.implode(', ', $missing));
        }

        return preg_replace_callback(
            '/\{\{\s*([a-z0-9_.]+)\s*\}\}/i',
            fn (array $match) => (string) $values[$match[1]],
            $content
        );
    }

    /** @return array<int, string> */
    public function placeholders(string $content): array
    {
        preg_match_all('/\{\{\s*([a-z0-9_.]+)\s*\}\}/i', $content, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * PRD 16K §26 — sintaks salah dan variabel tak dikenal ditolak lebih awal.
     *
     * @param  array<int, string>  $declared
     */
    public function assertRenderable(string $content, array $declared): void
    {
        // Kurung buka tanpa penutup adalah sintaks tidak valid yang lolos regex
        // placeholder, jadi diperiksa terpisah.
        if (substr_count($content, '{{') !== substr_count($content, '}}')) {
            throw ZakatException::conflict('Sintaks template tidak valid: kurung variabel tidak berpasangan.');
        }

        $known = array_merge(self::AMBIENT_VARIABLES, $declared);
        $unknown = array_diff($this->placeholders($content), $known);

        if ($unknown !== []) {
            throw ZakatException::conflict('Variabel tidak dikenal pada template: '.implode(', ', $unknown));
        }
    }

    /** PRD 16J §23 — uppercase, tanpa dash. */
    private function normalizeCode(string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '');
    }

    public function activeFor(string $organizationId, string $templateCode, ?NotificationChannel $channel = null): ?NotificationTemplate
    {
        return NotificationTemplate::query()
            ->acrossOrganizations()
            ->where('organization_id', $organizationId)
            ->where('template_code', $this->normalizeCode($templateCode))
            ->where('status', EntityStatus::Active)
            ->when($channel, fn ($query) => $query->where('channel', $channel))
            ->first();
    }
}
