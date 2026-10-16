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

