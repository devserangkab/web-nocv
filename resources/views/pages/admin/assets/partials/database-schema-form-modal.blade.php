    {{-- Modal Add/Edit Database Schema (IDDS Task Modal) --}}
    <div
        x-data="{ open: $wire.entangle('showSchemaModal') }"
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
            aria-labelledby="db-schema-modal-title"
            @click.stop
        >
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-content-primary" id="db-schema-modal-title">
                        {{ $editingSchemaId !== null ? 'Ubah Database' : 'Tambah Database' }}
                    </h2>
                    <p class="mt-1 text-sm text-content-secondary">
                        Database schema di instance {{ $instance->name ?? '-' }}.
                    </p>
                </div>
                <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="closeSchemaModal" aria-label="Tutup">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <form class="mt-6 space-y-5" wire:submit="saveSchema">
                <div class="ina-text-field">
                    <label for="db-schema-name" class="ina-text-field__label">
                        Nama Database
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('name') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="db-schema-name"
                            wire:model="schema_name"
                            class="ina-text-field__input"
                            placeholder="simpelda"
                            required
                        >
                    </div>
                    @error('name')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-area">
                    <label for="db-schema-note" class="ina-text-area__label">
                        Keterangan
                    </label>
                    <div class="ina-text-area__wrapper ina-text-area__wrapper--size-sm {{ $errors->has('note') ? 'ina-text-area__wrapper--status-error' : '' }}">
                        <textarea
                            id="db-schema-note"
                            wire:model="schema_note"
                            class="ina-text-area__input"
                            placeholder="Keterangan database (charset, kegunaan, dsb.)..."
                        ></textarea>
                    </div>
                    @error('note')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="ina-button ina-button--secondary ina-button--md" wire:click="closeSchemaModal">
                        Batal
                    </button>
                    <button type="submit" class="ina-button ina-button--primary ina-button--md" wire:loading.attr="disabled">
                        {{ $editingSchemaId !== null ? 'Simpan Perubahan' : 'Simpan' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
