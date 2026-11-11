<?php

use App\Enums\PanelType;
use App\Models\Hosting;
use App\Models\Panel;
use App\Models\Server;
use App\Models\Vms;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Actions\Assets\CreatePanel;
use App\Actions\Assets\DeletePanel;
use App\Actions\Assets\UpdatePanel;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use \App\Livewire\Concerns\Notifies;

    use \App\Livewire\Concerns\InteractsWithPanelForm;

    use WithPagination;

    #[Layout('layouts::admin')]
    #[Title('Panels')]

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'ALL')]
    public string $typeFilter = 'ALL';


    public bool $showDeleteModal = false;


    public ?int $deletingId = null;









    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }




    public function askDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if ($this->deletingId !== null) {
            app(DeletePanel::class)(Panel::findOrFail($this->deletingId));
        }

        $this->notify('Panel berhasil dihapus.');
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }



    /**
     * Opsi jenis panel untuk filter.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function typeFilterOptions(): array
    {
        return [
            [
                'value' => 'ALL',
                'label' => 'Semua Jenis',
            ],
            ...$this->typeOptions(),
        ];
    }

    /**
     * Opsi jenis panel untuk form.
     *
     * @return array<int, array{value: string, label: string}>
     */

    /**
     * Opsi host untuk form: tergrup "Di VM" dan "Di Server". Wajib memilih.
     *
     * @return array<int, array{label: string|null, options: array<int, array{value: string, label: string}>}>
     */

    public function panels(): LengthAwarePaginator
    {
        return Panel::query()
            ->with(['vm', 'server'])
            ->when($this->typeFilter !== 'ALL', fn ($query) => $query->where('type', $this->typeFilter))
            ->when($this->search !== '', fn ($query) => $query->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('panel_id', 'like', "%{$this->search}%")
                    ->orWhere('version', 'like', "%{$this->search}%")
                    ->orWhereHas('vm', fn ($vm) => $vm
                        ->where('name', 'like', "%{$this->search}%")
                        ->orWhere('vm_id', 'like', "%{$this->search}%"))
                    ->orWhereHas('server', fn ($server) => $server
                        ->where('server_name', 'like', "%{$this->search}%")
                        ->orWhere('server_id', 'like', "%{$this->search}%"));
            }))
            ->orderBy('panel_id')
            ->paginate(10);
    }

    /**
     * Jumlah application yang berjalan di host tempat tiap panel terpasang,
     * dikelompokkan per kunci host ("vm:{id}" / "srv:{id}") dalam satu query.
     *
     * @return array<string, int>
     */
    public function applicationCounts(): array
    {
        $counts = [];

        Hosting::query()
            ->get()
            ->groupBy(fn (Hosting $hosting) => $hosting->hostable_type.'#'.$hosting->hostable_id)
            ->each(function ($group) use (&$counts) {
                /** @var Hosting $hosting */
                $hosting = $group->first();
                $type = $hosting->hostable_type;

                if ($type === Vms::class || $type === Server::class) {
                    $counts[$type.'#'.$hosting->hostable_id] = $group->count();
                }
            });

        return $counts;
    }

    public function deletingPanel(): ?Panel
    {
        return $this->deletingId !== null ? Panel::with(['vm', 'server'])->find($this->deletingId) : null;
    }
};
?>

<div>
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-content-primary">Panels</h1>
            <p class="mt-1 text-sm text-content-secondary">Daftar control panel (aaPanel/cPanel) terpasang di VM atau server.</p>
        </div>
        @can('infrastructure.manage')
        <button type="button" wire:click="openCreate" class="ina-button ina-button--primary ina-button--md">
            <i class="ti ti-plus text-lg" aria-hidden="true"></i>
            Tambah Panel
        </button>
        @endcan
    </div>

    {{-- Filter kiri + Search kanan --}}
    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="w-44">
            <x-idds.select
                wire-prop="typeFilter"
                :value="$typeFilter"
                :options="$this->typeFilterOptions()"
                placeholder="Semua Jenis"
                aria-label="Filter jenis panel"
            />
        </div>

        <div class="w-full max-w-sm">
            <div class="ina-input-search ina-input-search--size-md">
                <div class="ina-input-search__wrapper">
                    <i class="ti ti-search ina-input-search__prefix-icon" aria-hidden="true"></i>
                    <input
                        type="search"
                        class="ina-input-search__input"
                        placeholder="Cari name, kode, address, atau host..."
                        wire:model.live.debounce.300ms="search"
                        aria-label="Cari panel"
                    >
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    @php $panels = $this->panels(); @endphp
    @php
        // Konteks daftar untuk link detail: halaman + filter aktif (param default dibuang)
        $detailContext = array_filter([
            'page' => $panels->currentPage() > 1 ? $panels->currentPage() : null,
            'search' => filled($search) ? $search : null,
            'typeFilter' => $typeFilter !== 'ALL' ? $typeFilter : null,
        ], fn ($value) => $value !== null);
    @endphp
    @if ($panels->count() > 0)
        <div class="mt-4 overflow-hidden rounded-lg border border-stroke-primary bg-background-primary">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-stroke-primary bg-background-secondary text-left text-[12px] font-medium text-content-secondary">
                            <th scope="col" class="h-10 px-4 font-medium">Nama</th>
                            <th scope="col" class="h-10 px-4 font-medium">Jenis</th>
                            <th scope="col" class="h-10 px-4 font-medium">Host</th>
                            <th scope="col" class="h-10 px-4 font-medium">URL Eksternal</th>
                            <th scope="col" class="h-10 px-4 font-medium">URL Internal</th>
                            <th scope="col" class="h-10 px-4 font-medium">Versi</th>
                            <th scope="col" class="h-10 px-4 font-medium text-right">Aplikasi</th>
                            <th scope="col" class="h-10 px-4 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $appCounts = $this->applicationCounts(); @endphp
                        @foreach ($panels as $panel)
                            <tr class="border-b border-stroke-primary transition-colors duration-150 h-14 last:border-b-0 hover:bg-neutral-50" wire:key="pnl-{{ $panel->id }}">
                                <td class="h-10 px-4 font-medium text-content-primary">{{ $panel->name }}</td>
                                <td class="h-10 px-4">
                                    <span class="ina-badge ina-badge--soft {{ $panel->type === PanelType::AaPanel ? 'ina-badge--info' : 'ina-badge--brand' }} ina-badge--sm">
                                        {{ $panel->type->value }}
                                    </span>
                                </td>
                                <td class="h-10 px-4 text-content-secondary">
                                    @if ($panel->vm !== null)
                                        {{ $panel->vm->name }}
                                        <span class="block text-[11px] text-content-tertiary">{{ $panel->vm->vm_id }}</span>
                                    @elseif ($panel->server !== null)
                                        {{ $panel->server->server_name }}
                                        <span class="block text-[11px] text-content-tertiary">{{ $panel->server->server_id }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="h-10 max-w-xs truncate px-4 text-content-secondary" title="{{ $panel->internet_address }}">{{ $panel->internet_address ?? '-' }}</td>
                                <td class="h-10 max-w-xs truncate px-4 text-content-secondary" title="{{ $panel->internal_address }}">{{ $panel->internal_address ?? '-' }}</td>
                                <td class="h-10 px-4 text-content-secondary">{{ $panel->version ?? '-' }}</td>
                                <td class="h-10 px-4 text-right text-content-primary">
                                    @php
                                        $hostType = $panel->vm !== null ? \App\Models\Vms::class : \App\Models\Server::class;
                                        $hostId = $panel->vm?->id ?? $panel->server?->id;
                                    @endphp
                                    {{ $hostId !== null ? ($appCounts[$hostType.'#'.$hostId] ?? 0) : 0 }} app
                                </td>
                                <td class="h-10 px-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <a
                                            href="{{ route('admin.assets.panels.show', [...$detailContext, 'panelId' => $panel->panel_id]) }}"
                                            wire:navigate
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                            aria-label="Detail {{ $panel->name }}"
                                            title="Detail"
                                        >
                                            <i class="ti ti-eye text-base"></i>
                                        </a>
                                        @can("infrastructure.manage")
                                        <button
                                            type="button"
                                            wire:click="edit({{ $panel->id }})"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                            aria-label="Ubah {{ $panel->name }}"
                                            title="Ubah"
                                        >
                                            <i class="ti ti-pencil text-base"></i>
                                        </button>
                                        @endcan
                                        @can("infrastructure.manage")
                                        <button
                                            type="button"
                                            wire:click="askDelete({{ $panel->id }})"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-negative-50 hover:text-negative-600"
                                            aria-label="Hapus {{ $panel->name }}"
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

        <div class="mt-6">{{ $panels->links('vendor.pagination.tailwind') }}</div>
    @else
        {{-- Empty state (IDDS) --}}
        <div class="ina-card ina-card--stack mt-4 px-6 py-12 text-center">
            <x-asset.illustration name="{{ $search !== '' || $typeFilter !== 'ALL' ? 'noresult' : 'nodata' }}" size="sm" />
            <h2 class="mt-4 text-base font-semibold text-content-primary">
                {{ $search !== '' || $typeFilter !== 'ALL' ? 'Tidak ada panel untuk filter ini' : 'Belum ada panel' }}
            </h2>
            <p class="mt-2 mx-auto max-w-sm text-sm text-content-secondary">
                {{ $search !== '' || $typeFilter !== 'ALL' ? 'Ubah kata kunci atau filter jenis.' : 'Panel yang ditambahkan akan tampil di sini, terpasang di VM atau server.' }}
            </p>
        </div>
    @endif

    @include('pages.admin.assets.partials.panel-form-modal')

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
            aria-labelledby="panels-delete-title"
            @click.stop
        >
            @php $deleting = $this->deletingPanel(); @endphp
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-negative-50 text-negative-600">
                        <i class="ti ti-alert-triangle text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-content-primary" id="panels-delete-title">Hapus Panel</h2>
                        <p class="mt-1 text-sm text-content-secondary">
                            Panel <span class="font-medium text-content-primary">{{ $deleting?->name ?? '-' }}</span>
                            @if ($deleting?->vm !== null)
                                di VM <span class="font-medium text-content-primary">{{ $deleting?->vm?->name }}</span>
                            @elseif ($deleting?->server !== null)
                                di server <span class="font-medium text-content-primary">{{ $deleting?->server?->server_name }}</span>
                            @endif
                            akan dihapus
                            dari daftar. Tindakan ini tidak bisa dibatalkan.
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
