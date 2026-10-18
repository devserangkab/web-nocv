<?php

use App\Actions\Auth\SetOwnPassword;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Layout('layouts::reset')]
    #[Title('Ganti Password')]

    #[Validate('required', message: 'Password baru wajib diisi.')]
    #[Validate('min:8', message: 'Password minimal 8 karakter.')]
    public string $password = '';

    #[Validate('required', message: 'Konfirmasi password wajib diisi.')]
    #[Validate('same:password', message: 'Konfirmasi password tidak cocok.')]
    public string $passwordConfirmation = '';

    public function save(): void
    {
        $this->validate();

        /** @var \App\Models\User $user */
        $user = Auth::user();

        app(SetOwnPassword::class)($user, $this->password);

        // Password berubah — regenerasi id session.
        session()->regenerate();

        $this->redirect(route('admin.dashboard'), navigate: true);
    }

    public function logout(): void
    {
        Auth::guard('web')->logout();

        session()->invalidate();
        session()->regenerateToken();

        $this->redirect(route('login'), navigate: true);
    }
};
?>

<div class="ina-card ina-card--stack overflow-visible! rounded-lg! bg-background-primary border border-stroke-primary p-8">
    {{-- Brand --}}
    <div class="flex items-center gap-3">
        {{-- <img
            src="{{ asset('favicon/android-chrome-192x192.png') }}"
            alt="Logo NOC"
            class="h-10 w-10 shrink-0 rounded-lg"
        > --}}
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-content-primary">{{ config('app.name', 'Laravel') }}</p>
            <p class="truncate text-xs text-content-tertiary">Network Operation Center Virtual</p>
        </div>
    </div>

    <div class="mt-6 border-t border-stroke-primary pt-6">
        <h1 class="text-md font-semibold text-content-primary">Ganti password Anda</h1>
        <p class="mt-1.5 text-xs text-content-secondary">
            Password awal dari admin dianggap sementara. Buat password baru untuk
            mengamankan akun sebelum masuk ke panel.
        </p>
    </div>

    <form class="mt-6 flex flex-col gap-4" wire:submit="save">
        <div class="ina-text-field">
            <label for="change-password" class="ina-text-field__label">
                Password baru
                <span class="ina-text-field__required">*</span>
            </label>
            <div class="ina-text-field__wrapper ina-text-field__wrapper--size-lg">
                <input
                    type="password"
                    id="change-password"
                    wire:model="password"
                    class="ina-text-field__input"
                    placeholder="minimal 8 karakter"
                    autocomplete="new-password"
                    required
                >
            </div>
            @error('password')
                <p class="mt-1.5 text-sm text-content-negative">{{ $message }}</p>
            @enderror
        </div>

        <div class="ina-text-field">
            <label for="change-password-confirmation" class="ina-text-field__label">
                Ulangi password baru
                <span class="ina-text-field__required">*</span>
            </label>
            <div class="ina-text-field__wrapper ina-text-field__wrapper--size-lg">
                <input
                    type="password"
                    id="change-password-confirmation"
                    wire:model="passwordConfirmation"
                    class="ina-text-field__input"
                    placeholder="ulangi password baru"
                    autocomplete="new-password"
                    required
                >
            </div>
            @error('passwordConfirmation')
                <p class="mt-1.5 text-sm text-content-negative">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            class="ina-button ina-button--primary ina-button--lg w-full"
        >
            <i class="ti ti-lock text-base" wire:loading.remove wire:target="save"></i>
            <i class="ti ti-loader animate-spin text-base" wire:loading wire:target="save"></i>
            Simpan password baru
        </button>
    </form>

    {{-- Satu-satunya jalan keluar: logout (flag tetap, dipaksa reset di login berikutnya) --}}
    <div class="mt-6 border-t border-stroke-primary pt-4 text-center">
        <button
            type="button"
            wire:click="logout"
            class="text-xs font-medium text-content-tertiary transition-colors duration-150 hover:text-content-primary"
        >
            Keluar
        </button>
    </div>
</div>
