<?php

use App\Enums\ServerStatus;
use App\Models\Location;
use App\Models\Rack;
use App\Models\Server;
use Illuminate\Support\Collection;
use App\Actions\Assets\CreateServer;
use App\Actions\Assets\DeleteServer;
use App\Actions\Assets\UpdateServer;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    use \App\Livewire\Concerns\InteractsWithServerForm;

    #[Layout('layouts::admin')]
    #[Title('Server')]

    public string $search = '';

    public string $locationFilter = '';

    public string $rackFilter = 'ALL';

    public bool $showDeleteModal = false;

    public ?int $deletingId = null;

    public function mount(): void
    {
        $this->locationFilter = Location::query()->orderBy('location_id')->value('location_id') ?? '';
    }

    public function updatedLocationFilter(): void
    {
        $this->rackFilter = 'ALL';
    }

    public function askDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if ($this->deletingId !== null) {
            app(DeleteServer::class)(Server::findOrFail($this->deletingId));
        }

        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    /**
     * Data server dikelompokkan per rack, urut kode rak, "Tanpa Rack" terakhir.
     *
     * @return array<string, array{label: string, code: string|null, color: string|null, location: string|null, servers: Collection<int, Server>}>
     */
    public function raks(): array
    {
        $servers = Server::query()
            ->with(['rack.location', 'location', 'proxmox', 'panels'])
            ->when($this->locationFilter !== '', function ($query) {
                $query->where(function ($query) {
                    $query->whereHas('rack', fn ($r) => $r->where('location_id', $this->locationFilter))
                        ->orWhere(fn ($q) => $q->whereNull('rack_id')->where('location_id', $this->locationFilter));
                });
            })
            ->when($this->rackFilter !== 'ALL', function ($query) {
                if ($this->rackFilter === 'NONE') {
                    $query->whereNull('rack_id');
                } else {
                    $query->where('rack_id', $this->rackFilter);
                }
            })
            ->when(
                $this->search !== '',
                fn ($query) => $query->where(function ($query) {
                    $query->where('server_name', 'like', "%{$this->search}%")
                        ->orWhere('brand', 'like', "%{$this->search}%")
                        ->orWhere('cpu', 'like', "%{$this->search}%")
                        ->orWhere('os', 'like', "%{$this->search}%")
                        ->orWhere('storage', 'like', "%{$this->search}%")
                        ->orWhere('memory', 'like', "%{$this->search}%");
                })
            )
            ->orderBy('position')
            ->orderBy('server_name')
            ->get();

        $grouped = $servers->groupBy(fn (Server $server) => $server->rack_id ?? '_null');

        $raks = [];

        foreach ($grouped->keys() as $key) {
            /** @var Rack|null $rack */
            $rack = $key === '_null' ? null : $servers->firstWhere('rack_id', $key)?->rack;

            $raks[$key] = [
                'label' => $rack?->name ?? 'Tanpa Rack',
                'code' => $rack?->rack_id,
                'color' => $rack?->color,
                'location' => $rack?->location?->name,
                'servers' => $grouped->get($key)->sortBy(fn (Server $server) => $server->position ?? PHP_INT_MAX)->values(),
            ];
        }

        // "Tanpa Rack" ke urutan posisi terakhir
        uksort($raks, function ($a, $b) {
            $an = $a === '_null';
            $bn = $b === '_null';

            if ($an !== $bn) {
                return $an ? 1 : -1;
            }

            return $a <=> $b;
        });

        return $raks;
    }

    /**
     * Rack options untuk filter, mengikuti lokasi terpilih.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function rackFilterOptions(): array
    {
        $options = [
            [
                'value' => 'ALL',
                'label' => 'Semua Rack',
            ],
        ];

        if ($this->locationFilter !== '') {
            $showRackless = Server::query()
                ->whereNull('rack_id')
                ->where('location_id', $this->locationFilter)
                ->exists();

            if ($showRackless) {
                $options[] = [
                    'value' => 'NONE',
                    'label' => 'Tanpa Rack',
                ];
            }

            $racks = Rack::query()
                ->with('location')
                ->where('location_id', $this->locationFilter)
                ->orderBy('rack_id')
                ->get();

            foreach ($racks as $rack) {
                $options[] = [
                    'value' => $rack->rack_id,
                    'label' => "{$rack->name} ({$rack->rack_id})",
                ];
            }
        }

        return $options;
    }
};

?>

<div>
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-content-primary">Server</h1>
            <p class="mt-1 text-sm text-content-secondary">Daftar server pada rak data center, dikelompokkan per rak.</p>
        </div>
        @can('infrastructure.manage')
        <button type="button" wire:click="openCreate" class="ina-button ina-button--primary ina-button--md">
            <i class="ti ti-plus text-lg" aria-hidden="true"></i>
            Tambah Server
        </button>
        @endcan
    </div>

    {{-- Filter kiri + Search kanan --}}
    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex gap-3">
            <div class="w-44">
                <x-idds.select
                    wire-prop="locationFilter"
                    :value="$locationFilter"
                    :options="$this->locationOptions()->map(fn ($loc) => ['value' => $loc->location_id, 'label' => $loc->name])->all()"
                    placeholder="Pilih Lokasi"
                    aria-label="Filter lokasi"
                />
            </div>

            <div class="w-44">
                <x-idds.select
                    wire-prop="rackFilter"
                    :value="$rackFilter"
                    :options="$this->rackFilterOptions()"
                    placeholder="Semua Rack"
                    aria-label="Filter rack"
                />
            </div>
        </div>

        <div class="w-full max-w-sm">
            <div class="ina-input-search ina-input-search--size-md">
                <div class="ina-input-search__wrapper">
                    <i class="ti ti-search ina-input-search__prefix-icon" aria-hidden="true"></i>
                    <input
                        type="search"
                        class="ina-input-search__input"
                        placeholder="Cari nama, brand, atau CPU..."
                        wire:model.live.debounce.300ms="search"
                        aria-label="Cari server"
                    >
                </div>
            </div>
        </div>
    </div>

    {{-- Data: kelompok per rack --}}
    @if (count($this->raks()) > 0)
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 mt-8 ">
            @foreach ($this->raks() as $rak)
                <section class="">
                    <div class="ina-card ina-card--stack p-2">
                        <div class="flex flex-wrap items-center gap-3">

                            <i class="ti ti-server text-lg text-content-tertiary" aria-hidden="true"></i>
                            <h2 class="text-base font-semibold text-content-primary">{{ $rak['label'] }}</h2>
                            @if ($rak['code'] !== null)
                                <span class="text-[12px] text-content-tertiary">{{ $rak['code'] }}</span>
                            @endif
                            <span class="ina-badge ina-badge--soft ina-badge--neutral ina-badge--sm">
                                {{ $rak['servers']->count() }} unit
                            </span>
                            @if ($rak['location'] !== null)
                                <span class="ina-badge ina-badge--soft ina-badge--info ina-badge--sm">
                                    <i class="ti ti-map-pin text-sm" aria-hidden="true"></i>
                                    {{ $rak['location'] }}
                                </span>
                            @endif
                        </div>
                    </div>
    
                    {{-- <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3"> --}}
                    <div class="mt-3 flex flex-col gap-4">
                        @foreach ($rak['servers'] as $server)
                            <div class="ina-card ina-card--stack p-5" x-data="{ open: false }">
                                <button
                                    type="button"
                                    class="flex w-full cursor-pointer items-start justify-between gap-2 text-left"
                                    @click="open = !open"
                                    :aria-expanded="open.toString()"
                                    aria-label="Tampilkan detail {{ $server->server_name }}"
                                >
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <h3 class="truncate text-base font-semibold text-content-primary" title="{{ $server->server_name }}">
                                                {{ $server->server_name }}
                                            </h3>
                                        </div>
                                        <p class="mt-0.5 break-words text-sm text-content-secondary" title="{{ $server->brand }}">
                                            {{ $server->brand ?? '-' }}
                                        </p>
                                    </div>
                                    <span class="flex shrink-0 items-center gap-2">
                                        <span class="ina-badge ina-badge--soft ina-badge--sm {{ $server->status === ServerStatus::On ? 'ina-badge--positive' : 'ina-badge--neutral' }} whitespace-nowrap">
                                            {{ $server->status->value }}
                                        </span>
                                        @if ($rak['code'] !== null)
                                            <span class="ina-badge ina-badge--soft ina-badge--brand ina-badge--md whitespace-nowrap">
                                                Pos {{ $server->position ?? '-' }}
                                            </span>
                                        @endif
                                        <i class="ti ti-chevron-down text-lg text-content-tertiary transition-transform duration-200" :class="open && 'rotate-180'" aria-hidden="true"></i>
                                    </span>
                                </button>
    
                                <div
                                    class="grid transition-[grid-template-rows] duration-300 ease-in-out"
                                    :class="open ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'"
                                >
                                    <div class="overflow-hidden">
                                        <dl class="mt-4 space-y-2 border-t border-stroke-primary pt-4">
                                    <div>
                                        <div class="flex justify-between gap-3">
                                            <dt class="text-[11px] font-medium uppercase tracking-wide text-content-tertiary">Storage</dt>
                                            @if ($server->storage_size !== null)
                                                <span class="text-[11px] text-content-tertiary">{{ number_format($server->storage_size / 1024) }} GB</span>
                                            @endif
                                        </div>
                                        <dd class="mt-0.5 text-[13px] font-medium leading-relaxed text-content-primary break-words" title="{{ $server->storage ?? '-' }}">
                                            {{ $server->storage ?? '-' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <div class="flex justify-between gap-3">
                                            <dt class="text-[11px] font-medium uppercase tracking-wide text-content-tertiary">Memory</dt>
                                            @if ($server->memory_size !== null)
                                                <span class="text-[11px] text-content-tertiary">{{ number_format($server->memory_size / 1024) }} GB</span>
                                            @endif
                                        </div>
                                        <dd class="mt-0.5 text-[13px] font-medium leading-relaxed text-content-primary break-words" title="{{ $server->memory ?? '-' }}">
                                            {{ $server->memory ?? '-' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <div class="flex justify-between gap-3">
                                            <dt class="text-[11px] font-medium uppercase tracking-wide text-content-tertiary">CPU</dt>
                                        </div>
                                        <dd class="mt-0.5 text-[13px] font-medium leading-relaxed text-content-primary break-words" title="{{ $server->cpu }}">
                                            {{ $server->cpu ?? '-' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <div class="flex justify-between gap-3">
                                            <dt class="text-[11px] font-medium uppercase tracking-wide text-content-tertiary">Static IP</dt>
                                        </div>
                                        <dd class="mt-0.5 text-[13px] font-medium leading-relaxed text-content-primary" title="{{ $server->static_ip }}">
                                            {{ $server->static_ip ?? '-' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <div class="flex justify-between gap-3">
                                            <dt class="text-[11px] font-medium uppercase tracking-wide text-content-tertiary">OS</dt>
                                        </div>
                                        <dd class="mt-0.5 text-[13px] font-medium leading-relaxed text-content-primary break-words" title="{{ $server->os }}">
                                            {{ $server->os ?? '-' }}
                                        </dd>
                                    </div>
                                        </dl>
                                    </div>
                                </div>

                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    @if ($server->effective() !== null)
                                        <span class="ina-badge ina-badge--soft ina-badge--info ina-badge--sm">
                                            <i class="ti ti-map-pin text-sm" aria-hidden="true"></i>
                                            {{ $server->effective()->name }}
                                        </span>
                                    @endif
                                    @if ($server->proxmox !== null)
                                        <span class="ina-badge ina-badge--soft ina-badge--brand ina-badge--sm" title="Menjalankan Proxmox">
                                            <i class="ti ti-stack-2 text-sm" aria-hidden="true"></i>
                                            Proxmox
                                        </span>
                                    @endif
                                    @if ($server->panels->isNotEmpty())
                                        <span class="ina-badge ina-badge--soft ina-badge--info ina-badge--sm" title="Panel terpasang langsung">
                                            <i class="ti ti-app-window text-sm" aria-hidden="true"></i>
                                            {{ $server->panels->count() }} panel
                                        </span>
                                    @endif
                                </div>
    
                                <div class="mt-5 flex items-center gap-2 border-t border-stroke-primary pt-4">
                                    <a href="{{ route('admin.assets.servers.show', $server->server_id) }}" wire:navigate class="ina-button ina-button--primary ina-button--sm">
                                        <i class="ti ti-eye text-base" aria-hidden="true"></i>
                                        Detail
                                    </a>
                                    @can("infrastructure.manage")
                                    <button type="button" wire:click="edit({{ $server->id }})" class="ina-button ina-button--secondary ina-button--sm">
                                        <i class="ti ti-pencil text-base" aria-hidden="true"></i>
                                        Ubah
                                    </button>
                                    @endcan
                                    @can("infrastructure.manage")
                                    <button type="button" wire:click="askDelete({{ $server->id }})" class="ina-button ina-button--secondary ina-button--sm text-negative-600! hover:bg-negative-50!">
                                        <i class="ti ti-trash text-base" aria-hidden="true"></i>
                                        Hapus
                                    </button>
                                    @endcan
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @else

        {{-- Empty state (IDDS) --}}
        <div class="ina-card ina-card--stack mt-4 px-6 py-12 text-center">
            <x-asset.illustration name="{{ $search !== '' ? 'noresult' : 'nodata' }}" size="sm" />
            <h2 class="mt-4 text-base font-semibold text-content-primary">
                {{ $search !== '' ? 'Tidak ada hasil untuk "'.$search.'"' : 'Belum ada server' }}
            </h2>
            <p class="mt-2 mx-auto max-w-sm text-sm text-content-secondary">
                {{ $search !== '' ? 'Coba kata kunci lain atau bersihkan pencarian.' : 'Server yang ditambahkan akan tampil di sini. Mulai dengan mengisi data server pertama.' }}
            </p>
        </div>
    @endif

    @include('pages.admin.assets.partials.server-form-modal')

    {{-- Modal Konfirmasi Hapus (IDDS Confirm Modal) --}}
    <div
        x-data="{ open: $wire.entangle('showDeleteModal') }"
        x-on:keydown.escape.window="if (open) open = false"
        x-cloak
    >
        <div class="fixed inset-0 z-50 bg-black/50" x-show="open" x-transition.opacity @click="open = false" aria-hidden="true"></div>

        <div
            class="fixed left-1/2 top-1/2 z-50 w-full max-w-md max-h-[calc(100vh-32px)] overflow-y-auto -translate-x-1/2 -translate-y-1/2 rounded-xl border border-stroke-primary bg-background-primary p-6 shadow-lg"
            x-show="open"
            x-transition.opacity.duration.150ms
            role="dialog"
            aria-modal="true"
            aria-labelledby="delete-modal-title"
            @click.stop
        >
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-negative-50 text-negative-600">
                        <i class="ti ti-alert-triangle text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-content-primary" id="delete-modal-title">Hapus Server</h2>
                        <p class="mt-1 text-sm text-content-secondary">
                            Server <span class="font-medium text-content-primary">{{ Server::find($deletingId)?->server_name ?? '-' }}</span> akan dihapus dari daftar. Tindakan ini tidak bisa dibatalkan.
                        </p>
                    </div>
                </div>
                <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="$set('showDeleteModal', false)" aria-label="Tutup">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="ina-button ina-button--secondary ina-button--md" wire:click="$set('showDeleteModal', false)">
                    Batal
                </button>
                <button type="button" class="ina-button ina-button--secondary ina-button--md bg-negative-600! text-white! hover:bg-negative-700!" wire:loading.attr="disabled" wire:click="delete">
                    Hapus
                </button>
            </div>
        </div>
    </div>
</div>
