<?php

namespace App\Http\Controllers;

use App\Models\DaySheet;
use App\Services\SheetRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The forty-second screen. Everything else in this product exists to make this
 * one worth opening.
 */
class SheetController extends Controller
{
    public function __construct(private readonly SheetRecorder $recorder)
    {
    }

    public function edit(Request $request, ?string $date = null)
    {
        $user = $request->user();
        $on = $this->date($user->today(), $date);

        $sheet = $this->recorder->sheetFor($user, $on);
        $sheet->load('counts');

        $products = $user->activeProducts()->get();
        $existing = $sheet->counts->keyBy('product_id');

        // Last time this weekday was counted, so the baker is comparing against
        // the same kind of day rather than against yesterday.
        $lastSame = $user->daySheets()
            ->with('counts')
            ->where('weekday', (int) $on->isoWeekday())
            ->where('status', DaySheet::COUNTED)
            ->where('on_date', '<', $on->toDateString())
            ->orderByDesc('on_date')
            ->first();

        return view('sheet', [
            'user' => $user,
            'date' => $on,
            'sheet' => $sheet,
            'products' => $products,
            'existing' => $existing,
            'lastSame' => $lastSame,
            'lastSameCounts' => $lastSame?->counts->keyBy('product_id') ?? collect(),
            'isToday' => $on->isSameDay($user->today()),
        ]);
    }

    public function update(Request $request, string $date)
    {
        $user = $request->user();
        $on = $this->date($user->today(), $date);

        $data = $request->validate([
            'entries' => ['array'],
            'entries.*.baked' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'entries.*.left' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'entries.*.sold_out_at' => ['nullable', 'date_format:H:i'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $sheet = $this->recorder->record($user, $on, $data['entries'] ?? [], $data['note'] ?? null);

        return redirect()->route('sheet.edit', $on->toDateString())
            ->with('status', $sheet->isCounted()
                ? trans_choice('flash.counted', $sheet->counts->count(), ['n' => $sheet->counts->count()])
                : __('flash.cleared'));
    }

    public function mark(Request $request, string $date)
    {
        $user = $request->user();
        $on = $this->date($user->today(), $date);

        $data = $request->validate(['status' => ['required', 'in:shut,skipped,open']]);
        $this->recorder->mark($user, $on, $data['status']);

        return redirect()->route('dashboard')->with('status', __('flash.day_marked'));
    }

    /**
     * A date in the past or today, never the future: nobody counts what has not
     * been sold yet, and a sheet dated next Friday would silently poison the
     * history for next Friday.
     */
    private function date(\Carbon\CarbonInterface $today, ?string $value): \Carbon\CarbonInterface
    {
        if ($value === null) {
            return $today->copy();
        }

        try {
            // Parsed in the shop's own timezone, and compared as a calendar
            // date rather than an instant. A bakery in Tirana asking for "today"
            // means its own Thursday, and midnight there is ten at night in UTC:
            // comparing the two as moments makes today look like the future.
            $on = Carbon::parse($value, $today->getTimezone())->startOfDay();
        } catch (\Throwable) {
            throw new NotFoundHttpException();
        }

        if ($on->toDateString() > $today->toDateString()
            || $on->toDateString() < $today->copy()->subYears(2)->toDateString()) {
            throw new NotFoundHttpException();
        }

        return $on;
    }
}
