<?php

use App\Actions\Nas\CreateNas;
use App\Actions\Nas\DeleteNas;
use App\Actions\Nas\UpdateNas;
use App\Enums\NasStatus;
use App\Models\Location;
use App\Models\Nas;
use App\Models\Rack;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use \App\Livewire\Concerns\Notifies;

    use WithPagination;

    #[Layout('layouts::admin')]
    #[Title('NAS')]

    public string $search = '';

    public string $statusFilter = 'ALL';

    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public string $rack_id = '';

    public string $location_id = '';

    public string $name = '';

    public string $brand = '';

    public string $storage_size = '';

    public string $bays = '';

    public string $position = '';

    public string $raid_level = '';

    public string $os = '';

    public string $status = NasStatus::Off->value;

    public string $static_ip = '';

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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $nas = Nas::findOrFail($id);

        $this->editingId = $nas->id;
        $this->rack_id = (string) $nas->rack_id;
        $this->location_id = (string) $nas->location_id;
        $this->name = (string) $nas->name;
        $this->brand = (string) $nas->brand;
        $this->storage_size = (string) $nas->storage_size;
        $this->bays = (string) $nas->bays;
        $this->position = (string) $nas->position;
        $this->raid_level = (string) $nas->raid_level;
        $this->os = (string) $nas->os;
        $this->status = $nas->status?->value ?? NasStatus::Off->value;
        $this->static_ip = (string) $nas->static_ip;
        $this->username = (string) $nas->username;
        $this->password = '';

        $this->showModal = true;
    }

    public function save(): void
    {
        // Semua rule & pesan validasi dimiliki oleh action (satu sumber kebenaran).
        $data = [
            'name' => $this->name,
            'rack_id' => $this->rack_id === '' ? null : $this->rack_id,
            'location_id' => $this->location_id === '' ? null : $this->location_id,
            'brand' => $this->brand === '' ? null : $this->brand,
            'storage_size' => $this->storage_size === '' ? null : (int) $this->storage_size,
            'bays' => $this->bays === '' ? null : (int) $this->bays,
            'position' => $this->position === '' ? null : (int) $this->position,
            'raid_level' => $this->raid_level === '' ? null : $this->raid_level,
            'os' => $this->os === '' ? null : $this->os,
            'status' => $this->status,
            'static_ip' => $this->static_ip,
            'username' => $this->username,
            'password' => $this->password,
        ];

        if ($this->editingId !== null) {
            app(UpdateNas::class)(Nas::findOrFail($this->editingId), $data);
        } else {
            app(CreateNas::class)($data);
        }

        $this->notify('NAS berhasil disimpan.');
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
            app(DeleteNas::class)(Nas::findOrFail($this->deletingId));
        }

        $this->notify('NAS berhasil dihapus.');
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
        $this->rack_id = '';
        $this->location_id = '';
        $this->name = '';
        $this->brand = '';
        $this->storage_size = '';
        $this->bays = '';
        $this->position = '';
        $this->raid_level = '';
        $this->os = '';
        $this->status = NasStatus::Off->value;
        $this->static_ip = '';
        $this->username = '';
        $this->password = '';
    }

    /**
     * Opsi rack untuk form & filter, bergrup per lokasi.
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

    /**
     * Location options untuk form.
     *
     * @return Collection<int, Location>
     */
    public function locationOptions(): Collection
    {
        return Location::query()->orderBy('location_id')->get();
    }

    /**
     * Data NAS — tabel sederhana: kode, nama, lokasi, kapasitas, status.
     */
    public function nasList(): LengthAwarePaginator
    {
        return Nas::query()
            ->with(['location', 'rack'])
            ->when($this->statusFilter !== 'ALL', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->search !== '', fn ($query) => $query->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('nas_id', 'like', "%{$this->search}%")
                    ->orWhere('brand', 'like', "%{$this->search}%")
                    ->orWhere('os', 'like', "%{$this->search}%")
                    ->orWhere('raid_level', 'like', "%{$this->search}%")
                    ->orWhereHas('rack', fn ($rack) => $rack
                        ->where('name', 'like', "%{$this->search}%")
                        ->orWhere('rack_id', 'like', "%{$this->search}%"))
                    ->orWhereHas('location', fn ($location) => $location
                        ->where('name', 'like', "%{$this->search}%"));
            }))
            ->orderBy('nas_id')
            ->paginate(10);
    }

    public function statusFilterOptions(): array
    {
        return [
            ['value' => 'ALL', 'label' => 'Semua Status'],
            ...collect(NasStatus::cases())
                ->map(fn (NasStatus $status) => ['value' => $status->value, 'label' => $status->value])
                ->all(),
        ];
    }

    public function deletingNas(): ?Nas
    {
        return $this->deletingId !== null ? Nas::query()->find($this->deletingId) : null;
    }
};
?>

<div>
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-content-primary">NAS</h1>
            <p class="mt-1 text-sm text-content-secondary">Perangkat Network Attached Storage: diletakkan di rack atau langsung di server room.</p>
        </div>
        @can('infrastructure.manage')
        <button type="button" wire:click="openCreate" class="ina-button ina-button--primary ina-button--md">
            <i class="ti ti-database text-lg" aria-hidden="true"></i>
            Tambah NAS
        </button>
        @endcan
    </div>

    {{-- Filter kiri + Search kanan --}}
    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="w-44">
            <x-idds.select
                wire-prop="statusFilter"
                :value="$statusFilter"
                :options="$this->statusFilterOptions()"
                placeholder="Semua Status"
                aria-label="Filter status NAS"
            />
        </div>

        <div class="w-full max-w-sm">
            <div class="ina-input-search ina-input-search--size-md">
                <div class="ina-input-search__wrapper">
                    <i class="ti ti-search ina-input-search__prefix-icon" aria-hidden="true"></i>
                    <input
                        type="search"
                        class="ina-input-search__input"
                        placeholder="Cari nama, kode, brand, RAID, atau lokasi..."
                        wire:model.live.debounce.300ms="search"
                        aria-label="Cari NAS"
                    >
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    @php $nasList = $this->nasList(); @endphp
    @if ($nasList->count() > 0)
        <div class="mt-4 overflow-hidden rounded-lg border border-stroke-primary bg-background-primary">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-stroke-primary bg-background-secondary text-left text-[12px] font-medium text-content-secondary">
                            <th scope="col" class="h-10 px-4 font-medium">Nama</th>
                            <th scope="col" class="h-10 px-4 font-medium">Brand</th>
                            <th scope="col" class="h-10 px-4 font-medium">Lokasi</th>
                            <th scope="col" class="h-10 px-4 font-medium text-right">Kapasitas</th>
                            <th scope="col" class="h-10 px-4 font-medium text-right">Posisi</th>
                            <th scope="col" class="h-10 px-4 font-medium">RAID</th>
                            <th scope="col" class="h-10 px-4 font-medium">OS</th>
                            <th scope="col" class="h-10 px-4 font-medium">Status</th>
                            <th scope="col" class="h-10 px-4 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($nasList as $nas)
                            <tr class="border-b border-stroke-primary transition-colors duration-150 h-14 last:border-b-0 hover:bg-neutral-50" wire:key="nas-{{ $nas->id }}">
                                <td class="h-14 px-4 font-medium text-content-primary">{{ $nas->name }}</td>
                                <td class="h-14 px-4 text-content-secondary">{{ $nas->brand ?? '-' }}</td>
                                <td class="h-14 px-4 text-content-secondary">
                                    @if ($nas->effective() !== null)
                                        {{ $nas->rack->name ?? '' }}
                                        <span class="block text-[11px] text-content-tertiary">{{ $nas->effective()->name }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="h-14 px-4 text-right text-content-primary">{{ $nas->storage_size !== null ? number_format($nas->storage_size / 1024).' GB' : '-' }}</td>
                                <td class="h-14 px-4 text-right text-content-secondary">{{ $nas->position !== null ? 'U'.$nas->position : '-' }}</td>
                                <td class="h-14 px-4 text-content-secondary">
                                    @if ($nas->raid_level !== null)
                                        <span class="ina-badge ina-badge--soft ina-badge--neutral ina-badge--sm">{{ $nas->raid_level }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="h-14 px-4 text-content-secondary">{{ $nas->os ?? '-' }}</td>
                                <td class="h-14 px-4">
                                    <span class="ina-badge ina-badge--soft ina-badge--sm {{ $nas->status === NasStatus::On ? 'ina-badge--success' : 'ina-badge--neutral' }}">{{ $nas->status->value }}</span>
                                </td>
                                <td class="h-14 px-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <a
                                            href="{{ route('admin.assets.nas.show', $nas->nas_id) }}"
                                            wire:navigate
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                            aria-label="Detail {{ $nas->name }}"
                                            title="Detail"
                                        >
                                            <i class="ti ti-eye text-base"></i>
                                        </a>
                                        @can("infrastructure.manage")
                                        <button
                                            type="button"
                                            wire:click="edit({{ $nas->id }})"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                            aria-label="Ubah {{ $nas->name }}"
                                            title="Ubah"
                                        >
                                            <i class="ti ti-pencil text-base"></i>
                                        </button>
                                        @endcan
                                        @can("infrastructure.manage")
                                        <button
                                            type="button"
                                            wire:click="askDelete({{ $nas->id }})"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-negative-50 hover:text-negative-600"
                                            aria-label="Hapus {{ $nas->name }}"
                                            title="Hapus"
                                        >
                                            <i class="ti ti-trash text-base"></i>
                                        </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $nasList->links('vendor.pagination.tailwind') }}</div>
    @else
        {{-- Empty state (IDDS) --}}
        <div class="ina-card ina-card--stack mt-4 px-6 py-12 text-center">
            <x-asset.illustration name="{{ $search !== '' || $statusFilter !== 'ALL' ? 'noresult' : 'nodata' }}" size="sm" />
            <h2 class="mt-4 text-base font-semibold text-content-primary">
                {{ $search !== '' || $statusFilter !== 'ALL' ? 'Tidak ada NAS untuk filter ini' : 'Belum ada NAS' }}
            </h2>
            <p class="mt-2 mx-auto max-w-sm text-sm text-content-secondary">
                {{ $search !== '' || $statusFilter !== 'ALL' ? 'Ubah kata kunci atau bersihkan filter.' : 'Perangkat NAS yang ditambahkan akan tampil di sini — di rack atau langsung di server room.' }}
            </p>
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
            class="fixed left-1/2 top-1/2 z-50 w-full max-w-lg max-h-[calc(100vh-32px)] overflow-y-auto -translate-x-1/2 -translate-y-1/2 rounded-xl border border-stroke-primary bg-background-primary p-6 shadow-lg"
            x-show="open"
            x-transition.opacity.duration.150ms
            role="dialog"
            aria-modal="true"
            aria-labelledby="nas-modal-title"
            @click.stop
        >
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-content-primary" id="nas-modal-title">
                        {{ $editingId !== null ? 'Ubah NAS' : 'Tambah NAS' }}
                    </h2>
                    <p class="mt-1 text-sm text-content-secondary">
                        {{ $editingId !== null ? 'Perbarui data NAS.' : 'Kode akan terisi otomatis setelah disimpan.' }}
                    </p>
                </div>
                <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="closeModal" aria-label="Tutup">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <form class="mt-6 space-y-5" wire:submit="save">
                <div class="ina-text-field">
                    <label for="nas_name" class="ina-text-field__label">
                        Nama NAS
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('name') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="nas_name"
                            wire:model="name"
                            class="ina-text-field__input"
                            placeholder="NAS Utama Arsip"
                            required
                            autofocus
                        >
                    </div>
                    @error('name')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="ina-text-field">
                        <label for="nas_rack" class="ina-text-field__label">
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
                        <label for="nas-location" class="ina-text-field__label">
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

                <div class="grid grid-cols-2 gap-4">
                    <div class="ina-text-field">
                        <label for="nas_brand" class="ina-text-field__label">
                            Brand
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('brand') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="nas_brand"
                                wire:model="brand"
                                class="ina-text-field__input"
                                placeholder="Synology"
                                list="nas-brand-list"
                            >
                            <datalist id="nas-brand-list">
                                <option value="Synology"></option>
                                <option value="QNAP"></option>
                                <option value="TrueNAS"></option>
                            </datalist>
                        </div>
                        @error('brand')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="nas_storage_size" class="ina-text-field__label">
                            Storage (MB)
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('storage_size') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="number"
                                min="0"
                                id="nas_storage_size"
                                wire:model="storage_size"
                                class="ina-text-field__input"
                                placeholder="192000"
                            >
                        </div>
                        @error('storage_size')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="ina-text-field">
                        <label for="nas_bays" class="ina-text-field__label">
                            Bays
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('bays') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="number"
                                min="1"
                                id="nas_bays"
                                wire:model="bays"
                                class="ina-text-field__input"
                                placeholder="4"
                            >
                        </div>
                        @error('bays')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field" @if ($rack_id === '')
                        style="display: none"
                    @endif>
                        <label for="nas_position" class="ina-text-field__label">
                            Position (U)
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('position') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="number"
                                min="1"
                                id="nas_position"
                                wire:model="position"
                                class="ina-text-field__input"
                                placeholder="5"
                            >
                        </div>
                        <p class="mt-1.5 text-xs text-content-tertiary">1 = paling atas di dalam rack.</p>
                        @error('position')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="nas_raid" class="ina-text-field__label">
                            RAID Level
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('raid_level') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="nas_raid"
                                wire:model="raid_level"
                                class="ina-text-field__input"
                                placeholder="SHR-2 / RAID-6"
                            >
                        </div>
                        @error('raid_level')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="ina-text-field">
                        <label for="nas_os" class="ina-text-field__label">
                            OS
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('os') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="nas_os"
                                wire:model="os"
                                class="ina-text-field__input"
                                placeholder="DSM 7.2 / TrueNAS SCALE 24.x"
                            >
                        </div>
                        @error('os')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="nas-status" class="ina-text-field__label">
                            Status
                        </label>
                        <x-idds.select
                            wire-prop="status"
                            :value="$status"
                            :options="collect(NasStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->value])->all()"
                            aria-label="Pilih status NAS"
                            :error="$errors->has('status')"
                        />
                        @error('status')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="ina-text-field">
                        <label for="nas_static_ip" class="ina-text-field__label">
                            Static IP
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('static_ip') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="nas_static_ip"
                                wire:model="static_ip"
                                class="ina-text-field__input"
                                placeholder="192.168.1.20"
                            >
                        </div>
                        @error('static_ip')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="nas-username" class="ina-text-field__label">
                            Username
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('username') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="nas-username"
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
                </div>

                <div class="ina-text-field">
                    <label for="nas-password" class="ina-text-field__label">
                        Password
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('password') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="password"
                            id="nas-password"
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
            aria-labelledby="nas-delete-title"
            @click.stop
        >
            @php $deleting = $this->deletingNas(); @endphp
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-negative-50 text-negative-600">
                        <i class="ti ti-alert-triangle text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-content-primary" id="nas-delete-title">Hapus NAS</h2>
                        <p class="mt-1 text-sm text-content-secondary">
                            NAS <span class="font-medium text-content-primary">{{ $deleting?->name ?? '-' }}</span> akan dihapus beserta media galerinya. Tindakan ini tidak bisa dibatalkan.
                        </p>
                        @error('deletingId')
                            <p class="mt-1.5 text-sm text-negative-600">{{ $message }}</p>
                        @enderror
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
                <button
                    type="button"
                    class="ina-button ina-button--secondary ina-button--md bg-negative-600! text-white! hover:bg-negative-700!"
                    wire:loading.attr="disabled"
                    wire:click="delete"
                >
                    Hapus
                </button>
            </div>
        </div>
    </div>
</div>
