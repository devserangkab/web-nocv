    {{-- Modal Add/Edit (IDDS Task Modal) --}}
    <div
        x-data="{ open: $wire.entangle('showModal') }"
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
            aria-labelledby="panels-modal-title"
            @click.stop
        >
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-content-primary" id="panels-modal-title">
                        {{ $editingId !== null ? 'Ubah Panel' : 'Tambah Panel' }}
                    </h2>
                    <p class="mt-1 text-sm text-content-secondary">
                        {{ $editingId !== null ? 'Perbarui data panel.' : 'Kode akan terisi otomatis setelah disimpan.' }}
                    </p>
                </div>
                <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="closeModal" aria-label="Tutup">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <form class="mt-6 space-y-5" wire:submit="save">
                <div class="ina-text-field">
                    <label for="panel-host" class="ina-text-field__label">
                        Host
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <x-idds.select
                        wire-prop="host"
                        :value="$host"
                        :groups="$this->hostOptions()"
                        placeholder="Pilih host..."
                        aria-label="Pilih host panel"
                        :error="$errors->has('host')"
                    />
                    @error('host')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-field">
                    <label for="panel-name" class="ina-text-field__label">
                        Name
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('name') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="panel-name"
                            wire:model="name"
                            class="ina-text-field__input"
                            placeholder="aapanel-web-1"
                            required
                        >
                    </div>
                    @error('name')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-field">
                    <label for="panel-type" class="ina-text-field__label">
                        Jenis Panel
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <x-idds.select
                        wire-prop="type"
                        :value="$type"
                        :options="$this->typeOptions()"
                        placeholder="Pilih jenis..."
                        aria-label="Pilih jenis panel"
                        :error="$errors->has('type')"
                    />
                    @error('type')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="ina-text-field">
                        <label for="panel-internet" class="ina-text-field__label">
                            Internet Address
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('internet_address') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="panel-internet"
                                wire:model="internet_address"
                                class="ina-text-field__input"
                                placeholder="https://panel.domain:7800"
                            >
                        </div>
                        @error('internet_address')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="panel-internal" class="ina-text-field__label">
                            Internal Address
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('internal_address') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="panel-internal"
                                wire:model="internal_address"
                                class="ina-text-field__input"
                                placeholder="https://192.0.2.10:7800"
                            >
                        </div>
                        @error('internal_address')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="ina-text-field">
                    <label for="panel-version" class="ina-text-field__label">
                        Version
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('version') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="panel-version"
                            wire:model="version"
                            class="ina-text-field__input"
                            placeholder="7.0.11"
                        >
                    </div>
                    @error('version')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="ina-text-field">
                        <label for="panel-username" class="ina-text-field__label">
                            Username
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('username') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="panel-username"
                                wire:model="username"
                                class="ina-text-field__input"
                                autocomplete="off"
                                placeholder="admin"
                            >
                        </div>
                        @error('username')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="panel-password" class="ina-text-field__label">
                            Password
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('password') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="password"
                                id="panel-password"
                                wire:model="password"
                                class="ina-text-field__input"
                                autocomplete="new-password"
                                placeholder="{{ $editingId !== null ? 'Kosongkan bila tidak diubah' : '••••••••' }}"
                            >
                        </div>
                        @error('password')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
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

