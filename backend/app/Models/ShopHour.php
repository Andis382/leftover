<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

/** Opening hours for one weekday. No times means the shop is closed that day. */
class ShopHour extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'weekday', 'opens_at', 'closes_at'];

    protected function casts(): array
    {
        return ['weekday' => 'integer'];
    }
}
