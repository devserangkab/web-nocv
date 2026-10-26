<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot aplikasi ↔ database schema. User database dirujuk lewat
 * db_user_id (katalog database_users); null = aplikasi memakai
 * kredensial root/admin instance.
 */
class ApplicationDatabase extends Pivot
{
    protected $table = 'application_databases';

    protected $fillable = [
        'application_id',
        'db_id',
        'db_user_id',
        'note',
    ];

    public function dbUser(): BelongsTo
    {
        return $this->belongsTo(DatabaseUser::class, 'db_user_id', 'dbu_id');
    }
}
