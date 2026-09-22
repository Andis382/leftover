<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->randomElement(['Bukë e bardhë', 'Simite me susam', 'Kroasan i thjeshtë', 'Byrek me spinaq', 'Petulla']),
            'category' => 'BREAD',
            'unit_price_cents' => 80,
            'unit_cost_cents' => 30,
            'tray_size' => 1,
            'baselines' => [20, 20, 20, 20, 20, 30, 12],
            'active_weekdays' => [1, 2, 3, 4, 5, 6, 7],
            'shelf_order' => fake()->numberBetween(1, 50),
        ];
    }
}
