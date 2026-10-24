<?php

use App\Models\Application;
use App\Models\Location;
use App\Models\Panel;
use App\Models\Proxmox;
use App\Models\Rack;
use App\Models\Server;
use App\Models\Vms;
use App\Models\Waf;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new class extends Component
{
    #[Layout('layouts::admin')]
    #[Title('Topology Explorer')]

    public string $search = '';

    /** @var array<string, bool> jenis aset yang ditampilkan (re-parent bila dimatikan) */
    public array $visibleTypes = [
        'location' => true,
        'rack' => true,
        'server' => true,
        'nas' => true,
        'proxmox' => true,
        'vms' => true,
        'panel' => true,
        'waf' => true,
        'application' => true,
    ];

    public string $mode = 'hierarchy';

    public function setMode(string $mode): void
    {
        $this->mode = $mode;
    }

    public function toggleType(string $type): void
    {
        if (array_key_exists($type, $this->visibleTypes)) {
            $this->visibleTypes[$type] = ! $this->visibleTypes[$type];
        }
    }

    public function showAllTypes(): void
    {
        foreach ($this->visibleTypes as $key => $value) {
            $this->visibleTypes[$key] = true;
        }
    }

    /**
     * Pohon topologi dari data nyata. Semua rantai bersumber dari relasi database;
     * aset tanpa relasi tetap muncul sebagai daun (relasi opsional tidak dipaksakan).
     */
    public function rawTree(): array
    {
        $locations = Location::query()
            ->with([
                'racks.servers.proxmox.vms.panels',
                'racks.servers.proxmox.vms.wafs',
                'racks.servers.proxmox.vms.hostings.application',
                'racks.servers.panels',
                'racks.servers.wafs',
                'racks.servers.hostings.application',
                'racks.nas',
                'servers.proxmox.vms.panels',
                'servers.proxmox.vms.wafs',
                'servers.proxmox.vms.hostings.application',
                'servers.panels',
                'servers.wafs',
                'servers.hostings.application',
                'nas',
            ])
            ->orderBy('location_id')
            ->get();

        return [
            'type' => 'root',
            'label' => 'Infrastruktur',
            'code' => null,
            'icon' => 'ti-sitemap',
            'color' => 'brand',
            'url' => null,
            'children' => $locations->map(fn (Location $location) => $this->locationNode($location))->all(),
        ];
    }

    /**
     * Pohon yang difilter: jenis tidak aktif di-re-parent ke induk,
     * pencarian memangkas cabang yang tidak memuat kecocokan (induk tetap utuh).
     */
    public function tree(): array    {
        $tree = $this->typeFilteredTree($this->rawTree());

        if (trim($this->search) !== '') {
            $tree = $this->searchPrunedTree($tree, mb_strtolower(trim($this->search)));
        }

        return $tree;
    }

    /**
     * Total node yang tampil (untuk ringkasan kartu "aset ditampilkan").
     */
    public function visibleCount(): int
    {
        $count = 0;

        $walk = function (array $node, callable $walk) use (&$count) {
            if ($node['type'] !== 'root') {
                $count++;
            }

            foreach ($node['children'] as $child) {
                $walk($child, $walk);
            }
        };

        $walk($this->tree(), $walk);

        return $count;
    }

    private function locationNode(Location $location): array
    {
        $children = [];

        foreach ($location->racks as $rack) {
            $children[] = $this->rackNode($rack);
        }

        // Server & NAS tanpa rack langsung di server room
        foreach ($location->servers as $server) {
            $children[] = $this->serverNode($server, false);
        }

        foreach ($location->nas as $nas) {
            $children[] = $this->nasNode($nas);
        }

        return [
            'type' => 'location',
            'label' => $location->name,
            'code' => $location->location_id,
            'icon' => 'ti-building',
            'color' => 'info',
            'url' => route('admin.server-rooms.show', $location->location_id),
            'children' => $children,
        ];
    }

    private function rackNode(Rack $rack): array
    {
        return [
            'type' => 'rack',
            'label' => $rack->name,
            'code' => $rack->rack_id,
            'icon' => 'ti-server-2',
            'color' => 'neutral',
            'url' => route('admin.assets.racks.show', $rack->rack_id),
            'children' => array_merge(
                $rack->servers->map(fn (Server $server) => $this->serverNode($server, true))->all(),
                $rack->nas->map(fn (\App\Models\Nas $nas) => $this->nasNode($nas))->all(),
            ),
        ];
    }

    private function nasNode(\App\Models\Nas $nas): array
    {
        return [
            'type' => 'nas',
            'label' => $nas->name,
            'code' => $nas->nas_id,
            'icon' => 'ti-database',
            'color' => 'info',
            'url' => route('admin.assets.nas.show', $nas->nas_id),
            'children' => [],
        ];
    }

    private function serverNode(Server $server, bool $inRack): array
    {
        $children = [];

        if ($server->proxmox !== null) {
            $children[] = $this->proxmoxNode($server->proxmox);
        }

        foreach ($server->panels as $panel) {
            $children[] = $this->panelNode($panel);
        }

        foreach ($server->wafs as $waf) {
            $children[] = $this->wafNode($waf);
        }

        foreach ($server->hostings as $hosting) {
            if ($hosting->application !== null) {
                $children[] = $this->applicationNode($hosting->application);
            }
        }

        return [
            'type' => 'server',
            'label' => $server->server_name,
            'code' => $server->server_id,
            'icon' => 'ti-server',
            'color' => 'brand',
            'url' => route('admin.assets.servers.show', $server->server_id),
            'children' => $children,
        ];
    }

    private function proxmoxNode(Proxmox $proxmox): array
    {
        return [
            'type' => 'proxmox',
            'label' => $proxmox->name,
            'code' => $proxmox->proxmox_id,
            'icon' => 'ti-stack-2',
            'color' => 'warning',
            'url' => route('admin.assets.proxmox.show', $proxmox->proxmox_id),
            'children' => $proxmox->vms->map(fn (Vms $vm) => $this->vmNode($vm))->all(),
        ];
    }

    private function vmNode(Vms $vm): array
    {
        $children = [];

        foreach ($vm->panels as $panel) {
            $children[] = $this->panelNode($panel);
        }

        foreach ($vm->wafs as $waf) {
            $children[] = $this->wafNode($waf);
        }

        foreach ($vm->hostings as $hosting) {
            if ($hosting->application !== null) {
                $children[] = $this->applicationNode($hosting->application);
            }
        }

        return [
            'type' => 'vms',
            'label' => $vm->name,
            'code' => $vm->vm_id,
            'icon' => 'ti-box-multiple-3',
            'color' => 'info',
            'url' => route('admin.assets.vms.show', $vm->vm_id),
            'children' => $children,
        ];
    }

    private function panelNode(Panel $panel): array
    {
        $host = $panel->vm !== null ? $panel->vm : $panel->server;

        return [
            'type' => 'panel',
            'label' => $panel->name,
            'code' => $panel->panel_id.($host !== null ? ' · di '.$host->name : ''),
            'icon' => 'ti-app-window',
            'color' => 'neutral',
            'url' => route('admin.assets.panels.show', $panel->panel_id),
            'children' => [],
        ];
    }

    private function wafNode(Waf $waf): array
    {
        $host = $waf->vm !== null ? $waf->vm : $waf->server;

        return [
            'type' => 'waf',
            'label' => $waf->name,
            'code' => $waf->waf_id.($host !== null ? ' · di '.$host->name : ''),
            'icon' => 'ti-shield-lock',
            'color' => 'waf',
            'url' => route('admin.assets.wafs.show', $waf->waf_id),
            'children' => [],
        ];
    }

    private function applicationNode(Application $application): array
    {
        return [
            'type' => 'application',
            'label' => $application->name,
            'code' => $application->application_id,
            'icon' => 'ti-apps',
            'color' => 'positive',
            'url' => route('admin.applications.show', $application->application_id),
            'children' => [],
        ];
    }

    /**
     * Jenis yang tidak ditampilkan dilepas dari pohon; anak-anaknya
     * dipindah (re-parent) ke induk agar tetap terlihat.
     */
    private function typeFilteredTree(array $node): array
    {
        $children = [];

        foreach ($node['children'] as $child) {
            $pruned = $this->typeFilteredTree($child);

            if ($pruned === null) {
                continue;
            }

            if ($this->visibleTypes[$child['type']] ?? true) {
                $children[] = $pruned;
            } else {
                // Node disembunyikan, anak-anaknya naik satu level
                $children = array_merge($children, $pruned['children']);
            }
        }

        if (! ($this->visibleTypes[$node['type']] ?? true)) {
            return [
                'type' => $node['type'],
                'label' => $node['label'],
                'code' => $node['code'],
                'icon' => $node['icon'],
                'color' => $node['color'],
                'url' => $node['url'],
                'children' => $children,
                'hidden' => true,
            ];
        }

        $node['children'] = $children;

        return $node;
    }

    /**
     * Cari: node tampil bila label/kode cocok ATAU punya keturunan yang cocok.
     * Cabang tanpa kecocokan dipangkas; semua sisa dibuat seluler terbuka.
     */
    private function searchPrunedTree(array $node, string $search): ?array
    {
        $text = ($node['label'].' '.($node['code'] ?? '')).' ';
        $selfMatch = str_contains(mb_strtolower($text), $search);

        $children = [];

        foreach ($node['children'] as $child) {
            $pruned = $this->searchPrunedTree($child, $search);

            if ($pruned !== null) {
                $children[] = $pruned;
            }
        }

        if (! $selfMatch && $children === [] && $node['type'] !== 'root') {
            return null;
        }

        $node['children'] = $children;

        return $node;
    }
};
?>

<div>
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-content-primary">Topology Explorer</h1>
            <p class="mt-1 text-sm text-content-secondary">Jelajahi rantai infrastruktur: Server Room → Rack → Server → Proxmox → VM → Panel → WAF → Application.</p>
        </div>

        {{-- Mode --}}
        <div class="flex gap-1 w-fit rounded-lg border border-stroke-primary bg-background-primary p-1">
            <button
                type="button"
                wire:click="setMode('hierarchy')"
                @class([
                    'flex h-9 items-center gap-2 rounded-md px-4 text-[13px] font-medium transition-colors duration-150',
                    'bg-guide-50 text-guide-600' => $mode === 'hierarchy',
                    'text-content-secondary hover:bg-neutral-100 hover:text-content-primary' => $mode !== 'hierarchy',
                ])
            >
                <i class="ti ti-list-tree text-base"></i>
                Hierarki
            </button>
            <button
                type="button"
                wire:click="setMode('diagram')"
                @class([
                    'flex h-9 items-center gap-2 rounded-md px-4 text-[13px] font-medium transition-colors duration-150',
                    'bg-guide-50 text-guide-600' => $mode === 'diagram',
                    'text-content-secondary hover:bg-neutral-100 hover:text-content-primary' => $mode !== 'diagram',
                ])
            >
                <i class="ti ti-vector-bezier text-base"></i>
                Diagram
            </button>
        </div>
    </div>

    {{-- Filter jenis + pencarian --}}
    <div class="mt-6 flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            @foreach ([
                'location' => ['build', 'Server Room', 'ti-building'],
                'rack' => ['rack', 'Rack', 'ti-server-2'],
                'server' => ['server', 'Server', 'ti-server'],
                'nas' => ['nas', 'NAS', 'ti-database'],
                'proxmox' => ['proxmox', 'Proxmox', 'ti-stack-2'],
                'vms' => ['vms', 'Virtual Machine', 'ti-box-multiple-3'],
                'panel' => ['panel', 'Panel', 'ti-app-window'],
                'waf' => ['waf', 'WAF', 'ti-shield-lock'],
                'application' => ['apps', 'Aplikasi', 'ti-apps'],
            ] as $typeKey => [$unused, $typeLabel, $typeIcon])
                <button
                    type="button"
                    wire:click="toggleType('{{ $typeKey }}')"
                    @class([
                        'flex h-8 items-center gap-1.5 rounded-full border px-3 text-[12px] font-medium transition-colors duration-150',
                        $visibleTypes[$typeKey] ? 'border-guide-300 bg-guide-50 text-guide-600' : 'border-stroke-primary bg-background-primary text-content-tertiary',
                    ])
                    @if ($visibleTypes[$typeKey]) title="Klik untuk menyembunyikan {{ $typeLabel }}" @else title="Klik untuk menampilkan {{ $typeLabel }}" @endif
                >
                    <i class="ti {{ $typeIcon }} text-sm"></i>
                    {{ $typeLabel }}
                    <i class="ti {{ $visibleTypes[$typeKey] ? 'ti-check text-[10px]' : 'ti-x text-[10px]' }}"></i>
                </button>
            @endforeach
            @if (collect($this->visibleTypes)->contains(fn ($enabled) => $enabled === false))
                <button type="button" wire:click="showAllTypes" class="text-xs font-medium text-guide-600 hover:underline">Tampilkan semua</button>
            @endif
        </div>

        <div class="w-full max-w-xs">
            <div class="ina-input-search ina-input-search--size-md">
                <div class="ina-input-search__wrapper">
                    <i class="ti ti-search ina-input-search__prefix-icon" aria-hidden="true"></i>
                    <input
                        type="search"
                        class="ina-input-search__input"
                        placeholder="Cari aset di topologi..."
                        wire:model.live.debounce.300ms="search"
                        aria-label="Cari aset di topologi"
                    >
                </div>
            </div>
        </div>
    </div>

    {{-- Ringkasan tampilan --}}
    <p class="mt-3 text-xs text-content-tertiary">{{ $this->visibleCount() }} aset ditampilkan{{ trim($this->search) !== '' ? ' berdasarkan pencarian "'.$this->search.'" dan filter jenis.' : '.' }}</p>

    {{-- Tree --}}
    @php $tree = $this->tree(); @endphp
    @if (count($tree['children']) > 0)
        <div class="mt-4 rounded-lg border border-stroke-primary bg-background-primary p-4 {{ $mode === 'diagram' ? 'overflow-x-auto' : '' }}">
            @include('components.topology.node-tree', ['node' => $tree])
        </div>
    @else
        {{-- Empty state (IDDS) --}}
        <div class="ina-card ina-card--stack mt-4 px-6 py-12 text-center">
            <x-asset.illustration name="{{ $search !== '' ? 'noresult' : 'nodata' }}" size="sm" />
            <h2 class="mt-4 text-base font-semibold text-content-primary">Tidak ada aset yang cocok</h2>
            <p class="mt-2 mx-auto max-w-sm text-sm text-content-secondary">Ubah kata kunci pencarian atau aktifkan jenis aset yang tersembunyi.</p>
        </div>
    @endif
</div>
