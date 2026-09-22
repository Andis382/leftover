<?php

namespace App\Http\Controllers;

use App\Bakery\PlanSender;
use App\Bakery\PlanService;
use App\Http\Controllers\Concerns\ReadsShopDay;
use App\Models\Product;
use App\Support\Phones;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Bake plans (everyone reads them), plus the owner's changes, morning confirmation and sending. */
class PlanController extends Controller
{
    use ReadsShopDay;

    public function __construct(
        private readonly PlanService $plans,
        private readonly PlanSender $sender,
    ) {}

    public function show(Request $request, string $date): JsonResponse
    {
        $clock = $this->clock($request);

        return response()->json($this->plans->view($clock, $this->day($date, $clock), app()->getLocale()));
    }

    public function setWillBake(Request $request, string $date, int $productId): JsonResponse
    {
        $clock = $this->clock($request);
        $date = $this->day($date, $clock);
        $data = $request->validate(['willBake' => ['present', 'nullable', 'integer', 'min:0', 'max:10000']]);
        $this->notInPast($date, $clock, 'willBake');
        $this->plans->setWillBake($clock, $date, Product::findOrFail($productId), $data['willBake'], $request->user());

        return response()->json($this->plans->view($clock, $date, app()->getLocale()));
    }

    public function confirmBaked(Request $request, string $date): JsonResponse
    {
        $clock = $this->clock($request);
        $date = $this->day($date, $clock);
        $data = $request->validate([
            'items' => ['required', 'array', 'max:500'],
            'items.*.productId' => ['required', 'integer', 'distinct'],
            'items.*.baked' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);
        $this->notInFuture($date, $clock);
        $ids = array_column($data['items'], 'productId');
        if (Product::whereIn('id', $ids)->count() !== count($ids)) {
            throw ValidationException::withMessages(['items' => [__('errors.not_found')]]);
        }
        $this->plans->confirmBaked($clock, $date, array_column($data['items'], 'baked', 'productId'), $request->user());

        return response()->json($this->plans->view($clock, $date, app()->getLocale()));
    }

    public function message(Request $request, string $date): JsonResponse
    {
        $clock = $this->clock($request);

        return response()->json($this->sender->preview($clock, $this->day($date, $clock), $request->query('phone')));
    }

    public function send(Request $request, string $date): JsonResponse
    {
        $clock = $this->clock($request);
        $date = $this->day($date, $clock);
        $data = $request->validate(['phone' => ['nullable', 'string', 'max:40']]);
        $phone = Phones::normalize($data['phone'] ?? null);
        if (($data['phone'] ?? null) !== null && ! Phones::isPlausible($phone)) {
            throw ValidationException::withMessages(['phone' => [__('errors.phone_invalid')]]);
        }
        $plan = $this->plans->generate($clock, $date, $request->user());
        $message = $this->sender->send($clock, $plan, $phone);
        if ($message === null) {
            throw ValidationException::withMessages(['phone' => [__('errors.no_plan_phone')]]);
        }

        return response()->json([
            'plan' => $this->plans->view($clock, $date, app()->getLocale()),
            'message' => $message->toApi(),
        ]);
    }
}
