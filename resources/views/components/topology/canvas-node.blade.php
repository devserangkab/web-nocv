<?php
/**
 * Kartu node pada kanvas topologi — diposisikan absolut oleh diagramLayout.
 */

$node = $node ?? [];
$nodeWidth = $nodeWidth ?? 190;
?>
<div
    class="absolute h-9"
    style="left: {{ $node['x'] }}px; top: {{ $node['y'] }}px; width: {{ $nodeWidth }}px;"
    wire:key="cn-{{ $node['key'] }}"
>
    <div class="flex h-9 items-center gap-2 overflow-hidden rounded-lg border border-stroke-primary bg-background-primary py-0 pl-2 pr-2 shadow-sm">
        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded {{ $node['colorClass'] }}" title="{{ $node['label'] }}">
            <i class="ti {{ $node['icon'] }} text-sm"></i>
        </span>

        @if ($node['url'] !== null)
            <a href="{{ $node['url'] }}" wire:navigate class="flex min-w-0 flex-1 items-baseline gap-1.5">
                <span class="truncate text-[13px] font-medium text-content-primary" title="{{ $node['label'] }}">{{ $node['label'] }}</span>
                @if ($node['code'] !== null)
                    <span class="truncate text-[10px] text-content-tertiary" title="{{ $node['code'] }}">{{ $node['code'] }}</span>
                @endif
            </a>
        @else
            <span class="min-w-0 flex-1 truncate text-[13px] font-semibold text-content-primary">{{ $node['label'] }}</span>
        @endif
    </div>
</div>
