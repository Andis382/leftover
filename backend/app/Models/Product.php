<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Something the bakery bakes and sells: a loaf, a tray of byrek, a piece of bakllava. */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use BelongsToOrganization, HasFactory;

    public const CATEGORIES = ['BREAD', 'PASTRY', 'SAVORY', 'SWEET', 'OTHER'];

    protected $fillable = [
        'name', 'category', 'unit_price_cents', 'unit_cost_cents', 'tray_size', 'baselines', 'active_weekdays', 'shelf_order', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'unit_price_cents' => 'integer',
            'unit_cost_cents' => 'integer',
            'tray_size' => 'integer',
            'baselines' => 'array',
            'active_weekdays' => 'array',
            'shelf_order' => 'integer',
            'archived_at' => 'datetime',
        ];
    }

    /** @param  Builder<Product>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /** @param  Builder<Product>  $query */
    public function scopeShelf(Builder $query): void
    {
        $query->orderBy('shelf_order')->orderBy('id');
    }

    public function isActive(): bool
    {
        return $this->archived_at === null;
    }

    /** The baker's usual quantity for an ISO weekday. */
    public function baselineFor(int $weekday): int
    {
        return (int) ($this->baselines[$weekday - 1] ?? 0);
    }

    public function bakedOn(int $weekday): bool
    {
        return in_array($weekday, array_map('intval', $this->active_weekdays ?? []), true);
    }

    /** What a piece left over costs the bakery: the cost when known, else the shelf price. */
    public function wasteValueCents(): int
    {
        return $this->unit_cost_cents ?? $this->unit_price_cents;
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'unitPriceCents' => $this->unit_price_cents,
            'unitCostCents' => $this->unit_cost_cents,
            'traySize' => $this->tray_size,
            'baselines' => array_map('intval', $this->baselines ?? []),
            'activeWeekdays' => array_values(array_map('intval', $this->active_weekdays ?? [])),
            'shelfOrder' => $this->shelf_order,
            'active' => $this->isActive(),
            'archivedAt' => $this->archived_at?->toIso8601String(),
        ];
    }
}
