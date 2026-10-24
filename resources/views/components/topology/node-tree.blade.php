<?php
/**
 * Node pohon topology — dirender rekursif untuk mode hierarki & diagram.
 * Sumber node identik; mode hanya mengubah tampilan.
 */

$node = $node ?? [];
$mode = $mode ?? 'hierarchy';
$depth = $depth ?? 0;
$path = $path ?? 'n';

$hasChildren = count($node['children']) > 0;
$isRoot = $node['type'] === 'root';
$nodeKey = $path.'-'.$depth;

// Warna ikon per jenis aset (konsisten ikon modul)
$colorClasses = [
    'location' => 'bg-guide-500 text-white',
    'rack' => 'bg-background-tertiary text-content-secondary',
    'server' => 'bg-guide-50 text-guide-600',
    'nas' => 'bg-guide-100 text-guide-600',
    'proxmox' => 'bg-warning-50 text-warning-600',
    'vms' => 'bg-background-tertiary text-content-secondary',
    'panel' => 'bg-guide-50 text-guide-600',
    'waf' => 'bg-negative-50 text-negative-600',
    'application' => 'bg-guide-100 text-guide-600',
    'root' => 'bg-guide-500 text-white',
];
$colorClass = $colorClasses[$node['colorKey'] ?? $node['type']] ?? $colorClasses['vms'];
?>
@if ($mode === 'hierarchy')
    {{-- Mode hierarki: daftar expandable --}}
    <div
        x-data="{ open: @js($depth <= 1) }"
        class="{{ $depth > 0 ? 'ml-1 border-l border-stroke-primary pl-3' : '' }}"
        wire:key="tw-{{ $nodeKey }}"
    >
        <div class="flex items-center gap-1.5 rounded-md px-1 py-1 hover:bg-neutral-50">
            @if ($hasChildren)
                <button
                    type="button"
                    @click="open = !open"
                    :aria-expanded="open.toString()"
                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded text-content-tertiary hover:bg-background-tertiary"
                    aria-label="Buka/tutup {{ $node['label'] }}"
                >
                    <i class="ti ti-chevron-right text-sm transition-transform duration-200" :class="open && 'rotate-90'"></i>
                </button>
            @else
                <span class="flex h-6 w-6 shrink-0 items-center justify-center">
                    <span class="h-1.5 w-1.5 rounded-full bg-stroke-primary"></span>
                </span>
            @endif

            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded {{ $colorClass }}" title="{{ $node['label'] }}">
                <i class="ti {{ $node['icon'] }} text-sm"></i>
            </span>

            @if ($node['url'] !== null)
                <a href="{{ $node['url'] }}" wire:navigate class="flex min-w-0 items-baseline gap-2">
                    <span class="truncate text-sm font-medium text-content-primary" title="{{ $node['label'] }}">{{ $node['label'] }}</span>
                    @if ($node['code'] !== null)
                        <span class="truncate text-[11px] text-content-tertiary">{{ $node['code'] }}</span>
                    @endif
                </a>
            @else
                <span class="truncate text-sm font-semibold text-content-primary" title="{{ $node['label'] }}">{{ $node['label'] }}</span>
            @endif

            @if ($hasChildren)
                <span class="ina-badge ina-badge--soft ina-badge--neutral ina-badge--sm ml-auto shrink-0">{{ count($node['children']) }}</span>
            @endif
        </div>

        @if ($hasChildren)
            <div x-show="open" class="py-0.5">
                @foreach ($node['children'] as $child)
                    @include('components.topology.node-tree', ['node' => $child, 'mode' => 'hierarchy', 'depth' => $depth + 1, 'path' => $nodeKey])
                @endforeach
            </div>
        @endif
    </div>
@else
    {{-- Mode diagram: pohon grafis dengan connector margin-based --}}
    <div class="relative {{ $depth > 0 ? 'ml-6 border-l border-stroke-primary pl-6' : '' }}" wire:key="dg-{{ $nodeKey }}">
        <div class="@if ($depth > 0) relative my-1 inline-block @endif">
            <div class="flex w-fit max-w-full items-center gap-2 rounded-lg border border-stroke-primary bg-background-primary py-1.5 pl-2 pr-3 shadow-sm {{ $isRoot ? 'underline-offset-*' : '' }} {{ $depth > 0 ? '' : 'mb-2' }}">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded {{ $colorClass }}">
                    <i class="ti {{ $node['icon'] }} text-sm"></i>
                </span>

                @if ($node['url'] !== null)
                    <a href="{{ $node['url'] }}" wire:navigate class="flex min-w-0 items-baseline gap-2">
                        <span class="truncate text-[13px] font-medium text-content-primary" title="{{ $node['label'] }}">{{ $node['label'] }}</span>
                        @if ($node['code'] !== null)
                            <span class="truncate text-[10px] text-content-tertiary" title="{{ $node['code'] }}">{{ $node['code'] }}</span>
                        @endif
                    </a>
                @else
                    <span class="truncate text-[13px] font-semibold text-content-primary">{{ $node['label'] }}</span>
                @endif

                @if ($hasChildren)
                    <span class="ina-badge ina-badge--soft ina-badge--neutral ina-badge--sm">{{ count($node['children']) }}</span>
                @endif
            </div>

            @if ($hasChildren)
                <div class="mt-1 ml-1">
                    @foreach ($node['children'] as $child)
                        @include('components.topology.node-tree', ['node' => $child, 'mode' => 'diagram', 'depth' => $depth + 1, 'path' => $nodeKey])
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endif
