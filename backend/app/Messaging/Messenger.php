<?php

namespace App\Messaging;

use App\Models\OutboundMessage;

/**
 * The one way the app writes to customers: render, store in the outbox, deliver.
 * Texts live in lang/{locale}/msg.php with :named placeholders.
 */
class Messenger
{
    public function __construct(
        private readonly LogDriver $log,
        private readonly WhatsAppCloudDriver $whatsApp,
    ) {}

    /**
     * @param  array<string, string|int|float|null>  $params
     */
    public function send(
        ?int $organizationId,
        string $to,
        ?string $toName,
        string $templateKey,
        ?string $locale,
        array $params = [],
        ?string $link = null,
        ?string $relatedType = null,
        ?int $relatedId = null,
    ): OutboundMessage {
        $locale ??= config('product.default_locale');
        if ($link !== null) {
            $params['link'] ??= $link;
        }
        $body = $this->render($templateKey, $locale, $params);
        if ($link !== null && ! str_contains($body, $link)) {
            $body .= "\n".$link;
        }

        $message = new OutboundMessage([
            'channel' => 'WHATSAPP',
            'recipient' => $to,
            'recipient_name' => $toName,
            'template_key' => $templateKey,
            'locale' => $locale,
            'body' => $body,
            'link' => $link,
            'params' => array_map(fn ($v) => $v === null ? null : (string) $v, $params),
            'status' => OutboundMessage::QUEUED,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
        ]);
        $message->organization_id = $organizationId;
        $message->save();

        $this->deliver($message);

        return $message;
    }

    /** Sends (again) through the configured driver. Used on first send and on "retry". */
    public function deliver(OutboundMessage $message): void
    {
        $driver = config('product.messaging.driver') === 'whatsapp' && $this->whatsApp->configured() ? $this->whatsApp : $this->log;
        $result = $driver->deliver($message);
        $message->provider = $driver->name();
        if ($result['ok']) {
            $message->status = $result['status'];
            $message->provider_message_id = $result['provider_message_id'];
            $message->sent_at = now();
            $message->error = null;
        } else {
            $message->status = OutboundMessage::FAILED;
            $message->error = $result['error'];
        }
        $message->save();
    }

    /** @param  array<string, mixed>  $params */
    public function render(string $templateKey, string $locale, array $params): string
    {
        $replace = array_map(fn ($v) => (string) ($v ?? ''), $params);

        return trans('msg.'.$templateKey, $replace, $locale);
    }
}
