<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDownloadController extends Controller
{
    /**
     * Unduh dokumen dengan otorisasi. Semua user yang login dapat mengunduh,
     * anonim tidak. File dicari di disk private terlebih dahulu; disk public
     * hanya fallback untuk file lama sebelum migrasi ke private. With preview=1,
     * file yang didukung (pdf/gambar) mengalir inline untuk preview browser.
     */
    public function __invoke(string $documentId, Request $request): StreamedResponse
    {
        $document = Document::query()
            ->with('documentable')
            ->where('document_id', $documentId)
            ->firstOrFail();

        if (blank($document->document)) {
            abort(404);
        }

        $disk = Storage::disk('private');

        if (! $disk->exists($document->document)) {
            $disk = Storage::disk('public');

            if (! $disk->exists($document->document)) {
                abort(404);
            }
        }

        $extension = strtolower(pathinfo((string) $document->document, PATHINFO_EXTENSION));
        $inline = $request->boolean('preview') && in_array($extension, ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'txt', 'csv', 'md'], true);

        if ($inline) {
            return $disk->response($document->document, basename((string) $document->document));
        }

        return $disk->download(
            $document->document,
            basename((string) $document->document),
        );
    }
}
