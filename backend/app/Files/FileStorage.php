<?php

namespace App\Files;

use App\Models\StoredFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Keeps uploads on the local disk (storage/app/private/files/yyyy/mm), one row per file. */
class FileStorage
{
    public const IMAGES = ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif'];

    public const DOCUMENTS = ['application/pdf'];

    public const AUDIO = ['audio/webm', 'audio/ogg', 'audio/mpeg', 'audio/mp4', 'audio/wav', 'audio/x-m4a', 'audio/aac'];

    private const EXTENSIONS = [
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/heic' => 'heic', 'image/heif' => 'heif',
        'application/pdf' => 'pdf', 'audio/webm' => 'webm', 'audio/ogg' => 'ogg', 'audio/mpeg' => 'mp3', 'audio/mp4' => 'm4a',
        'audio/wav' => 'wav', 'audio/x-m4a' => 'm4a', 'audio/aac' => 'aac',
    ];

    public function storeUpload(UploadedFile $file, ?int $organizationId = null): StoredFile
    {
        return $this->storeBytes($file->get(), $this->baseType($file->getMimeType()), $file->getClientOriginalName(), $organizationId);
    }

    public function storeBytes(string $bytes, string $contentType, ?string $originalName = null, ?int $organizationId = null): StoredFile
    {
        $type = $this->baseType($contentType);
        $id = (string) Str::uuid();
        $path = sprintf('files/%s/%s.%s', now()->format('Y/m'), $id, self::EXTENSIONS[$type] ?? 'bin');
        Storage::disk('local')->put($path, $bytes);

        $file = new StoredFile([
            'original_name' => $originalName,
            'content_type' => $type,
            'size_bytes' => strlen($bytes),
            'path' => $path,
        ]);
        $file->id = $id;
        if ($organizationId !== null) {
            $file->organization_id = $organizationId;
        }
        $file->save();

        return $file;
    }

    public function bytes(StoredFile $file): string
    {
        return Storage::disk('local')->get($file->path);
    }

    private function baseType(?string $contentType): string
    {
        return strtolower(trim(explode(';', (string) $contentType)[0])) ?: 'application/octet-stream';
    }
}
