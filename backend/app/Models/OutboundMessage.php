<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

/**
 * Every message the app sends to a customer. With the "log" driver the outbox is the
 * delivery: the owner sees it and can forward it from their own WhatsApp.
 */
class OutboundMessage extends Model
{
    use BelongsToOrganization;

    public const QUEUED = 'QUEUED';

    public const SENT = 'SENT';

    public const DELIVERED = 'DELIVERED';

    public const READ = 'READ';

    public const FAILED = 'FAILED';

    public const SIMULATED = 'SIMULATED';

    protected $fillable = [
        'organization_id', 'channel', 'recipient', 'recipient_name', 'template_key', 'locale', 'body', 'link', 'params',
        'status', 'provider', 'provider_message_id', 'error', 'related_type', 'related_id', 'sent_at', 'delivered_at', 'read_at',
    ];

    protected function casts(): array
    {
        return [
            'params' => 'array',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    /** Click-to-chat link so the owner can send it from their own WhatsApp. */
    public function waMeUrl(): string
    {
        return 'https://wa.me/'.$this->recipient.'?text='.rawurlencode($this->body);
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'channel' => $this->channel,
            'recipient' => $this->recipient,
            'recipientName' => $this->recipient_name,
            'templateKey' => $this->template_key,
            'body' => $this->body,
            'link' => $this->link,
            'status' => $this->status,
            'error' => $this->error,
            'relatedType' => $this->related_type,
            'relatedId' => $this->related_id,
            'createdAt' => $this->created_at?->toIso8601String(),
            'sentAt' => $this->sent_at?->toIso8601String(),
            'deliveredAt' => $this->delivered_at?->toIso8601String(),
            'readAt' => $this->read_at?->toIso8601String(),
            'waMeUrl' => $this->waMeUrl(),
        ];
    }
}
