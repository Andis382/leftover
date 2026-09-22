<?php

namespace App\Messaging;

use App\Models\InboundMessage;
use App\Models\OutboundMessage;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Handlers come from config('product.messaging.inbound_handlers'), in priority order. */
class InboundDispatcher
{
    /** Stores the message once (provider ids are unique) and gives it to the first willing handler. */
    public function receive(InboundMessage $message): InboundMessage
    {
        if ($message->provider_message_id && InboundMessage::where('provider_message_id', $message->provider_message_id)->exists()) {
            return $message;
        }
        // Default owner: whoever last wrote to this phone. Handlers may re-assign.
        $last = OutboundMessage::withoutGlobalScope('organization')
            ->where('recipient', $message->from_phone)
            ->latest('id')
            ->first();
        $message->organization_id = $last?->organization_id;
        $message->received_at ??= now();
        $message->save();

        foreach (config('product.messaging.inbound_handlers', []) as $class) {
            try {
                if (app($class)->handle($message)) {
                    break;
                }
            } catch (Throwable $e) {
                Log::warning('Inbound handler failed', ['handler' => $class, 'error' => $e->getMessage()]);
            }
        }
        $message->save();

        return $message;
    }
}
