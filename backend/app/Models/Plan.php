<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A day's bake plan, fixed once generated. The numbers live on the day's DailyRecords. */
class Plan extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'date', 'generated_at', 'generated_by', 'outbound_message_id', 'sent_at', 'baked_confirmed_at', 'baked_confirmed_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
            'generated_at' => 'datetime',
            'sent_at' => 'datetime',
            'baked_confirmed_at' => 'datetime',
        ];
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'baked_confirmed_by');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(OutboundMessage::class, 'outbound_message_id');
    }
}
