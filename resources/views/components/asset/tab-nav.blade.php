{{-- Navigasi tab halaman detail (URL query ?tab=) — data & tampilan tab konsisten antar modul --}}
@props([
    'url',
    'current',
    'tabs',
])

@php
    $baseTabUrl = str_contains($url, '?') ? $url.'&tab=' : $url.'?tab=';
@endphp

<div class="mt-6 flex flex-wrap gap-1 rounded-lg border border-stroke-primary bg-background-primary p-1">
    @foreach ($tabs as $tab)
        <a
            href="{{ $tab['disabled'] ?? false ? '#' : $baseTabUrl.$tab['key'] }}"
            wire:navigate
            @class([
                'flex h-9 items-center gap-2 rounded-md px-4 text-[13px] font-medium transition-colors duration-150',
                'bg-guide-50 text-guide-600' => $current === $tab['key'],
                'text-content-secondary hover:bg-neutral-100 hover:text-content-primary' => $current !== $tab['key'],
            ])
            @if ($current === $tab['key']) aria-current="page" @endif
        >
            <i class="ti {{ $tab['icon'] }} text-base"></i>
            {{ $tab['label'] }}
            @if (($tab['count'] ?? 0) > 0)
                <span class="rounded-full px-1.5 text-[11px] font-semibold {{ $current === $tab['key'] ? 'bg-guide-100 text-guide-700' : 'bg-background-tertiary text-content-tertiary' }}">{{ $tab['count'] }}</span>
            @endif
        </a>
    @endforeach
</div>
