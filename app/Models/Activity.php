<?php

namespace App\Models;

use App\Enums\ActivityType;
use App\Models\Concerns\GeneratesCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Activity extends Model
{
    use GeneratesCode;

    protected $table = 'activities';

    protected static string $codeColumn = 'activity_id';

    protected static string $codePrefix = 'ACT-';

    protected static int $codeLength = 9;

    protected $fillable = [
        'activity_id',
        'subject_type',
        'subject_id',
        'user_id',
        'activity',
        'description',
        'type',
        'category',
        'finished_at',
    ];

    protected $attributes = [
        'type' => 'WORK',
    ];

    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'finished_at' => 'datetime',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
