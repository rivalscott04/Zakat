<?php

namespace App\Models\Concerns;

use App\Services\BusinessNumberService;

/**
 * PRD 00 §11 — business number di-generate backend, immutable, dan tidak
 * pernah dipakai ulang. Model pemakai wajib mendefinisikan businessCode().
 */
trait HasBusinessNumber
{
    public static function bootHasBusinessNumber(): void
    {
        static::creating(function ($model) {
            if (blank($model->business_number)) {
                $model->business_number = app(BusinessNumberService::class)->next(static::businessCode());
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('business_number')) {
                // Immutable: kembalikan ke nilai semula, jangan diam-diam menerima perubahan.
                $model->business_number = $model->getOriginal('business_number');
            }
        });
    }

    abstract public static function businessCode(): string;
}
