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

    /** Model dengan kolom bernama lain, misalnya `payment_number`, menimpa ini. */
    public function businessNumberColumn(): string
    {
        return 'business_number';
    }

    public function save(array $options = [])
    {
        $column = $this->businessNumberColumn();

        if (! $this->exists && blank($this->{$column})) {
            $this->{$column} = app(BusinessNumberService::class)->next(static::businessCode());
        }

        if ($this->exists && $this->isDirty($column)) {
            // Immutable: kembalikan ke nilai semula, jangan diam-diam menerima perubahan.
            $this->{$column} = $this->getOriginal($column);
        }

        return parent::save($options);
    }
}
