<?php

namespace App\Models;

use App\Models\Concerns\GeneratesCode;
use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
    use GeneratesCode;

    protected $table = 'institutions';

    protected static string $codeColumn = 'institution_id';

    protected static string $codePrefix = 'INS-';

    protected static int $codeLength = 9;

    protected $fillable = [
        'institution_id',
        'name',
        'abbreviation',
    ];
}
