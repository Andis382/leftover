<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * A bakery. One shop, one owner, one account — which is the whole market this
 * is aimed at, and the reason it is not priced per seat.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'shop_name', 'email', 'password', 'city', 'phone', 'locale',
        'currency', 'timezone', 'opens_at', 'closes_at', 'plan_at',
        'notify_driver', 'telegram_chat_id', 'onboarded_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'onboarded_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class)->orderBy('sort')->orderBy('name');
    }

    public function activeProducts(): HasMany
    {
        return $this->products()->where('active', true);
    }

    public function daySheets(): HasMany
    {
        return $this->hasMany(DaySheet::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
    }

    public function displayName(): string
    {
        return $this->shop_name ?: $this->name;
    }

    public function today(): CarbonInterface
    {
        return now($this->timezone ?: config('app.timezone'))->startOfDay();
    }

    /** "06:00", not "06:00:00", which is what the arithmetic wants. */
    public function opensAt(): string
    {
        return substr((string) $this->opens_at, 0, 5);
    }

    public function closesAt(): string
    {
        return substr((string) $this->closes_at, 0, 5);
    }

    public function money(float $amount): string
    {
        $symbol = config('leftover.currency_symbols.'.$this->currency, $this->currency);

        return $symbol.' '.number_format($amount, 0, '.', ' ');
    }
}
