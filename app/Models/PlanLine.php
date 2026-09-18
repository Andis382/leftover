<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'suggested_qty', 'previous_qty', 'delta', 'observations',
        'confidence', 'reason', 'avg_left', 'sold_out_days',
    ];

    protected function casts(): array
    {
        return ['avg_left' => 'float'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * "Five fewer croissants." Never "reduce croissant production by 12.4%",
     * which is a sentence nobody has ever acted on at four in the morning.
     */
    public function sentence(): string
    {
        $name = $this->product?->name ?? '';

        if ($this->suggested_qty === null) {
            return __('plan.line.unknown', ['product' => $name]);
        }

        if ($this->delta === 0) {
            return __('plan.line.same', ['count' => $this->suggested_qty, 'product' => $name]);
        }

        return trans_choice($this->delta > 0 ? 'plan.line.more' : 'plan.line.fewer', abs($this->delta), [
            'count' => abs($this->delta),
            'product' => $name,
            'total' => $this->suggested_qty,
        ]);
    }

    public function reasonSentence(): string
    {
        $replace = [
            'left' => $this->formattedAverageLeft(),
            'days' => $this->sold_out_days,
            'seen' => $this->observations,
        ];

        // A couple of the reasons count days out loud, so they have to go
        // through the plural resolver rather than a straight lookup.
        return $this->reason === 'sold_out_sometimes'
            ? trans_choice('plan.reason.sold_out_sometimes', max(1, (int) $this->sold_out_days), $replace)
            : __('plan.reason.'.$this->reason, $replace);
    }

    public function formattedAverageLeft(): string
    {
        return rtrim(rtrim(number_format($this->avg_left ?? 0, 1), '0'), '.,');
    }

    public function confidenceLabel(): string
    {
        return __('plan.confidence.'.$this->confidence);
    }

    public function tone(): string
    {
        return match (true) {
            $this->suggested_qty === null => 'quiet',
            $this->delta > 0 => 'up',
            $this->delta < 0 => 'down',
            default => 'level',
        };
    }
}
