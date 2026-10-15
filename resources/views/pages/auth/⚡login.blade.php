<?php

use App\Actions\Auth\GenerateMathCaptcha;
use App\Actions\Auth\ValidateMathCaptcha;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Layout('layouts::auth')]
    #[Title('Masuk | appdoc')]
    #[Validate('required', message: 'Email wajib diisi.')]
    #[Validate('email', message: 'Format email tidak valid.')]
    public string $email = '';

    #[Validate('required', message: 'Password wajib diisi.')]
    public string $password = '';

    #[Validate('required', message: 'Verifikasi matematika wajib dijawab.')]
    public string $captchaAnswer = '';

    public string $captchaId = '';

    public string $captchaQuestion = '';

    public array $captchaOptions = [];

    public function mount(): void
    {
        $this->refreshCaptcha();
    }

    public function refreshCaptcha(): void
    {
        $challenge = app(GenerateMathCaptcha::class)();

        $this->captchaId = $challenge['id'];
        $this->captchaQuestion = $challenge['question'];
        $this->captchaOptions = $challenge['options'];
        $this->captchaAnswer = '';
    }

    public function login(): void
    {
        $this->validate();

        // Captcha dicek sebelum kredensial — gagal tidak membocorkan
        // validitas email/password. Challenge single-use, jadi selalu
        // dibuat ulang setelah dipakai (benar maupun salah).
        if (! app(ValidateMathCaptcha::class)($this->captchaAnswer, $this->captchaId)) {
            $this->addError('captchaAnswer', 'Jawaban captcha salah atau sudah kedaluwarsa.');

            $this->refreshCaptcha();

            return;
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            $this->addError('email', 'Email atau password salah.');

            $this->refreshCaptcha();

            return;
        }

        session()->regenerate();

        $this->redirect(route('admin.dashboard'));
    }
};
?>

<div class="mx-auto flex w-full max-w-7xl flex-col md:flex-row">
    {{-- Toggle tema — pojok kanan atas viewport --}}
    <div class="fixed top-4 right-4 z-40">
        <button
            type="button"
            class="flex h-9 w-9 items-center justify-center rounded-md border border-stroke-primary bg-background-primary text-content-tertiary transition-colors duration-150 hover:text-content-primary"
            x-data="{ dark: document.documentElement.dataset.theme === 'dark' }"
            x-on:theme-sync.window="dark = $event.detail.dark"
            x-on:click="dark = !dark; localStorage.setItem('theme', dark ? 'dark' : 'light'); document.cookie = 'theme=' + (dark ? 'dark' : 'light') + '; path=/; max-age=31536000; samesite=lax'; document.documentElement.dataset.theme = dark ? 'dark' : 'light'"
            aria-label="Ganti tema"
        >
            {{-- Bulan (tampilkan saat light) --}}
            <svg class="h-4 w-4" x-show="!dark" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"></path>
            </svg>
            {{-- Matahari (tampilkan saat dark) --}}
            <svg class="h-4 w-4" x-show="dark" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"></path>
            </svg>
        </button>
    </div>

    {{-- Panel kiri — brand --}}
    <div class="relative hidden w-[42%] grow flex-col justify-end gap-4 border-r border-stroke-primary p-10 md:flex">
        <div class="relative mb-24">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-guide-500 text-white">
                    <i class="ti ti-box-seam text-lg"></i>
                </div>
                <p class="text-lg font-semibold tracking-tight text-content-primary">APPDOC</p>
            </div>

            {{-- <p class="mt-4 max-w-xs text-sm font-medium text-content-secondary">
                Technology Ecosystem Management &amp; Product Archive for Technical Catalog, Operations &amp;
                Documentation
            </p> --}}
            <p class="mt-3 max-w-xs text-sm text-content-tertiary">
                Katalog, operasional, dan dokumentasi produk teknologi dalam satu tempat.
            </p>
        </div>

        <p class="relative text-xs text-content-tertiary">
            &copy; {{ now()->year }} Dinas Komunikasi, Informatika, Statistik Dan Persandian Kabupaten Serang. All Rights
            Reserved.
        </p>
    </div>

    {{-- Panel kanan — form --}}
    <div class="flex min-h-screen w-full flex-1 flex-col justify-center p-6 sm:p-10 md:min-h-0">
        <form class="mx-auto w-full max-w-sm" wire:submit="login" x-data="{ showPassword: false }">
            <h1 class="text-2xl font-semibold text-content-primary">Masuk ke akun</h1>
            <p class="mt-2 text-sm text-content-secondary">
                Gunakan email, kata sandi, dan verifikasi matematika yang terdaftar.
            </p>

            {{-- Email --}}
            <div class="mt-8 ina-text-field">
                <label for="email" class="ina-text-field__label">
                    Email
                    <span class="ina-text-field__required">*</span>
                </label>
                <div class="ina-text-field__wrapper ina-text-field__wrapper--size-lg">
                    <input
                        type="email"
                        id="email"
                        wire:model="email"
                        class="ina-text-field__input"
                        placeholder="nama@serangkab.go.id"
                        autocomplete="email"
                        required
                        autofocus
                    >
                </div>
                @error('email')
                    <p class="mt-1.5 text-sm text-content-negative">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div class="mt-5 ina-text-field">
                <div class="flex items-center justify-between">
                    <label for="password" class="ina-text-field__label mb-0!">
                        Password
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <a
                        href="#"
                        class="text-xs font-medium text-guide-600 transition-colors duration-150 hover:text-guide-500"
                    >
                        Lupa Password?
                    </a>
                </div>
                <div class="ina-text-field__wrapper ina-text-field__wrapper--size-lg mt-2">
                    <input
                        :type="showPassword ? 'text' : 'password'"
                        id="password"
                        wire:model="password"
                        class="ina-text-field__input"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    >
                    <button
                        type="button"
                        class="ina-text-field__suffix-icon"
                        x-on:click="showPassword = !showPassword"
                        :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                    >
                        <i class="ti text-lg" :class="showPassword ? 'ti-eye-off' : 'ti-eye'"></i>
                    </button>
                </div>
                @error('password')
                    <p class="mt-1.5 text-sm text-content-negative">{{ $message }}</p>
                @enderror
            </div>

            {{-- Verifikasi matematika --}}
            <div
                class="mt-5"
                x-data="{ shown: false }"
                x-init="$nextTick(() => { setTimeout(() => { shown = true }, 120) })"
            >
                <div class="flex items-center justify-between">
                    <label class="ina-text-field__label mb-0!">
                        Verifikasi matematika
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <button
                        type="button"
                        wire:click="refreshCaptcha"
                        class="flex items-center gap-1 text-xs font-medium text-guide-600 transition-colors duration-150 hover:text-guide-500"
                        title="Soal baru"
                    >
                        <i class="ti ti-refresh text-sm"></i>
                        Soal baru
                    </button>
                </div>

                <div
                    class="mt-2 rounded-lg border border-stroke-primary bg-background-secondary px-4 py-3 transition-all duration-300 ease-out"
                    x-show="shown"
                    x-cloak
                >
                    <p class="text-center font-mono text-lg font-semibold tracking-wide text-content-primary">
                        {{ $captchaQuestion }}
                    </p>

                    <div class="mt-3 grid grid-cols-4 gap-2">
                        @foreach ($captchaOptions as $option)
                            <button
                                type="button"
                                wire:click="$set('captchaAnswer', '{{ $option }}')"
                                class="rounded-lg border px-2 py-2 text-center text-sm font-medium transition-colors duration-150 {{ $captchaAnswer === (string) $option ? 'border-transparent bg-guide-500 text-white' : 'border-stroke-primary bg-background-primary text-content-primary hover:border-guide-500' }}"
                            >
                                {{ $option }}
                            </button>
                        @endforeach
                    </div>
                </div>

                @error('captchaAnswer')
                    <p class="mt-1.5 text-sm text-content-negative">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit --}}
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="ina-button ina-button--primary ina-button--lg mt-8 w-full"
            >
                Masuk
            </button>
        </form>

        <p class="mx-auto mt-12 w-full max-w-sm text-xs text-content-tertiary">
            APPDOC &middot; Panel Layanan Digital Kabupaten Serang
        </p>
    </div>
</div>
