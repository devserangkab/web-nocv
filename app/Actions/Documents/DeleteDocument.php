<?php

namespace App\Actions\Documents;

use App\Actions\Activities\RecordActivity;
use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteDocument
{
    public function __invoke(Document $document): bool
    {
        return DB::transaction(function () use ($document): bool {
            if (filled($document->document)) {
                Storage::disk('private')->delete($document->document);
                Storage::disk('public')->delete($document->document);
            }

            $deleted = $document->delete();

            app(RecordActivity::class)->audit($document, 'Menghapus dokumen '.$document->name);

            return $deleted;
        });
    }
}
