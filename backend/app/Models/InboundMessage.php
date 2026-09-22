<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A message a customer sent to the platform number (a reply, a button tap, a photo).
 * Not tenant-scoped on read: it arrives before we know whose it is.
 */
class InboundMessage extends Model
{
    protected $fillable = [
        'organization_id', 'from_phone', 'body', 'kind', 'media_id', 'media_type', 'provider_message_id',
        'handled', 'handled_by', 'related_type', 'related_id', 'received_at',
    ];

    protected function casts(): array
    {
        return [
            'handled' => 'boolean',
            'received_at' => 'datetime',
        ];
    }

    /** Lower-cased, trimmed text for keyword matching ("1", "po", "gati?"). */
    public function normalizedBody(): string
    {
        return mb_strtolower(trim((string) $this->body));
    }

    public function markHandled(string $handler, ?int $organizationId, ?string $relatedType = null, ?int $relatedId = null): void
    {
        $this->handled = true;
        $this->handled_by = $handler;
        $this->organization_id = $organizationId ?? $this->organization_id;
        $this->related_type = $relatedType;
        $this->related_id = $relatedId;
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'fromPhone' => $this->from_phone,
            'body' => $this->body,
            'kind' => $this->kind,
            'handled' => $this->handled,
            'handledBy' => $this->handled_by,
            'relatedType' => $this->related_type,
            'relatedId' => $this->related_id,
            'receivedAt' => $this->received_at?->toIso8601String(),
        ];
    }
}
