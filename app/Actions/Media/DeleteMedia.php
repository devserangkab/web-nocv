<?php

namespace App\Actions\Media;

use App\Actions\Activities\RecordActivity;
use App\Models\Media;
use App\Support\Authorize;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteMedia
{
    public function __invoke(Media $media): bool
    {
        Authorize::mediaDelete($media);

        return DB::transaction(function () use ($media): bool {
            if (filled($media->file)) {
                Storage::disk('private')->delete($media->file);
                Storage::disk('public')->delete($media->file);
            }

            if (filled($media->thumbnail)) {
                Storage::disk('private')->delete($media->thumbnail);
                Storage::disk('public')->delete($media->thumbnail);
            }

            $deleted = $media->delete();

            app(RecordActivity::class)->audit($media, 'Menghapus media '.($media->title ?? $media->media_id));

            return $deleted;
        });
    }
}
