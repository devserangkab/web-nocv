<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportAssetController extends Controller
{
    /**
     * Tampilkan asset gambar laporan (disk private) dengan otorisasi login.
     * Path berasal dari URL /admin/report-assets/{filename}.
     */
    public function __invoke(string $filename): StreamedResponse
    {
        $fullPath = 'report-assets/'.$filename;

        $disk = Storage::disk('private');

        if (! $disk->exists($fullPath)) {
            abort(404);
        }

        return $disk->response($fullPath, $filename, [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
