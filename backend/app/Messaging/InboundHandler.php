<?php

namespace App\Messaging;

use App\Models\InboundMessage;

/**
 * Reacts to a customer's message. Handlers listed in InboundDispatcher::$handlers are tried
 * in order; the first that returns true owns the message (and calls markHandled()).
 */
interface InboundHandler
{
    public function handle(InboundMessage $message): bool;
}
