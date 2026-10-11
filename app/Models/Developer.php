<?php

namespace App\Models;

use App\Enums\DeveloperType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Developer extends Model
{
    protected $table = 'developers';

    protected $fillable = [
        'application_id',
        'developer_id',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'type' => DeveloperType::class,
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id', 'application_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'developer_id', 'id');
    }
}
