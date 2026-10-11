<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::auth.login')->name('login');

Route::middleware('auth')->group(function () {
    Route::livewire('/admin/dashboard', 'pages::admin.dashboard')->name('admin.dashboard');
    Route::livewire('/admin/server-rooms', 'pages::admin.assets.location-index')->name('admin.server-rooms.index');
    Route::livewire('/admin/assets/servers', 'pages::admin.assets.server-index')->name('admin.assets.servers.index');
    Route::livewire('/admin/assets/racks', 'pages::admin.assets.rack-index')->name('admin.assets.racks.index');
    Route::livewire('/admin/assets/proxmox', 'pages::admin.assets.proxmox-index')->name('admin.assets.proxmox.index');
    Route::livewire('/admin/assets/vms', 'pages::admin.assets.vms-index')->name('admin.assets.vms.index');
    Route::livewire('/admin/assets/panels', 'pages::admin.assets.panels-index')->name('admin.assets.panels.index');
    Route::livewire('/admin/users', 'pages::admin.management.user-index')->name('admin.users.index');
    Route::livewire('/admin/roles', 'pages::admin.management.role-index')->name('admin.roles.index');
    Route::livewire('/admin/institutions', 'pages::admin.management.institution-index')->name('admin.institutions.index');
    Route::livewire('/admin/settings', 'pages::admin.settings')->name('admin.settings.index');
    Route::livewire('/admin/applications', 'pages::admin.application.application-index')->name('admin.applications.index');

    Route::post('/logout', function () {
        Auth::guard('web')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
