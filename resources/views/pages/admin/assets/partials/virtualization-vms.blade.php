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
                                <span class="ina-badge ina-badge--soft ina-badge--sm {{ $vm->status === VmsStatus::On ? 'ina-badge--positive' : ($vm->status === VmsStatus::Destroyed ? 'ina-badge--warning' : 'ina-badge--neutral') }}">
                                    {{ $vm->status->value }}
                                </span>
                            </td>
                            <td class="h-10 px-4">
                                <div class="flex items-center justify-end gap-1">
                                    <a
                                        href="{{ route('admin.assets.vms.show', $vm->vm_id) }}"
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

{{-- Modal Add/Edit (IDDS Task Modal) --}}
<div
    x-data="{ open: $wire.entangle('showVmsModal') }"
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
        aria-labelledby="vms-modal-title"
        @click.stop
    >
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-lg font-semibold text-content-primary" id="vms-modal-title">
                    {{ $editingVmsId !== null ? 'Ubah VM' : 'Tambah VM' }}
                </h2>
                <p class="mt-1 text-sm text-content-secondary">
                    {{ $editingVmsId !== null ? 'Perbarui data VM.' : 'Kode akan terisi otomatis setelah disimpan.' }}
                </p>
            </div>
            <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="closeVmsModal" aria-label="Tutup">
                <i class="ti ti-x text-lg"></i>
            </button>
        </div>

        <form class="mt-6 space-y-5" wire:submit="saveVms">
            <div class="grid grid-cols-2 gap-4">
                <div class="ina-text-field">
                    <label for="vm-host" class="ina-text-field__label">
                        Proxmox Induk
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <x-idds.select
                        wire-prop="proxmox_id"
                        :value="$proxmox_id"
                        :options="$this->proxmoxOptions()"
                        placeholder="Pilih host..."
                        aria-label="Pilih host proxmox"
                        :error="$errors->has('proxmox_id')"
                    />
                    @error('proxmox_id')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-field">
                    <label for="vm-number" class="ina-text-field__label">
                        VMID
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('proxmox_number') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="number"
                            min="1"
                            id="vm-number"
                            wire:model="proxmox_number"
                            class="ina-text-field__input"
                            placeholder="101"
                        >
                    </div>
                    @error('proxmox_number')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="ina-text-field">
                <label for="vm-name" class="ina-text-field__label">
                    Nama
                    <span class="ina-text-field__required">*</span>
                </label>
                <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('name') ? 'ina-text-field__wrapper--status-error' : '' }}">
                    <input
                        type="text"
                        id="vm-name"
                        wire:model="vm_name"
                        class="ina-text-field__input"
                        placeholder="vm-web-1"
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
                    <label for="vm-local-ip" class="ina-text-field__label">
                        Local IP
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('local_ip') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="vm-local-ip"
                            wire:model="local_ip"
                            class="ina-text-field__input"
                            placeholder="192.0.2.10"
                        >
                    </div>
                    @error('local_ip')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-field">
                    <label for="vm-public-ip" class="ina-text-field__label">
                        Public IP
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('public_ip') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="vm-public-ip"
                            wire:model="public_ip"
                            class="ina-text-field__input"
                            placeholder="198.51.100.7"
                        >
                    </div>
                    @error('public_ip')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="ina-text-field">
                    <label for="vm-username" class="ina-text-field__label">
                        Username
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('username') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="vm-username"
                            wire:model="username"
                            class="ina-text-field__input"
                            autocomplete="off"
                            placeholder="root"
                        >
                    </div>
                    @error('username')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-field">
                    <label for="vm-password" class="ina-text-field__label">
                        Password
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('password') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="password"
                            id="vm-password"
                            wire:model="password"
                            class="ina-text-field__input"
                            autocomplete="new-password"
                            placeholder="{{ $editingVmsId !== null ? 'Kosongkan bila tidak diubah' : '••••••••' }}"
                        >
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="ina-text-field">
                    <label for="vm-cpu" class="ina-text-field__label">
                        CPU (core)
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('cpu') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="number"
                            min="1"
                            id="vm-cpu"
                            wire:model="cpu"
                            class="ina-text-field__input"
                            placeholder="4"
                        >
                    </div>
                    @error('cpu')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-field">
                    <label for="vm-ram" class="ina-text-field__label">
                        RAM (MB)
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('ram') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="number"
                            min="0"
                            id="vm-ram"
                            wire:model="ram"
                            class="ina-text-field__input"
                            placeholder="8192"
                        >
                    </div>
                    @error('ram')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-field">
                    <label for="vm-storage" class="ina-text-field__label">
                        Storage (MB)
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('storage') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="number"
                            min="0"
                            id="vm-storage"
                            wire:model="storage"
                            class="ina-text-field__input"
                            placeholder="81920"
                        >
                    </div>
                    @error('storage')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="ina-text-field">
                <label for="vm-os" class="ina-text-field__label">
                    OS
                </label>
                <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('os') ? 'ina-text-field__wrapper--status-error' : '' }}">
                    <input
                        type="text"
                        id="vm-os"
                        wire:model="os"
                        class="ina-text-field__input"
                        placeholder="Ubuntu Server 22.04 LTS"
                    >
                </div>
                @error('os')
                    <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="ina-text-area">
                <label for="vm-note" class="ina-text-area__label">
                    Catatan
                </label>
                <div class="ina-text-area__wrapper ina-text-area__wrapper--size-sm {{ $errors->has('note') ? 'ina-text-area__wrapper--status-error' : '' }}">
                    <textarea
                        id="vm-note"
                        wire:model="note"
                        class="ina-text-area__input"
                        placeholder="Catatan tambahan VM..."
                    ></textarea>
                </div>
                @error('note')
                    <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="ina-text-field">
                <label for="vm-status" class="ina-text-field__label">
                    Status
                </label>
                <x-idds.select
                    wire-prop="status"
                    :value="$status"
                    :options="collect(VmsStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->value])->all()"
                    aria-label="Pilih status VM"
                    :error="$errors->has('status')"
                />
                @error('status')
                    <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="ina-button ina-button--secondary ina-button--md" wire:click="closeVmsModal">
                    Batal
                </button>
                <button type="submit" class="ina-button ina-button--primary ina-button--md" wire:loading.attr="disabled">
                    {{ $editingVmsId !== null ? 'Simpan Perubahan' : 'Simpan' }}
                </button>
            </div>
        </form>
    </div>
</div>

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
