<?php

namespace App\Models;

use App\Models\Concerns\GeneratesCode;
use App\Models\Concerns\OwnsMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use GeneratesCode;
    use OwnsMedia;

    protected static string $codeColumn = 'location_id';

    protected static string $codePrefix = 'LOC-';

    protected static int $codeLength = 9;

    protected $fillable = [
        'location_id',
        'name',
        'address',
    ];

    public function racks(): HasMany
    {
        return $this->hasMany(Rack::class, 'location_id', 'location_id');
    }

    /**
     * Server yang terhubung langsung ke server room tanpa rack.
     * Server di dalam rack tidak termasuk (rack_id terisi).
     */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class, 'location_id', 'location_id')->whereNull('rack_id');
    }

    /**
     * NAS yang terhubung langsung ke server room tanpa rack.
     */
    public function nas(): HasMany
    {
        return $this->hasMany(Nas::class, 'location_id', 'location_id')->whereNull('rack_id');
    }
}
