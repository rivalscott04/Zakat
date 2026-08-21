<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentSourceType;
use App\Enums\PaymentStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasBusinessNumber;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PRD 13C §7.
 *
 * Nominal, status, dan referensi provider tidak fillable: seluruhnya ditentukan
 * Service Layer atau jawaban provider, tidak pernah dari payload client
 * (CLAUDE.md §34, PRD 13V §44 soal manipulasi amount dan status).
 */
#[Fillable(['source_type', 'source_id', 'payer_name', 'payer_email', 'payer_phone', 'payment_method', 'internal_reference', 'metadata'])]
class Payment extends Model
{
    use BelongsToOrganization, HasBusinessNumber, HasUlids;

    public static function businessCode(): string
    {
        return 'PAY';
    }

    protected function casts(): array
    {
        return [
            'source_type' => PaymentSourceType::class,
            'payment_method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'verified_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function businessNumberColumn(): string
    {
        return 'payment_number';
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(PaymentProvider::class, 'provider_id');
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(PaymentWebhook::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(PaymentRefund::class);
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(PaymentReconciliation::class);
    }

    /** PRD 13M §25 — pending yang lewat masa berlaku dianggap kedaluwarsa. */
    public function isExpired(): bool
    {
        return $this->status === PaymentStatus::Pending
            && $this->expires_at !== null
            && $this->expires_at->isPast();
    }

    /** PRD 13O §29 — refund tidak boleh melebihi nominal yang sudah dibayar. */
    public function refundableAmount(): string
    {
        $refunded = (string) $this->refunds()
            ->whereIn('status', ['approved', 'processing', 'completed'])
            ->sum('amount');

        return bcsub((string) $this->amount, $refunded, 2);
    }
}
