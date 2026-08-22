<?php

namespace App\Models;

use App\Enums\EntityStatus;
use App\Enums\ReportCategory;
use App\Enums\ReportVisibility;
use App\Models\Concerns\HasBusinessNumber;
use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PRD 19C §5.
 *
 * organization_id NULL berarti laporan bawaan sistem yang tersedia untuk semua
 * organisasi, sama seperti role sistem pada PRD 01 §23. Karena itu isolasinya
 * tidak memakai OrganizationScope biasa, melainkan scope visible() di bawah.
 */
#[Fillable(['report_code', 'name', 'description', 'category', 'report_type', 'data_source', 'visibility'])]
class Report extends Model
{
    use HasBusinessNumber, HasUlids;

    public static function businessCode(): string
    {
        return 'RPT';
    }

    public function businessNumberColumn(): string
    {
        return 'report_number';
    }

    protected function casts(): array
    {
        return [
            'category' => ReportCategory::class,
            'visibility' => ReportVisibility::class,
            'status' => EntityStatus::class,
            'is_system' => 'boolean',
        ];
    }

    /** Laporan bawaan sistem ditambah laporan milik organisasi aktif. */
    public function scopeVisible(Builder $query): Builder
    {
        $organizationId = OrganizationContext::id();

        return $query->where(fn (Builder $inner) => $inner
            ->whereNull('organization_id')
            ->when($organizationId, fn (Builder $scoped) => $scoped->orWhere('organization_id', $organizationId)));
    }

    public function parameters(): HasMany
    {
        return $this->hasMany(ReportParameter::class)->orderBy('sort_order');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(ReportRun::class);
    }
}
