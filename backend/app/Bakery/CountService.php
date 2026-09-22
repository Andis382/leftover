<?php

namespace App\Bakery;

use App\Models\DailyRecord;
use App\Models\DayClosing;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The closing count: what is left of each product at the end of the day, saved one product at
 * a time so an interrupted count loses nothing, then finished (or the day skipped).
 */
final class CountService
{
    public function sheet(ShopClock $clock, string $date): array
    {
        $weekday = ShopClock::weekdayOf($date);
        $records = DailyRecord::with('counter:id,name')->where('date', $date)->get()->keyBy('product_id');
        $products = Product::query()
            ->where(fn ($q) => $q->whereNull('archived_at')->orWhereIn('id', $records->keys()))
            ->shelf()
            ->get()
            ->filter(function (Product $product) use ($records, $weekday) {
                $baked = $records->get($product->id)?->effectiveBaked();

                return $baked !== null ? $baked > 0 : $product->isActive() && $product->bakedOn($weekday);
            });
        $closing = DayClosing::with('closer:id,name')->where('date', $date)->first();
        $items = $products->map(fn (Product $p) => $this->item($p, $records->get($p->id)))->values()->all();

        return [
            'date' => $date,
            'weekday' => $weekday,
            'isToday' => $date === $clock->today(),
            'shopOpen' => $clock->isOpenOn($weekday),
            'opensAt' => ShopClock::clock($clock->opensMinute($weekday)),
            'closesAt' => ShopClock::clock($clock->closesMinute($weekday)),
            'status' => $closing->status ?? DayClosing::OPEN,
            'closedAt' => $closing?->closed_at?->toIso8601String(),
            'closedBy' => $closing?->closer?->name,
            'skipReason' => $closing?->skip_reason,
            'items' => $items,
            'summary' => self::summarize($items),
        ];
    }

    /** Saves one product's count. Selling out means nothing is left. */
    public function record(string $date, Product $product, ?int $left, ?string $soldOutAt, User $user): array
    {
        return DB::transaction(function () use ($date, $product, $left, $soldOutAt, $user) {
            $closing = DayClosing::createOrFirst(['date' => $date], ['status' => DayClosing::OPEN]);
            abort_if($closing->status === DayClosing::SKIPPED, 409, __('errors.day_skipped'));

            $record = DailyRecord::firstOrNew(['product_id' => $product->id, 'date' => $date]);
            if ($soldOutAt !== null) {
                $left = 0;
            }
            $baked = $record->effectiveBaked();
            if ($left !== null && $baked !== null && $left > $baked) {
                throw ValidationException::withMessages(['left' => [__('errors.more_than_baked', ['baked' => $baked])]]);
            }
            $record->fill([
                'left_qty' => $left,
                'sold_out_at' => $left === 0 ? $soldOutAt : null,
                'counted_by' => $left === null ? null : $user->id,
                'counted_at' => $left === null ? null : now(),
            ])->save();
            $record->setRelation('counter', $left === null ? null : $user);

            return $this->item($product, $record);
        });
    }

    public function finish(string $date, User $user): DayClosing
    {
        $counted = DailyRecord::where('date', $date)->whereNotNull('left_qty')->exists();
        if (! $counted) {
            throw ValidationException::withMessages(['count' => [__('errors.nothing_counted')]]);
        }

        return $this->close($date, DayClosing::COUNTED, null, $user);
    }

    public function skip(string $date, string $reason, User $user): DayClosing
    {
        return $this->close($date, DayClosing::SKIPPED, $reason, $user);
    }

    public function reopen(string $date): DayClosing
    {
        $closing = DayClosing::createOrFirst(['date' => $date], ['status' => DayClosing::OPEN]);
        $closing->fill(['status' => DayClosing::OPEN, 'skip_reason' => null, 'closed_by' => null, 'closed_at' => null])->save();

        return $closing;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array{counted: int, total: int, bakedUnits: int, soldUnits: int, leftUnits: int, wasteCents: int, soldOut: int}
     */
    public static function summarize(array $items): array
    {
        $summary = ['counted' => 0, 'total' => count($items), 'bakedUnits' => 0, 'soldUnits' => 0, 'leftUnits' => 0, 'wasteCents' => 0, 'soldOut' => 0];
        foreach ($items as $item) {
            if ($item['left'] === null) {
                continue;
            }
            $summary['counted']++;
            $summary['leftUnits'] += $item['left'];
            $summary['wasteCents'] += $item['left'] * $item['wasteValueCents'];
            $summary['soldOut'] += $item['soldOutAt'] !== null ? 1 : 0;
            if ($item['baked'] !== null) {
                $summary['bakedUnits'] += $item['baked'];
                $summary['soldUnits'] += max(0, $item['baked'] - $item['left']);
            }
        }

        return $summary;
    }

    private function close(string $date, string $status, ?string $reason, User $user): DayClosing
    {
        $closing = DayClosing::createOrFirst(['date' => $date], ['status' => DayClosing::OPEN]);
        $closing->fill(['status' => $status, 'skip_reason' => $reason, 'closed_by' => $user->id, 'closed_at' => now()])->save();

        return $closing->setRelation('closer', $user);
    }

    private function item(Product $product, ?DailyRecord $record): array
    {
        return [
            'productId' => $product->id,
            'name' => $product->name,
            'category' => $product->category,
            'baked' => $record?->effectiveBaked(),
            'left' => $record?->left_qty,
            'soldOutAt' => $record?->soldOutTime(),
            'countedAt' => $record?->counted_at?->toIso8601String(),
            'countedBy' => $record?->counter?->name,
            'unitPriceCents' => $product->unit_price_cents,
            'wasteValueCents' => $product->wasteValueCents(),
        ];
    }
}
