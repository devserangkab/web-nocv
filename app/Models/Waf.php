<?php

namespace App\Models;

use App\Enums\WafTech;
use App\Models\Casts\EncryptedAttr;
use App\Models\Concerns\GeneratesCode;
use App\Models\Concerns\OwnsMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Waf extends Model
{
    use GeneratesCode;
    use OwnsMedia;

    protected $table = 'wafs';

    protected static string $codeColumn = 'waf_id';

    protected static string $codePrefix = 'WAF-';

    protected static int $codeLength = 9;

    protected $fillable = [
        'vm_id',
        'server_id',
        'name',
        'internet_address',
        'internal_address',
        'tech',
        'username',
        'password',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'tech' => WafTech::class,
            'internet_address' => EncryptedAttr::class,
            'internal_address' => EncryptedAttr::class,
            'username' => EncryptedAttr::class,
            'password' => EncryptedAttr::class,
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
}
