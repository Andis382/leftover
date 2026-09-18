<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    public const UNITS = ['piece', 'kg', 'tray'];

    public const CATEGORIES = ['bread', 'pastry', 'savoury', 'cake', 'other'];

    protected $fillable = [
        'name', 'unit', 'category', 'price', 'cost', 'typical_batch',
        'round_to', 'active', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost' => 'decimal:2',
            'active' => 'bool',
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

    /**
     * What one unsold unit costs.
     *
     * The price is what the bin swallowed in takings; the cost is what it
     * swallowed in flour, gas and hours. If only one is known, use it. Waste is
     * abstract until it is money, and it stays abstract as long as it is
     * measured in kilograms of flour that left through the back door.
     */
    public function wasteValue(): float
    {
        return (float) ($this->cost ?? $this->price ?? 0);
    }

    public function unitLabel(): string
    {
        return __('product.unit.'.$this->unit);
    }

    public function categoryLabel(): string
    {
        return $this->category ? __('product.category.'.$this->category) : '';
    }

    public function icon(): string
    {
        return match ($this->category) {
            'bread' => 'bread',
            'pastry' => 'cookie',
            'savoury' => 'bag',
            'cake' => 'cake',
            default => 'basket',
        };
    }
}
