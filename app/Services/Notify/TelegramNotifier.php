<?php

namespace App\Services\Notify;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * A bot pushes the plan at the hour the shop chose.
 *
 * Telegram rather than WhatsApp for one unglamorous reason: a server that sends
 * a scheduled WhatsApp message needs a Meta Business account, a verified number
 * and templates approved in advance by someone at Meta. A Telegram bot token
 * takes two minutes, costs nothing, and nobody has to approve the sentence
 * "five fewer croissants". The WhatsApp route still exists on every plan screen
 * as a link that opens in the baker's own WhatsApp — which is the honest way to
 * use WhatsApp without pretending to be a business platform.
 */
class TelegramNotifier implements PlanNotifier
{
    public function __construct(private readonly ?string $token)
    {
    }

    public function name(): string
    {
        return 'telegram';
    }

    public function send(User $user, Plan $plan): bool
    {
        if (! $this->token || ! $user->telegram_chat_id) {
            Log::warning('leftover: telegram is selected but not configured', ['user' => $user->id]);

            return false;
        }

        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$this->token}/sendMessage", [
                'chat_id' => $user->telegram_chat_id,
                'text' => $plan->body,
                'disable_web_page_preview' => true,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('leftover: telegram refused the plan', [
                'user' => $user->id,
                'status' => $response->status(),
            ]);
        } catch (\Throwable $e) {
            // A push that fails must never cost the plan. It is on its page.
            Log::warning('leftover: telegram unreachable', ['user' => $user->id, 'error' => $e->getMessage()]);
        }

        return false;
    }
}
