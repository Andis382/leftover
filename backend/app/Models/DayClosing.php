<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The end of one business day: counted, explicitly skipped, or still open. */
class DayClosing extends Model
{
    use BelongsToOrganization;

    public const OPEN = 'OPEN';

    public const COUNTED = 'COUNTED';

    public const SKIPPED = 'SKIPPED';

    protected $fillable = ['organization_id', 'date', 'status', 'skip_reason', 'closed_by', 'closed_at', 'reminded_at'];

    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
            'closed_at' => 'datetime',
            'reminded_at' => 'datetime',
        ];
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function isDone(): bool
    {
        return $this->status === self::COUNTED || $this->status === self::SKIPPED;
    }
}
