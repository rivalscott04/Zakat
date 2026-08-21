<?php

namespace App\Models;

use App\Enums\PaymentProviderStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PRD 13B §4.
 *
 * Kredensial tidak fillable dan selalu hidden: keduanya hanya boleh diisi lewat
 * service, dan tidak pernah ikut serialisasi (PRD 13U §5, PRD 13T §40).
 */
#[Fillable(['provider_code', 'name', 'driver', 'sandbox_mode'])]
#[Hidden(['config_encrypted', 'webhook_secret_encrypted'])]
class PaymentProvider extends Model
{
    use Auditable, BelongsToOrganization, HasUlids;

    protected function casts(): array
    {
        return [
            'config_encrypted' => 'encrypted:array',
            'webhook_secret_encrypted' => 'encrypted',
            'status' => PaymentProviderStatus::class,
            'sandbox_mode' => 'boolean',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'provider_id');
    }

    public function isActive(): bool
    {
        return $this->status === PaymentProviderStatus::Active;
    }

    /** Yang boleh dilihat pengguna: kunci konfigurasinya saja, bukan nilainya. */
    public function configuredKeys(): array
    {
        return array_keys($this->config_encrypted ?? []);
    }

    public function hasWebhookSecret(): bool
    {
        return filled($this->webhook_secret_encrypted);
    }

    public function auditPrefix(): string
    {
        return 'payment_provider';
    }
}
