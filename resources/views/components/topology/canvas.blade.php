<?php
/**
 * Kanvas topologi (mode diagram): viewport pan/zoom dengan transform CSS.
 * Posisi node & garis penghubung dihitung server-side (diagramLayout);
 * interaksi geser/perbesar murni client-side via helper topologyCanvas().
 */

$layout = $layout ?? [];
$nodeWidth = $layout['nodeWidth'];
$nodeHeight = $layout['nodeHeight'];
$halfNodeHeight = (int) ($nodeHeight / 2);
?>
<div
    x-data="topologyCanvas()"
    class="mt-4"
    @resize.window.debounce.150ms="fit()"
>
    <div
        x-ref="viewport"
        class="relative h-[72vh] min-h-[440px] cursor-grab select-none overflow-hidden rounded-lg border border-stroke-primary bg-background-primary"
        style="touch-action: none;"
        :class="dragging && 'cursor-grabbing'"
        @pointerdown="startPan($event)"
        @pointermove="movePan($event)"
        @pointerup="endPan($event)"
        @pointercancel="endPan($event)"
        @pointerleave="endPan($event)"
        @wheel.prevent="zoomAt($event, $event.deltaY < 0 ? 1.1 : 1 / 1.1)"
    >
        <div
            x-ref="content"
            class="topology-canvas-grid absolute top-0 left-0 origin-top-left"
            :style="`transform: translate(${x}px, ${y}px) scale(${scale}); width: {{ $layout['width'] }}px; height: {{ $layout['height'] }}px;`"
        >
            <svg
                class="pointer-events-none absolute top-0 left-0"
                width="{{ $layout['width'] }}"
                height="{{ $layout['height'] }}"
                aria-hidden="true"
            >
                @foreach ($layout['edges'] as $edge)
                    @php
                        $from = $layout['nodes'][$edge['from']];
                        $to = $layout['nodes'][$edge['to']];
                        $x1 = $from['x'] + $nodeWidth;
                        $y1 = $from['y'] + $halfNodeHeight;
                        $x2 = $to['x'];
                        $y2 = $to['y'] + $halfNodeHeight;
                        $midX = (int) (($x1 + $x2) / 2);
                    @endphp
                    <path
                        d="M {{ $x1 }} {{ $y1 }} C {{ $midX }} {{ $y1 }}, {{ $midX }} {{ $y2 }}, {{ $x2 }} {{ $y2 }}"
                        fill="none"
                        stroke="var(--ina-stroke-primary)"
                        stroke-width="1.5"
                    />
                @endforeach
            </svg>

            @foreach ($layout['nodes'] as $node)
                @include('components.topology.canvas-node', ['node' => $node, 'nodeWidth' => $nodeWidth])
            @endforeach
        </div>

        {{-- Minimap: seluruh pohon + indikator area yang terlihat --}}
        @php
            $minimapHeight = 140;
            $minimapWidth = (int) min(260, max(110, round($minimapHeight * $layout['width'] / max($layout['height'], 1))));
            $minimapStroke = max(3, (int) round($layout['width'] / $minimapWidth * 1.5));
        @endphp
        <div
            x-ref="minimap"
            class="absolute bottom-4 left-4 z-10 cursor-crosshair overflow-hidden rounded-lg border border-stroke-primary bg-background-primary shadow-sm"
            @pointerdown="startMinimapJump($event)"
            @pointermove="moveMinimapJump($event)"
            @pointerup="endMinimapJump()"
            @pointercancel="endMinimapJump()"
            @pointerleave="endMinimapJump()"
        >
            <svg width="{{ $minimapWidth }}" height="{{ $minimapHeight }}" viewBox="0 0 {{ $layout['width'] }} {{ $layout['height'] }}" class="block" aria-hidden="true">
                @foreach ($layout['edges'] as $edge)
                    @php
                        $edgeFrom = $layout['nodes'][$edge['from']];
                        $edgeTo = $layout['nodes'][$edge['to']];
                    @endphp
                    <line
                        x1="{{ $edgeFrom['x'] + $nodeWidth }}" y1="{{ $edgeFrom['y'] + $halfNodeHeight }}"
                        x2="{{ $edgeTo['x'] }}" y2="{{ $edgeTo['y'] + $halfNodeHeight }}"
                        stroke="var(--ina-stroke-primary)"
                        stroke-width="{{ $minimapStroke }}"
                        opacity="0.6"
                    />
                @endforeach
                @foreach ($layout['nodes'] as $node)
                    <rect
                        x="{{ $node['x'] }}" y="{{ $node['y'] }}"
                        width="{{ $nodeWidth }}" height="{{ $nodeHeight }}" rx="4"
                        fill="var(--ina-stroke-primary)" opacity="0.5"
                    />
                @endforeach
                <rect
                    :x="Math.round(-x / scale)"
                    :y="Math.round(-y / scale)"
                    :width="Math.round(viewportWidth / scale)"
                    :height="Math.round(viewportHeight / scale)"
                    fill="none"
                    stroke="var(--ina-guide-600)"
                    stroke-width="{{ $minimapStroke }}"
                    rx="8"
                />
            </svg>
        </div>

        {{-- Toolbar zoom --}}
        <div class="absolute right-4 bottom-4 z-10 flex items-center gap-1 rounded-lg border border-stroke-primary bg-background-primary p-1 shadow-sm">
            <button
                type="button"
                @click="zoomOut()"
                class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                aria-label="Perkecil tampilan"
                title="Perkecil"
            >
                <i class="ti ti-minus text-base"></i>
            </button>
            <span class="w-12 text-center text-xs font-medium text-content-secondary" x-text="Math.round(scale * 100) + '%'"></span>
            <button
                type="button"
                @click="zoomIn()"
                class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                aria-label="Perbesar tampilan"
                title="Perbesar"
            >
                <i class="ti ti-plus text-base"></i>
            </button>
            <button
                type="button"
                @click="fit()"
                class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                aria-label="Muat seluruh topologi ke tampilan"
                title="Muat semua"
            >
                <i class="ti ti-frame text-base"></i>
            </button>
        </div>
    </div>
    <p class="mt-2 text-xs text-content-tertiary">Seret kanvas untuk menggeser, gulir roda mouse untuk memperbesar/memperkecil.</p>
</div>
