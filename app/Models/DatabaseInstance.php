<?php

namespace App\Models;

use App\Enums\DatabaseTech;
use App\Models\Casts\EncryptedAttr;
use App\Models\Concerns\GeneratesCode;
use App\Models\Concerns\OwnsMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DatabaseInstance extends Model
{
    use GeneratesCode;
    use OwnsMedia;

    protected $table = 'database_instances';

    protected static string $codeColumn = 'dbi_id';

    protected static string $codePrefix = 'DBS-';

    protected static int $codeLength = 9;

    protected $fillable = [
        'server_id',
        'vm_id',
        'panel_id',
        'name',
        'tech',
        'version',
        'internal_address',
        'username',
        'password',
    ];

    protected function casts(): array
    {
        return [
            'tech' => DatabaseTech::class,
            'internal_address' => EncryptedAttr::class,
            'username' => EncryptedAttr::class,
            'password' => EncryptedAttr::class,
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id', 'server_id');
    }

    public function vm(): BelongsTo
    {
        return $this->belongsTo(Vms::class, 'vm_id', 'vm_id');
    }

    public function panel(): BelongsTo
    {
        return $this->belongsTo(Panel::class, 'panel_id', 'panel_id');
    }

    public function schemas(): HasMany
    {
        return $this->hasMany(Database::class, 'dbi_id', 'dbi_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(DatabaseUser::class, 'dbi_id', 'dbi_id');
    }

    /**
     * Host efektif: panel, VM, atau server tempat instance ter-install.
     */
    public function host(): ?Model
    {
        return $this->panel ?? $this->vm ?? $this->server;
    }
}
