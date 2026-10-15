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
use Livewire\Component;

new class extends Component
{
    #[Layout('layouts::admin')]
    #[Title('Server')]

    public string $search = '';

    public string $locationFilter = '';

    public string $rackFilter = 'ALL';

    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public string $server_name = '';

    public string $rack_id = '';

    public string $position = '';

    public string $brand = '';

    public string $storage_size = '';

    public string $storage = '';

    public string $memory_size = '';

    public string $memory = '';

    public string $status = ServerStatus::Off->value;

    public string $cpu = '';

    public string $static_ip = '';

    public string $location_id = '';

    public string $os = '';

    public string $username = '';

    public string $password = '';

    public function updatedRackId(string $value): void
    {
        if ($value === '') {
            $this->location_id = '';

            return;
        }

        $this->location_id = (string) (Rack::with('location')->where('rack_id', $value)->first()?->location?->location_id ?? '');
    }

    public function mount(): void
    {
        $this->locationFilter = Location::query()->orderBy('location_id')->value('location_id') ?? '';
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $server = Server::findOrFail($id);

        $this->editingId = $server->id;
        $this->server_name = $server->server_name;
        $this->rack_id = (string) $server->rack_id;
        $this->location_id = (string) $server->location_id;
        $this->position = (string) $server->position;
        $this->brand = (string) $server->brand;
        $this->storage_size = (string) $server->storage_size;
        $this->storage = (string) $server->storage;
        $this->memory_size = (string) $server->memory_size;
        $this->memory = (string) $server->memory;
        $this->status = $server->status?->value ?? ServerStatus::Off->value;
        $this->cpu = (string) $server->cpu;
        $this->static_ip = (string) $server->static_ip;
        $this->os = (string) $server->os;
        $this->username = (string) $server->username;
        $this->password = '';

        $this->showModal = true;
    }

    public function save(): void
    {
        // Semua rule & pesan validasi dimiliki oleh action (satu sumber kebenaran).
        $data = [
            'server_name' => $this->server_name,
            'rack_id' => $this->rack_id === '' ? null : $this->rack_id,
            'location_id' => $this->location_id === '' ? null : $this->location_id,
            'position' => $this->position === '' ? null : (int) $this->position,
            'brand' => $this->brand === '' ? null : $this->brand,
            'storage_size' => $this->storage_size === '' ? null : (int) $this->storage_size,
            'storage' => $this->storage === '' ? null : $this->storage,
            'memory_size' => $this->memory_size === '' ? null : (int) $this->memory_size,
            'memory' => $this->memory === '' ? null : $this->memory,
            'status' => $this->status,
            'cpu' => $this->cpu === '' ? null : $this->cpu,
            'static_ip' => $this->static_ip === '' ? null : $this->static_ip,
            'os' => $this->os === '' ? null : $this->os,
            'username' => $this->username,
            'password' => $this->password,
        ];

        if ($this->editingId !== null) {
            app(UpdateServer::class)(Server::findOrFail($this->editingId), $data);
        } else {
            app(CreateServer::class)($data);
        }

        $this->showModal = false;
        $this->resetForm();
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

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->resetValidation();
        $this->server_name = '';
        $this->rack_id = '';
        $this->location_id = '';
        $this->position = '';
        $this->brand = '';
        $this->storage_size = '';
        $this->storage = '';
        $this->memory_size = '';
        $this->memory = '';
        $this->status = ServerStatus::Off->value;
        $this->cpu = '';
        $this->static_ip = '';
        $this->os = '';
        $this->username = '';
        $this->password = '';
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

    public function updatedLocationFilter(): void
    {
        $this->rackFilter = 'ALL';
    }

    /**
     * Location options untuk filter (tanpa "Semua" — wajib memilih).
     *
     * @return Collection<int, Location>
     */
    public function locationOptions(): Collection
    {
        return Location::query()->orderBy('location_id')->get();
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

    /**
     * Daftar rack untuk pilihan form, bergrup per lokasi.
     *
     * @return array<string, array<int, array{value: string, label: string}>>
     */
    public function rackOptions(): array
    {
        return Rack::query()
            ->with('location')
            ->orderBy('rack_id')
            ->get()
            ->groupBy(fn (Rack $rack) => $rack->location?->name ?? 'Tanpa Lokasi')
            ->map(fn ($racks) => $racks->map(fn (Rack $rack) => [
                'value' => $rack->rack_id,
                'label' => "{$rack->name} ({$rack->rack_id})",
            ])->all())
            ->all();
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
        <button type="button" wire:click="openCreate" class="ina-button ina-button--primary ina-button--md">
            <i class="ti ti-plus text-lg" aria-hidden="true"></i>
            Tambah Server
        </button>
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
                                        <div class="flex items-center justify-between gap-3">
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
                                        <div class="flex items-center justify-between gap-3">
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
                                        <div class="flex items-center justify-between gap-3">
                                            <dt class="text-[11px] font-medium uppercase tracking-wide text-content-tertiary">CPU</dt>
                                        </div>
                                        <dd class="mt-0.5 text-[13px] font-medium leading-relaxed text-content-primary break-words" title="{{ $server->cpu }}">
                                            {{ $server->cpu ?? '-' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <div class="flex items-center justify-between gap-3">
                                            <dt class="text-[11px] font-medium uppercase tracking-wide text-content-tertiary">Static IP</dt>
                                        </div>
                                        <dd class="mt-0.5 text-[13px] font-medium leading-relaxed text-content-primary" title="{{ $server->static_ip }}">
                                            {{ $server->static_ip ?? '-' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <div class="flex items-center justify-between gap-3">
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
                                    <button type="button" wire:click="edit({{ $server->id }})" class="ina-button ina-button--secondary ina-button--sm">
                                        <i class="ti ti-pencil text-base" aria-hidden="true"></i>
                                        Ubah
                                    </button>
                                    <button type="button" wire:click="askDelete({{ $server->id }})" class="ina-button ina-button--secondary ina-button--sm text-negative-600! hover:bg-negative-50!">
                                        <i class="ti ti-trash text-base" aria-hidden="true"></i>
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @else

        {{-- Empty state (IDDS) --}}
        <div class="ina-card ina-card--stack mt-4 items-center px-6 py-12 text-center">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-background-tertiary">
                <i class="ti ti-server-2 text-2xl text-content-tertiary"></i>
            </div>
            <h2 class="mt-4 text-base font-semibold text-content-primary">
                {{ $search !== '' ? 'Tidak ada hasil untuk "'.$search.'"' : 'Belum ada server' }}
            </h2>
            <p class="mt-2 max-w-sm text-sm text-content-secondary">
                {{ $search !== '' ? 'Coba kata kunci lain atau bersihkan pencarian.' : 'Server yang ditambahkan akan tampil di sini. Mulai dengan mengisi data server pertama.' }}
            </p>
            @if ($search === '')
                <button type="button" wire:click="openCreate" class="ina-button ina-button--primary ina-button--md mt-6">
                    <i class="ti ti-plus text-lg" aria-hidden="true"></i>
                    Tambah Server
                </button>
            @else
                <button type="button" wire:click="$set('search', '')" class="ina-button ina-button--secondary ina-button--md mt-6">
                    Bersihkan Pencarian
                </button>
            @endif
        </div>
    @endif

    {{-- Modal Add/Edit (IDDS Task Modal) --}}
    <div
        x-data="{ open: $wire.entangle('showModal') }"
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
            aria-labelledby="server-modal-title"
            @click.stop
        >
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-content-primary" id="server-modal-title">
                        {{ $editingId !== null ? 'Ubah Server' : 'Tambah Server' }}
                    </h2>
                    <p class="mt-1 text-sm text-content-secondary">
                        {{ $editingId !== null ? 'Perbarui data server.' : 'Lengkapi data server untuk ditambahkan ke daftar.' }}
                    </p>
                </div>
                <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="closeModal" aria-label="Tutup">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <form class="mt-6 space-y-5" wire:submit="save">
                <div class="ina-text-field">
                    <label for="server_name" class="ina-text-field__label">
                        Nama Server
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('server_name') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="server_name"
                            wire:model="server_name"
                            class="ina-text-field__input"
                            placeholder="SRV-APP-01"
                            required
                            autofocus
                        >
                    </div>
                    @error('server_name')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="ina-text-field">
                        <label for="rack_id" class="ina-text-field__label">
                            Rack
                        </label>
                        <x-idds.select
                            wire-prop="rack_id"
                            :value="$rack_id"
                            :groups="collect($this->rackOptions())->map(fn ($options, $label) => ['label' => $label, 'options' => $options])->values()->prepend(['label' => null, 'options' => [['value' => '', 'label' => 'Tanpa Rack']]])->all()"
                            placeholder="Tanpa Rack"
                            aria-label="Pilih rack"
                            :error="$errors->has('rack_id')"
                        />
                        <p class="mt-1.5 text-xs text-content-tertiary">
                            {{ $rack_id !== '' ? 'Lokasi mengikuti rack yang dipilih.' : 'Pilih lokasi bila tidak memakai rack.' }}
                        </p>
                        @error('rack_id')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="form-location" class="ina-text-field__label">
                            Location
                            <span class="ina-text-field__required" @if ($rack_id !== '')
                                style="display: none"
                            @endif>*</span>
                        </label>
                        <x-idds.select
                            wire-prop="location_id"
                            :value="$location_id"
                            :options="$this->locationOptions()->map(fn ($loc) => ['value' => $loc->location_id, 'label' => $loc->name])->all()"
                            placeholder="Pilih lokasi..."
                            aria-label="Pilih lokasi"
                            :disabled="$rack_id !== ''"
                            :error="$errors->has('location_id')"
                        />
                        @error('location_id')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="ina-text-field">
                    <label for="position" class="ina-text-field__label">
                        Position
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('position') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="number"
                            min="1"
                            id="position"
                            wire:model="position"
                            class="ina-text-field__input"
                            placeholder="1"
                        >
                    </div>
                    <p class="mt-1.5 text-xs text-content-tertiary">1 = paling atas di dalam rack.</p>
                    @error('position')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-field">
                    <label for="brand" class="ina-text-field__label">
                        Brand
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('brand') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="brand"
                            wire:model="brand"
                            class="ina-text-field__input"
                            placeholder="HPE ProLiant DL380 Gen10"
                        >
                    </div>
                    @error('brand')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="ina-text-field">
                        <label for="storage_size" class="ina-text-field__label">
                            Storage (MB)
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('storage_size') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="number"
                                min="0"
                                id="storage_size"
                                wire:model="storage_size"
                                class="ina-text-field__input"
                                placeholder="475000"
                            >
                        </div>
                        @error('storage_size')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="memory_size" class="ina-text-field__label">
                            Memory (MB)
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('memory_size') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="number"
                                min="0"
                                id="memory_size"
                                wire:model="memory_size"
                                class="ina-text-field__input"
                                placeholder="64081"
                            >
                        </div>
                        @error('memory_size')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="ina-text-field">
                        <label for="storage" class="ina-text-field__label">
                            Spesifikasi Storage
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('storage') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="storage"
                                wire:model="storage"
                                class="ina-text-field__input"
                                placeholder="HPE 3.8TB 15K RPM HDD"
                            >
                        </div>
                        @error('storage')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="memory" class="ina-text-field__label">
                            Spesifikasi Memory
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('memory') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="memory"
                                wire:model="memory"
                                class="ina-text-field__input"
                                placeholder="HPE 64GB (4x16GB) DDR4 2666MT/s"
                            >
                        </div>
                        @error('memory')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="ina-text-field">
                    <label for="server-status" class="ina-text-field__label">
                        Status
                    </label>
                    <x-idds.select
                        wire-prop="status"
                        :value="$status"
                        :options="collect(ServerStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->value])->all()"
                        aria-label="Pilih status server"
                        :error="$errors->has('status')"
                    />
                    @error('status')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-field">
                    <label for="cpu" class="ina-text-field__label">
                        CPU
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('cpu') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="cpu"
                            wire:model="cpu"
                            class="ina-text-field__input"
                            placeholder="Intel Xeon Gold 6248 20 Core"
                        >
                    </div>
                    <p class="mt-1.5 text-xs text-content-tertiary">Contoh: Intel Xeon Gold 6248 20 Core.</p>
                    @error('cpu')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="ina-text-field">
                        <label for="static_ip" class="ina-text-field__label">
                            Static IP
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('static_ip') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="static_ip"
                                wire:model="static_ip"
                                class="ina-text-field__input"
                                placeholder="192.0.2.15"
                            >
                        </div>
                        @error('static_ip')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="os" class="ina-text-field__label">
                            OS
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('os') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="os"
                                wire:model="os"
                                class="ina-text-field__input"
                                placeholder="Ubuntu Server 22.04 LTS"
                            >
                        </div>
                        @error('os')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="ina-text-field">
                        <label for="server-username" class="ina-text-field__label">
                            Username
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('username') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="server-username"
                                wire:model="username"
                                class="ina-text-field__input"
                                autocomplete="off"
                                placeholder="admin"
                            >
                        </div>
                        @error('username')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="server-password" class="ina-text-field__label">
                            Password
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('password') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="password"
                                id="server-password"
                                wire:model="password"
                                class="ina-text-field__input"
                                autocomplete="new-password"
                                placeholder="{{ $editingId !== null ? 'Kosongkan bila tidak diubah' : '••••••••' }}"
                            >
                        </div>
                        @error('password')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="ina-button ina-button--secondary ina-button--md" wire:click="closeModal">
                        Batal
                    </button>
                    <button type="submit" class="ina-button ina-button--primary ina-button--md" wire:loading.attr="disabled">
                        {{ $editingId !== null ? 'Simpan Perubahan' : 'Simpan' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

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
