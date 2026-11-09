<?php

namespace App\Models;

use App\Enums\ServerStatus;
use App\Models\Casts\EncryptedAttr;
use App\Models\Concerns\GeneratesCode;
use App\Models\Concerns\OwnsMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Server extends Model
{
    use GeneratesCode;
    use OwnsMedia;

    protected static string $codeColumn = 'server_id';

    protected static string $codePrefix = 'SVR-';

    protected static int $codeLength = 9;

    protected $attributes = [
        'status' => 'OFF',
    ];

    protected $fillable = [
        'server_id',
        'server_name',
        'rack_id',
        'location_id',
        'position',
        'brand',
        'storage_size',
        'storage',
        'memory_size',
        'memory',
        'cpu',
        'static_ip',
        'public_ip',
        'username',
        'password',
        'os',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'storage_size' => 'integer',
            'memory_size' => 'integer',
            'status' => ServerStatus::class,
            'static_ip' => EncryptedAttr::class,
            'public_ip' => EncryptedAttr::class,
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
     * Lokasi efektif: dari rack (jika ada) atau lokasi langsung (server tanpa rack).
     */
    public function effective(): ?Location
    {
        return $this->rack?->location ?? $this->location;
    }

    public function proxmox(): HasOne
    {
        return $this->hasOne(Proxmox::class, 'server_id', 'server_id');
    }

    /**
     * Panel yang dipasang langsung di server (tanpa VM).
     */
    public function panels(): HasMany
    {
        return $this->hasMany(Panel::class, 'server_id', 'server_id');
    }

    public function wafs(): HasMany
    {
        return $this->hasMany(Waf::class, 'server_id', 'server_id');
    }

    public function databases(): HasMany
    {
        return $this->hasMany(DatabaseInstance::class, 'server_id', 'server_id');
    }

    /**
     * Aplikasi yang dihosting langsung di server ini.
     */
    public function hostings(): MorphMany
    {
        return $this->morphMany(Hosting::class, 'hostable');
    }
}
