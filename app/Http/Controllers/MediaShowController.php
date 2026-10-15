<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaShowController extends Controller
{
    /**
     * Tampilkan file/thumbnail media dengan otorisasi login. Semua user yang
     * login dapat melihat; anonim tidak. Query ?t=1 mengembalikan thumbnail.
     */
    public function __invoke(string $mediaId): StreamedResponse
    {
        $media = Media::query()->where('media_id', $mediaId)->firstOrFail();

        $path = request()->boolean('t') && filled($media->thumbnail)
            ? $media->thumbnail
            : $media->file;

        if (blank($path)) {
            abort(404);
        }

        $disk = Storage::disk('private');

        if (! $disk->exists($path)) {
            $disk = Storage::disk('public');

            if (! $disk->exists($path)) {
                abort(404);
            }
        }

        return $disk->response($path, basename($path), [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
