<?php

namespace App\Http\Controllers;

use App\Bakery\Shelf;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct(private readonly Shelf $shelf) {}

    public function index(): JsonResponse
    {
        return response()->json(Product::shelf()->get()->map->toApi());
    }

    public function store(Request $request): JsonResponse
    {
        $product = Product::create($this->attributes($request) + ['shelf_order' => $this->shelf->nextPosition()]);

        return response()->json($product->toApi(), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->update($this->attributes($request));

        return response()->json($product->toApi());
    }

    public function move(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]]);
        $this->shelf->move(Product::findOrFail($id), $data['direction'] === 'up' ? -1 : 1);

        return $this->index();
    }

    public function archive(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $this->shelf->archive($product);

        return response()->json($product->toApi());
    }

    public function restore(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $this->shelf->restore($product);

        return response()->json($product->toApi());
    }

    private function attributes(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', Rule::in(Product::CATEGORIES)],
            'unitPriceCents' => ['required', 'integer', 'min:0', 'max:100000'],
            'unitCostCents' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'traySize' => ['required', 'integer', 'min:1', 'max:500'],
            'baselines' => ['required', 'array', 'size:7'],
            'baselines.*' => ['required', 'integer', 'min:0', 'max:5000'],
            'activeWeekdays' => ['present', 'array'],
            'activeWeekdays.*' => ['integer', 'between:1,7', 'distinct'],
        ]);
        $weekdays = array_map('intval', $data['activeWeekdays']);
        sort($weekdays);

        return [
            'name' => trim($data['name']),
            'category' => $data['category'],
            'unit_price_cents' => $data['unitPriceCents'],
            'unit_cost_cents' => $data['unitCostCents'] ?? null,
            'tray_size' => $data['traySize'],
            'baselines' => array_map('intval', array_values($data['baselines'])),
            'active_weekdays' => $weekdays,
        ];
    }
}
