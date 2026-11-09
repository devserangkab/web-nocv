<?php

namespace App\Models;

use App\Enums\SyncProposalAction;
use App\Enums\SyncProposalStatus;
use App\Enums\SyncSource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncProposal extends Model
{
    use HasFactory;

    protected $table = 'sync_proposals';

    protected $fillable = [
        'sync_run_id',
        'source',
        'panel_id',
        'application_id',
        'site_name',
        'action',
        'panel_data',
        'changes',
        'status',
        'applied_by',
        'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'source' => SyncSource::class,
            'action' => SyncProposalAction::class,
            'status' => SyncProposalStatus::class,
            'panel_data' => 'array',
            'changes' => 'array',
            'applied_at' => 'datetime',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(SyncRun::class, 'sync_run_id', 'id');
    }

    public function panel(): BelongsTo
    {
        return $this->belongsTo(Panel::class, 'panel_id', 'panel_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id', 'application_id');
    }
}
