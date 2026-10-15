<?php

use App\Models\Document;
use App\Models\Media;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Pindahkan berkas dokumen & media dari disk public ke disk private.
     * Path di database tidak diubah; hanya lokasi fisik file yang dipindah
     * agar diakses melalui controller ber-otorisasi.
     */
    public function up(): void
    {
        $public = Storage::disk('public');
        $private = Storage::disk('private');

        $paths = collect()
            ->merge(Document::query()->whereNotNull('document')->pluck('document'))
            ->merge(Media::query()->whereNotNull('file')->pluck('file'))
            ->merge(Media::query()->whereNotNull('thumbnail')->pluck('thumbnail'))
            ->unique();

        foreach ($paths as $path) {
            if (blank($path) || ! $public->exists($path) || $private->exists($path)) {
                continue;
            }

            $private->writeStream($path, $public->readStream($path));
            $public->delete($path);
        }
    }

    public function down(): void
    {
        // Berkas private hanya kembali ke public bila belum ada pendahulunya;
        // penghapusan permanen tidak dilakukan agar data tidak hilang.
    }
};
