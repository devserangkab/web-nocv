<?php

namespace App\Models;

use App\Enums\MediaType;
use App\Models\Concerns\GeneratesCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Media extends Model
{
    use GeneratesCode;

    protected $table = 'media';

    protected static string $codeColumn = 'media_id';

    protected static string $codePrefix = 'MED-';

    protected static int $codeLength = 9;

    protected $fillable = [
        'media_id',
        'mediable_type',
        'mediable_id',
        'type',
        'title',
        'description',
        'file',
        'thumbnail',
        'added_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => MediaType::class,
        ];
    }

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by', 'id');
    }

    public function isVideo(): bool
    {
        return $this->type === MediaType::Video;
    }
}
