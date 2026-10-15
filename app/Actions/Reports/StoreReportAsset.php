<?php

namespace App\Actions\Reports;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

class StoreReportAsset
{
    /**
     * Simpan gambar yang di-embed ke editor laporan (disk private),
     * dikembalikan sebagai path untuk layout (URL /admin/report-assets/{asset}).
     */
    public function __invoke(UploadedFile $file, ?int $userId): array
    {
        $validated = Validator::make(
            ['file' => $file],
            ['file' => ['image', 'max:4096']],
            [
                'file.image' => 'File harus berupa gambar (jpg, png, webp).',
                'file.max' => 'Ukuran gambar maksimal 4 MB.',
            ],
        )->validate();

        $path = $file->store('report-assets', 'private');

        return [
            'path' => $path,
        ];
    }
}
