<?php
    // Tema server-authoritative dari cookie (dievaluasi sebelum <html>)
    $theme = request()->cookie('theme') === 'dark' ? 'dark' : 'light';
?>
<!DOCTYPE html>
<html lang="id" data-theme="{{ $theme }}" data-brand="serangkab">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? 'Ganti Password' }} | {{ config('app.name', 'Laravel') }}</title>

        @include('layouts.partials.head-meta')

        {{-- Anti-FOUC: terapkan tema tersimpan sebelum paint --}}
        <script>
            document.documentElement.dataset.theme = localStorage.getItem('theme') === 'dark' ? 'dark' : 'light';
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="min-h-screen bg-background-secondary text-content-primary antialiased">
        <main class="relative flex min-h-screen items-center justify-center p-6">
            {{-- Toggle tema — kanan atas (perilaku identik dengan halaman login) --}}
            <button
                type="button"
                class="fixed right-4 top-4 flex h-9 w-9 items-center justify-center rounded-md border border-stroke-primary bg-background-primary text-content-tertiary transition-colors duration-150 hover:text-content-primary"
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

            {{-- Ornament dekor — sudut kiri bawah (motif wallpaper ruang NOC) --}}
            <x-asset.ornament class="pointer-events-none absolute bottom-6 left-6 h-48 w-48 text-guide-500 opacity-20" />

            <div class="w-full max-w-md relative z-10">
                {{ $slot }}
            </div>
        </main>

        @livewireScripts
    </body>
</html>
