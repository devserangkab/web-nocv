<?php

namespace App\Models;

use App\Models\Concerns\GeneratesCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use GeneratesCode;

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
}
