    {{-- Modal Add/Edit User Database (IDDS Task Modal) --}}
    <div
        x-data="{ open: $wire.entangle('showUserModal') }"
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
            aria-labelledby="db-user-modal-title"
            @click.stop
        >
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-content-primary" id="db-user-modal-title">
                        {{ $editingUserId !== null ? 'Ubah User Database' : 'Tambah User Database' }}
                    </h2>
                    <p class="mt-1 text-sm text-content-secondary">
                        User kredensial di instance {{ $instance->name ?? '-' }} — dipakai oleh satu atau lebih application.
                    </p>
                </div>
                <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="closeUserModal" aria-label="Tutup">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <form class="mt-6 space-y-5" wire:submit="saveUser">
                <div class="ina-text-field">
                    <label for="db-user-username" class="ina-text-field__label">
                        Username
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('username') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="db-user-username"
                            wire:model="user_username"
                            class="ina-text-field__input"
                            placeholder="simpelda_app"
                            autocomplete="off"
                            required
                        >
                    </div>
                    @error('username')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-field">
                    <label for="db-user-password" class="ina-text-field__label">
                        Password
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('password') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="password"
                            id="db-user-password"
                            wire:model="user_password"
                            class="ina-text-field__input"
                            autocomplete="new-password"
                            placeholder="{{ $editingUserId !== null ? 'Kosongkan bila tidak diubah' : '••••••••' }}"
                        >
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-area">
                    <label for="db-user-note" class="ina-text-area__label">
                        Keterangan
                    </label>
                    <div class="ina-text-area__wrapper ina-text-area__wrapper--size-sm {{ $errors->has('note') ? 'ina-text-area__wrapper--status-error' : '' }}">
                        <textarea
                            id="db-user-note"
                            wire:model="user_note"
                            class="ina-text-area__input"
                            placeholder="Kegunaan user, aplikasi pemakai, hak akses..."
                        ></textarea>
                    </div>
                    @error('note')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="ina-button ina-button--secondary ina-button--md" wire:click="closeUserModal">
                        Batal
                    </button>
                    <button type="submit" class="ina-button ina-button--primary ina-button--md" wire:loading.attr="disabled">
                        {{ $editingUserId !== null ? 'Simpan Perubahan' : 'Simpan' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Konfirmasi Hapus User --}}
    <div
        x-data="{ open: $wire.entangle('showUserDeleteModal') }"
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
            aria-labelledby="db-user-delete-title"
            @click.stop
        >
            @php $deletingUser = $this->deletingUser(); @endphp
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-negative-50 text-negative-600">
                        <i class="ti ti-alert-triangle text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-content-primary" id="db-user-delete-title">Hapus User Database</h2>
                        <p class="mt-1 text-sm text-content-secondary">
                            User <span class="font-medium text-content-primary">{{ $deletingUser?->username ?? '-' }}</span>
                            @php $deletingUserUsageCount = $deletingUser !== null ? \App\Models\ApplicationDatabase::query()->where('db_user_id', $deletingUser->dbu_id)->count() : 0; @endphp
                            @if ($deletingUserUsageCount > 0)
                                sedang dipakai oleh {{ $deletingUserUsageCount }} pemakaian application
                                (kredensialnya akan lepas, relasi application tetap ada).
                            @endif
                            Aksi ini tidak bisa dibatalkan.
                        </p>
                        @error('deletingUserId')
                            <p class="mt-1.5 text-sm text-negative-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="$set('showUserDeleteModal', false)" aria-label="Tutup">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="ina-button ina-button--secondary ina-button--md" wire:click="$set('showUserDeleteModal', false)">
                    Batal
                </button>
                <button
                    type="button"
                    class="ina-button ina-button--secondary ina-button--md bg-negative-600! text-white! hover:bg-negative-700!"
                    wire:loading.attr="disabled"
                    wire:click="deleteUser"
                >
                    Hapus
                </button>
            </div>
        </div>
    </div>
