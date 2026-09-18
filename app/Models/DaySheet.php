<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One day in the shop.
 *
 * A sheet exists for a day even when nobody counted, because "we did not count
 * on Thursday" and "we sold everything on Thursday" have to be different facts.
 * The forecaster ignores anything that is not `counted`, which is how the
 * product survives a tired week: a missed day is a smaller sample, never a zero.
 */
class DaySheet extends Model
{
    use HasFactory;

    public const OPEN = 'open';        // today, nothing recorded yet
    public const COUNTED = 'counted';  // somebody counted at closing
    public const SKIPPED = 'skipped';  // open for business, nobody counted
    public const SHUT = 'shut';        // the shop was closed

    protected $fillable = ['on_date', 'weekday', 'status', 'note', 'counted_at'];

    protected function casts(): array
    {
        return [
            'on_date' => 'date',
            'counted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function counts(): HasMany
    {
        return $this->hasMany(Count::class);
    }

    public function isCounted(): bool
    {
        return $this->status === self::COUNTED;
    }

    public function statusLabel(): string
    {
        return __('sheet.status.'.$this->status);
    }

    /** Everything left on the shelves, in money. */
    public function wasteValue(): float
    {
        return $this->counts->sum(fn (Count $count) => $count->wasteValue());
    }

    public function leftTotal(): int
    {
        return (int) $this->counts->sum('left_qty');
    }

    public function soldOutCount(): int
    {
        return $this->counts->whereNotNull('sold_out_at')->count();
    }
}
