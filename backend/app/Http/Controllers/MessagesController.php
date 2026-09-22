<?php

namespace App\Http\Controllers;

use App\Messaging\InboundDispatcher;
use App\Messaging\Messenger;
use App\Models\InboundMessage;
use App\Models\OutboundMessage;
use App\Support\Phones;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MessagesController extends Controller
{
    public function outbox(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 100), 500);

        return response()->json(OutboundMessage::latest('id')->limit($limit)->get()->map->toApi());
    }

    public function inbox(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 100), 500);
        $messages = InboundMessage::where('organization_id', $request->user()->organization_id)
            ->latest('id')->limit($limit)->get();

        return response()->json($messages->map->toApi());
    }

    public function retry(int $id, Messenger $messenger): JsonResponse
    {
        $message = OutboundMessage::findOrFail($id);
        $messenger->deliver($message);

        return response()->json($message->toApi());
    }

    /** Demo only: pretend a customer replied on WhatsApp, and show what the app answered. */
    public function simulate(Request $request, InboundDispatcher $dispatcher): JsonResponse
    {
        abort_unless(config('product.demo'), 404);
        $data = $request->validate([
            'from' => ['required', 'string', 'max:40'],
            'body' => ['required', 'string', 'max:1000'],
        ]);
        $from = Phones::normalize($data['from']);
        $lastId = (int) OutboundMessage::withoutGlobalScope('organization')->max('id');

        $inbound = $dispatcher->receive(new InboundMessage([
            'from_phone' => $from,
            'body' => $data['body'],
            'kind' => 'text',
            'provider_message_id' => 'sim-'.Str::uuid(),
        ]));

        $replies = OutboundMessage::withoutGlobalScope('organization')
            ->where('recipient', $from)->where('id', '>', $lastId)->orderBy('id')->get();

        return response()->json(['inbound' => $inbound->toApi(), 'replies' => $replies->map->toApi()]);
    }
}
