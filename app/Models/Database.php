<?php

namespace App\Models;

use App\Models\Concerns\GeneratesCode;
use App\Models\Concerns\OwnsMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Database extends Model
{
    use GeneratesCode;
    use OwnsMedia;

    protected $table = 'database_schemas';

    protected static string $codeColumn = 'db_id';

    protected static string $codePrefix = 'DB-';

    protected static int $codeLength = 8;

    protected $fillable = [
        'dbi_id',
        'name',
        'note',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(DatabaseInstance::class, 'dbi_id', 'dbi_id');
    }

    /**
     * Aplikasi pemakai schema ini, dengan kredensial per-aplikasi di pivot.
     */
    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'application_databases', 'db_id', 'application_id', 'db_id', 'application_id')
            ->using(ApplicationDatabase::class)
            ->withPivot('db_user_id', 'note');
    }

    /**
     * User database katalog di instance schema ini.
     */
    public function users(): HasMany
    {
        return $this->instance->users();
    }
}
