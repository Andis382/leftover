<?php

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    protected $fillable = ['name', 'phone', 'country', 'locale', 'timezone', 'currency', 'plan_time', 'count_reminder_offset', 'plan_phone'];

    protected function casts(): array
    {
        return ['count_reminder_offset' => 'integer'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hours(): HasMany
    {
        return $this->hasMany(ShopHour::class);
    }

    /** "04:00" */
    public function planTime(): string
    {
        return substr((string) $this->plan_time, 0, 5);
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'country' => $this->country,
            'locale' => $this->locale,
            'timezone' => $this->timezone,
            'currency' => $this->currency,
        ];
    }
}
