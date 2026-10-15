<?php

namespace App\Models;

use App\Enums\VmsStatus;
use App\Models\Casts\EncryptedAttr;
use App\Models\Concerns\GeneratesCode;
use App\Models\Concerns\OwnsMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Vms extends Model
{
    use GeneratesCode;
    use OwnsMedia;

    protected $table = 'vms';

    protected static string $codeColumn = 'vm_id';

    protected static string $codePrefix = 'VMS-';

    protected static int $codeLength = 9;

    protected $attributes = [
        'status' => 'OFF',
    ];

    protected $fillable = [
        'vm_id',
        'proxmox_id',
        'proxmox_number',
        'name',
        'local_ip',
        'public_ip',
        'username',
        'password',
        'cpu',
        'ram',
        'storage',
        'os',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'cpu' => 'integer',
            'ram' => 'integer',
            'storage' => 'integer',
            'status' => VmsStatus::class,
            'local_ip' => EncryptedAttr::class,
            'public_ip' => EncryptedAttr::class,
            'username' => EncryptedAttr::class,
            'password' => EncryptedAttr::class,
        ];
    }

    public function proxmox(): BelongsTo
    {
        return $this->belongsTo(Proxmox::class, 'proxmox_id', 'proxmox_id');
    }

    /**
     * Panel yang dipasang langsung di VM ini.
     */
    public function panels(): HasMany
    {
        return $this->hasMany(Panel::class, 'vm_id', 'vm_id');
    }

    /**
     * Aplikasi yang dihosting di VM ini.
     */
    public function hostings(): MorphMany
    {
        return $this->morphMany(Hosting::class, 'hostable');
    }
}
