<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Hosting extends Model
{
    protected $table = 'hostings';

    protected $fillable = [
        'application_id',
        'hostable_type',
        'hostable_id',
        'name',
        'note',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id', 'application_id');
    }

    public function hostable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Jenis host: 'vm', 'server', atau 'external'.
     */
    public function kind(): string
    {
        return match (true) {
            $this->hostable_type === Vms::class => 'vm',
            $this->hostable_type === Server::class => 'server',
            default => 'external',
        };
    }
}
