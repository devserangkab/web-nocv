<?php

namespace App\Models;

use App\Models\Casts\EncryptedAttr;
use App\Models\Concerns\GeneratesCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vms extends Model
{
    use GeneratesCode;

    protected $table = 'vms';

    protected static string $codeColumn = 'vm_id';

    protected static string $codePrefix = 'VMS-';

    protected static int $codeLength = 9;

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
            'status' => 'boolean',
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
}
