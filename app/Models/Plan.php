<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tomorrow's numbers, written down the night before.
 *
 * The body is stored verbatim rather than regenerated on demand, so that what
 * the baker read at four in the morning stays readable afterwards even if the
 * data behind it changes. A plan is a thing that was said, not a view.
 */
class Plan extends Model
{
    use HasFactory;

    public const DRAFT = 'draft';
    public const SENT = 'sent';

    protected $fillable = ['for_date', 'weekday', 'status', 'body', 'sent_at'];

    protected function casts(): array
    {
        return [
            'for_date' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PlanLine::class)->orderBy('id');
    }

    /** The lines actually worth reading first: the ones that changed. */
    public function changes(): \Illuminate\Support\Collection
    {
        return $this->lines->filter(fn (PlanLine $line) => $line->delta !== 0 && $line->suggested_qty !== null);
    }

    public function unchanged(): \Illuminate\Support\Collection
    {
        return $this->lines->filter(fn (PlanLine $line) => $line->delta === 0 && $line->suggested_qty !== null);
    }

    public function silent(): \Illuminate\Support\Collection
    {
        return $this->lines->filter(fn (PlanLine $line) => $line->suggested_qty === null);
    }
}
