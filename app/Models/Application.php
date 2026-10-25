<?php

namespace App\Models;

use App\Enums\ApplicationDatabase;
use App\Enums\ApplicationFramework;
use App\Enums\ApplicationLanguage;
use App\Enums\ApplicationOwnership;
use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Models\Concerns\GeneratesCode;
use App\Models\Concerns\OwnsMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    use GeneratesCode;
    use OwnsMedia;

    protected $table = 'applications';

    protected static string $codeColumn = 'application_id';

    protected static string $codePrefix = 'APP-';

    protected static int $codeLength = 9;

    protected $fillable = [
        'name',
        'abbreviation',
        'description',
        'status',
        'requested_by',
        'domain',
        'repo_url',
        'icon',
        'framework',
        'framework_version',
        'language',
        'language_version',
        'database',
        'database_version',
        'type',
        'ownership',
        'vendor_name',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'type' => ApplicationType::class,
            'framework' => ApplicationFramework::class,
            'language' => ApplicationLanguage::class,
            'database' => ApplicationDatabase::class,
            'ownership' => ApplicationOwnership::class,
        ];
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'requested_by', 'id');
    }

    public function developers(): HasMany
    {
        return $this->hasMany(Developer::class, 'application_id', 'application_id');
    }

    public function hostings(): HasMany
    {
        return $this->hasMany(Hosting::class, 'application_id', 'application_id');
    }
}
