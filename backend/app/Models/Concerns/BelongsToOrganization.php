<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PRD 00 §22 dan §23, PRD 02 §27 — data isolation multi-tenant.
 *
 * Query di-scope otomatis ke active organization context. Context hanya diisi
 * backend setelah membership diverifikasi, dan organization_id dari frontend
 * tidak pernah dipakai. Bila context kosong (akses platform-level) scope tidak
 * diterapkan; otorisasi platform dicek terpisah lewat permission.
 */
trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope);

        static::creating(function ($model) {
            // organization_id tidak boleh datang dari input client (PRD 22 §5).
            if (blank($model->organization_id) && ($id = OrganizationContext::id())) {
                $model->organization_id = $id;
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** Lepas isolasi organisasi. Hanya untuk operasi platform-level yang sudah diotorisasi. */
    public function scopeAcrossOrganizations(Builder $query): Builder
    {
        return $query->withoutGlobalScope(OrganizationScope::class);
    }
}
