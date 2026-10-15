<?php

namespace App\Models;

use App\Enums\DocumentCategory;
use App\Models\Concerns\GeneratesCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use GeneratesCode;

    protected $table = 'documents';

    protected static string $codeColumn = 'document_id';

    protected static string $codePrefix = 'DOC-';

    protected static int $codeLength = 9;

    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'category',
        'application_id',
        'added_by',
        'name',
        'document',
    ];

    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
        ];
    }

    protected static function booted(): void
    {
        // Dokumen yang dibuat via application_id (data lama/seeder) otomatis
        // ter-link ke entitas application agar relasi polymorphic konsisten.
        static::saving(function (self $document) {
            if (blank($document->documentable_type) && filled($document->application_id)) {
                $application = Application::query()
                    ->where('application_id', $document->application_id)
                    ->first();

                if ($application !== null) {
                    $document->documentable_type = Application::class;
                    $document->documentable_id = $application->id;
                }
            }
        });

        // File dokumen ikut terhapus dari disk saat record dihapus
        static::deleting(function (self $document) {
            if (! blank($document->document)) {
                Storage::disk('private')->delete($document->document);
                Storage::disk('public')->delete($document->document);
            }
        });
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
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
