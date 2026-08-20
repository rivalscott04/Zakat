<?php

namespace App\Models\Scopes;

use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/** PRD 00 §23 — data isolation di-enforce backend, bukan oleh query pemanggil. */
class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($id = OrganizationContext::id()) {
            $builder->where($model->getTable().'.organization_id', $id);
        }
    }
}
