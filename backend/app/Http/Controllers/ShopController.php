<?php

namespace App\Http\Controllers;

use App\Bakery\ShopClock;
use App\Models\ShopHour;
use App\Support\Phones;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Opening hours, when the plan goes out, when the count reminder goes out, and to whom. */
class ShopController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json($this->payload($request));
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.weekday' => ['required', 'integer', 'between:1,7', 'distinct'],
            'hours.*.open' => ['required', 'boolean'],
            'hours.*.opensAt' => ['nullable', 'required_if_accepted:hours.*.open', 'date_format:H:i'],
            'hours.*.closesAt' => ['nullable', 'required_if_accepted:hours.*.open', 'date_format:H:i'],
            'planTime' => ['required', 'date_format:H:i'],
            'countReminderOffset' => ['required', 'integer', 'min:0', 'max:240'],
            'planPhone' => ['nullable', 'string', 'max:40'],
        ]);
        $errors = [];
        foreach ($data['hours'] as $i => $day) {
            if ($day['open'] && ShopClock::minutes($day['closesAt']) <= ShopClock::minutes($day['opensAt'])) {
                $errors["hours.$i.closesAt"] = [__('errors.closes_before_opens')];
            }
        }
        $phone = Phones::normalize($data['planPhone'] ?? null);
        if ($phone !== null && ! Phones::isPlausible($phone)) {
            $errors['planPhone'] = [__('errors.phone_invalid')];
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $organization = $request->user()->organization;
        DB::transaction(function () use ($data, $organization, $phone) {
            foreach ($data['hours'] as $day) {
                ShopHour::updateOrCreate(['weekday' => $day['weekday']], [
                    'opens_at' => $day['open'] ? $day['opensAt'] : null,
                    'closes_at' => $day['open'] ? $day['closesAt'] : null,
                ]);
            }
            $organization->forceFill([
                'plan_time' => $data['planTime'],
                'count_reminder_offset' => $data['countReminderOffset'],
                'plan_phone' => $phone,
            ])->save();
        });

        return response()->json($this->payload($request));
    }

    private function payload(Request $request): array
    {
        $organization = $request->user()->organization->fresh();

        return [
            'hours' => ShopClock::for($organization)->toApi(),
            'planTime' => $organization->planTime(),
            'countReminderOffset' => $organization->count_reminder_offset,
            'planPhone' => $organization->plan_phone,
            'timezone' => $organization->timezone,
        ];
    }
}
