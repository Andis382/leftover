<?php

namespace App\Bakery;

use App\Forecast\ReasonText;
use App\Messaging\Messenger;
use App\Models\OutboundMessage;
use App\Models\Plan;
use App\Models\User;
use App\Support\Phones;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** The bake plan as a WhatsApp message to the baker: headline, the biggest changes, a link. */
final class PlanSender
{
    /** Changes listed one per line before "and N more as usual". */
    public const LINES = 6;

    public function __construct(
        private readonly PlanService $plans,
        private readonly Messenger $messenger,
    ) {}

    /** @return array{phone: ?string, name: ?string, locale: string} */
    public function recipient(ShopClock $clock, ?string $phone = null): array
    {
        $organization = $clock->organization;
        $owner = User::where('organization_id', $organization->id)->where('role', User::OWNER)->orderBy('id')->first();

        return [
            'phone' => Phones::normalize($phone) ?? $organization->plan_phone ?? $owner?->phone ?? $organization->phone,
            'name' => $owner === null ? null : strtok($owner->name, ' '),
            'locale' => $owner?->locale ?? $organization->locale ?? config('product.default_locale'),
        ];
    }

    /** What would be sent, for the preview dialog. */
    public function preview(ShopClock $clock, string $date, ?string $phone = null): array
    {
        $recipient = $this->recipient($clock, $phone);
        [$params, $link] = $this->params($clock, $date, $recipient);
        $body = $this->messenger->render('bake_plan', $recipient['locale'], $params + ['link' => $link]);

        return [
            'phone' => $recipient['phone'],
            'name' => $recipient['name'],
            'locale' => $recipient['locale'],
            'body' => $body,
            'waUrl' => $recipient['phone'] ? 'https://wa.me/'.$recipient['phone'].'?text='.rawurlencode($body) : null,
        ];
    }

    /** Sends now, even if it went out before (the "Send to WhatsApp" button). */
    public function send(ShopClock $clock, Plan $plan, ?string $phone = null): ?OutboundMessage
    {
        $recipient = $this->recipient($clock, $phone);
        if ($recipient['phone'] === null) {
            return null;
        }
        [$params, $link] = $this->params($clock, $plan->date->toDateString(), $recipient);
        $message = $this->messenger->send(
            $clock->organization->id, $recipient['phone'], $recipient['name'], 'bake_plan', $recipient['locale'], $params, $link, 'plan', $plan->id,
        );
        $plan->forceFill(['sent_at' => now(), 'outbound_message_id' => $message->id])->save();

        return $message;
    }

    /** Sends unless it already went out; safe to call from a job that may run twice. */
    public function sendOnce(ShopClock $clock, Plan $plan): ?OutboundMessage
    {
        return DB::transaction(function () use ($clock, $plan) {
            $locked = Plan::whereKey($plan->id)->lockForUpdate()->first();

            return $locked->sent_at === null ? $this->send($clock, $locked) : null;
        });
    }

    /** @return array{0: array<string, string|int>, 1: string} */
    private function params(ShopClock $clock, string $date, array $recipient): array
    {
        $locale = $recipient['locale'];
        $view = $this->plans->view($clock, $date, $locale);
        $changed = array_values(array_filter($view['rows'], fn (array $r) => $r['change'] !== null && $r['change'] !== 0));
        usort($changed, fn (array $a, array $b) => abs($b['change']) <=> abs($a['change']));
        $listed = array_slice($changed, 0, self::LINES);

        $lines = array_map(fn (array $r) => trans('plan.line_change', [
            'name' => $r['name'],
            'qty' => $r['willBake'],
            'change' => ($r['change'] > 0 ? '+' : '−').abs($r['change']),
        ], $locale), $listed);
        $others = count($view['rows']) - count($listed);
        if ($others > 0) {
            $key = count($changed) > count($listed) ? 'plan.others_more' : 'plan.others_usual';
            $lines[] = trans_choice($key, $others, ['count' => $others], $locale);
        }
        $day = trans('plan.day', [
            'weekday' => ReasonText::weekday('on', $view['weekday'], $locale),
            'date' => CarbonImmutable::parse($date)->locale($locale)->isoFormat('D MMMM'),
        ], $locale);

        return [[
            'name' => $recipient['name'] ?? '',
            'day' => $day,
            'headline' => $view['headline'],
            'lines' => implode("\n", $lines),
            'total' => $view['totals']['units'],
        ], config('product.public_url').'/plan/'.$date];
    }
}
