<?php

use App\Actions\Reports\StoreReportAsset;
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

    Route::livewire('/admin/dashboard', 'pages::admin.dashboard')->name('admin.dashboard');

    // Infrastruktur
    Route::livewire('/admin/server-rooms', 'pages::admin.assets.location-index')->name('admin.server-rooms.index');
    Route::livewire('/admin/server-rooms/detail/{locationId}', 'pages::admin.assets.location-show')->name('admin.server-rooms.show');
    Route::livewire('/admin/assets/racks', 'pages::admin.assets.rack-index')->name('admin.assets.racks.index');
    Route::livewire('/admin/assets/racks/detail/{rackId}', 'pages::admin.assets.rack-show')->name('admin.assets.racks.show');
    Route::livewire('/admin/assets/servers', 'pages::admin.assets.server-index')->name('admin.assets.servers.index');
    Route::livewire('/admin/assets/servers/detail/{serverId}', 'pages::admin.assets.server-show')->name('admin.assets.servers.show');
    Route::livewire('/admin/assets/virtualization', 'pages::admin.assets.virtualization-index')->name('admin.assets.virtualization.index');
    Route::livewire('/admin/assets/proxmox/detail/{proxmoxId}', 'pages::admin.assets.proxmox-show')->name('admin.assets.proxmox.show');
    Route::livewire('/admin/assets/vms/detail/{vmId}', 'pages::admin.assets.vms-show')->name('admin.assets.vms.show');
    Route::livewire('/admin/assets/panels', 'pages::admin.assets.panels-index')->name('admin.assets.panels.index');
    Route::livewire('/admin/assets/panels/detail/{panelId}', 'pages::admin.assets.panel-show')->name('admin.assets.panels.show');
    Route::livewire('/admin/assets/wafs', 'pages::admin.assets.waf-index')->name('admin.assets.wafs.index');
    Route::livewire('/admin/assets/wafs/detail/{wafId}', 'pages::admin.assets.waf-show')->name('admin.assets.wafs.show');
    Route::livewire('/admin/assets/nas', 'pages::admin.assets.nas-index')->name('admin.assets.nas.index');
    Route::livewire('/admin/assets/nas/detail/{nasId}', 'pages::admin.assets.nas-show')->name('admin.assets.nas.show');

    // Aplikasi
    Route::livewire('/admin/applications', 'pages::admin.application.application-index')->name('admin.applications.index');
    Route::livewire('/admin/applications/detail/{applicationId}', 'pages::admin.application.application-show')->name('admin.applications.show');

    // Aktivitas
    Route::livewire('/admin/activities', 'pages::admin.activity.activity-index')->name('admin.activities.index');

    // Laporan Bulanan
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

    // Topology
    Route::livewire('/admin/topology', 'pages::admin.topology.topology-index')->name('admin.topology.index');

    // Pengaturan — hanya role yang berwenang (superadmin & user legacy)
    Route::middleware('can:management.manage')->group(function () {
        Route::livewire('/admin/users', 'pages::admin.management.user-index')->name('admin.users.index');
        Route::livewire('/admin/roles', 'pages::admin.management.role-index')->name('admin.roles.index');
        Route::livewire('/admin/institutions', 'pages::admin.management.institution-index')->name('admin.institutions.index');
    });

    // Tool developer (superadmin, developer)
    Route::middleware('can:devtools.manage')->group(function () {
        Route::livewire('/admin/dev/bcrypt', 'pages::admin.dev.bcrypt-tool')
            ->name('admin.dev.bcrypt');
    });

    Route::livewire('/admin/settings', 'pages::admin.settings')->name('admin.settings.index');

    // Dokumentasi & Gallery
    Route::livewire('/admin/documents', 'pages::admin.document.document-index')->name('admin.documents.index');
    Route::livewire('/admin/gallery', 'pages::admin.gallery.gallery-index')->name('admin.galleries.index');

    // Unduh/preview dokumen (privat, semua user yang login dapat mengakses)
    Route::get('/admin/documents/{documentId}/download', DocumentDownloadController::class)
        ->name('admin.documents.download');

    // Tampilkan file/thumbnail media (privat, semua user yang login dapat mengakses)
    Route::get('/admin/media/{mediaId}', MediaShowController::class)
        ->name('admin.media.show');

    Route::post('/logout', function () {
        Auth::guard('web')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
