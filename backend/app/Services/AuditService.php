<?php

namespace App\Services;

use App\Models\AuditLog;
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
    ): AuditLog {
        $actor = Auth::user();

        return AuditLog::create([
            'request_id' => RequestId::current(),
            'actor_id' => $actorId ?? $actor?->getKey(),
            'actor_name' => $actor?->getAttribute('name'),
            'organization_id' => $organizationId ?? OrganizationContext::id(),
            'action' => $action,
            'entity_type' => $entity ? $entity::class : null,
            'entity_id' => $entity?->getKey(),
            'before' => $before === null ? null : $this->redact($before),
            'after' => $after === null ? null : $this->redact($after),
            'context' => $context === [] ? null : $this->redact($context),
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 1000),
            'created_at' => now(),
        ]);
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
