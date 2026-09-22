<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

/** A photo or document on the local disk, owned by one organisation. */
class StoredFile extends Model
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['organization_id', 'original_name', 'content_type', 'size_bytes', 'path'];

    public function isImage(): bool
    {
        return str_starts_with($this->content_type, 'image/');
    }

    /** For signed-in screens. */
    public function url(): string
    {
        return '/api/files/'.$this->id;
    }

    /** For public pages (warranty card, quote, ticket): signed and expiring. */
    public function publicUrl(int $minutes = 60 * 24 * 7): string
    {
        $url = URL::temporarySignedRoute('files.public', now()->addMinutes($minutes), ['file' => $this->id], absolute: false);

        return $url;
    }
}
