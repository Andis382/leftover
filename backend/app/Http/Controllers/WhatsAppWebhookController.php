<?php

namespace App\Http\Controllers;

use App\Messaging\InboundDispatcher;
use App\Models\InboundMessage;
use App\Models\OutboundMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/** Meta's webhook: verification handshake, customer messages and delivery receipts. */
class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $expected = (string) config('product.messaging.whatsapp.verify_token');
        if ($request->query('hub_mode') === 'subscribe' && $expected !== '' && hash_equals($expected, (string) $request->query('hub_verify_token'))) {
            return response((string) $request->query('hub_challenge'));
        }

        return response('', 403);
    }

    public function receive(Request $request, InboundDispatcher $dispatcher): Response
    {
        if (! $this->validSignature($request)) {
            return response('', 401);
        }
        foreach ($request->input('entry', []) as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];
                foreach ($value['messages'] ?? [] as $m) {
                    $dispatcher->receive($this->toInbound($m));
                }
                foreach ($value['statuses'] ?? [] as $s) {
                    $this->applyStatus($s);
                }
            }
        }

        return response('', 200);
    }

    private function toInbound(array $m): InboundMessage
    {
        $type = $m['type'] ?? 'text';
        $body = null;
        $mediaId = null;
        $mediaType = null;
        switch ($type) {
            case 'text':
                $body = $m['text']['body'] ?? null;
                break;
            case 'button':
                $body = $m['button']['payload'] ?? ($m['button']['text'] ?? null);
                break;
            case 'interactive':
                $reply = $m['interactive']['button_reply'] ?? ($m['interactive']['list_reply'] ?? []);
                $body = $reply['id'] ?? ($reply['title'] ?? null);
                break;
            case 'image':
            case 'audio':
            case 'document':
            case 'video':
                $mediaId = $m[$type]['id'] ?? null;
                $mediaType = $m[$type]['mime_type'] ?? null;
                $body = $m[$type]['caption'] ?? null;
                break;
        }

        return new InboundMessage([
            'from_phone' => (string) ($m['from'] ?? ''),
            'body' => $body,
            'kind' => $type,
            'media_id' => $mediaId,
            'media_type' => $mediaType,
            'provider_message_id' => $m['id'] ?? null,
            'received_at' => isset($m['timestamp']) ? Carbon::createFromTimestamp((int) $m['timestamp']) : now(),
        ]);
    }

    private function applyStatus(array $s): void
    {
        $message = OutboundMessage::withoutGlobalScope('organization')->where('provider_message_id', $s['id'] ?? '')->first();
        if (! $message) {
            return;
        }
        $at = isset($s['timestamp']) ? Carbon::createFromTimestamp((int) $s['timestamp']) : now();
        match ($s['status'] ?? '') {
            'delivered' => $message->fill(['status' => $message->status === OutboundMessage::READ ? OutboundMessage::READ : OutboundMessage::DELIVERED, 'delivered_at' => $at]),
            'read' => $message->fill(['status' => OutboundMessage::READ, 'read_at' => $at]),
            'failed' => $message->fill(['status' => OutboundMessage::FAILED, 'error' => $s['errors'][0]['title'] ?? 'failed']),
            default => null,
        };
        $message->save();
    }

    private function validSignature(Request $request): bool
    {
        $secret = (string) config('product.messaging.whatsapp.app_secret');
        if ($secret === '') {
            if (! config('product.demo')) {
                Log::warning('Rejecting webhook: WHATSAPP_APP_SECRET is not set');
            }

            return (bool) config('product.demo');
        }
        $header = (string) $request->header('X-Hub-Signature-256');
        if (! str_starts_with($header, 'sha256=')) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $request->getContent(), $secret), substr($header, 7));
    }
}
