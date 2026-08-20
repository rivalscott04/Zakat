<?php

namespace App\Models;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasBusinessNumber;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/** PRD 02 §5 — organization sebagai tenant utama. */
#[Fillable([
    'code', 'name', 'legal_name', 'organization_type', 'email', 'phone',
    'website', 'currency', 'timezone', 'locale',
])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use Auditable, HasBusinessNumber, HasFactory, HasUlids, SoftDeletes;

    public static function businessCode(): string
    {
        return 'ORG';
    }

    protected function casts(): array
    {
        return [
            'status' => OrganizationStatus::class,
            'organization_type' => OrganizationType::class,
        ];
    }

    // ------------------------------------------------------------- relations

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(OrganizationMember::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(OrganizationAddress::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(OrganizationContact::class);
    }

    public function amils(): HasMany
    {
        return $this->hasMany(Amil::class);
    }

    // ------------------------------------------------------------- hierarchy

    /**
     * ULID seluruh ancestor, dari parent langsung ke atas.
     *
     * @return array<int, string>
     */
    public function ancestorIds(): array
    {
        $ids = [];
        $current = $this->parent_id;

        // Dibatasi jumlah organisasi supaya data yang sudah terlanjur circular
        // tidak membuat loop tak berujung.
        $guard = 0;

        while ($current !== null && $guard++ < 64) {
            if (in_array($current, $ids, true)) {
                break;
            }

            $ids[] = $current;
            $current = static::withTrashed()->where('id', $current)->value('parent_id');
        }

        return $ids;
    }

    /**
     * ULID organisasi ini beserta seluruh turunannya.
     *
     * @return array<int, string>
     */
    public function selfAndDescendantIds(): array
    {
        $rows = DB::select(
            'WITH RECURSIVE tree AS (
                 SELECT id FROM organizations WHERE id = ? AND deleted_at IS NULL
                 UNION ALL
                 SELECT o.id FROM organizations o JOIN tree t ON o.parent_id = t.id WHERE o.deleted_at IS NULL
             )
             SELECT id FROM tree',
            [$this->getKey()]
        );

        return array_column($rows, 'id');
    }

    public function isOperational(): bool
    {
        return $this->status->isOperational();
    }
}
