<?php

namespace App\Models;

use App\Enums\HostingKind;
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
     * Jenis host internal (Vms/Server/Panel) atau null bila eksternal.
     */
    public function kind(): ?HostingKind
    {
        return HostingKind::fromHostable($this->hostable_type);
    }
}
