<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

/** A join link for a colleague, driver, carer or relative. Single use, expires. */
class Invitation extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'token', 'role', 'name', 'invited_by', 'expires_at', 'accepted_at', 'accepted_user_id'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function isUsable(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }

    public function url(): string
    {
        return config('product.public_url').'/join/'.$this->token;
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role,
            'url' => $this->url(),
            'expiresAt' => $this->expires_at?->toIso8601String(),
        ];
    }
}
