<?php

namespace App\Messaging;

use App\Models\OutboundMessage;

/** Delivers one message. Implementations never throw; they report failure in the result. */
interface MessageDriver
{
    public function name(): string;

    /** @return array{ok: bool, provider_message_id: ?string, status: string, error: ?string} */
    public function deliver(OutboundMessage $message): array;
}
