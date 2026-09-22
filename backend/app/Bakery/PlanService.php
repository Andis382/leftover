<?php

namespace App\Bakery;

use App\Forecast\ForecastInput;
use App\Forecast\ForecastService;
use App\Forecast\Headline;
use App\Forecast\ReasonText;
use App\Forecast\Suggestion;
use App\Models\DailyRecord;
use App\Models\Plan;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Bake plans: a live preview for any day, fixed once generated (by the morning job, or the
 * first time someone changes or sends it). The baker's own numbers are kept next to the
 * suggestion, so the history shows both what the app said and what was baked.
 */
final class PlanService
{
    public function __construct(
        private readonly ForecastService $forecast,
        private readonly HistoryLoader $history,
    ) {}

    /**
     * @param  Collection<int, Product>  $products
     * @return array<int, Suggestion> by product id; products not baked on that weekday are absent
     */
    public function suggestions(ShopClock $clock, Collection $products, string $date): array
    {
        $weekday = ShopClock::weekdayOf($date);
        $histories = $this->history->load($clock, $products, $date);
        $suggestions = [];
        foreach ($products as $product) {
            $history = $histories[$product->id];
            $suggestion = $this->forecast->suggest(new ForecastInput(
                $date,
                $product->baselineFor($weekday),
                $product->tray_size,
                $product->bakedOn($weekday),
                $history['observations'],
                $history['lastBaked'],
            ));
            if ($suggestion !== null) {
                $suggestions[$product->id] = $suggestion;
            }
        }

        return $suggestions;
    }

    /** Makes the day's plan once; every later call returns the same plan untouched. */
    public function generate(ShopClock $clock, string $date, ?User $by = null): Plan
    {
        return DB::transaction(function () use ($clock, $date, $by) {
            $plan = Plan::createOrFirst(['date' => $date], ['generated_at' => now(), 'generated_by' => $by?->id]);
            if (! $plan->wasRecentlyCreated) {
                return $plan;
            }
            $now = now();
            $rows = [];
            foreach ($this->suggestions($clock, Product::active()->shelf()->get(), $date) as $productId => $suggestion) {
                $rows[] = [
                    'organization_id' => $clock->organization->id,
                    'product_id' => $productId,
                    'date' => $date,
                    'planned_qty' => $suggestion->quantity,
                    'forecast' => json_encode($suggestion->snapshot()),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if ($rows !== []) {
                DailyRecord::upsert($rows, ['organization_id', 'product_id', 'date'], ['planned_qty', 'forecast', 'updated_at']);
            }

            return $plan;
        });
    }

    /** The baker's "will bake" number. Null, or the suggestion itself, means "as suggested". */
    public function setWillBake(ShopClock $clock, string $date, Product $product, ?int $quantity, User $by): void
    {
        $this->generate($clock, $date, $by);
        $record = DailyRecord::where('date', $date)->where('product_id', $product->id)->whereNotNull('planned_qty')->first();
        abort_if($record === null, 404, __('errors.not_in_plan'));
        $record->baked_qty = $quantity === $record->planned_qty ? null : $quantity;
        $record->save();
    }

    /**
     * This morning's confirmation of what actually went into the oven.
     *
     * @param  array<int, int>  $baked  product id => pieces
     */
    public function confirmBaked(ShopClock $clock, string $date, array $baked, User $by): Plan
    {
        return DB::transaction(function () use ($clock, $date, $baked, $by) {
            $plan = $this->generate($clock, $date, $by);
            $records = DailyRecord::where('date', $date)->whereIn('product_id', array_keys($baked))->get()->keyBy('product_id');
            foreach ($baked as $productId => $quantity) {
                $record = $records->get($productId) ?? new DailyRecord(['product_id' => $productId, 'date' => $date]);
                $record->baked_qty = $record->planned_qty === $quantity ? null : $quantity;
                $record->save();
            }
            $plan->forceFill(['baked_confirmed_at' => now(), 'baked_confirmed_by' => $by->id])->save();

            return $plan;
        });
    }

    /** Everything the plan screen, the print sheet and the WhatsApp message show. */
    public function view(ShopClock $clock, string $date, string $locale): array
    {
        $weekday = ShopClock::weekdayOf($date);
        $plan = Plan::with(['generator:id,name', 'confirmer:id,name', 'message'])->where('date', $date)->first();
        $records = DailyRecord::where('date', $date)->get()->keyBy('product_id');
        $active = Product::active()->shelf()->get();

        if ($plan !== null) {
            $planned = $records->filter(fn (DailyRecord $r) => $r->planned_qty !== null);
            $products = Product::whereIn('id', $planned->keys())->shelf()->get();
            $suggestions = $planned->map(fn (DailyRecord $r) => Suggestion::fromSnapshot($r->planned_qty, $r->forecast ?? []))->all();
        } else {
            $products = $active;
            $suggestions = $this->suggestions($clock, $products, $date);
        }

        $rows = [];
        foreach ($products as $product) {
            $suggestion = $suggestions[$product->id] ?? null;
            if ($suggestion !== null) {
                $rows[] = $this->row($product, $suggestion, $records->get($product->id), $weekday, $locale);
            }
        }
        $today = $clock->today();

        return [
            'date' => $date,
            'weekday' => $weekday,
            'shopOpen' => $clock->isOpenOn($weekday),
            'isToday' => $date === $today,
            'isPast' => $date < $today,
            'generated' => $plan !== null,
            'generatedAt' => $plan?->generated_at?->toIso8601String(),
            'generatedBy' => $plan?->generator?->name,
            'finalAt' => $clock->planTimeOn($date)->toIso8601String(),
            'sentAt' => $plan?->sent_at?->toIso8601String(),
            'sentTo' => $plan?->message?->recipient,
            'bakedConfirmedAt' => $plan?->baked_confirmed_at?->toIso8601String(),
            'bakedConfirmedBy' => $plan?->confirmer?->name,
            'headline' => Headline::from(array_map(fn (array $r) => ['name' => $r['name'], 'change' => $r['change']], $rows))
                ->render($locale, ReasonText::weekday('last', $weekday, $locale)),
            'rows' => $rows,
            'totals' => $this->totals($rows),
            'notBaked' => $active->reject(fn (Product $p) => $p->bakedOn($weekday))
                ->map(fn (Product $p) => ['productId' => $p->id, 'name' => $p->name])->values()->all(),
        ];
    }

    private function row(Product $product, Suggestion $suggestion, ?DailyRecord $record, int $weekday, string $locale): array
    {
        $willBake = $record?->baked_qty ?? $suggestion->quantity;

        return [
            'productId' => $product->id,
            'name' => $product->name,
            'category' => $product->category,
            'traySize' => $product->tray_size,
            'unitPriceCents' => $product->unit_price_cents,
            'suggested' => $suggestion->quantity,
            'willBake' => $willBake,
            'overridden' => $willBake !== $suggestion->quantity,
            'lastBaked' => $suggestion->lastBaked,
            'change' => $suggestion->lastBaked === null ? null : $willBake - $suggestion->lastBaked,
            'reasonCode' => $suggestion->reason->code,
            'reason' => ReasonText::render($suggestion->reason, $weekday, $locale),
            'confidence' => $suggestion->confidence,
            'observations' => $suggestion->observations,
        ];
    }

    /** @param  list<array<string, mixed>>  $rows */
    private function totals(array $rows): array
    {
        $totals = ['units' => 0, 'trays' => 0, 'valueCents' => 0, 'more' => 0, 'fewer' => 0, 'same' => 0];
        foreach ($rows as $row) {
            $totals['units'] += $row['willBake'];
            $totals['trays'] += (int) ceil($row['willBake'] / max(1, $row['traySize']));
            $totals['valueCents'] += $row['willBake'] * $row['unitPriceCents'];
            $key = match (true) {
                $row['change'] > 0 => 'more',
                $row['change'] < 0 => 'fewer',
                default => 'same',
            };
            $totals[$key]++;
        }

        return $totals;
    }
}
