<?php

namespace App\Support;

use App\Models\Document;
use App\Models\Media;
use App\Models\MonthlyReport;

/**
 * Gerbang otorisasi untuk Action class (permission format modul.aksi).
 * Superadmin & user legacy tanpa role dilewatkan otomatis oleh Gate::before.
 */
class Authorize
{
    /**
     * Tolak bila user tidak punya permission tunggal (mis. racks.create).
     */
    public static function permission(string $permission, string $message = 'Tidak berwenang melakukan aksi ini.'): void
    {
        $user = auth()->user();

        // Konteks sistem/konsol (tanpa user HTTP) — rute sudah di-guard login.
        if ($user === null) {
            return;
        }

        abort_unless($user->can($permission), 403, $message);
    }

    public static function documentDelete(Document $document): void
    {
        self::ownOrPermissions(
            $document->added_by,
            'documents.delete',
            'documents.delete-own',
            'Tidak berwenang menghapus dokumen milik orang lain.'
        );
    }

    public static function mediaDelete(Media $media): void
    {
        self::ownOrPermissions(
            $media->added_by,
            'media.delete',
            'media.delete-own',
            'Tidak berwenang menghapus media milik orang lain.'
        );
    }

    public static function monthlyReportOwn(MonthlyReport $report): void
    {
        self::ownOrPermissions(
            $report->created_by,
            'monthly-reports.view-all',
            'monthly-reports.update-own',
            'Laporan bulanan hanya dapat dikelola oleh pembuatnya.'
        );
    }

    private static function ownOrPermissions(?int $ownerId, string $allPermission, string $ownPermission, string $message): void
    {
        $user = auth()->user();

        // Konteks sistem/konsol (tanpa user HTTP) — rute sudah di-guard login.
        if ($user === null) {
            return;
        }

        if ($user->can($allPermission)) {
            return;
        }

        abort_unless(
            $user->can($ownPermission) && $ownerId === $user->id,
            403,
            $message
        );
    }
}
