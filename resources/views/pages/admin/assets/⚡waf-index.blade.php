<?php

use App\Enums\WafTech;
use App\Models\Waf;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Actions\Assets\CreateWaf;
use App\Actions\Assets\DeleteWaf;
use App\Actions\Assets\UpdateWaf;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use \App\Livewire\Concerns\Notifies;

    use \App\Livewire\Concerns\InteractsWithWafForm;

    use WithPagination;

    #[Layout('layouts::admin')]
    #[Title('WAF')]

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'ALL')]
    public string $techFilter = 'ALL';

    public bool $showDeleteModal = false;

    public ?int $deletingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTechFilter(): void
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
            app(DeleteWaf::class)(Waf::findOrFail($this->deletingId));
        }

        $this->notify('WAF berhasil dihapus.');
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    /**
     * Opsi teknologi WAF untuk filter.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function techFilterOptions(): array
    {
        return [
            [
                'value' => 'ALL',
                'label' => 'Semua Teknologi',
            ],
            ...$this->techOptions(),
        ];
    }

    /**
     * Opsi teknologi WAF untuk form.
     *
     * @return array<int, array{value: string, label: string}>
     */

    /**
     * Opsi host untuk form: tergrup "Di VM" dan "Di Server". Wajib memilih.
     *
     * @return array<int, array{label: string|null, options: array<int, array{value: string, label: string}>}>
     */

    public function wafs(): LengthAwarePaginator
    {
        return Waf::query()
            ->with(['vm', 'server'])
            ->when($this->techFilter !== 'ALL', fn ($query) => $query->where('tech', $this->techFilter))
            ->when($this->search !== '', fn ($query) => $query->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('waf_id', 'like', "%{$this->search}%")
                    ->orWhere('version', 'like', "%{$this->search}%")
                    ->orWhereHas('vm', fn ($vm) => $vm
                        ->where('name', 'like', "%{$this->search}%")
                        ->orWhere('vm_id', 'like', "%{$this->search}%"))
                    ->orWhereHas('server', fn ($server) => $server
                        ->where('server_name', 'like', "%{$this->search}%")
                        ->orWhere('server_id', 'like', "%{$this->search}%"));
            }))
            ->orderBy('waf_id')
            ->paginate(10);
    }

    public function deletingWaf(): ?Waf
    {
        return $this->deletingId !== null ? Waf::with(['vm', 'server'])->find($this->deletingId) : null;
    }
};
?>

<div>
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-content-primary">WAF</h1>
            <p class="mt-1 text-sm text-content-secondary">Daftar Web Application Firewall terpasang di VM atau server.</p>
        </div>
        @can('infrastructure.manage')
        <button type="button" wire:click="openCreate" class="ina-button ina-button--primary ina-button--md">
            <i class="ti ti-plus text-lg" aria-hidden="true"></i>
            Tambah WAF
        </button>
        @endcan
    </div>

    {{-- Filter kiri + Search kanan --}}
    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="w-44">
            <x-idds.select
                wire-prop="techFilter"
                :value="$techFilter"
                :options="$this->techFilterOptions()"
                placeholder="Semua Teknologi"
                aria-label="Filter teknologi WAF"
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
                        aria-label="Cari WAF"
                    >
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    @php $wafs = $this->wafs(); @endphp
    @php
        // Konteks daftar untuk link detail: halaman + filter aktif (param default dibuang)
        $detailContext = array_filter([
            'page' => $wafs->currentPage() > 1 ? $wafs->currentPage() : null,
            'search' => filled($search) ? $search : null,
            'techFilter' => $techFilter !== 'ALL' ? $techFilter : null,
        ], fn ($value) => $value !== null);
    @endphp
    @if ($wafs->count() > 0)
        <div class="mt-4 overflow-hidden rounded-lg border border-stroke-primary bg-background-primary">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-stroke-primary bg-background-secondary text-left text-[12px] font-medium text-content-secondary">
                            <th scope="col" class="h-10 px-4 font-medium">Nama</th>
                            <th scope="col" class="h-10 px-4 font-medium">Teknologi</th>
                            <th scope="col" class="h-10 px-4 font-medium">Host</th>
                            <th scope="col" class="h-10 px-4 font-medium">URL Eksternal</th>
                            <th scope="col" class="h-10 px-4 font-medium">URL Internal</th>
                            <th scope="col" class="h-10 px-4 font-medium">Versi</th>
                            <th scope="col" class="h-10 px-4 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($wafs as $waf)
                            <tr class="border-b border-stroke-primary transition-colors duration-150 h-14 last:border-b-0 hover:bg-neutral-50" wire:key="waf-{{ $waf->id }}">
                                <td class="h-10 px-4 font-medium text-content-primary">{{ $waf->name }}</td>
                                <td class="h-10 px-4">
                                    <span class="ina-badge ina-badge--soft ina-badge--info ina-badge--sm">
                                        {{ $waf->tech->value }}
                                    </span>
                                </td>
                                <td class="h-10 px-4 text-content-secondary">
                                    @if ($waf->vm !== null)
                                        {{ $waf->vm->name }}
                                        <span class="block text-[11px] text-content-tertiary">{{ $waf->vm->vm_id }}</span>
                                    @elseif ($waf->server !== null)
                                        {{ $waf->server->server_name }}
                                        <span class="block text-[11px] text-content-tertiary">{{ $waf->server->server_id }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="h-10 max-w-xs truncate px-4 text-content-secondary" title="{{ $waf->internet_address }}">{{ $waf->internet_address ?? '-' }}</td>
                                <td class="h-10 max-w-xs truncate px-4 text-content-secondary" title="{{ $waf->internal_address }}">{{ $waf->internal_address ?? '-' }}</td>
                                <td class="h-10 px-4 text-content-secondary">{{ $waf->version ?? '-' }}</td>
                                <td class="h-10 px-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <a
                                            href="{{ route('admin.assets.wafs.show', [...$detailContext, 'wafId' => $waf->waf_id]) }}"
                                            wire:navigate
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                            aria-label="Detail {{ $waf->name }}"
                                            title="Detail"
                                        >
                                            <i class="ti ti-eye text-base"></i>
                                        </a>
                                        @can("infrastructure.manage")
                                        <button
                                            type="button"
                                            wire:click="edit({{ $waf->id }})"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                            aria-label="Ubah {{ $waf->name }}"
                                            title="Ubah"
                                        >
                                            <i class="ti ti-pencil text-base"></i>
                                        </button>
                                        @endcan
                                        @can("infrastructure.manage")
                                        <button
                                            type="button"
                                            wire:click="askDelete({{ $waf->id }})"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-negative-50 hover:text-negative-600"
                                            aria-label="Hapus {{ $waf->name }}"
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

        <div class="mt-6">{{ $wafs->links('vendor.pagination.tailwind') }}</div>
    @else
        {{-- Empty state (IDDS) --}}
        <div class="ina-card ina-card--stack mt-4 px-6 py-12 text-center">
            <x-asset.illustration name="{{ $search !== '' || $techFilter !== 'ALL' ? 'noresult' : 'nodata' }}" size="sm" />
            <h2 class="mt-4 text-base font-semibold text-content-primary">
                {{ $search !== '' || $techFilter !== 'ALL' ? 'Tidak ada WAF untuk filter ini' : 'Belum ada WAF' }}
            </h2>
            <p class="mt-2 mx-auto max-w-sm text-sm text-content-secondary">
                {{ $search !== '' || $techFilter !== 'ALL' ? 'Ubah kata kunci atau filter teknologi.' : 'WAF yang ditambahkan akan tampil di sini, terpasang di VM atau server.' }}
            </p>
        </div>
    @endif

    @include('pages.admin.assets.partials.waf-form-modal')

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
            aria-labelledby="wafs-delete-title"
            @click.stop
        >
            @php $deleting = $this->deletingWaf(); @endphp
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-negative-50 text-negative-600">
                        <i class="ti ti-alert-triangle text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-content-primary" id="wafs-delete-title">Hapus WAF</h2>
                        <p class="mt-1 text-sm text-content-secondary">
                            WAF <span class="font-medium text-content-primary">{{ $deleting?->name ?? '-' }}</span>
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
