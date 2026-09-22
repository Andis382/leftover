<?php

namespace App\Messaging;

use App\Models\OutboundMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * WhatsApp Cloud API. Business-initiated messages need an approved template, configured in
 * config('product.messaging.whatsapp.templates') as key => "template_name|param1,param2".
 * Without a mapping the message goes out as plain text, which WhatsApp only accepts inside
 * the 24-hour window after the customer last wrote.
 */
class WhatsAppCloudDriver implements MessageDriver
{
    public function name(): string
    {
        return 'whatsapp';
    }

    public function configured(): bool
    {
        return config('product.messaging.whatsapp.token') !== '' && config('product.messaging.whatsapp.phone_number_id') !== '';
    }

    public function deliver(OutboundMessage $message): array
    {
        if (! $this->configured()) {
            return ['ok' => false, 'provider_message_id' => null, 'status' => OutboundMessage::FAILED, 'error' => 'WhatsApp is not configured'];
        }
        $cfg = config('product.messaging.whatsapp');
        $payload = ['messaging_product' => 'whatsapp', 'to' => $message->recipient];
        $mapping = $cfg['templates'][$message->template_key] ?? null;
        if ($mapping) {
            $payload['type'] = 'template';
            $payload['template'] = $this->template($mapping, $message);
        } else {
            $payload['type'] = 'text';
            $payload['text'] = ['body' => $message->body, 'preview_url' => true];
        }

        try {
            $response = Http::withToken($cfg['token'])
                ->timeout(20)
                ->post("https://graph.facebook.com/{$cfg['api_version']}/{$cfg['phone_number_id']}/messages", $payload);
            if (! $response->successful()) {
                Log::warning('WhatsApp send failed', ['status' => $response->status(), 'body' => $response->body()]);

                return ['ok' => false, 'provider_message_id' => null, 'status' => OutboundMessage::FAILED, 'error' => 'HTTP '.$response->status().': '.$response->body()];
            }

            return ['ok' => true, 'provider_message_id' => $response->json('messages.0.id'), 'status' => OutboundMessage::SENT, 'error' => null];
        } catch (Throwable $e) {
            return ['ok' => false, 'provider_message_id' => null, 'status' => OutboundMessage::FAILED, 'error' => $e->getMessage()];
        }
    }

    private function template(string $mapping, OutboundMessage $message): array
    {
        [$name, $paramList] = array_pad(explode('|', $mapping, 2), 2, '');
        $params = $message->params ?? [];
        $parameters = [];
        foreach (array_filter(array_map('trim', explode(',', $paramList))) as $key) {
            $value = $key === 'link' ? $message->link : ($params[$key] ?? null);
            $parameters[] = ['type' => 'text', 'text' => $value ?: '-'];
        }
        $template = ['name' => trim($name), 'language' => ['code' => $message->locale]];
        if ($parameters) {
            $template['components'] = [['type' => 'body', 'parameters' => $parameters]];
        }

        return $template;
    }

    /** Downloads media a customer sent (photo, voice note). @return array{bytes: string, mime: string}|null */
    public function downloadMedia(?string $mediaId): ?array
    {
        if (! $this->configured() || ! $mediaId) {
            return null;
        }
        $cfg = config('product.messaging.whatsapp');
        try {
            $info = Http::withToken($cfg['token'])->get("https://graph.facebook.com/{$cfg['api_version']}/{$mediaId}")->json();
            if (empty($info['url'])) {
                return null;
            }
            $file = Http::withToken($cfg['token'])->get($info['url']);

            return ['bytes' => $file->body(), 'mime' => $info['mime_type'] ?? 'application/octet-stream'];
        } catch (Throwable $e) {
            Log::warning('WhatsApp media download failed', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
