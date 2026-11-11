{{-- Header halaman detail: tombol kembali, judul, kode aset, slot kanan --}}
@props([
    'backRoute',
    'title',
    'code' => null,
    'subtitle' => null,
    'backQuery' => [],
])

@php
    // Konteks list asal (page/filter) dibawa kembali agar index terbuka di posisi terakhir
    $backParams = collect($backQuery)->filter(fn ($value) => filled($value))->all();
    $backHref = empty($backParams)
        ? $backRoute
        : $backRoute.(str_contains($backRoute, '?') ? '&' : '?').\Illuminate\Support\Arr::query($backParams);
@endphp

<div class="flex flex-wrap items-start justify-between gap-4">
    <div class="flex items-start gap-3">
        <a
            href="{{ $backHref }}"
            wire:navigate
            class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
            aria-label="Kembali"
            title="Kembali"
        >
            <i class="ti ti-arrow-left text-base"></i>
        </a>
        <div class="min-w-0">
            <h1 class="flex flex-wrap items-center gap-2 text-xl font-semibold text-content-primary">
                {{ $title }}
                @if ($code)
                    <span class="ina-badge ina-badge--soft ina-badge--neutral ina-badge--sm">{{ $code }}</span>
                @endif
            </h1>
            <p class="mt-1 text-sm text-content-secondary">{{ $subtitle ?? 'Detail beserta informasi terkait.' }}</p>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        {{ $right ?? '' }}
    </div>
</div>
