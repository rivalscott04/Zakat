<?php

namespace App\Models\Concerns;

use App\Services\BusinessNumberService;

/**
 * PRD 00 §11 — business number di-generate backend, immutable, dan tidak
 * pernah dipakai ulang. Model pemakai wajib mendefinisikan businessCode().
 *
 * Sengaja tidak memakai model event: saveQuietly() akan melewatinya dan
 * meninggalkan baris tanpa business number.
 */
trait HasBusinessNumber
{
    abstract public static function businessCode(): string;

    public function save(array $options = [])
    {
        if (! $this->exists && blank($this->business_number)) {
            $this->business_number = app(BusinessNumberService::class)->next(static::businessCode());
        }

        if ($this->exists && $this->isDirty('business_number')) {
            // Immutable: kembalikan ke nilai semula, jangan diam-diam menerima perubahan.
            $this->business_number = $this->getOriginal('business_number');
        }

        return parent::save($options);
    }
}
