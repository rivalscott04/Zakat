<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasBusinessNumber;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** PRD 16R §37. */
#[Fillable(['name'])]
class NotificationBatch extends Model
{
    use BelongsToOrganization, HasBusinessNumber, HasUlids;

    public static function businessCode(): string
    {
        return 'NFB';
    }

    public function businessNumberColumn(): string
    {
        return 'batch_number';
    }

    protected function casts(): array
    {
        return [
            'total_recipient' => 'integer',
            'total_success' => 'integer',
            'total_failed' => 'integer',
        ];
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'batch_id');
    }
}
