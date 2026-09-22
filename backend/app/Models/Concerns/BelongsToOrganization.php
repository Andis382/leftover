<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Support\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tenant isolation: every query on a model using this trait is limited to the current
 * organisation, and new rows are stamped with it. Public token pages opt out explicitly
 * with withoutGlobalScope('organization').
 */
trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope('organization', function (Builder $query) {
            $id = Tenant::id();
            if ($id !== null) {
                $query->where($query->getModel()->getTable().'.organization_id', $id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->organization_id) && ($id = Tenant::id()) !== null) {
                $model->organization_id = $id;
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
