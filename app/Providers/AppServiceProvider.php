<?php

namespace App\Providers;

use App\Models\User;
use App\Support\SecureCipher;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SecureCipher::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Superadmin melewati semua cek permission. User tanpa role
        // (akun legacy data lama) diperlakukan setara admin agar
        // prilaku aplikasi tidak berubah sebelum role disetinkan.
        Gate::before(function (User $user) {
            if ($user->roles->isEmpty() || $user->hasRole('superadmin')) {
                return true;
            }

            return null;
        });
    }
}
