<?php

namespace App\Models;

use App\Models\Concerns\GeneratesCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use GeneratesCode;

    protected $table = 'documents';

    protected static string $codeColumn = 'document_id';

    protected static string $codePrefix = 'DOC-';

    protected static int $codeLength = 9;

    protected $fillable = [
        'application_id',
        'added_by',
        'name',
        'document',
    ];

    protected static function booted(): void
    {
        // File dokumen ikut terhapus dari disk saat record dihapus
        static::deleting(function (self $document) {
            if (! blank($document->document)) {
                Storage::disk('public')->delete($document->document);
            }
        });
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id', 'application_id');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by', 'id');
    }
}
