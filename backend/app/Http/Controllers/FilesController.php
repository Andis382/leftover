<?php

namespace App\Http\Controllers;

use App\Models\StoredFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FilesController extends Controller
{
    /** Signed-in: only files of your own organisation (the global scope enforces it). */
    public function show(string $id): StreamedResponse
    {
        return $this->serve(StoredFile::findOrFail($id), 'private, max-age=604800');
    }

    /** Public pages: the route's signature middleware has already checked the link. */
    public function publicShow(string $file): StreamedResponse
    {
        return $this->serve(StoredFile::withoutGlobalScope('organization')->findOrFail($file), 'private, max-age=3600');
    }

    private function serve(StoredFile $file, string $cache): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($file->path), 404);

        return Storage::disk('local')->response($file->path, $file->original_name, [
            'Content-Type' => $file->content_type,
            'Cache-Control' => $cache,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
