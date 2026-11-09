<?php

namespace App\Models;

use App\Enums\PanelType;
use App\Models\Casts\EncryptedAttr;
use App\Models\Concerns\GeneratesCode;
use App\Models\Concerns\OwnsMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Panel extends Model
{
    use GeneratesCode;
    use OwnsMedia;

    protected $table = 'panels';

    protected static string $codeColumn = 'panel_id';

    protected static string $codePrefix = 'PNL-';

    protected static int $codeLength = 9;

    protected $fillable = [
        'vm_id',
        'server_id',
        'name',
        'internet_address',
        'internal_address',
        'type',
        'username',
        'password',
        'api_key',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'type' => PanelType::class,
            'internet_address' => EncryptedAttr::class,
            'internal_address' => EncryptedAttr::class,
            'username' => EncryptedAttr::class,
            'password' => EncryptedAttr::class,
            'api_key' => EncryptedAttr::class,
        ];
    }

    public function vm(): BelongsTo
    {
        return $this->belongsTo(Vms::class, 'vm_id', 'vm_id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id', 'server_id');
    }

    public function databases(): HasMany
    {
        return $this->hasMany(DatabaseInstance::class, 'panel_id', 'panel_id');
    }
}
