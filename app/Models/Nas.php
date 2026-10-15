<?php

namespace App\Models;

use App\Enums\NasStatus;
use App\Models\Casts\EncryptedAttr;
use App\Models\Concerns\GeneratesCode;
use App\Models\Concerns\OwnsMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Nas extends Model
{
    use GeneratesCode;
    /** @use HasFactory<NasFactory> */
    use HasFactory;

    use OwnsMedia;

    protected static string $codeColumn = 'nas_id';

    protected static string $codePrefix = 'NAS-';

    protected static int $codeLength = 9;

    protected $attributes = [
        'status' => 'OFF',
    ];

    protected $fillable = [
        'nas_id',
        'rack_id',
        'location_id',
        'name',
        'brand',
        'storage_size',
        'bays',
        'raid_level',
        'os',
        'static_ip',
        'username',
        'password',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'storage_size' => 'integer',
            'bays' => 'integer',
            'status' => NasStatus::class,
            'static_ip' => EncryptedAttr::class,
            'username' => EncryptedAttr::class,
            'password' => EncryptedAttr::class,
        ];
    }

    public function rack(): BelongsTo
    {
        return $this->belongsTo(Rack::class, 'rack_id', 'rack_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }

    /**
     * Lokasi efektif: dari rack (jika ada) atau lokasi langsung (NAS tanpa rack).
     */
    public function effective(): ?Location
    {
        return $this->rack?->location ?? $this->location;
    }
}
