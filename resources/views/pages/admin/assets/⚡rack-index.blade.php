<?php

use App\Models\Rack;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Actions\Assets\CreateRack;
use App\Actions\Assets\DeleteRack;
use App\Actions\Assets\UpdateRack;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Layout('layouts::admin')]
    #[Title('Rack')]

    public string $search = '';

    public string $locationFilter = 'ALL';

    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public string $location_id = '';

    public string $name = '';

    public string $brand = '';

    public string $color = '';
    public string $units = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedLocationFilter(): void
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
        $rack = Rack::findOrFail($id);

        $this->editingId = $rack->id;
        $this->location_id = (string) $rack->location_id;
        $this->name = (string) $rack->name;
        $this->brand = (string) $rack->brand;
        $this->color = (string) $rack->color;
        $this->units = (string) $rack->units;

        $this->showModal = true;
    }

    public function save(): void
    {
        $data = [
            'location_id' => $this->location_id === '' ? null : $this->location_id,
            'name' => $this->name,
            'brand' => $this->brand === '' ? null : $this->brand,
            'color' => $this->color === '' ? null : strtoupper($this->color),
            'units' => $this->units === '' ? null : (int) $this->units,
        ];

        if ($this->editingId !== null) {
            app(UpdateRack::class)(Rack::findOrFail($this->editingId), $data);
        } else {
            app(CreateRack::class)($data);
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
        $rack = Rack::with('servers')->findOrFail($this->deletingId);

        try {
            app(DeleteRack::class)($rack);
        } catch (ValidationException $e) {
            // Bonus biarkan modal terbuka — message berada di error bag deletingId
            foreach ($e->errors()['deletingId'] ?? [] as $message) {
                $this->addError('deletingId', $message);
            }

            return;
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
        $this->location_id = '';
        $this->name = '';
        $this->brand = '';
        $this->color = '';
        $this->units = '';
    }

    /**
     * Opsi lokasi untuk filter & form.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function locationOptions(): array
    {
        return \App\Models\Location::query()
            ->orderBy('location_id')
            ->get()
            ->map(fn ($location) => [
                'value' => $location->location_id,
                'label' => $location->name,
            ])
            ->all();
    }

    public function racks(): LengthAwarePaginator
    {
        return Rack::query()
            ->with('location')
            ->withCount('servers')
            ->when($this->locationFilter !== 'ALL', fn ($query) => $query->where('location_id', $this->locationFilter))
            ->when($this->search !== '', fn ($query) => $query->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('rack_id', 'like', "%{$this->search}%")
                    ->orWhere('brand', 'like', "%{$this->search}%");
            }))
            ->orderBy('rack_id')
            ->paginate(10);
    }

    public function deletingRack(): ?Rack
    {
        return $this->deletingId !== null ? Rack::withCount('servers')->find($this->deletingId) : null;
    }
};
?>

<div>
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-content-primary">Rack</h1>
            <p class="mt-1 text-sm text-content-secondary">Daftar rack pada setiap server room.</p>
        </div>
        <button type="button" wire:click="openCreate" class="ina-button ina-button--primary ina-button--md">
            <i class="ti ti-plus text-lg" aria-hidden="true"></i>
            Tambah Rack
        </button>
    </div>

    {{-- Filter kiri + Search kanan --}}
    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="w-48">
            <x-idds.select
                wire-prop="locationFilter"
                :value="$locationFilter"
                :options="collect($this->locationOptions())->prepend(['value' => 'ALL', 'label' => 'Semua Lokasi'])->all()"
                placeholder="Semua Lokasi"
                aria-label="Filter lokasi rack"
            />
        </div>

        <div class="w-full max-w-sm sm:shrink-0">
            <div class="ina-input-search ina-input-search--size-md">
                <div class="ina-input-search__wrapper">
                    <i class="ti ti-search ina-input-search__prefix-icon" aria-hidden="true"></i>
                    <input
                        type="search"
                        class="ina-input-search__input"
                        placeholder="Cari nama, kode, atau brand..."
                        wire:model.live.debounce.300ms="search"
                        aria-label="Cari rack"
                    >
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    @php $racks = $this->racks(); @endphp
    @if ($racks->count() > 0)
        <div class="mt-4 overflow-hidden rounded-lg border border-stroke-primary bg-background-primary">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-stroke-primary bg-background-secondary text-left text-[12px] font-medium text-content-secondary">
                            <th scope="col" class="h-10 px-4 font-medium">Kode</th>
                            <th scope="col" class="h-10 px-4 font-medium">Nama</th>
                            <th scope="col" class="h-10 px-4 font-medium">Lokasi</th>
                            <th scope="col" class="h-10 px-4 font-medium">Brand</th>
                            <th scope="col" class="h-10 px-4 font-medium">Units</th>
                            <th scope="col" class="h-10 px-4 font-medium text-right">Server</th>
                            <th scope="col" class="h-10 px-4 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($racks as $rack)
                            <tr class="border-b border-stroke-primary transition-colors duration-150 h-14 last:border-b-0 hover:bg-neutral-50">
                                <td class="h-10 px-4">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[13px] text-content-primary">{{ $rack->rack_id }}</span>
                                    </div>
                                </td>
                                <td class="h-10 px-4 font-medium text-content-primary">{{ $rack->name }}</td>
                                <td class="h-10 px-4 text-content-secondary">{{ $rack->location?->name ?? '-' }}</td>
                                <td class="h-10 px-4 text-content-secondary">{{ $rack->brand ?? '-' }}</td>
                                <td class="h-10 px-4 text-content-secondary">{{ $rack->units !== null ? $rack->units.' U' : '-' }}</td>
                                <td class="h-10 px-4 text-right text-content-primary">{{ $rack->servers_count }} unit</td>
                                <td class="h-10 px-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            type="button"
                                            wire:click="edit({{ $rack->id }})"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                            aria-label="Ubah {{ $rack->name }}"
                                            title="Ubah"
                                        >
                                            <i class="ti ti-pencil text-base"></i>
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="askDelete({{ $rack->id }})"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-negative-50 hover:text-negative-600"
                                            aria-label="Hapus {{ $rack->name }}"
                                            title="Hapus"
                                        >
                                            <i class="ti ti-trash text-base"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $racks->links('vendor.pagination.tailwind') }}</div>
    @else
        {{-- Empty state (IDDS) --}}
        <div class="ina-card ina-card--stack mt-4 items-center px-6 py-12 text-center">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-background-tertiary">
                <i class="ti ti-server-2 text-2xl text-content-tertiary"></i>
            </div>
            <h2 class="mt-4 text-base font-semibold text-content-primary">
                {{ $search !== '' || $locationFilter !== 'ALL' ? 'Tidak ada rack untuk filter ini' : 'Belum ada rack' }}
            </h2>
            <p class="mt-2 max-w-sm text-sm text-content-secondary">
                {{ $search !== '' || $locationFilter !== 'ALL' ? 'Ubah kata kunci atau filter lokasi.' : 'Rack dapat ditambahkan setelah server room tersedia.' }}
            </p>
            @if ($search === '' && $locationFilter === 'ALL')
                <button type="button" wire:click="openCreate" class="ina-button ina-button--primary ina-button--md mt-6">
                    <i class="ti ti-plus text-lg" aria-hidden="true"></i>
                    Tambah Rack
                </button>
            @else
                <button type="button" wire:click="$set('locationFilter', 'ALL'); $set('search', '')" class="ina-button ina-button--secondary ina-button--md mt-6">
                    Bersihkan Filter
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
            aria-labelledby="rack-modal-title"
            @click.stop
        >
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-content-primary" id="rack-modal-title">
                        {{ $editingId !== null ? 'Ubah Rack' : 'Tambah Rack' }}
                    </h2>
                    <p class="mt-1 text-sm text-content-secondary">
                        {{ $editingId !== null ? 'Perbarui data rack.' : 'Kode akan terisi otomatis setelah disimpan.' }}
                    </p>
                </div>
                <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="closeModal" aria-label="Tutup">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <form class="mt-6 space-y-5" wire:submit="save">
                <div class="ina-text-field">
                    <label for="rack-location" class="ina-text-field__label">
                        Lokasi
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <x-idds.select
                        wire-prop="location_id"
                        :value="$location_id"
                        :options="$this->locationOptions()"
                        placeholder="Pilih lokasi..."
                        aria-label="Pilih lokasi"
                        :error="$errors->has('location_id')"
                    />
                    @error('location_id')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-field">
                    <label for="rack-name" class="ina-text-field__label">
                        Nama
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('name') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="rack-name"
                            wire:model="name"
                            class="ina-text-field__input"
                            placeholder="Rak 1"
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
                        <label for="rack-brand" class="ina-text-field__label">
                            Brand
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('brand') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="rack-brand"
                                wire:model="brand"
                                class="ina-text-field__input"
                                placeholder="APC"
                                list="rack-brand-list"
                            >
                            <datalist id="rack-brand-list">
                                <option value="APC"></option>
                                <option value="Schneider Electric"></option>
                                <option value="Vertiv"></option>
                            </datalist>
                        </div>
                        @error('brand')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="rack-color" class="ina-text-field__label">
                            Warna
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('color') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            @if ($color !== '')
                                <span class="h-4 w-4 shrink-0 rounded-full border border-stroke-primary" style="background-color: {{ $color }}"></span>
                            @endif
                            <input
                                type="text"
                                id="rack-color"
                                wire:model="color"
                                class="ina-text-field__input"
                                placeholder="#1B604E"
                                maxlength="7"
                            >
                        </div>
                        @error('color')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="ina-text-field">
                    <label for="rack-units" class="ina-text-field__label">
                        Units (U)
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('units') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="number"
                            min="1"
                            id="rack-units"
                            wire:model="units"
                            class="ina-text-field__input"
                            placeholder="42"
                        >
                    </div>
                    <p class="mt-1.5 text-xs text-content-tertiary">Jumlah unit U, mis. 42 untuk rak standar.</p>
                    @error('units')
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
            aria-labelledby="rack-delete-title"
            @click.stop
        >
            @php $deleting = $this->deletingRack(); @endphp
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ ($deleting?->servers_count ?? 0) > 0 ? 'bg-warning-50 text-warning-600' : 'bg-negative-50 text-negative-600' }}">
                        <i class="ti {{ ($deleting?->servers_count ?? 0) > 0 ? 'ti-alert-circle' : 'ti-alert-triangle' }} text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-content-primary" id="rack-delete-title">Hapus Rack</h2>
                        <p class="mt-1 text-sm text-content-secondary">
                            @if (($deleting?->servers_count ?? 0) > 0)
                                <span class="font-medium text-content-primary">{{ $deleting?->name }}</span> masih memuat {{ $deleting?->servers_count }} server. Kosongkan rack terlebih dahulu sebelum menghapusnya.
                            @else
                                Rack <span class="font-medium text-content-primary">{{ $deleting?->name ?? '-' }}</span> akan dihapus. Tindakan ini tidak bisa dibatalkan.
                            @endif
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
                    @if (($deleting?->servers_count ?? 0) > 0) disabled @endif
                >
                    Hapus
                </button>
            </div>
        </div>
    </div>
</div>
