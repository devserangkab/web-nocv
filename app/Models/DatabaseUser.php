<?php

namespace App\Models;

use App\Models\Casts\EncryptedAttr;
use App\Models\Concerns\GeneratesCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Katalog user database di sebuah instance. Aplikasi yang memakai user ini
 * menautkannya lewat pivot application_databases (tanpa duplikasi kredensial).
 */
class DatabaseUser extends Model
{
    use GeneratesCode;

    protected $table = 'database_users';

    protected static string $codeColumn = 'dbu_id';

    protected static string $codePrefix = 'DBU-';

    protected static int $codeLength = 9;

    protected $fillable = [
        'dbi_id',
        'username',
        'password',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'password' => EncryptedAttr::class,
        ];
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(DatabaseInstance::class, 'dbi_id', 'dbi_id');
    }
}
