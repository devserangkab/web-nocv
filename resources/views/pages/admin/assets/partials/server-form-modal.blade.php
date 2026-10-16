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
            aria-labelledby="server-modal-title"
            @click.stop
        >
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-content-primary" id="server-modal-title">
                        {{ $editingId !== null ? 'Ubah Server' : 'Tambah Server' }}
                    </h2>
                    <p class="mt-1 text-sm text-content-secondary">
                        {{ $editingId !== null ? 'Perbarui data server.' : 'Lengkapi data server untuk ditambahkan ke daftar.' }}
                    </p>
                </div>
                <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="closeModal" aria-label="Tutup">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <form class="mt-6 space-y-5" wire:submit="save">
                <div class="ina-text-field">
                    <label for="server_name" class="ina-text-field__label">
                        Nama Server
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('server_name') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="server_name"
                            wire:model="server_name"
                            class="ina-text-field__input"
                            placeholder="SRV-APP-01"
                            required
                            autofocus
                        >
                    </div>
                    @error('server_name')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="ina-text-field">
                        <label for="rack_id" class="ina-text-field__label">
                            Rack
                        </label>
                        <x-idds.select
                            wire-prop="rack_id"
                            :value="$rack_id"
                            :groups="collect($this->rackOptions())->map(fn ($options, $label) => ['label' => $label, 'options' => $options])->values()->prepend(['label' => null, 'options' => [['value' => '', 'label' => 'Tanpa Rack']]])->all()"
                            placeholder="Tanpa Rack"
                            aria-label="Pilih rack"
                            :error="$errors->has('rack_id')"
                        />
                        <p class="mt-1.5 text-xs text-content-tertiary">
                            {{ $rack_id !== '' ? 'Lokasi mengikuti rack yang dipilih.' : 'Pilih lokasi bila tidak memakai rack.' }}
                        </p>
                        @error('rack_id')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="form-location" class="ina-text-field__label">
                            Location
                            <span class="ina-text-field__required" @if ($rack_id !== '')
                                style="display: none"
                            @endif>*</span>
                        </label>
                        <x-idds.select
                            wire-prop="location_id"
                            :value="$location_id"
                            :options="$this->locationOptions()->map(fn ($loc) => ['value' => $loc->location_id, 'label' => $loc->name])->all()"
                            placeholder="Pilih lokasi..."
                            aria-label="Pilih lokasi"
                            :disabled="$rack_id !== ''"
                            :error="$errors->has('location_id')"
                        />
                        @error('location_id')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="ina-text-field">
                    <label for="position" class="ina-text-field__label">
                        Position
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('position') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="number"
                            min="1"
                            id="position"
                            wire:model="position"
                            class="ina-text-field__input"
                            placeholder="1"
                        >
                    </div>
                    <p class="mt-1.5 text-xs text-content-tertiary">1 = paling atas di dalam rack.</p>
                    @error('position')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-field">
                    <label for="brand" class="ina-text-field__label">
                        Brand
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('brand') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="brand"
                            wire:model="brand"
                            class="ina-text-field__input"
                            placeholder="HPE ProLiant DL380 Gen10"
                        >
                    </div>
                    @error('brand')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="ina-text-field">
                        <label for="storage_size" class="ina-text-field__label">
                            Storage (MB)
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('storage_size') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="number"
                                min="0"
                                id="storage_size"
                                wire:model="storage_size"
                                class="ina-text-field__input"
                                placeholder="475000"
                            >
                        </div>
                        @error('storage_size')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="memory_size" class="ina-text-field__label">
                            Memory (MB)
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('memory_size') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="number"
                                min="0"
                                id="memory_size"
                                wire:model="memory_size"
                                class="ina-text-field__input"
                                placeholder="64081"
                            >
                        </div>
                        @error('memory_size')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="ina-text-field">
                        <label for="storage" class="ina-text-field__label">
                            Spesifikasi Storage
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('storage') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="storage"
                                wire:model="storage"
                                class="ina-text-field__input"
                                placeholder="HPE 3.8TB 15K RPM HDD"
                            >
                        </div>
                        @error('storage')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="memory" class="ina-text-field__label">
                            Spesifikasi Memory
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('memory') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="memory"
                                wire:model="memory"
                                class="ina-text-field__input"
                                placeholder="HPE 64GB (4x16GB) DDR4 2666MT/s"
                            >
                        </div>
                        @error('memory')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="ina-text-field">
                    <label for="server-status" class="ina-text-field__label">
                        Status
                    </label>
                    <x-idds.select
                        wire-prop="status"
                        :value="$status"
                        :options="collect(\App\Enums\ServerStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->value])->all()"
                        aria-label="Pilih status server"
                        :error="$errors->has('status')"
                    />
                    @error('status')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-field">
                    <label for="cpu" class="ina-text-field__label">
                        CPU
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('cpu') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="cpu"
                            wire:model="cpu"
                            class="ina-text-field__input"
                            placeholder="Intel Xeon Gold 6248 20 Core"
                        >
                    </div>
                    <p class="mt-1.5 text-xs text-content-tertiary">Contoh: Intel Xeon Gold 6248 20 Core.</p>
                    @error('cpu')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="ina-text-field">
                        <label for="static_ip" class="ina-text-field__label">
                            Static IP
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('static_ip') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="static_ip"
                                wire:model="static_ip"
                                class="ina-text-field__input"
                                placeholder="192.0.2.15"
                            >
                        </div>
                        @error('static_ip')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="os" class="ina-text-field__label">
                            OS
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('os') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="os"
                                wire:model="os"
                                class="ina-text-field__input"
                                placeholder="Ubuntu Server 22.04 LTS"
                            >
                        </div>
                        @error('os')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="ina-text-field">
                        <label for="server-username" class="ina-text-field__label">
                            Username
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('username') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="server-username"
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
                        <label for="server-password" class="ina-text-field__label">
                            Password
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('password') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="password"
                                id="server-password"
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

