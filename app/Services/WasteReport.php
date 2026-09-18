<?php

namespace App\Services;

use App\Models\DaySheet;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * What the bin took, in money and in things.
 *
 * Waste leaves a bakery through the back door, at night, in a bag, where the
 * owner does not see it. Eight per cent of production is treated as weather.
 * The only job here is to put a number and a currency symbol on it, because
 * "thirty-one croissants, forty-six euro" is an argument and "some waste" is not.
 *
 * The same report counts sell-outs, which are the opposite mistake and cost
 * exactly as much — they just never appear in any total anywhere.
 */
class WasteReport
{
    /** @return array{days:int, baked:int, left:int, sold_out:int, value:float, rate:float} */
    public function between(User $user, CarbonInterface $from, CarbonInterface $to): array
    {
        $sheets = $this->sheets($user, $from, $to);

        $baked = 0;
        $left = 0;
        $soldOut = 0;
        $value = 0.0;

        foreach ($sheets as $sheet) {
            foreach ($sheet->counts as $count) {
                $baked += (int) $count->baked_qty;
                $left += (int) $count->left_qty;
                $soldOut += $count->soldOut() ? 1 : 0;
                $value += $count->wasteValue();
            }
        }

        return [
            'days' => $sheets->count(),
            'baked' => $baked,
            'left' => $left,
            'sold_out' => $soldOut,
            'value' => round($value, 2),
            'rate' => $baked > 0 ? round($left / $baked * 100, 1) : 0.0,
        ];
    }

    /** One row per counted day, newest first, for the history page. */
    public function daily(User $user, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return $this->sheets($user, $from, $to)
            ->map(fn (DaySheet $sheet) => [
                'sheet' => $sheet,
                'baked' => (int) $sheet->counts->sum('baked_qty'),
                'left' => $sheet->leftTotal(),
                'sold_out' => $sheet->soldOutCount(),
                'value' => round($sheet->wasteValue(), 2),
            ])
            ->sortByDesc(fn (array $row) => $row['sheet']->on_date->timestamp)
            ->values();
    }

    /** The products the bin sees most, worst first. */
    public function worstProducts(User $user, CarbonInterface $from, CarbonInterface $to, int $limit = 6): Collection
    {
        $totals = [];

        foreach ($this->sheets($user, $from, $to) as $sheet) {
            foreach ($sheet->counts as $count) {
                $id = $count->product_id;
                $totals[$id] ??= ['product' => $count->product, 'left' => 0, 'baked' => 0, 'value' => 0.0, 'sold_out' => 0];
                $totals[$id]['left'] += (int) $count->left_qty;
                $totals[$id]['baked'] += (int) $count->baked_qty;
                $totals[$id]['value'] += $count->wasteValue();
                $totals[$id]['sold_out'] += $count->soldOut() ? 1 : 0;
            }
        }

        return collect($totals)
            ->map(function (array $row) {
                $row['value'] = round($row['value'], 2);
                $row['rate'] = $row['baked'] > 0 ? round($row['left'] / $row['baked'] * 100, 1) : 0.0;

                return $row;
            })
            ->sortByDesc('value')
            ->take($limit)
            ->values();
    }

    private function sheets(User $user, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return $user->daySheets()
            ->with(['counts.product'])
            ->where('status', DaySheet::COUNTED)
            ->whereBetween('on_date', [$from->toDateString(), $to->toDateString()])
            ->get();
    }
}
