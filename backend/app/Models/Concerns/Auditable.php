<?php

namespace App\Models\Concerns;

use App\Services\AuditService;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * PRD 00 §24 — perubahan entity sensitif otomatis tercatat pada audit trail.
 * Action mengikuti penamaan PRD 02 §39, contoh `organization_updated`.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->recordAudit('created', null, $model->auditAttributes($model->getAttributes()));
        });

        static::updated(function ($model) {
            $changed = array_keys($model->getChanges());

            if ($changed === [] || $changed === [$model->getUpdatedAtColumn()]) {
                return;
            }

            $model->recordAudit(
                'updated',
                $model->auditAttributes(Arr::only($model->getOriginal(), $changed)),
                $model->auditAttributes($model->getChanges()),
            );
        });

        static::deleted(function ($model) {
            $model->recordAudit('deleted', $model->auditAttributes($model->getOriginal()), null);
        });
    }

    /** @param array<string, mixed>|null $before */
    public function recordAudit(string $event, ?array $before, ?array $after, array $context = []): void
    {
        app(AuditService::class)->record(
            action: $this->auditPrefix().'_'.$event,
            entity: $this,
            before: $before,
            after: $after,
            context: $context,
            organizationId: $this->auditOrganizationId(),
        );
    }

    public function auditPrefix(): string
    {
        return Str::snake(class_basename($this));
    }

    protected function auditOrganizationId(): ?string
    {
        if ($this instanceof \App\Models\Organization) {
            return $this->getKey();
        }

        return $this->getAttribute('organization_id');
    }

    /**
     * Buang kolom yang tidak bermakna atau sensitif sebelum masuk audit.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function auditAttributes(array $attributes): array
    {
        return app(AuditService::class)->redact(
            collect($attributes)->except($this->getHidden())->except(['created_at', 'updated_at'])->all()
        );
    }
}
