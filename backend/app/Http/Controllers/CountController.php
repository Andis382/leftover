<?php

namespace App\Http\Controllers;

use App\Bakery\CountService;
use App\Http\Controllers\Concerns\ReadsShopDay;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The closing count. Every change is saved on its own (PATCH per product). */
class CountController extends Controller
{
    use ReadsShopDay;

    public function __construct(private readonly CountService $counts) {}

    public function show(Request $request, ?string $date = null): JsonResponse
    {
        $clock = $this->clock($request);

        return response()->json($this->counts->sheet($clock, $this->day($date, $clock)));
    }

    public function update(Request $request, string $date, int $productId): JsonResponse
    {
        $clock = $this->clock($request);
        $date = $this->day($date, $clock);
        $data = $request->validate([
            'left' => ['present', 'nullable', 'integer', 'min:0', 'max:10000'],
            'soldOutAt' => ['nullable', 'date_format:H:i'],
        ]);
        $this->notInFuture($date, $clock);
        $product = Product::findOrFail($productId);

        return response()->json($this->counts->record($date, $product, $data['left'], $data['soldOutAt'] ?? null, $request->user()));
    }

    public function finish(Request $request, string $date): JsonResponse
    {
        $clock = $this->clock($request);
        $date = $this->day($date, $clock);
        $this->notInFuture($date, $clock);
        $this->counts->finish($date, $request->user());

        return response()->json($this->counts->sheet($clock, $date));
    }

    public function skip(Request $request, string $date): JsonResponse
    {
        $clock = $this->clock($request);
        $date = $this->day($date, $clock);
        $data = $request->validate(['reason' => ['required', 'string', 'max:200']]);
        $this->notInFuture($date, $clock);
        $this->counts->skip($date, trim($data['reason']), $request->user());

        return response()->json($this->counts->sheet($clock, $date));
    }

    public function reopen(Request $request, string $date): JsonResponse
    {
        $clock = $this->clock($request);
        $date = $this->day($date, $clock);
        $this->counts->reopen($date);

        return response()->json($this->counts->sheet($clock, $date));
    }
}
