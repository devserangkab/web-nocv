<?php

namespace App\Models\Concerns;

use App\Actions\Media\DeleteMedia;
use App\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Memberi entitas kemampuan menyimpan galeri media (foto/video kondisi
 * existing, evidence aktivitas, diagram, dll) secara polymorphic, dan
 * membersihkan record + file saat entitas dihapus.
 */
trait OwnsMedia
{
    public function medias(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public static function bootOwnsMedia(): void
    {
        static::deleting(function ($model) {
            $model->medias()->each(fn (Media $media) => app(DeleteMedia::class)($media));
        });
    }
}
