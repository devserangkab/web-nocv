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
                    :options="collect(\App\Enums\VmsStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->value])->all()"
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

