<?php
/**
 * Partial tab Proxmox — menyertakan tabel, modal, dan filter.
 * Property & method diambil dari komponen induk ⚡virtualization-index.
 */
use App\Models\Proxmox;
?>
{{-- Searchbar --}}
<div class="mt-6 max-w-sm sm:ml-auto w-full">
    <div class="ina-input-search ina-input-search--size-md">
        <div class="ina-input-search__wrapper">
            <i class="ti ti-search ina-input-search__prefix-icon" aria-hidden="true"></i>
            <input
                type="search"
                class="ina-input-search__input"
                placeholder="Cari nama, kode, URL, atau server..."
                wire:model.live.debounce.300ms="proxmoxSearch"
                aria-label="Cari proxmox"
            >
        </div>
    </div>
</div>

{{-- Table --}}
@php $proxmoxs = $this->proxmoxs(); @endphp
@if ($proxmoxs->count() > 0)
    <div class="mt-4 overflow-hidden rounded-lg border border-stroke-primary bg-background-primary">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-stroke-primary bg-background-secondary text-left text-[12px] font-medium text-content-secondary">
                        <th scope="col" class="h-10 px-4 font-medium">Kode</th>
                        <th scope="col" class="h-10 px-4 font-medium">Nama</th>
                        <th scope="col" class="h-10 px-4 font-medium">URL</th>
                        <th scope="col" class="h-10 px-4 font-medium">Versi</th>
                        <th scope="col" class="h-10 px-4 font-medium">Server Host</th>
                        <th scope="col" class="h-10 px-4 font-medium text-right">VM</th>
                        <th scope="col" class="h-10 px-4 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($proxmoxs as $proxmox)
                        <tr class="border-b border-stroke-primary transition-colors duration-150 h-14 last:border-b-0 hover:bg-neutral-50" wire:key="pmx-{{ $proxmox->id }}">
                            <td class="h-10 px-4 text-[13px] text-content-primary">{{ $proxmox->proxmox_id }}</td>
                            <td class="h-10 px-4 font-medium text-content-primary">{{ $proxmox->name }}</td>
                            <td class="h-10 px-4">
                                @if ($proxmox->url !== null)
                                    <a
                                        href="{{ $proxmox->url }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-[13px] text-guide-600 underline-offset-2 hover:underline"
                                    >{{ $proxmox->url }}</a>
                                @else
                                    <span class="text-content-tertiary">-</span>
                                @endif
                            </td>
                            <td class="h-10 px-4">
                                @if ($proxmox->version !== null)
                                    <span class="ina-badge ina-badge--soft ina-badge--neutral ina-badge--sm">v{{ $proxmox->version }}</span>
                                @else
                                    <span class="text-content-tertiary">-</span>
                                @endif
                            </td>
                            <td class="h-10 px-4">
                                @if ($proxmox->server !== null)
                                    <div class="flex flex-col">
                                        <span class="truncate font-medium text-content-primary" title="{{ $proxmox->server->server_name }}">{{ $proxmox->server->server_name }}</span>
                                        <span class="text-[11px] text-content-tertiary">{{ $proxmox->server->server_id }}</span>
                                    </div>
                                @else
                                    <span class="text-content-tertiary">-</span>
                                @endif
                            </td>
                            <td class="h-10 px-4 text-right text-content-primary">{{ $proxmox->vms_count }} VM</td>
                            <td class="h-10 px-4">
                                <div class="flex items-center justify-end gap-1">
                                    <a
                                        href="{{ route('admin.assets.proxmox.show', $proxmox->proxmox_id) }}"
                                        wire:navigate
                                        class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                        aria-label="Detail {{ $proxmox->name }}"
                                        title="Detail"
                                    >
                                        <i class="ti ti-eye text-base"></i>
                                    </a>
                                    <button
                                        type="button"
                                        wire:click="editProxmox({{ $proxmox->id }})"
                                        class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                        aria-label="Ubah {{ $proxmox->name }}"
                                        title="Ubah"
                                    >
                                        <i class="ti ti-pencil text-base"></i>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="askDeleteProxmox({{ $proxmox->id }})"
                                        class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-negative-50 hover:text-negative-600"
                                        aria-label="Hapus {{ $proxmox->name }}"
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

    <div class="mt-6">{{ $proxmoxs->links('vendor.pagination.tailwind') }}</div>
@else
    {{-- Empty state (IDDS) --}}
    <div class="ina-card ina-card--stack mt-4 px-6 py-12 text-center">
        <x-asset.illustration name="{{ $proxmoxSearch !== '' ? 'noresult' : 'nodata' }}" size="sm" />
        <h2 class="mt-4 text-base font-semibold text-content-primary">
            {{ $proxmoxSearch !== '' ? 'Tidak ada hasil untuk "'.$proxmoxSearch.'"' : 'Belum ada Proxmox' }}
        </h2>
        <p class="mt-2 mx-auto max-w-sm text-sm text-content-secondary">
            {{ $proxmoxSearch !== '' ? 'Coba kata kunci lain atau bersihkan pencarian.' : 'Host Proxmox yang ditambahkan akan tampil di sini.' }}
        </p>
    </div>
@endif

{{-- Modal Add/Edit (IDDS Task Modal) --}}
<div
    x-data="{ open: $wire.entangle('showProxmoxModal') }"
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
        aria-labelledby="proxmox-modal-title"
        @click.stop
    >
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-lg font-semibold text-content-primary" id="proxmox-modal-title">
                    {{ $editingProxmoxId !== null ? 'Ubah Proxmox' : 'Tambah Proxmox' }}
                </h2>
                <p class="mt-1 text-sm text-content-secondary">
                    {{ $editingProxmoxId !== null ? 'Perbarui data Proxmox.' : 'Kode akan terisi otomatis setelah disimpan.' }}
                </p>
            </div>
            <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="closeProxmoxModal" aria-label="Tutup">
                <i class="ti ti-x text-lg"></i>
            </button>
        </div>

        <form class="mt-6 space-y-5" wire:submit="saveProxmox">
            <div class="ina-text-field">
                <label for="proxmox-name" class="ina-text-field__label">
                    Nama
                    <span class="ina-text-field__required">*</span>
                </label>
                <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('name') ? 'ina-text-field__wrapper--status-error' : '' }}">
                    <input
                        type="text"
                        id="proxmox-name"
                        wire:model="proxmox_name"
                        class="ina-text-field__input"
                        placeholder="Proxmox VE 1"
                        required
                        autofocus
                    >
                </div>
                @error('name')
                    <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="ina-text-field">
                <label for="proxmox-url" class="ina-text-field__label">
                    URL
                </label>
                <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('url') ? 'ina-text-field__wrapper--status-error' : '' }}">
                    <input
                        type="text"
                        id="proxmox-url"
                        wire:model="proxmox_url"
                        class="ina-text-field__input"
                        placeholder="https://10.10.1.11:8006"
                    >
                </div>
                @error('url')
                    <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="ina-text-field">
                <label for="proxmox-version" class="ina-text-field__label">
                    Versi
                </label>
                <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('version') ? 'ina-text-field__wrapper--status-error' : '' }}">
                    <input
                        type="text"
                        id="proxmox-version"
                        wire:model="proxmox_version"
                        class="ina-text-field__input"
                        placeholder="8.2.4"
                        maxlength="20"
                    >
                </div>
                @error('version')
                    <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="ina-text-field">
                    <label for="proxmox-username" class="ina-text-field__label">
                        Username
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('username') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="proxmox-username"
                            wire:model="proxmox_username"
                            class="ina-text-field__input"
                            autocomplete="off"
                            placeholder="root@pam"
                        >
                    </div>
                    @error('username')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-field">
                    <label for="proxmox-password" class="ina-text-field__label">
                        Password
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('password') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="password"
                            id="proxmox-password"
                            wire:model="proxmox_password"
                            class="ina-text-field__input"
                            autocomplete="new-password"
                            placeholder="{{ $editingProxmoxId !== null ? 'Kosongkan bila tidak diubah' : '••••••••' }}"
                        >
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="ina-text-field">
                <label for="proxmox-server" class="ina-text-field__label">
                    Server Host
                </label>
                <x-idds.select
                    wire-prop="assign_server_id"
                    :value="$assign_server_id"
                    :options="collect($this->serverOptions())->prepend(['value' => '', 'label' => 'Tanpa Server'])->map(fn ($server) => ['value' => $server['value'], 'label' => $server['label'], 'disabled' => $server['taken'] ?? false])->values()->all()"
                    placeholder="Tanpa Server"
                    aria-label="Pilih server"
                    :error="$errors->has('assign_server_id')"
                />
                @error('assign_server_id')
                    <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="ina-button ina-button--secondary ina-button--md" wire:click="closeProxmoxModal">
                    Batal
                </button>
                <button type="submit" class="ina-button ina-button--primary ina-button--md" wire:loading.attr="disabled">
                    {{ $editingProxmoxId !== null ? 'Simpan Perubahan' : 'Simpan' }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Konfirmasi Hapus (IDDS Confirm Modal) --}}
<div
    x-data="{ open: $wire.entangle('showProxmoxDeleteModal') }"
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
        aria-labelledby="proxmox-delete-title"
        @click.stop
    >
        @php $deleting = $this->deletingProxmox(); @endphp
        <div class="flex items-start justify-between">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-negative-50 text-negative-600">
                    <i class="ti ti-alert-triangle text-lg"></i>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-content-primary" id="proxmox-delete-title">Hapus Proxmox</h2>
                    <p class="mt-1 text-sm text-content-secondary">
                        Proxmox <span class="font-medium text-content-primary">{{ $deleting?->name ?? '-' }}</span> akan dihapus
                        @if ($deleting?->server !== null)
                            dari server <span class="font-medium text-content-primary">{{ $deleting?->server->server_name }}</span>.
                        @else
                            dari daftar.
                        @endif
                        Tindakan ini tidak bisa dibatalkan.
                    </p>
                    @error('deletingProxmoxId')
                        <p class="mt-1.5 text-sm text-negative-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="$set('showProxmoxDeleteModal', false)" aria-label="Tutup">
                <i class="ti ti-x text-lg"></i>
            </button>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" class="ina-button ina-button--secondary ina-button--md" wire:click="$set('showProxmoxDeleteModal', false)">
                Batal
            </button>
            <button
                type="button"
                class="ina-button ina-button--secondary ina-button--md bg-negative-600! text-white! hover:bg-negative-700!"
                wire:loading.attr="disabled"
                wire:click="deleteProxmox"
            >
                Hapus
            </button>
        </div>
    </div>
</div>
