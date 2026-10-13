<?php

namespace App\Actions\Media;

use App\Models\Media;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteMedia
{
    public function __invoke(Media $media): bool
    {
        return DB::transaction(function () use ($media): bool {
            if (filled($media->file)) {
                Storage::disk('public')->delete($media->file);
            }

            if (filled($media->thumbnail)) {
                Storage::disk('public')->delete($media->thumbnail);
            }

            return $media->delete();
        });
    }
}
