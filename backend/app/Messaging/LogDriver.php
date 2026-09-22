<?php

namespace App\Messaging;

use App\Models\OutboundMessage;
use Illuminate\Support\Facades\Log;

/** Development and "no provider yet" mode: the message stays in the outbox, marked simulated. */
class LogDriver implements MessageDriver
{
    public function name(): string
    {
        return 'log';
    }

    public function deliver(OutboundMessage $message): array
    {
        Log::info('[outbox] to='.$message->recipient.' template='.$message->template_key.' body='.str_replace("\n", ' ', $message->body));

        return ['ok' => true, 'provider_message_id' => null, 'status' => OutboundMessage::SIMULATED, 'error' => null];
    }
}
