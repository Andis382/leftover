<?php

namespace Database\Seeders;

use App\Models\DaySheet;
use App\Models\Product;
use App\Models\User;
use App\Services\PlanBuilder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * A bakery with ten weeks behind it.
 *
 * The history is generated from a per-product weekday shape plus noise, so the
 * demo shows what the product is actually for: a croissant line that is
 * consistently over-baked on Tuesdays, a sesame roll that sells out most
 * Saturday mornings, a burek that is fine, and one item added recently with too
 * little history to say anything about. A seed of round numbers would hide
 * every one of those.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $shop = User::updateOrCreate(
            ['email' => 'demo@leftover.test'],
            [
                'name' => 'Petrit Hoxha',
                'shop_name' => 'Furra Petrela',
                'city' => 'Tiranë',
                'phone' => '+355691112233',
                'password' => 'password',
                'locale' => 'sq',
                'currency' => 'EUR',
                'timezone' => 'Europe/Tirane',
                'opens_at' => '06:00',
                'closes_at' => '20:00',
                'plan_at' => '04:00',
                'notify_driver' => 'none',
                'onboarded_at' => now(),
            ]
        );

        $shop->products()->delete();
        $shop->daySheets()->delete();
        $shop->plans()->delete();

        // name, category, unit, price, cost, batch, round, shape per weekday
        // (Mon…Sun as a multiplier), waste bias, sell-out tendency
        $catalogue = [
            ['Bukë e bardhë',   'bread',   1.00, 0.45, 120, 10, [1.0, 1.0, 1.0, 1.05, 1.15, 1.3, 0.7], 0.05, 0.05],
            ['Bukë integrale',  'bread',   1.30, 0.60,  40,  5, [1.0, 0.95, 1.0, 1.0, 1.1, 1.2, 0.6], 0.10, 0.02],
            ['Kruasan',         'pastry',  0.80, 0.30,  50, 10, [1.0, 1.05, 1.0, 1.0, 1.1, 1.25, 1.1], 0.18, 0.02],
            ['Simite me susam', 'bread',   0.50, 0.18,  60, 10, [1.0, 1.0, 1.05, 1.1, 1.2, 1.45, 1.2], 0.02, 0.35],
            ['Byrek me spinaq', 'savoury', 1.50, 0.70,  40,  5, [1.1, 1.0, 1.0, 1.0, 1.1, 1.2, 0.9], 0.07, 0.10],
            ['Byrek me mish',   'savoury', 1.80, 0.90,  30,  5, [1.0, 1.0, 1.0, 1.05, 1.15, 1.25, 0.9], 0.06, 0.15],
            ['Kek me kakao',    'cake',    2.20, 1.00,  12,  1, [0.9, 0.9, 1.0, 1.0, 1.1, 1.4, 1.2], 0.20, 0.03],
            ['Petulla',         'pastry',  0.60, 0.22,  35,  5, [1.0, 1.0, 1.0, 1.0, 1.05, 1.3, 1.4], 0.12, 0.08],
            ['Sandwich',        'savoury', 2.50, 1.20,  20,  1, [1.2, 1.1, 1.1, 1.1, 1.15, 0.8, 0.5], 0.15, 0.05],
        ];

        $products = [];
        foreach ($catalogue as $i => [$name, $category, $price, $cost, $batch, $round, $shape, $waste, $sellout]) {
            $product = $shop->products()->create([
                'name' => $name,
                'category' => $category,
                'unit' => 'piece',
                'price' => $price,
                'cost' => $cost,
                'typical_batch' => $batch,
                'round_to' => $round,
                'active' => true,
                'sort' => ($i + 1) * 10,
            ]);

            $products[] = [$product, $batch, $shape, $waste, $sellout];
        }

        // Added last week: too little history to say anything about, which is a
        // state the product has to handle out loud rather than by guessing.
        $shop->products()->create([
            'name' => 'Tortë molle',
            'category' => 'cake',
            'unit' => 'piece',
            'price' => 2.80,
            'cost' => 1.30,
            'typical_batch' => 8,
            'round_to' => 1,
            'active' => true,
            'sort' => 200,
        ]);

        mt_srand(20260919);
        $today = Carbon::now('Europe/Tirane')->startOfDay();

        for ($back = 70; $back >= 1; $back--) {
            $date = $today->copy()->subDays($back);
            $weekday = (int) $date->isoWeekday();

            // Sunday afternoons the shop is shut, and one Thursday nobody got
            // round to counting. Both have to survive.
            if ($weekday === 7 && $back % 14 < 7) {
                $shop->daySheets()->create([
                    'on_date' => $date->toDateString(),
                    'weekday' => $weekday,
                    'status' => DaySheet::SHUT,
                ]);

                continue;
            }

            if ($back === 18) {
                $shop->daySheets()->create([
                    'on_date' => $date->toDateString(),
                    'weekday' => $weekday,
                    'status' => DaySheet::SKIPPED,
                    'note' => 'Ditë e ngarkuar, nuk u numërua.',
                ]);

                continue;
            }

            $sheet = $shop->daySheets()->create([
                'on_date' => $date->toDateString(),
                'weekday' => $weekday,
                'status' => DaySheet::COUNTED,
                'counted_at' => $date->copy()->setTime(20, mt_rand(5, 40)),
            ]);

            foreach ($products as [$product, $batch, $shape, $waste, $sellout]) {
                $factor = $shape[$weekday - 1];
                $baked = (int) round($batch * $factor * (1 + (mt_rand(-6, 6) / 100)));
                $baked = max(1, (int) (round($baked / $product->round_to) * $product->round_to));

                $soldOutAt = null;

                if (mt_rand(0, 100) / 100 < $sellout) {
                    // It went. Somewhere between mid-morning and the afternoon.
                    $hour = mt_rand(9, 16);
                    $soldOutAt = sprintf('%02d:%02d', $hour, mt_rand(0, 3) * 15);
                    $left = 0;
                } else {
                    $left = (int) round($baked * $waste * (mt_rand(40, 170) / 100));
                    $left = max(0, min($baked, $left));
                }

                $sheet->counts()->create([
                    'product_id' => $product->id,
                    'baked_qty' => $baked,
                    'left_qty' => $left,
                    'sold_out_at' => $soldOutAt,
                ]);
            }
        }

        // Today's sheet, deliberately left uncounted so the demo opens on the
        // screen the whole product exists for.
        $shop->daySheets()->firstOrCreate(
            ['on_date' => $today->toDateString()],
            ['weekday' => (int) $today->isoWeekday(), 'status' => DaySheet::OPEN],
        );

        app(PlanBuilder::class)->build($shop, $today->copy()->addDay());
        app(PlanBuilder::class)->build($shop, $today);

        $this->command?->info('Seeded Furra Petrela: demo@leftover.test / password');
    }
}
