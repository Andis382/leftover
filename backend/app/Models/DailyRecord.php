<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One product on one day: what the plan suggested, what went into the oven and what was
 * left at closing. A null left_qty means nobody counted it, which is not the same as zero.
 */
class DailyRecord extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'product_id', 'date', 'planned_qty', 'forecast', 'baked_qty', 'left_qty', 'sold_out_at', 'counted_by', 'counted_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
            'planned_qty' => 'integer',
            'baked_qty' => 'integer',
            'left_qty' => 'integer',
            'forecast' => 'array',
            'counted_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    /** Baked as confirmed or overridden, otherwise as planned. */
    public function effectiveBaked(): ?int
    {
        return $this->baked_qty ?? $this->planned_qty;
    }

    public function isCounted(): bool
    {
        return $this->left_qty !== null;
    }

    public function soldOutTime(): ?string
    {
        return $this->sold_out_at === null ? null : substr($this->sold_out_at, 0, 5);
    }
}
