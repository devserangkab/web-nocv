{{-- Ilustrasi SVG state (noresult / nodata / underconstruction) --}}
@props([
    'name' => 'nodata', // noresult | nodata | underconstruction
    'size' => 'md',     // sm | md | lg
    'alt' => null,
])

@php
    $maps = [
        'noresult' => 'images/noresultsfound.svg',
        'nodata' => 'images/state_nodata.svg',
        'underconstruction' => 'images/underconstruction.svg',
    ];

    $sizes = [
        'sm' => 'h-16 sm:h-20',
        'md' => 'h-20 sm:h-24',
        'lg' => 'h-24 sm:h-32',
    ];

    $file = $maps[$name] ?? $maps['nodata'];
    $class = $sizes[$size] ?? $sizes['md'];
@endphp

<img
    src="{{ asset($file) }}"
    alt="{{ $alt ?? 'Ilustrasi '.($name === 'noresult' ? 'tidak ada hasil' : ($name === 'nodata' ? 'belum ada data' : 'dalam pengembangan')) }}"
    loading="lazy"
    class="{{ $class }} w-auto mx-auto"
    aria-hidden="true"
>
