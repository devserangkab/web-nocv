<?php
/**
 * Partial tab Virtual Machine — menyertakan tabel, modal, dan filter.
 * Property & method diambil dari komponen induk ⚡virtualization-index.
 */
use App\Enums\VmsStatus;
use App\Models\Vms;
?>
{{-- Filter kiri + Search kanan --}}
<div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <div class="w-48">
            <x-idds.select
                wire-prop="proxmoxFilter"
                :value="$proxmoxFilter"
                :options="$this->proxmoxFilterOptions()"
                placeholder="Semua Proxmox"
                aria-label="Filter host proxmox"
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
                    placeholder="Cari nama, kode, IP, atau OS..."
                    wire:model.live.debounce.300ms="vmSearch"
                    aria-label="Cari vm"
                >
            </div>
        </div>
    </div>
</div>

{{-- Table --}}
@php $vms = $this->vms(); @endphp
@php
    // Konteks daftar untuk link detail: halaman + filter aktif (param default dibuang)
    $detailContext = array_filter([
        'vms_page' => $vms->currentPage() > 1 ? $vms->currentPage() : null,
        'vmSearch' => filled($vmSearch) ? $vmSearch : null,
        'proxmoxFilter' => $proxmoxFilter !== 'ALL' ? $proxmoxFilter : null,
    ], fn ($value) => $value !== null);
@endphp
@if ($vms->count() > 0)
    <div class="mt-4 overflow-hidden rounded-lg border border-stroke-primary bg-background-primary">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-stroke-primary bg-background-secondary text-left text-[12px] font-medium text-content-secondary">
                        <th scope="col" class="h-10 px-4 font-medium">VMID</th>
                        <th scope="col" class="h-10 px-4 font-medium">Nama</th>
                        <th scope="col" class="h-10 px-4 font-medium">Proxmox Induk</th>
                        <th scope="col" class="h-10 px-4 font-medium">Local IP</th>
                        <th scope="col" class="h-10 px-4 font-medium">Public IP</th>
                        <th scope="col" class="h-10 px-4 font-medium text-right">CPU</th>
                        <th scope="col" class="h-10 px-4 font-medium text-right">RAM</th>
                        <th scope="col" class="h-10 px-4 font-medium text-right">Storage</th>
                        <th scope="col" class="h-10 px-4 font-medium">OS</th>
                        <th scope="col" class="h-10 px-4 font-medium text-right">Panel</th>
                        <th scope="col" class="h-10 px-4 font-medium text-right">Aplikasi</th>
                        <th scope="col" class="h-10 px-4 font-medium">Status</th>
                        <th scope="col" class="h-10 px-4 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($vms as $vm)
                        <tr class="border-b border-stroke-primary transition-colors duration-150 h-14 last:border-b-0 hover:bg-neutral-50" wire:key="vms-{{ $vm->id }}">
                            <td class="h-10 px-4 text-content-secondary">{{ $vm->proxmox_number ?? '-' }}</td>
                            <td class="h-10 px-4 font-medium text-content-primary">{{ $vm->name }}</td>
                            <td class="h-10 px-4 text-content-secondary">
                                {{ $vm->proxmox?->name ?? '-' }}
                                <span class="block text-[11px] text-content-tertiary">{{ $vm->proxmox?->proxmox_id }}</span>
                            </td>
                            <td class="h-10 px-4 text-content-secondary">{{ $vm->local_ip ?? '-' }}</td>
                            <td class="h-10 px-4 text-content-secondary">{{ $vm->public_ip ?? '-' }}</td>
                            <td class="h-10 px-4 text-right text-content-primary">{{ $vm->cpu !== null ? $vm->cpu.' core' : '-' }}</td>
                            <td class="h-10 px-4 text-right text-content-primary">{{ $vm->ram !== null ? number_format($vm->ram / 1024).' GB' : '-' }}</td>
                            <td class="h-10 px-4 text-right text-content-primary">{{ $vm->storage !== null ? number_format($vm->storage / 1024).' GB' : '-' }}</td>
                            <td class="h-10 px-4 text-content-secondary">{{ $vm->os ?? '-' }}</td>
                            <td class="h-10 px-4 text-right text-content-primary">{{ $vm->panels_count }} panel</td>
                            <td class="h-10 px-4 text-right text-content-primary">{{ $vm->hostings_count }} app</td>
                            <td class="h-10 px-4">
                                <span class="ina-badge ina-badge--soft ina-badge--sm {{ $vm->status === VmsStatus::On ? 'ina-badge--success' : ($vm->status === VmsStatus::Destroyed ? 'ina-badge--error' : 'ina-badge--neutral') }}">
                                    {{ $vm->status->value }}
                                </span>
                            </td>
                            <td class="h-10 px-4">
                                <div class="flex items-center justify-end gap-1">
                                    <a
                                        href="{{ route('admin.assets.vms.show', [...$detailContext, 'vmId' => $vm->vm_id]) }}"
                                        wire:navigate
                                        class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                        aria-label="Detail {{ $vm->name }}"
                                        title="Detail"
                                    >
                                        <i class="ti ti-eye text-base"></i>
                                    </a>
                                    @can('infrastructure.manage')
                                    <button
                                        type="button"
                                        wire:click="editVms({{ $vm->id }})"
                                        class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                        aria-label="Ubah {{ $vm->name }}"
                                        title="Ubah"
                                    >
                                        <i class="ti ti-pencil text-base"></i>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="askDeleteVms({{ $vm->id }})"
                                        class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-negative-50 hover:text-negative-600"
                                        aria-label="Hapus {{ $vm->name }}"
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

    <div class="mt-6">{{ $vms->links('vendor.pagination.tailwind') }}</div>
@else
    {{-- Empty state (IDDS) --}}
    <div class="ina-card ina-card--stack mt-4 px-6 py-12 text-center">
        <x-asset.illustration name="{{ $vmSearch !== '' || $proxmoxFilter !== 'ALL' ? 'noresult' : 'nodata' }}" size="sm" />
        <h2 class="mt-4 text-base font-semibold text-content-primary">
            {{ $vmSearch !== '' || $proxmoxFilter !== 'ALL' ? 'Tidak ada VM untuk filter ini' : 'Belum ada VM' }}
        </h2>
        <p class="mt-2 mx-auto max-w-sm text-sm text-content-secondary">
            {{ $vmSearch !== '' || $proxmoxFilter !== 'ALL' ? 'Ubah kata kunci atau filter host.' : 'VM yang ditambahkan akan tampil di sini, terhubung ke host Proxmox.' }}
        </p>
    </div>
@endif

    @include('pages/admin/assets/partials/vms-form-modal')

{{-- Modal Konfirmasi Hapus (IDDS Confirm Modal) --}}
<div
    x-data="{ open: $wire.entangle('showVmsDeleteModal') }"
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
        aria-labelledby="vms-delete-title"
        @click.stop
    >
        @php $deleting = $this->deletingVm(); @endphp
        <div class="flex items-start justify-between">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-negative-50 text-negative-600">
                    <i class="ti ti-alert-triangle text-lg"></i>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-content-primary" id="vms-delete-title">Hapus VM</h2>
                    <p class="mt-1 text-sm text-content-secondary">
                        VM <span class="font-medium text-content-primary">{{ $deleting?->name ?? '-' }}</span>
                        @if ($deleting?->proxmox !== null)
                            di host <span class="font-medium text-content-primary">{{ $deleting?->proxmox?->name }}</span> akan dihapus
                        @else
                            akan dihapus
                        @endif
                        dari daftar. Tindakan ini tidak bisa dibatalkan.
                    </p>
                    @error('deletingVmsId')
                        <p class="mt-1.5 text-sm text-negative-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="$set('showVmsDeleteModal', false)" aria-label="Tutup">
                <i class="ti ti-x text-lg"></i>
            </button>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" class="ina-button ina-button--secondary ina-button--md" wire:click="$set('showVmsDeleteModal', false)">
                Batal
            </button>
            <button
                type="button"
                class="ina-button ina-button--secondary ina-button--md bg-negative-600! text-white! hover:bg-negative-700!"
                wire:loading.attr="disabled"
                wire:click="deleteVms"
            >
                Hapus
            </button>
        </div>
    </div>
</div>
