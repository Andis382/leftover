<?php

namespace App\Models;

use App\Support\Observation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Count extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'baked_qty', 'left_qty', 'sold_out_at'];

    public function daySheet(): BelongsTo
    {
        return $this->belongsTo(DaySheet::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sold(): int
    {
        return max(0, $this->baked_qty - $this->left_qty);
    }

    public function soldOut(): bool
    {
        return $this->sold_out_at !== null;
    }

    public function wasteValue(): float
    {
        return $this->left_qty * ($this->product?->wasteValue() ?? 0);
    }

    /** The shape the forecaster reads. Nothing but four facts. */
    public function toObservation(): Observation
    {
        return new Observation(
            date: $this->daySheet->on_date,
            baked: (int) $this->baked_qty,
            left: (int) $this->left_qty,
            soldOutAt: $this->sold_out_at ? substr((string) $this->sold_out_at, 0, 5) : null,
        );
    }
}
