<?php

namespace App\Models;

use App\Models\Concerns\GeneratesCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rack extends Model
{
    use GeneratesCode;

    protected static string $codeColumn = 'rack_id';

    protected static string $codePrefix = 'RAK-';

    protected static int $codeLength = 9;

    protected $fillable = [
        'rack_id',
        'location_id',
        'name',
        'brand',
        'color',
        'units',
    ];

    protected function casts(): array
    {
        return [
            'units' => 'integer',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class, 'rack_id', 'rack_id');
    }
}
