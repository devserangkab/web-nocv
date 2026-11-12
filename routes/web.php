<?php

use App\Actions\Reports\StoreReportAsset;
use App\Http\Controllers\ApplicationIconController;
use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\MediaShowController;
use App\Http\Controllers\ReportAssetController;
use App\Http\Controllers\ReportPrintController;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::auth.login')->name('login');

Route::middleware(['auth', 'force.password'])->group(function () {
    // Ganti password wajib (user baru / dipaksa admin)
    Route::livewire('/auth/change-password', 'pages::auth.change-password')
        ->name('auth.change-password');

    // Dashboard & topologi — visualisasi umum, terbuka untuk semua yang login
    Route::livewire('/admin/dashboard', 'pages::admin.dashboard')->name('admin.dashboard');
    Route::livewire('/admin/topology', 'pages::admin.topology.topology-index')->name('admin.topology.index');

    // Infrastruktur — halaman modul di-guard permission view modulnya
    Route::middleware('can:locations.view')->group(function () {
        Route::livewire('/admin/server-rooms', 'pages::admin.assets.location-index')->name('admin.server-rooms.index');
        Route::livewire('/admin/server-rooms/detail/{locationId}', 'pages::admin.assets.location-show')->name('admin.server-rooms.show');
    });

    Route::middleware('can:racks.view')->group(function () {
        Route::livewire('/admin/assets/racks', 'pages::admin.assets.rack-index')->name('admin.assets.racks.index');
        Route::livewire('/admin/assets/racks/detail/{rackId}', 'pages::admin.assets.rack-show')->name('admin.assets.racks.show');
    });

    Route::middleware('can:servers.view')->group(function () {
        Route::livewire('/admin/assets/servers', 'pages::admin.assets.server-index')->name('admin.assets.servers.index');
        Route::livewire('/admin/assets/servers/detail/{serverId}', 'pages::admin.assets.server-show')->name('admin.assets.servers.show');
    });

    // Virtualisasi: index terbuka bila punya salah satu view proxmox/vms;
    // halaman detail masing-masing di-guard view modulnya.
    Route::middleware('permission:proxmox.view|vms.view')->group(function () {
        Route::livewire('/admin/assets/virtualization', 'pages::admin.assets.virtualization-index')->name('admin.assets.virtualization.index');
    });
    Route::middleware('can:proxmox.view')->group(function () {
        Route::livewire('/admin/assets/proxmox/detail/{proxmoxId}', 'pages::admin.assets.proxmox-show')->name('admin.assets.proxmox.show');
    });
    Route::middleware('can:vms.view')->group(function () {
        Route::livewire('/admin/assets/vms/detail/{vmId}', 'pages::admin.assets.vms-show')->name('admin.assets.vms.show');
    });

    Route::middleware('can:panels.view')->group(function () {
        Route::livewire('/admin/assets/panels', 'pages::admin.assets.panels-index')->name('admin.assets.panels.index');
        Route::livewire('/admin/assets/panels/detail/{panelId}', 'pages::admin.assets.panel-show')->name('admin.assets.panels.show');
    });

    Route::middleware('can:wafs.view')->group(function () {
        Route::livewire('/admin/assets/wafs', 'pages::admin.assets.waf-index')->name('admin.assets.wafs.index');
        Route::livewire('/admin/assets/wafs/detail/{wafId}', 'pages::admin.assets.waf-show')->name('admin.assets.wafs.show');
    });

    Route::middleware('can:databases.view')->group(function () {
        Route::livewire('/admin/assets/databases', 'pages::admin.assets.database-index')->name('admin.assets.databases.index');
        Route::livewire('/admin/assets/databases/detail/{dbiId}', 'pages::admin.assets.database-show')->name('admin.assets.databases.show');
    });

    Route::middleware('can:nas.view')->group(function () {
        Route::livewire('/admin/assets/nas', 'pages::admin.assets.nas-index')->name('admin.assets.nas.index');
        Route::livewire('/admin/assets/nas/detail/{nasId}', 'pages::admin.assets.nas-show')->name('admin.assets.nas.show');
    });

    // Aplikasi
    Route::middleware('can:applications.view')->group(function () {
        Route::livewire('/admin/applications', 'pages::admin.application.application-index')->name('admin.applications.index');
        Route::livewire('/admin/applications/detail/{applicationId}', 'pages::admin.application.application-show')->name('admin.applications.show');
        Route::get('/admin/applications/{applicationId}/icon', ApplicationIconController::class)->name('admin.applications.icon');
    });

    // Aktivitas
    Route::middleware('can:activities.view')->group(function () {
        Route::livewire('/admin/activities', 'pages::admin.activity.activity-index')->name('admin.activities.index');
    });

    // Laporan Bulanan
    Route::middleware('can:monthly-reports.view')->group(function () {
        Route::livewire('/admin/reports', 'pages::admin.report.report-index')->name('admin.reports.index');
        Route::livewire('/admin/reports/{reportId}/edit', 'pages::admin.report.report-edit')->name('admin.reports.edit');
        Route::get('/admin/reports/{reportId}/print', ReportPrintController::class)->name('admin.reports.print');

        // Upload gambar laporan (privat — dipakai oleh editor tiptap)
        Route::post('/admin/report-assets/upload', function () {
            $path = app(StoreReportAsset::class)(request()->file('file'), auth()->id());

            return response()->json(['url' => url('/admin/report-assets/'.basename($path['path']))]);
        })->name('admin.report-assets.store');

        // Tampilkan asset gambar laporan (privat, semua user yang login dapat mengakses)
        Route::get('/admin/report-assets/{path}', ReportAssetController::class)
            ->where('path', '.*')
            ->name('admin.report-assets.show');
    });

    // Pengaturan — dipecah per entitas (user, role, institution)
    Route::middleware('can:users.view')->group(function () {
        Route::livewire('/admin/users', 'pages::admin.management.user-index')->name('admin.users.index');
    });
    Route::middleware('can:roles.view')->group(function () {
        Route::livewire('/admin/roles', 'pages::admin.management.role-index')->name('admin.roles.index');
    });
    Route::middleware('can:institutions.view')->group(function () {
        Route::livewire('/admin/institutions', 'pages::admin.management.institution-index')->name('admin.institutions.index');
    });

    // Tool developer (superadmin, developer)
    Route::middleware('can:devtools.manage')->group(function () {
        Route::livewire('/admin/dev/bcrypt', 'pages::admin.dev.bcrypt-tool')
            ->name('admin.dev.bcrypt');
    });

    // Sinkronisasi — halaman khusus superadmin. Cek role langsung (bukan can:)
    // karena Gate::before juga meneruskan akun tanpa role ke semua permission.
    Route::middleware('role:superadmin')->group(function () {
        Route::livewire('/admin/synchronization', 'pages::admin.synchronization.sync-index')
            ->name('admin.synchronization.index');
    });

    Route::livewire('/admin/settings', 'pages::admin.settings')->name('admin.settings.index');

    // Dokumentasi & Gallery — halaman di-guard view; file tetap login-only
    // karena dipakai lintas modul (dashboard, panel aset, show aplikasi).
    Route::middleware('can:documents.view')->group(function () {
        Route::livewire('/admin/documents', 'pages::admin.document.document-index')->name('admin.documents.index');
    });
    Route::get('/admin/documents/{documentId}/download', DocumentDownloadController::class)
        ->name('admin.documents.download');

    Route::middleware('can:media.view')->group(function () {
        Route::livewire('/admin/gallery', 'pages::admin.gallery.gallery-index')->name('admin.galleries.index');
    });
    Route::get('/admin/media/{mediaId}', MediaShowController::class)
        ->name('admin.media.show');

    Route::post('/logout', function () {
        Auth::guard('web')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
