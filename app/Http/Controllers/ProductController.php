<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesToShop;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    use ScopesToShop;

    public function index(Request $request)
    {
        return view('products.index', [
            'user' => $request->user(),
            'products' => $request->user()->products()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->rules($request);
        $data['sort'] = (int) ($request->user()->products()->max('sort') ?? 0) + 10;

        $request->user()->products()->create($data);

        return redirect()->route('products.index')->with('status', __('flash.product_added', ['name' => $data['name']]));
    }

    public function edit(Request $request, Product $product)
    {
        $this->mine($product);

        return view('products.edit', ['user' => $request->user(), 'product' => $product]);
    }

    public function update(Request $request, Product $product)
    {
        $this->mine($product);
        $product->update($this->rules($request));

        return redirect()->route('products.index')->with('status', __('flash.saved'));
    }

    /**
     * Retiring a product, not erasing it.
     *
     * A product that has ever been counted is only ever deactivated: deleting
     * it would take a year of Tuesdays with it and quietly change every waste
     * figure the shop has already seen. Something never counted is a typo and
     * can go.
     */
    public function destroy(Request $request, Product $product)
    {
        $this->mine($product);

        if ($product->counts()->exists()) {
            $product->update(['active' => false]);

            return redirect()->route('products.index')->with('status', __('flash.product_retired', ['name' => $product->name]));
        }

        $product->delete();

        return redirect()->route('products.index')->with('status', __('flash.product_removed', ['name' => $product->name]));
    }

    private function rules(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'unit' => ['required', Rule::in(Product::UNITS)],
            'category' => ['nullable', Rule::in(Product::CATEGORIES)],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'typical_batch' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'round_to' => ['required', 'integer', 'min:1', 'max:100'],
            'active' => ['nullable', 'boolean'],
        ]) + ['active' => (bool) $request->boolean('active', true)];
    }
}
