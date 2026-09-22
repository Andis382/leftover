<?php

namespace App\Bakery;

use App\Models\Organization;
use App\Models\ShopHour;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * The shop's own calendar: today's date and the time in the organisation's time zone, and
 * its opening hours per weekday. Times of day are handled as minutes after midnight.
 */
final class ShopClock
{
    /** @var array<int, array{opens: ?int, closes: ?int}> */
    private array $hours = [];

    /** @param  ?Collection<int, ShopHour>  $hours */
    public function __construct(public readonly Organization $organization, ?Collection $hours = null)
    {
        $rows = $hours ?? ShopHour::withoutGlobalScope('organization')->where('organization_id', $organization->id)->get();
        foreach (range(1, 7) as $weekday) {
            $this->hours[$weekday] = ['opens' => null, 'closes' => null];
        }
        foreach ($rows as $row) {
            $this->hours[$row->weekday] = ['opens' => self::minutes($row->opens_at), 'closes' => self::minutes($row->closes_at)];
        }
    }

    public static function for(Organization $organization): self
    {
        return new self($organization);
    }

    public function timezone(): string
    {
        return $this->organization->timezone ?: config('product.default_timezone');
    }

    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone());
    }

    public function today(): string
    {
        return $this->now()->toDateString();
    }

    public function minuteOfDay(): int
    {
        $now = $this->now();

        return $now->hour * 60 + $now->minute;
    }

    public static function weekdayOf(string $date): int
    {
        return CarbonImmutable::parse($date)->isoWeekday();
    }

    public function opensMinute(int $weekday): ?int
    {
        return $this->hours[$weekday]['opens'];
    }

    public function closesMinute(int $weekday): ?int
    {
        return $this->hours[$weekday]['closes'];
    }

    public function isOpenOn(int $weekday): bool
    {
        $opens = $this->opensMinute($weekday);
        $closes = $this->closesMinute($weekday);

        return $opens !== null && $closes !== null && $closes > $opens;
    }

    /** The moment the shop closes on a date, as an instant (for "remind 15 minutes after closing"). */
    public function closingAt(string $date): ?CarbonImmutable
    {
        $closes = $this->closesMinute(self::weekdayOf($date));
        if ($closes === null || ! $this->isOpenOn(self::weekdayOf($date))) {
            return null;
        }

        return CarbonImmutable::parse($date, $this->timezone())->startOfDay()->addMinutes($closes);
    }

    public function planTimeOn(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date, $this->timezone())->startOfDay()->addMinutes(self::minutes($this->organization->planTime()) ?? 240);
    }

    /** @return list<array{weekday: int, open: bool, opensAt: ?string, closesAt: ?string}> */
    public function toApi(): array
    {
        return array_map(fn (int $weekday) => [
            'weekday' => $weekday,
            'open' => $this->isOpenOn($weekday),
            'opensAt' => self::clock($this->opensMinute($weekday)),
            'closesAt' => self::clock($this->closesMinute($weekday)),
        ], range(1, 7));
    }

    /** "06:30" or "06:30:00" to 390. */
    public static function minutes(?string $time): ?int
    {
        if ($time === null || ! preg_match('/^(\d{1,2}):(\d{2})/', $time, $m)) {
            return null;
        }

        return (int) $m[1] * 60 + (int) $m[2];
    }

    public static function clock(?int $minutes): ?string
    {
        return $minutes === null ? null : sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
