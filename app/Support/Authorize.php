<?php

namespace App\Support;

use App\Models\Document;
use App\Models\Media;
use App\Models\MonthlyReport;

/**
 * Gerbang otorisasi koarse-grained untuk Action class.
 * Superadmin & user legacy tanpa role dilewatkan otomatis oleh Gate::before.
 */
class Authorize
{
    public static function infrastructureManage(): void
    {
        self::deny('infrastructure.manage', 'Tidak berwenang mengubah data infrastruktur.');
    }

    public static function applicationsManage(): void
    {
        self::deny('applications.manage', 'Tidak berwenang mengubah application.');
    }

    public static function managementManage(): void
    {
        self::deny('management.manage', 'Tidak berwenang mengelola user, role, dan institution.');
    }

    public static function documentsCreate(): void
    {
        self::deny('documents.create', 'Tidak berwenang mengunggah dokumen.');
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

    public static function mediaCreate(): void
    {
        self::deny('media.create', 'Tidak berwenang mengunggah media gallery.');
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

    public static function activitiesCreate(): void
    {
        self::deny('activities.create', 'Tidak berwenang mencatat work log.');
    }

    public static function monthlyReportsCreate(): void
    {
        self::deny('monthly-reports.create', 'Tidak berwenang membuat laporan bulanan.');
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

    private static function deny(string $permission, string $message): void
    {
        $user = auth()->user();

        // Konteks sistem/konsol (tanpa user HTTP) — rute sudah di-guard login.
        if ($user === null) {
            return;
        }

        abort_unless($user->can($permission), 403, $message);
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
