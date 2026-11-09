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

        @include('layouts.partials.head-meta')

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
                <div class="min-w-0">
                    <svg class="h-5 text-guide-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 214 91" fill="none">
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

                    {{-- <p class="truncate text-sm font-semibold text-content-primary">{{ config('app.name', 'Laravel') }}</p> --}}
                    <p class="truncate text-xs text-content-tertiary">Network Operation Center Virtual</p>
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
                            href="{{ route('admin.assets.wafs.index') }}"
                            wire:navigate
                            class="flex h-9 items-center gap-3 rounded-lg px-3 pl-10 text-[13px] font-medium transition-colors duration-150 hover:bg-neutral-100 {{ request()->routeIs('admin.assets.wafs.*') ? 'bg-guide-50 text-guide-600' : 'text-content-secondary hover:text-content-primary' }}"
                        >
                            <i class="ti ti-shield-lock text-base"></i>
                            WAF
                        </a>
                        <a
                            href="{{ route('admin.assets.databases.index') }}"
                            wire:navigate
                            class="flex h-9 items-center gap-3 rounded-lg px-3 pl-10 text-[13px] font-medium transition-colors duration-150 hover:bg-neutral-100 {{ request()->routeIs('admin.assets.databases.*') ? 'bg-guide-50 text-guide-600' : 'text-content-secondary hover:text-content-primary' }}"
                        >
                            <i class="ti ti-database text-base"></i>
                            Database
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
                            <i class="ti ti-vault text-base"></i>
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

                {{-- Sinkronisasi — item mandiri di luar grup, khusus superadmin --}}
                @if (auth()->user()?->hasRole('superadmin'))
                    <div class="pt-3">
                        <a
                            href="{{ route('admin.synchronization.index') }}"
                            wire:navigate
                            class="flex h-10 items-center gap-3 rounded-lg px-3 text-sm font-medium transition-colors duration-150 hover:bg-neutral-100 {{ request()->routeIs('admin.synchronization.*') ? 'bg-guide-50 text-guide-600' : 'text-content-secondary hover:text-content-primary' }}"
                        >
                            <i class="ti ti-refresh-dot text-lg"></i>
                            Sinkronisasi
                        </a>
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
