<?php

namespace App\Services;

use App\Models\DaySheet;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Saving the closing count.
 *
 * Two rules, both of which exist because this is done at the end of a shift by
 * someone who has been on their feet since four.
 *
 * Nothing is compulsory. A sheet saves with two products filled in and the rest
 * blank, and the blanks are simply absent from the history rather than zero. A
 * partial count is worth incomparably more than the count that never happened
 * because the form insisted on all thirty items.
 *
 * Saving twice is saving once. The same day can be corrected as many times as
 * needed — somebody always remembers a tray in the back — and the sheet keeps
 * the latest numbers rather than accumulating duplicates.
 */
class SheetRecorder
{
    public function sheetFor(User $user, CarbonInterface $date): DaySheet
    {
        return $user->daySheets()->firstOrCreate(
            ['on_date' => $date->toDateString()],
            ['weekday' => (int) $date->isoWeekday(), 'status' => DaySheet::OPEN],
        );
    }

    /**
     * @param  array<int, array{baked:?int, left:?int, sold_out_at:?string}>  $entries  keyed by product id
     */
    public function record(User $user, CarbonInterface $date, array $entries, ?string $note = null): DaySheet
    {
        $sheet = $this->sheetFor($user, $date);
        $productIds = $user->products()->pluck('id')->all();

        DB::transaction(function () use ($sheet, $entries, $productIds, $note) {
            $counted = 0;

            foreach ($entries as $productId => $entry) {
                if (! in_array((int) $productId, $productIds, true)) {
                    continue;   // not this bakery's product
                }

                $baked = $this->number($entry['baked'] ?? null);

                // Nothing baked, nothing to say. Remove any earlier row so that
                // correcting a mistake genuinely removes it from the history.
                if ($baked === null) {
                    $sheet->counts()->where('product_id', $productId)->delete();

                    continue;
                }

                $left = min($this->number($entry['left'] ?? null) ?? 0, $baked);
                $soldOut = $this->time($entry['sold_out_at'] ?? null);

                // Something still on the shelf cannot also have sold out. The
                // count is the truth; the checkbox was a mis-tap.
                if ($left > 0) {
                    $soldOut = null;
                }

                $sheet->counts()->updateOrCreate(
                    ['product_id' => $productId],
                    ['baked_qty' => $baked, 'left_qty' => $left, 'sold_out_at' => $soldOut],
                );

                $counted++;
            }

            $sheet->note = $note;

            if ($counted > 0) {
                $sheet->status = DaySheet::COUNTED;
                $sheet->counted_at = now();
            } elseif ($sheet->status === DaySheet::COUNTED) {
                // Everything was cleared back out again.
                $sheet->status = DaySheet::OPEN;
                $sheet->counted_at = null;
            }

            $sheet->save();
        });

        return $sheet->fresh(['counts.product']);
    }

    /** Mark a day as one the shop was shut, or one nobody counted. */
    public function mark(User $user, CarbonInterface $date, string $status): DaySheet
    {
        $sheet = $this->sheetFor($user, $date);
        $sheet->status = in_array($status, [DaySheet::SKIPPED, DaySheet::SHUT], true) ? $status : DaySheet::OPEN;

        if ($sheet->status !== DaySheet::COUNTED) {
            $sheet->counted_at = null;
        }

        $sheet->save();

        return $sheet;
    }

    private function number(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return max(0, (int) $value);
    }

    private function time(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return preg_match('/^\d{1,2}:\d{2}/', $value) ? substr($value, 0, 5) : null;
    }
}
