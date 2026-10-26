<?php

use App\Enums\DatabaseTech;
use App\Models\DatabaseInstance;
use App\Models\Panel;
use App\Models\Server;
use App\Models\Vms;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Actions\Databases\CreateDatabaseInstance;
use App\Actions\Databases\DeleteDatabaseInstance;
use App\Actions\Databases\UpdateDatabaseInstance;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use \App\Livewire\Concerns\Notifies;

    use \App\Livewire\Concerns\InteractsWithDatabaseInstanceForm;

    use WithPagination;

    #[Layout('layouts::admin')]
    #[Title('Database')]

    public string $search = '';

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
            app(DeleteDatabaseInstance::class)(DatabaseInstance::findOrFail($this->deletingId));
        }

        $this->notify('Database server berhasil dihapus.');
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    /**
     * Opsi teknologi database untuk filter.
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

    public function instances(): LengthAwarePaginator
    {
        return DatabaseInstance::query()
            ->with(['vm', 'server', 'panel', 'schemas'])
            ->when($this->techFilter !== 'ALL', fn ($query) => $query->where('tech', $this->techFilter))
            ->when($this->search !== '', fn ($query) => $query->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('dbi_id', 'like', "%{$this->search}%")
                    ->orWhere('version', 'like', "%{$this->search}%")
                    ->orWhere('internal_address', 'like', "%{$this->search}%")
                    ->orWhereHas('vm', fn ($vm) => $vm
                        ->where('name', 'like', "%{$this->search}%")
                        ->orWhere('vm_id', 'like', "%{$this->search}%"))
                    ->orWhereHas('server', fn ($server) => $server
                        ->where('server_name', 'like', "%{$this->search}%")
                        ->orWhere('server_id', 'like', "%{$this->search}%"))
                    ->orWhereHas('panel', fn ($panel) => $panel
                        ->where('name', 'like', "%{$this->search}%")
                        ->orWhere('panel_id', 'like', "%{$this->search}%"));
            }))
            ->orderBy('dbi_id')
            ->paginate(10);
    }

    public function deletingInstance(): ?DatabaseInstance
    {
        return $this->deletingId !== null ? DatabaseInstance::with(['vm', 'server', 'panel', 'schemas'])->find($this->deletingId) : null;
    }
};
?>

<div>
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-content-primary">Database</h1>
            <p class="mt-1 text-sm text-content-secondary">Daftar database server (MySQL, PostgreSQL, dll) ter-install di server, VM, atau panel.</p>
        </div>
        @can('infrastructure.manage')
        <button type="button" wire:click="openCreate" class="ina-button ina-button--primary ina-button--md">
            <i class="ti ti-plus text-lg" aria-hidden="true"></i>
            Tambah Database Server
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
                aria-label="Filter teknologi database"
            />
        </div>

        <div class="w-full max-w-sm">
            <div class="ina-input-search ina-input-search--size-md">
                <div class="ina-input-search__wrapper">
                    <i class="ti ti-search ina-input-search__prefix-icon" aria-hidden="true"></i>
                    <input
                        type="search"
                        class="ina-input-search__input"
                        placeholder="Cari name, kode, versi, atau host..."
                        wire:model.live.debounce.300ms="search"
                        aria-label="Cari database server"
                    >
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    @php $instances = $this->instances(); @endphp
    @if ($instances->count() > 0)
        <div class="mt-4 overflow-hidden rounded-lg border border-stroke-primary bg-background-primary">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-stroke-primary bg-background-secondary text-left text-[12px] font-medium text-content-secondary">
                            <th scope="col" class="h-10 px-4 font-medium">Nama</th>
                            <th scope="col" class="h-10 px-4 font-medium">Teknologi</th>
                            <th scope="col" class="h-10 px-4 font-medium">Host</th>
                            <th scope="col" class="h-10 px-4 font-medium">Internal Address</th>
                            <th scope="col" class="h-10 px-4 font-medium text-right">Database</th>
                            <th scope="col" class="h-10 px-4 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($instances as $instance)
                            <tr class="border-b border-stroke-primary transition-colors duration-150 h-14 last:border-b-0 hover:bg-neutral-50" wire:key="dbs-{{ $instance->id }}">
                                <td class="h-10 px-4 font-medium text-content-primary">
                                    {{ $instance->name }}
                                    <span class="block text-[11px] font-normal text-content-tertiary">{{ $instance->dbi_id }}</span>
                                </td>
                                <td class="h-10 px-4">
                                    <span class="ina-badge ina-badge--soft ina-badge--info ina-badge--sm">
                                        {{ trim($instance->tech->value.' '.($instance->version ?? '')) }}
                                    </span>
                                </td>
                                <td class="h-10 px-4 text-content-secondary">
                                    @if ($instance->vm !== null)
                                        {{ $instance->vm->name }}
                                        <span class="block text-[11px] text-content-tertiary">{{ $instance->vm->vm_id }}</span>
                                    @elseif ($instance->server !== null)
                                        {{ $instance->server->server_name }}
                                        <span class="block text-[11px] text-content-tertiary">{{ $instance->server->server_id }}</span>
                                    @elseif ($instance->panel !== null)
                                        {{ $instance->panel->name }}
                                        <span class="block text-[11px] text-content-tertiary">{{ $instance->panel->panel_id }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="h-10 max-w-xs truncate px-4 text-content-secondary" title="{{ $instance->internal_address }}">{{ $instance->internal_address ?? '-' }}</td>
                                <td class="h-10 px-4 text-right">
                                    <span class="ina-badge ina-badge--soft ina-badge--neutral ina-badge--sm">{{ $instance->schemas->count() }} db</span>
                                </td>
                                <td class="h-10 px-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <a
                                            href="{{ route('admin.assets.databases.show', $instance->dbi_id) }}"
                                            wire:navigate
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                            aria-label="Detail {{ $instance->name }}"
                                            title="Detail"
                                        >
                                            <i class="ti ti-eye text-base"></i>
                                        </a>
                                        @can("infrastructure.manage")
                                        <button
                                            type="button"
                                            wire:click="edit({{ $instance->id }})"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                            aria-label="Ubah {{ $instance->name }}"
                                            title="Ubah"
                                        >
                                            <i class="ti ti-pencil text-base"></i>
                                        </button>
                                        @endcan
                                        @can("infrastructure.manage")
                                        <button
                                            type="button"
                                            wire:click="askDelete({{ $instance->id }})"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-negative-50 hover:text-negative-600"
                                            aria-label="Hapus {{ $instance->name }}"
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

        <div class="mt-6">{{ $instances->links('vendor.pagination.tailwind') }}</div>
    @else
        {{-- Empty state (IDDS) --}}
        <div class="ina-card ina-card--stack mt-4 px-6 py-12 text-center">
            <x-asset.illustration name="{{ $search !== '' || $techFilter !== 'ALL' ? 'noresult' : 'nodata' }}" size="sm" />
            <h2 class="mt-4 text-base font-semibold text-content-primary">
                {{ $search !== '' || $techFilter !== 'ALL' ? 'Tidak ada database server untuk filter ini' : 'Belum ada database server' }}
            </h2>
            <p class="mt-2 mx-auto max-w-sm text-sm text-content-secondary">
                {{ $search !== '' || $techFilter !== 'ALL' ? 'Ubah kata kunci atau filter teknologi.' : 'Database server yang ditambahkan akan tampil di sini, ter-install di server, VM, atau panel.' }}
            </p>
        </div>
    @endif

    @include('pages.admin.assets.partials.database-instance-form-modal')

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
            aria-labelledby="databases-delete-title"
            @click.stop
        >
            @php $deleting = $this->deletingInstance(); @endphp
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-negative-50 text-negative-600">
                        <i class="ti ti-alert-triangle text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-content-primary" id="databases-delete-title">Hapus Database Server</h2>
                        <p class="mt-1 text-sm text-content-secondary">
                            Database server <span class="font-medium text-content-primary">{{ $deleting?->name ?? '-' }}</span>
                            @if ($deleting?->schemas->isNotEmpty())
                                beserta {{ $deleting->schemas->count() }} database di dalamnya
                            @endif
                            akan dihapus dari daftar. Tindakan ini tidak bisa dibatalkan.
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
