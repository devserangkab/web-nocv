<?php

namespace App\Models;

use App\Enums\SyncSource;
use App\Models\Concerns\GeneratesCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SyncRun extends Model
{
    use GeneratesCode;
    use HasFactory;

    protected $table = 'sync_runs';

    protected static string $codeColumn = 'run_id';

    protected static string $codePrefix = 'SYR-';

    protected static int $codeLength = 9;

    protected $fillable = [
        'run_group',
        'source',
        'panel_id',
        'status',
        'total_sites',
        'total_new',
        'total_changed',
        'total_unchanged',
        'message',
        'triggered_by',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'source' => SyncSource::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function panel(): BelongsTo
    {
        return $this->belongsTo(Panel::class, 'panel_id', 'panel_id');
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by', 'id');
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(SyncProposal::class, 'sync_run_id', 'id');
    }
}
