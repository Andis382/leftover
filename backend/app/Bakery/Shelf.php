<?php

namespace App\Bakery;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

/** The order products stand in on the shelf, which is also the order they are counted in. */
final class Shelf
{
    /** Swaps a product with its neighbour; $step is -1 (up) or 1 (down). */
    public function move(Product $product, int $step): void
    {
        DB::transaction(function () use ($product, $step) {
            $products = Product::active()->shelf()->lockForUpdate()->get()->all();
            $index = array_search($product->id, array_map(fn (Product $p) => $p->id, $products), true);
            $target = $index === false ? -1 : $index + $step;
            if ($target < 0 || $target >= count($products)) {
                return;
            }
            [$products[$index], $products[$target]] = [$products[$target], $products[$index]];
            $this->renumber($products);
        });
    }

    public function archive(Product $product): void
    {
        $product->forceFill(['archived_at' => now()])->save();
    }

    /** A restored product goes back at the end of the shelf. */
    public function restore(Product $product): void
    {
        $product->forceFill(['archived_at' => null, 'shelf_order' => $this->nextPosition()])->save();
    }

    public function nextPosition(): int
    {
        return (int) Product::active()->max('shelf_order') + 1;
    }

    /** @param  list<Product>  $products */
    private function renumber(array $products): void
    {
        foreach ($products as $position => $product) {
            if ($product->shelf_order !== $position + 1) {
                $product->forceFill(['shelf_order' => $position + 1])->save();
            }
        }
    }
}
