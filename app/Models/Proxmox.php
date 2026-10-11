<?php

namespace App\Models;

use App\Models\Casts\EncryptedAttr;
use App\Models\Concerns\GeneratesCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proxmox extends Model
{
    use GeneratesCode;

    protected $table = 'proxmoxs';

    protected static string $codeColumn = 'proxmox_id';

    protected static string $codePrefix = 'PMX-';

    protected static int $codeLength = 9;

    protected $fillable = [
        'proxmox_id',
        'name',
        'url',
        'username',
        'password',
        'version',
        'server_id',
    ];

    protected function casts(): array
    {
        return [
            'url' => EncryptedAttr::class,
            'username' => EncryptedAttr::class,
            'password' => EncryptedAttr::class,
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id', 'server_id');
    }

    public function vms(): HasMany
    {
        return $this->hasMany(Vms::class, 'proxmox_id', 'proxmox_id');
    }
}
