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

    public string $captchaQuestionHtml = '';

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
        $this->captchaQuestionHtml = $challenge['questionHtml'];
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

<div class="mx-auto flex w-full max-w-7xl flex-col lg:flex-row">
    {{-- Panel kiri — brand --}}
    <div class="relative justify-end hidden flex-1 flex-col border-r border-stroke-primary p-10 lg:flex">
        {{-- Ornament dekor — sudut kanan atas, cermin logo di kiri atas (motif wallpaper ruang NOC) --}}
        <x-asset.ornament class="pointer-events-none absolute right-6 top-6 h-40 w-40 text-guide-500 opacity-25" />

        <div class="relative my-12">
            <svg class="h-10 text-guide-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 214 91" fill="none">
                    <g id="NOC" filter="url(#filter0_d_37_3921)">
                        <path id="C" d="M171.797 15.6626C174.782 14.233 176.995 13.6123 180.873 13.1175C183.509 12.7813 190.587 12.9154 193.447 13.3561C197.82 14.0297 204.257 15.5014 204.799 15.9512C205.002 16.1194 205.032 16.4163 204.95 17.4309C204.716 20.3147 203.827 21.5517 201.665 22.0034C200.365 22.275 196.358 22.1052 193.823 21.6711C187.438 20.5777 182.653 20.8331 179.471 22.4372C176.244 24.0634 172.993 27.4497 171.179 31.0728C169.71 34.0064 168.596 37.8334 168.122 41.5701C167.701 44.8884 167.851 49.7961 168.463 52.7098C169.862 59.3791 172.968 64.9151 177.041 68.0023C180.734 70.8004 186.429 71.5837 193.557 70.2741C196.054 69.8153 199.451 69.5323 200.774 69.6725C203.175 69.9271 204.328 70.9523 204.819 73.2675C205.068 74.4395 205.06 75.3626 204.798 75.7158C204.352 76.3182 200.635 77.2783 195.555 78.1026C186.674 79.5445 181.11 79.3573 175.134 77.4155C171.522 76.2419 167.358 73.8619 164.341 71.2461C159.202 66.7911 155.255 59.3514 153.925 51.6127C153.505 49.1704 153.504 42.8546 153.924 40.3042C154.909 34.3178 157.032 29.1608 160.233 24.9767C163.089 21.2435 167.408 17.7647 171.797 15.6626Z" fill="currentColor"/>
                        <path id="O" d="M87.0292 22.3057C89.6019 19.7102 90.9301 18.6505 92.2068 18.1754C93.1543 17.8225 95.1897 17.9535 96.1089 18.4262C97.21 18.9925 98.1081 19.8928 98.628 20.9514C99.0359 21.7823 99.097 22.105 99.096 23.4259C99.0943 25.4766 98.88 25.8373 95.7458 29.0618C94.3373 30.5108 92.9833 31.9621 92.7367 32.2871C91.4844 33.9376 89.7214 37.6967 89.0958 40.0511C88.718 41.4719 88.6692 41.9909 88.6669 44.6082C88.663 48.9272 89.1126 51.2137 90.5886 54.3789C92.6257 58.7474 96.1511 62.3106 100.616 64.5139C102.929 65.6554 104.779 66.2733 106.669 66.5362C108.323 66.7661 112.6 66.7712 113.995 66.5447C115.595 66.2851 117.961 65.4402 120 64.4004C125.655 61.5164 129.416 56.8212 131.339 50.2461C131.739 48.8774 131.779 48.4724 131.783 45.7053C131.79 41.3252 131.191 38.728 129.308 34.983C128.33 33.0381 127.837 32.4128 124.916 29.4177C123.422 27.886 121.977 26.253 121.704 25.7889C121.252 25.0203 121.208 24.8094 121.215 23.4259C121.221 22.1217 121.285 21.7875 121.665 21.063C122.236 19.974 123.349 18.902 124.415 18.4141C125.655 17.846 127.636 17.876 128.806 18.4809C131.495 19.8715 135.147 23.5795 137.786 27.5976C140.291 31.4107 142.145 36.7303 142.745 41.8184C143.052 44.427 142.849 48.9303 142.307 51.5284C140.885 58.3507 137.869 63.8757 132.999 68.58C130.241 71.2444 127.67 73.0351 124.283 74.6512C120.013 76.688 117.113 77.4131 111.966 77.731C107.199 78.0255 103.63 77.5379 99.2774 75.9979C89.8237 72.6524 82.3252 64.9395 79.0746 55.2171C78.2021 52.6079 77.8025 50.506 77.5851 47.3824C77.1646 41.3453 78.245 35.9318 80.9515 30.5148C82.5738 27.2678 83.9411 25.4211 87.0292 22.3057Z" fill="currentColor"/>
                        <path id="O-top" d="M105.087 10.6389C105.467 9.10247 107.326 7.51236 109.162 7.15151C110.191 6.94913 110.149 6.94913 111.256 7.1537C113.155 7.50494 113.991 8.19762 115.13 10.3638L115.723 11.4916L115.713 24.6321C115.707 31.8594 115.643 38.608 115.569 39.6291C115.41 41.8395 115.122 42.5187 113.866 43.6477C112.65 44.7414 111.436 45.1674 109.795 45.0759C108.278 44.9914 107.415 44.5647 106.288 43.3417C104.951 41.8912 104.882 41.5884 104.693 36.3379C104.488 30.6544 104.776 11.8968 105.087 10.6389Z" fill="currentColor"/>
                        <path id="N" d="M9.37521 71.4022C9.28828 70.3581 9.16828 56.7548 9.10853 41.1726L9 12.8413L13.0343 12.9228C17.4579 13.0125 18.0853 13.1178 20.3137 14.1457C23.0095 15.3889 26.2338 18.1636 28.4654 21.1605C29.8535 23.0247 33.4601 28.4902 41.8778 41.4859C51.065 55.6697 53.9036 59.9676 54.0839 59.9676C54.3955 59.9676 54.4523 56.0888 54.4532 34.7767L54.454 12.8771L57.3656 12.88C62.6061 12.8854 63.7886 13.3976 65.4732 16.3934L66.228 17.7355L66.2959 48.1594L66.3636 78.5833L63.236 78.4759C59.0119 78.3309 57.4067 77.9898 54.7541 76.6731C52.869 75.7376 51.7295 74.9364 50.1279 73.4205C47.577 71.0056 47.3074 70.6103 30.4853 44.6036C26.4304 38.3349 22.7401 32.6452 22.2848 31.9599L21.4569 30.7138L21.3538 34.9183C21.2972 37.2308 21.2402 47.9638 21.2272 58.7695L21.2037 78.4158L18.3766 78.2995C13.4532 78.0968 12.1201 77.7005 10.7867 76.0434C9.90485 74.9477 9.5823 73.8869 9.37521 71.4022Z" fill="currentColor"/>
                    </g>
                    <defs>
                        <filter id="filter0_d_37_3921" x="0" y="0" width="214" height="90.0491" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                            <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                            <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
                            <feOffset dy="2"/>
                            <feGaussianBlur stdDeviation="4.5"/>
                            <feComposite in2="hardAlpha" operator="out"/>
                            <feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.25 0"/>
                            <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_37_3921"/>
                            <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_37_3921" result="shape"/>
                        </filter>
                    </defs>
            </svg>

            <p class="text-sm font-semibold text-content-primary">Network Operation Center Virtual</p>
            <p class="max-w-md text-sm text-content-secondary">
                All network assets, topology, and documentation<br>in one panel.
            </p>
        </div>

        <p class="relative max-w-sm text-xs text-content-tertiary">
            &copy; {{ now()->year }} Dinas Komunikasi, Informatika, Statistik Dan Persandian Kabupaten Serang. All Rights
            Reserved.
        </p>
    </div>

    {{-- Panel kanan — form --}}
    <div class="flex min-h-screen w-full flex-col border-stroke-primary p-6 sm:p-10 lg:min-h-0 lg:w-[30rem] lg:border-l">
        {{-- Toggle tema — baris atas --}}
        <div class="w-full">
            <button
                type="button"
                class="flex mr-0 ml-auto h-9 w-9 items-center justify-center rounded-md border border-stroke-primary bg-background-primary text-content-tertiary transition-colors duration-150 hover:text-content-primary"
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

        {{-- Form — tengah vertikal di sisa ruang --}}
        <div class="flex flex-1 items-center justify-center py-10">
            <form class="w-full max-w-sm" wire:submit="login" x-data="{ showPassword: false }">
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
                    class="mt-2 transition-all duration-300 ease-out"
                    x-show="shown"
                    x-cloak
                >
                    {{-- Kartu soal --}}
                    <div class="flex justify-center rounded-md border border-stroke-primary bg-background-secondary px-4 py-5">
                        <p
                            class="flex flex-wrap items-center justify-center gap-1 font-[Caveat,cursive] text-3xl text-content-primary"
                            title="{{ $captchaQuestion }}"
                        >
                            {!! $captchaQuestionHtml !!}
                        </p>
                    </div>

                    {{-- Pilihan jawaban --}}
                    <div class="mt-2.5 grid grid-cols-4 gap-2">
                        @foreach ($captchaOptions as $option)
                            <button
                                type="button"
                                wire:click="$set('captchaAnswer', '{{ $option }}')"
                                class="rounded-md border h-10 px-2 text-center text-sm font-medium transition-colors duration-150 {{ $captchaAnswer === (string) $option ? 'border-transparent bg-guide-500 text-white' : 'border-stroke-primary bg-background-primary text-content-primary hover:border-guide-500 hover:bg-guide-50' }}"
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
        </div>
    </div>
</div>
