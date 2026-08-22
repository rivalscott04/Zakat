<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Support\AuditEventClassifier;
use App\Support\OrganizationContext;
use App\Support\RequestId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/** PRD 00 §24, PRD 01 §41, PRD 02 §40 — pencatatan audit trail. */
class AuditService
{
    /**
     * Key yang tidak boleh pernah masuk audit (PRD 01 §41).
     *
     * @var array<int, string>
     */
    private const REDACTED = [
        'password', 'password_confirmation', 'current_password', 'new_password',
        'remember_token', 'token', 'token_hash', 'secret', 'api_key', 'otp',
        'access_token', 'refresh_token', 'private_key', 'webhook_secret',
        // PRD 03 §16, §67 — ciphertext, hash identity, dan nilai contact
        // tidak boleh menjadi payload audit yang dapat diexport/dibaca ulang.
        'identity_number_encrypted', 'identity_number_hash', 'value_encrypted', 'value_hash',
    ];

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>  $context
     */
    public function record(
        string $action,
        ?Model $entity = null,
        ?array $before = null,
        ?array $after = null,
        array $context = [],
        ?string $organizationId = null,
        ?string $actorId = null,
        ?string $description = null,
    ): AuditLog {
        $actor = Auth::user();
        $classification = AuditEventClassifier::classify($action, $entity);

        return AuditLog::create([
            // PRD 17C §8 — penomoran audit.
            'audit_number' => app(BusinessNumberService::class)->next('AUD'),
            'event_name' => $classification['event_name'],
            'event_category' => $classification['event_category'],
            'module_code' => $classification['module_code'],
            'severity' => $classification['severity'],
            'request_id' => RequestId::current(),
            'actor_id' => $actorId ?? $actor?->getKey(),
            'actor_name' => $actor?->getAttribute('name'),
            // PRD 17F §11 — peristiwa tanpa pengguna dicatat sebagai SYSTEM.
            'actor_type' => ($actorId ?? $actor?->getKey()) === null ? 'SYSTEM' : 'USER',
            'organization_id' => $organizationId ?? OrganizationContext::id(),
            'action' => $action,
            'entity_type' => $entity ? $entity::class : null,
            'entity_id' => $entity?->getKey(),
            'entity_reference' => $this->reference($entity),
            'description' => $description,
            'old_values' => $before === null ? null : $this->redact($before),
            'new_values' => $after === null ? null : $this->redact($after),
            'metadata' => $context === [] ? null : $this->redact($context),
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 1000),
            // PRD 17C §6 — waktu kejadian dipisahkan dari waktu penyimpanan.
            'occurred_at' => now(),
            'created_at' => now(),
        ]);
    }

    /**
     * PRD 17G §14 — penanda yang dapat dibaca manusia untuk entitas terkait.
     *
     * Dicari dari kolom penomoran yang lazim dipakai modul, supaya baris audit
     * tetap bermakna walau entitasnya sudah terhapus.
     */
    private function reference(?Model $entity): ?string
    {
        if ($entity === null) {
            return null;
        }

        // Dibaca dari array atribut, bukan getAttribute(), karena Model::shouldBeStrict()
        // melempar exception untuk kolom yang tidak dimiliki model bersangkutan.
        $attributes = $entity->getAttributes();

        foreach (['business_number', 'payment_number', 'distribution_number', 'collection_number', 'document_number', 'session_number', 'refund_number', 'batch_number', 'movement_number', 'journal_number', 'account_code', 'mustahik_number', 'code', 'email'] as $column) {
            if (filled($attributes[$column] ?? null)) {
                return (string) $attributes[$column];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function redact(array $attributes): array
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, self::REDACTED, true)) {
                $attributes[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $attributes[$key] = $this->redact($value);
            }
        }

        return $attributes;
    }
}
