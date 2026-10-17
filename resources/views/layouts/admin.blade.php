<?php
    // Tema server-authoritative dari cookie (dievaluasi sebelum <html>)
    $theme = request()->cookie('theme') === 'dark' ? 'dark' : 'light';
?>
<!DOCTYPE html>
<html lang="id" data-theme="{{ $theme }}" data-brand="serangkab">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? 'Dashboard' }} | {{ config('app.name', 'Laravel') }}</title>

        {{-- Anti-FOUC + sinkron cookie: localStorage jadi sumber, cookie ditulis bila belum ada --}}
        <script>
            (function () {
                var saved = localStorage.getItem('theme');
                if ((saved === 'dark' || saved === 'light')) {
                    if (document.cookie.indexOf('theme=') === -1) {
                        document.cookie = 'theme=' + saved + '; path=/; max-age=31536000; samesite=lax';
                    }
                    if (saved !== document.documentElement.dataset.theme) {
                        document.documentElement.dataset.theme = saved;
                    }
                }
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="min-h-screen bg-background-secondary text-content-primary antialiased" x-data="{ menuOpen: false, profileOpen: false }" @keydown.escape.window="menuOpen = false; profileOpen = false">
        {{-- Sidebar — off-canvas di mobile, permanen di >= lg --}}
        <aside
            class="fixed flex flex-col justify-between inset-y-0 left-0 z-40 w-72 border-r border-stroke-primary bg-background-primary transition-transform duration-300 lg:translate-x-0"
            :class="menuOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            {{-- Brand --}}
            <div class="flex shrink-0 h-16 items-center gap-3 border-b border-stroke-primary px-6">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-guide-500 text-white">
                    <i class="ti ti-sitemap text-xl"></i>
                </div>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-content-primary">{{ config('app.name', 'Laravel') }}</p>
                    <p class="truncate text-xs text-content-tertiary">Inventaris Infrastruktur</p>
                </div>
            </div>

            {{-- Menu --}}
            <nav class="space-y-0.5 grow overflow-y-auto px-4 py-4">
                {{-- Dashboard --}}
                <a
                    href="{{ route('admin.dashboard') }}"
                    wire:navigate
                    class="flex h-10 items-center gap-3 rounded-lg px-3 text-sm font-medium transition-colors duration-150 hover:bg-neutral-100 {{ request()->routeIs('admin.dashboard') ? 'bg-guide-50 text-guide-600' : 'text-content-secondary hover:text-content-primary' }}"
                >
                    <i class="ti ti-layout-dashboard text-lg"></i>
                    Dashboard
                </a>

                {{-- Grup: Infrastruktur --}}
                <div x-data="{ open: true }" class="pt-3">
                    <button
                        type="button"
                        @click="open = !open"
                        :aria-expanded="open.toString()"
                        aria-controls="nav-infrastructure"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-medium text-content-secondary transition-colors duration-150 hover:bg-neutral-100 hover:text-content-primary"
                    >
                        <i class="ti ti-topology-star text-lg"></i>
                        <span class="flex-1 text-left">Infrastruktur</span>
                        <i class="ti ti-chevron-right text-base text-content-tertiary transition-transform duration-200" :class="open && 'rotate-90'" aria-hidden="true"></i>
                    </button>
                    <div id="nav-infrastructure" x-show="open" x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="space-y-0.5 pt-0.5">
                        <a
                            href="{{ route('admin.server-rooms.index') }}"
                            wire:navigate
                            class="flex h-9 items-center gap-3 rounded-lg px-3 pl-10 text-[13px] font-medium transition-colors duration-150 hover:bg-neutral-100 {{ request()->routeIs('admin.server-rooms.*') ? 'bg-guide-50 text-guide-600' : 'text-content-secondary hover:text-content-primary' }}"
                        >
                            <i class="ti ti-building text-base"></i>
                            Server Room
                        </a>
                        <a
                            href="{{ route('admin.assets.racks.index') }}"
                            wire:navigate
                            class="flex h-9 items-center gap-3 rounded-lg px-3 pl-10 text-[13px] font-medium transition-colors duration-150 hover:bg-neutral-100 {{ request()->routeIs('admin.assets.racks.*') ? 'bg-guide-50 text-guide-600' : 'text-content-secondary hover:text-content-primary' }}"
                        >
                            <i class="ti ti-server-2 text-base"></i>
                            Rack
                        </a>
                        <a
                            href="{{ route('admin.assets.servers.index') }}"
                            wire:navigate
                            class="flex h-9 items-center gap-3 rounded-lg px-3 pl-10 text-[13px] font-medium transition-colors duration-150 hover:bg-neutral-100 {{ request()->routeIs('admin.assets.servers.*') ? 'bg-guide-50 text-guide-600' : 'text-content-secondary hover:text-content-primary' }}"
                        >
                            <i class="ti ti-server text-base"></i>
                            Server
                        </a>
                        <a
                            href="{{ route('admin.assets.virtualization.index') }}"
                            wire:navigate
                            class="flex h-9 items-center gap-3 rounded-lg px-3 pl-10 text-[13px] font-medium transition-colors duration-150 hover:bg-neutral-100 {{ request()->routeIs(['admin.assets.virtualization.*', 'admin.assets.proxmox.*', 'admin.assets.vms.*']) ? 'bg-guide-50 text-guide-600' : 'text-content-secondary hover:text-content-primary' }}"
                        >
                            <i class="ti ti-stack-2 text-base"></i>
                            Virtualisasi
                        </a>
                        <a
                            href="{{ route('admin.assets.panels.index') }}"
                            wire:navigate
                            class="flex h-9 items-center gap-3 rounded-lg px-3 pl-10 text-[13px] font-medium transition-colors duration-150 hover:bg-neutral-100 {{ request()->routeIs('admin.assets.panels.*') ? 'bg-guide-50 text-guide-600' : 'text-content-secondary hover:text-content-primary' }}"
                        >
                            <i class="ti ti-app-window text-base"></i>
                            Panel
                        </a>
                        <a
                            href="{{ route('admin.assets.nas.index') }}"
                            wire:navigate
                            class="flex h-9 items-center gap-3 rounded-lg px-3 pl-10 text-[13px] font-medium transition-colors duration-150 hover:bg-neutral-100 {{ request()->routeIs('admin.assets.nas.*') ? 'bg-guide-50 text-guide-600' : 'text-content-secondary hover:text-content-primary' }}"
                        >
                            <i class="ti ti-database text-base"></i>
                            NAS
                        </a>
                        <a
                            href="{{ route('admin.applications.index') }}"
                            wire:navigate
                            class="flex h-9 items-center gap-3 rounded-lg px-3 pl-10 text-[13px] font-medium transition-colors duration-150 hover:bg-neutral-100 {{ request()->routeIs('admin.applications.*') ? 'bg-guide-50 text-guide-600' : 'text-content-secondary hover:text-content-primary' }}"
                        >
                            <i class="ti ti-apps text-base"></i>
                            Aplikasi
                        </a>
                        {{-- Topology Explorer --}}
                        <a
                            href="{{ route('admin.topology.index') }}"
                            wire:navigate
                            class="flex h-9 items-center gap-3 rounded-lg px-3 pl-10 text-[13px] font-medium transition-colors duration-150 hover:bg-neutral-100 {{ request()->routeIs('admin.topology.*') ? 'bg-guide-50 text-guide-600' : 'text-content-secondary hover:text-content-primary' }}"
                        >
                            <i class="ti ti-sitemap text-base"></i>
                            Topology Explorer
                        </a>
                    </div>
                </div>

                {{-- Dokumentasi --}}
                <div class="pt-3">
                    <a
                        href="{{ route('admin.documents.index') }}"
                        wire:navigate
                        class="flex h-10 items-center gap-3 rounded-lg px-3 text-sm font-medium transition-colors duration-150 hover:bg-neutral-100 {{ request()->routeIs('admin.documents.*') ? 'bg-guide-50 text-guide-600' : 'text-content-secondary hover:text-content-primary' }}"
                    >
                        <i class="ti ti-folder text-lg"></i>
                        Dokumentasi
                    </a>
                </div>

                {{-- Grup: Aktivitas --}}
                <div x-data="{ open: true }" class="pt-3">
                    <button
                        type="button"
                        @click="open = !open"
                        :aria-expanded="open.toString()"
                        aria-controls="nav-activities"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-medium text-content-secondary transition-colors duration-150 hover:bg-neutral-100 hover:text-content-primary"
                    >
                        <i class="ti ti-activity text-lg"></i>
                        <span class="flex-1 text-left">Aktivitas</span>
                        <i class="ti ti-chevron-right text-base text-content-tertiary transition-transform duration-200" :class="open && 'rotate-90'" aria-hidden="true"></i>
                    </button>
                    <div id="nav-activities" x-show="open" x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="space-y-0.5 pt-0.5">
                        <a
                            href="{{ route('admin.activities.index') }}"
                            wire:navigate
                            class="flex h-9 items-center gap-3 rounded-lg px-3 pl-10 text-[13px] font-medium transition-colors duration-150 hover:bg-neutral-100 {{ request()->routeIs('admin.activities.*') ? 'bg-guide-50 text-guide-600' : 'text-content-secondary hover:text-content-primary' }}"
                        >
                            <i class="ti ti-list-check text-base"></i>
                            Semua Aktivitas
                        </a>
                        {{-- Laporan Bulanan --}}
                        <a
                            href="{{ route('admin.reports.index') }}"
                            wire:navigate
                            class="flex h-9 items-center gap-3 rounded-lg px-3 pl-10 text-[13px] font-medium transition-colors duration-150 hover:bg-neutral-100 {{ request()->routeIs('admin.reports.*') ? 'bg-guide-50 text-guide-600' : 'text-content-secondary hover:text-content-primary' }}"
                        >
                            <i class="ti ti-file-report text-base"></i>
                            Laporan Bulanan
                        </a>
                    </div>
                </div>

                {{-- Gallery --}}
                <div class="pt-3">
                    <a
                        href="{{ route('admin.galleries.index') }}"
                        wire:navigate
                        class="flex h-10 items-center gap-3 rounded-lg px-3 text-sm font-medium transition-colors duration-150 hover:bg-neutral-100 {{ request()->routeIs('admin.galleries.*') ? 'bg-guide-50 text-guide-600' : 'text-content-secondary hover:text-content-primary' }}"
                    >
                        <i class="ti ti-photo text-lg"></i>
                        Gallery
                    </a>
                </div>

                {{-- Grup: Pengaturan (User, Role, Institution) — modul manajemen --}}
                @if (auth()->user()?->can('management.manage'))
                    <div x-data="{ open: true }" class="pt-3">
                        <button
                            type="button"
                            @click="open = !open"
                            :aria-expanded="open.toString()"
                            aria-controls="nav-settings"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-medium text-content-secondary transition-colors duration-150 hover:bg-neutral-100 hover:text-content-primary"
                        >
                            <i class="ti ti-settings text-lg"></i>
                            <span class="flex-1 text-left">Pengaturan</span>
                            <i class="ti ti-chevron-right text-base text-content-tertiary transition-transform duration-200" :class="open && 'rotate-90'" aria-hidden="true"></i>
                        </button>
                        <div id="nav-settings" x-show="open" x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="space-y-0.5 pt-0.5">
                            <a
                                href="{{ route('admin.users.index') }}"
                                wire:navigate
                                class="flex h-9 items-center gap-3 rounded-lg px-3 pl-10 text-[13px] font-medium transition-colors duration-150 hover:bg-neutral-100 {{ request()->routeIs('admin.users.*') ? 'bg-guide-50 text-guide-600' : 'text-content-secondary hover:text-content-primary' }}"
                            >
                                <i class="ti ti-users text-base"></i>
                                Pengguna
                            </a>
                            <a
                                href="{{ route('admin.roles.index') }}"
                                wire:navigate
                                class="flex h-9 items-center gap-3 rounded-lg px-3 pl-10 text-[13px] font-medium transition-colors duration-150 hover:bg-neutral-100 {{ request()->routeIs('admin.roles.*') ? 'bg-guide-50 text-guide-600' : 'text-content-secondary hover:text-content-primary' }}"
                            >
                                <i class="ti ti-shield-check text-base"></i>
                                Role &amp; Permission
                            </a>
                            <a
                                href="{{ route('admin.institutions.index') }}"
                                wire:navigate
                                class="flex h-9 items-center gap-3 rounded-lg px-3 pl-10 text-[13px] font-medium transition-colors duration-150 hover:bg-neutral-100 {{ request()->routeIs('admin.institutions.*') ? 'bg-guide-50 text-guide-600' : 'text-content-secondary hover:text-content-primary' }}"
                            >
                                <i class="ti ti-building text-base"></i>
                                Referensi Data
                            </a>
                        </div>
                    </div>
                @endif

                {{-- Grup: Developer — tool internal untuk tabel, hash, dan sebagainya --}}
                @if (auth()->user()?->can('devtools.manage'))
                    <div x-data="{ open: true }" class="pt-3">
                        <button
                            type="button"
                            @click="open = !open"
                            :aria-expanded="open.toString()"
                            aria-controls="nav-developer"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-medium text-content-secondary transition-colors duration-150 hover:bg-neutral-100 hover:text-content-primary"
                        >
                            <i class="ti ti-tools text-lg"></i>
                            <span class="flex-1 text-left">Developer</span>
                            <i class="ti ti-chevron-right text-base text-content-tertiary transition-transform duration-200" :class="open && 'rotate-90'" aria-hidden="true"></i>
                        </button>
                        <div id="nav-developer" x-show="open" x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="space-y-0.5 pt-0.5">
                            <a
                                href="{{ route('admin.dev.bcrypt') }}"
                                wire:navigate
                                class="flex h-9 items-center gap-3 rounded-lg px-3 pl-10 text-[13px] font-medium transition-colors duration-150 hover:bg-neutral-100 {{ request()->routeIs('admin.dev.*') ? 'bg-guide-50 text-guide-600' : 'text-content-secondary hover:text-content-primary' }}"
                            >
                                <i class="ti ti-key text-base"></i>
                                Bcrypt Hash
                            </a>
                        </div>
                    </div>
                @endif
            </nav>

            {{-- User — klik untuk buka menu akun --}}
            <div
                class="bg-background-primary relative border-t border-stroke-primary p-4"
                @click.outside="profileOpen = false"
            >
                <button
                    type="button"
                    class="flex w-full items-center gap-3 rounded-lg px-2 py-2 text-left transition-colors duration-150 hover:bg-background-tertiary"
                    aria-haspopup="menu"
                    aria-controls="profile-menu"
                    :aria-expanded="profileOpen.toString()"
                    @click="profileOpen = !profileOpen"
                >
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-guide-50 text-sm font-medium text-guide-600">
                        @if (auth()->user()?->avatar !== null)
                            <img src="{{ asset('storage/'.auth()->user()->avatar) }}" alt="{{ auth()->user()?->name }}" class="h-9 w-9 object-cover">
                        @else
                            {{ collect(str_split(optional(auth()->user())->name ?? '?', 1))->take(2)->map(fn ($c) => strtoupper($c))->implode('') }}
                        @endif
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium text-content-primary" title="{{ auth()->user()?->name }}">
                            {{ auth()->user()?->name }}
                        </span>
                        <span class="block truncate text-xs text-content-tertiary" title="{{ auth()->user()?->email }}">
                            {{ auth()->user()?->email }}
                        </span>
                    </span>
                    <i class="ti ti-chevron-up text-base text-content-tertiary transition-transform duration-200" :class="profileOpen && 'rotate-180'" aria-hidden="true"></i>
                </button>

                {{-- Menu akun --}}
                <div
                    id="profile-menu"
                    role="menu"
                    aria-label="Menu akun"
                    x-cloak
                    x-show="profileOpen"
                    x-transition:enter="transition duration-150 ease-out"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition duration-100 ease-in"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-1"
                    class="absolute bottom-full left-4 right-4 z-50 mb-2 rounded-lg border border-stroke-primary bg-background-primary p-1.5 shadow-lg"
                >
                    <a
                        href="{{ route('admin.settings.index') }}"
                        wire:navigate
                        role="menuitem"
                        @click="profileOpen = false"
                        class="flex w-full items-center gap-2.5 rounded-md px-3 py-2 text-left text-sm text-content-secondary transition-colors duration-150 hover:bg-neutral-100 hover:text-content-primary"
                    >
                        <i class="ti ti-settings text-base" aria-hidden="true"></i>
                        <span>Settings</span>
                    </a>

                    <div class="my-1.5 border-t border-stroke-primary"></div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            role="menuitem"
                            class="flex w-full items-center gap-2.5 rounded-md px-3 py-2 text-left text-sm text-negative-500 transition-colors duration-150 hover:bg-negative-50"
                        >
                            <i class="ti ti-logout text-base" aria-hidden="true"></i>
                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Overlay (mobile) --}}
        <div
            class="fixed inset-0 z-30 bg-black/50 lg:hidden"
            x-show="menuOpen"
            x-on:click="menuOpen = false"
            x-transition.opacity
            x-cloak
        ></div>

        {{-- Konten --}}
        <div class="flex min-h-screen flex-col pl-0 lg:pl-72">
            {{-- Topbar --}}
            <header class="sticky top-0 z-20 border-b border-stroke-primary bg-background-primary">
                <div class="flex h-16 items-center justify-between px-4 sm:px-6">
                    <div class="flex items-center gap-3">
                        {{-- Tombol menu (mobile) --}}
                        <button
                            type="button"
                            class="flex h-10 w-10 items-center justify-center rounded-lg text-content-primary hover:bg-background-tertiary lg:hidden"
                            x-on:click="menuOpen = true"
                            aria-label="Buka menu"
                        >
                            <i class="ti ti-menu-2 text-lg"></i>
                        </button>

                        {{-- Breadcrumb --}}
                        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-sm">
                            <a href="{{ route('admin.dashboard') }}" wire:navigate class="text-content-tertiary hover:text-content-primary">Admin</a>
                            <i class="ti ti-chevron-right text-sm text-content-tertiary" aria-hidden="true"></i>
                            <span class="font-medium text-content-primary">{{ $title ?? 'Dashboard' }}</span>
                        </nav>
                    </div>

                    <div class="flex items-center gap-2">
                        {{-- Toggle tema --}}
                        <button
                            type="button"
                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                            x-data="{ dark: document.documentElement.dataset.theme === 'dark' }"
                            x-on:theme-sync.window="dark = $event.detail.dark"
                            x-on:click="dark = !dark; localStorage.setItem('theme', dark ? 'dark' : 'light'); document.cookie = 'theme=' + (dark ? 'dark' : 'light') + '; path=/; max-age=31536000; samesite=lax'; document.documentElement.dataset.theme = dark ? 'dark' : 'light'"
                            aria-label="Ganti tema"
                        >
                            <svg class="h-4 w-4" x-show="!dark" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"></path>
                            </svg>
                            <svg class="h-4 w-4" x-show="dark" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"></path>
                            </svg>
                        </button>

                    </div>
                </div>
            </header>

            {{-- Konten halaman --}}
            <main class="flex-1 p-4 sm:p-6">
                {{ $slot }}
            </main>
        </div>

        {{-- Toast notification global --}}
        <x-asset.toast />

        @livewireScripts
    </body>
</html>
