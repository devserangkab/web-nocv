<?php

use Illuminate\Support\Facades\DB;
use App\Models\Location;
use App\Models\Server;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Actions\Locations\CreateLocation;
use App\Actions\Locations\DeleteLocation;
use App\Actions\Locations\UpdateLocation;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use \App\Livewire\Concerns\Notifies;

    use WithPagination;

    #[Layout('layouts::admin')]
    #[Title('Server Room')]

    #[Url(except: '')]
    public string $search = '';

    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public string $name = '';

    public string $address = '';

    public function updatedSearch(): void
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
        $location = Location::findOrFail($id);

        $this->editingId = $location->id;
        $this->name = (string) $location->name;
        $this->address = (string) $location->address;

        $this->showModal = true;
    }

    public function save(): void
    {
        $data = [
            'name' => $this->name,
            'address' => $this->address === '' ? null : $this->address,
        ];

        if ($this->editingId !== null) {
            app(UpdateLocation::class)(Location::findOrFail($this->editingId), $data);
        } else {
            app(CreateLocation::class)($data);
        }

        $this->notify('Server Room berhasil disimpan.');
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
        $location = Location::with('racks')->findOrFail($this->deletingId);

        try {
            app(DeleteLocation::class)($location);
        } catch (ValidationException $e) {
            foreach ($e->errors()['deletingId'] ?? [] as $message) {
                $this->addError('deletingId', $message);
            }

            return;
        }

        $this->notify('Server Room berhasil dihapus.');
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
        $this->name = '';
        $this->address = '';
    }

    public function locations(): LengthAwarePaginator
    {
        return Location::query()
            ->withCount(['racks', 'servers'])
            ->when($this->search !== '', fn ($query) => $query->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('address', 'like', "%{$this->search}%")
                    ->orWhere('location_id', 'like', "%{$this->search}%");
            }))
            ->orderBy('location_id')
            ->paginate(10);
    }

    /**
     * Jumlah server per server room mencakup server di dalam rack (mengikuti
     * lokasi rack) dan server yang terhubung langsung tanpa rack.
     *
     * @return array<string, int>
     */
    public function serverCounts(): array
    {
        return Server::query()
            ->selectRaw('COALESCE(r.location_id, s.location_id) as room, COUNT(*) as total')
            ->from('servers as s')
            ->leftJoin('racks as r', 'r.rack_id', '=', 's.rack_id')
            ->whereRaw('COALESCE(r.location_id, s.location_id) IS NOT NULL')
            ->groupByRaw('COALESCE(r.location_id, s.location_id)')
            ->pluck('total', 'room')
            ->all();
    }

    public function deletingLocation(): ?Location
    {
        return $this->deletingId !== null ? Location::withCount(['racks', 'servers'])->find($this->deletingId) : null;
    }
};
?>

<div>
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-content-primary">Server Room</h1>
            <p class="mt-1 text-sm text-content-secondary">Daftar ruang penempatan server dan rack.</p>
        </div>
        @can('infrastructure.manage')
        <button type="button" wire:click="openCreate" class="ina-button ina-button--primary ina-button--md">
            <i class="ti ti-plus text-lg" aria-hidden="true"></i>
            Tambah Server Room
        </button>
        @endcan
    </div>

    {{-- Searchbar --}}
    <div class="mt-6 max-w-sm w-full">
        <div class="ina-input-search ina-input-search--size-md">
            <div class="ina-input-search__wrapper">
                <i class="ti ti-search ina-input-search__prefix-icon" aria-hidden="true"></i>
                <input
                    type="search"
                    class="ina-input-search__input"
                    placeholder="Cari nama, alamat, atau kode..."
                    wire:model.live.debounce.300ms="search"
                    aria-label="Cari server room"
                >
            </div>
        </div>
    </div>

    {{-- Table --}}
    @php $locations = $this->locations(); $serverCounts = $this->serverCounts(); @endphp
    @php
        // Konteks daftar untuk link detail: halaman + filter aktif (param default dibuang)
        $detailContext = array_filter([
            'page' => $locations->currentPage() > 1 ? $locations->currentPage() : null,
            'search' => filled($search) ? $search : null,
        ], fn ($value) => $value !== null);
    @endphp
    @if ($locations->count() > 0)
        <div class="mt-4 overflow-hidden rounded-lg border border-stroke-primary bg-background-primary">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-stroke-primary bg-background-secondary text-left text-[12px] font-medium text-content-secondary">
                            <th scope="col" class="h-10 px-4 font-medium">Nama</th>
                            <th scope="col" class="h-10 px-4 font-medium">Lokasi</th>
                            <th scope="col" class="h-10 px-4 font-medium text-right">Rack</th>
                            <th scope="col" class="h-10 px-4 font-medium text-right">Server</th>
                            <th scope="col" class="h-10 px-4 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($locations as $location)
                            <tr class="border-b border-stroke-primary transition-colors duration-150 h-14 last:border-b-0 hover:bg-neutral-50" wire:key="loc-{{ $location->id }}">
                                <td class="h-10 px-4 font-medium text-content-primary">{{ $location->name }}</td>
                                <td class="h-10 max-w-xs truncate px-4 text-content-secondary" title="{{ $location->address }}">{{ $location->address ?? '-' }}</td>
                                <td class="h-10 px-4 text-right text-content-primary">{{ $location->racks_count }} rack</td>
                                <td class="h-10 px-4 text-right text-content-primary">{{ ($serverCounts[(string) $location->location_id] ?? 0) + $location->servers_count }} server</td>
                                <td class="h-10 px-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <a
                                            href="{{ route('admin.server-rooms.show', [...$detailContext, 'locationId' => $location->location_id]) }}"
                                            wire:navigate
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                            aria-label="Detail {{ $location->name }}"
                                            title="Detail"
                                        >
                                            <i class="ti ti-eye text-base"></i>
                                        </a>
                                        @can("infrastructure.manage")
                                        <button
                                            type="button"
                                            wire:click="edit({{ $location->id }})"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                            aria-label="Ubah {{ $location->name }}"
                                            title="Ubah"
                                        >
                                            <i class="ti ti-pencil text-base"></i>
                                        </button>
                                        @endcan
                                        @can("infrastructure.manage")
                                        <button
                                            type="button"
                                            wire:click="askDelete({{ $location->id }})"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-negative-50 hover:text-negative-600"
                                            aria-label="Hapus {{ $location->name }}"
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

        <div class="mt-6">{{ $locations->links('vendor.pagination.tailwind') }}</div>
    @else
        {{-- Empty state (IDDS) --}}
        <div class="ina-card ina-card--stack mt-4 px-6 py-12 text-center">
            <x-asset.illustration name="{{ $search !== '' ? 'noresult' : 'nodata' }}" size="sm" />
            <h2 class="mt-4 text-base font-semibold text-content-primary">
                {{ $search !== '' ? 'Tidak ada hasil untuk "'.$search.'"' : 'Belum ada server room' }}
            </h2>
            <p class="mt-2 mx-auto max-w-sm text-sm text-content-secondary">
                {{ $search !== '' ? 'Coba kata kunci lain atau bersihkan pencarian.' : 'Ruang penempatan server dapat ditambahkan di sini sebelum menyusun rack.' }}
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
            class="fixed left-1/2 top-1/2 z-50 w-full max-w-md max-h-[calc(100vh-32px)] overflow-y-auto -translate-x-1/2 -translate-y-1/2 rounded-xl border border-stroke-primary bg-background-primary p-6 shadow-lg"
            x-show="open"
            x-transition.opacity.duration.150ms
            role="dialog"
            aria-modal="true"
            aria-labelledby="location-modal-title"
            @click.stop
        >
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-content-primary" id="location-modal-title">
                        {{ $editingId !== null ? 'Ubah Server Room' : 'Tambah Server Room' }}
                    </h2>
                    <p class="mt-1 text-sm text-content-secondary">
                        {{ $editingId !== null ? 'Perbarui data server room.' : 'Kode akan terisi otomatis setelah disimpan.' }}
                    </p>
                </div>
                <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="closeModal" aria-label="Tutup">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <form class="mt-6 space-y-5" wire:submit="save">
                <div class="ina-text-field">
                    <label for="location-name" class="ina-text-field__label">
                        Nama
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('name') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="location-name"
                            wire:model="name"
                            class="ina-text-field__input"
                            placeholder="NOC"
                            required
                            autofocus
                        >
                    </div>
                    @error('name')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-field">
                    <label for="location-address" class="ina-text-field__label">
                        Alamat
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('address') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="location-address"
                            wire:model="address"
                            class="ina-text-field__input"
                            placeholder="Jl. Kantor Gubernur Banten, Serang"
                        >
                    </div>
                    @error('address')
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
            aria-labelledby="location-delete-title"
            @click.stop
        >
            @php $deleting = $this->deletingLocation(); @endphp
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ ($deleting?->racks_count ?? 0) > 0 || ($deleting?->servers_count ?? 0) > 0 ? 'bg-warning-50 text-warning-600' : 'bg-negative-50 text-negative-600' }}">
                        <i class="ti {{ ($deleting?->racks_count ?? 0) > 0 || ($deleting?->servers_count ?? 0) > 0 ? 'ti-alert-circle' : 'ti-alert-triangle' }} text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-content-primary" id="location-delete-title">Hapus Server Room</h2>
                        <p class="mt-1 text-sm text-content-secondary">
                            @if (($deleting?->racks_count ?? 0) > 0 || ($deleting?->servers_count ?? 0) > 0)
                                <span class="font-medium text-content-primary">{{ $deleting?->name }}</span> masih memuat {{ $deleting?->racks_count }} rack dan {{ $deleting?->servers_count }} server langsung. Lepaskan relasi terlebih dahulu sebelum menghapus server room.
                            @else
                                Server room <span class="font-medium text-content-primary">{{ $deleting?->name ?? '-' }}</span> akan dihapus. Tindakan ini tidak bisa dibatalkan.
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
                    @if (($deleting?->racks_count ?? 0) > 0 || ($deleting?->servers_count ?? 0) > 0) disabled @endif
                >
                    Hapus
                </button>
            </div>
        </div>
    </div>
</div>
