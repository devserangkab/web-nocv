{{-- Toast notification — listener event "notify" dari komponen Livewire --}}
<div
    x-data="{ toasts: [] }"
    @notify.window="
        const id = Date.now() + Math.random();
        toasts.push({ id, type: $event.detail.type ?? 'success', message: $event.detail.message ?? '' });
        setTimeout(() => { toasts = toasts.filter(t => t.id !== id) }, 4000)
    "
    x-cloak
    class="pointer-events-none fixed bottom-6 right-6 z-70 flex flex-col items-end gap-2"
    aria-live="polite"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            class="pointer-events-auto flex max-w-md items-start gap-2.5 rounded-lg border px-4 py-2.5 shadow-lg"
            :class="toast.type === 'error' ? 'ina-toast-error' : 'ina-toast-success'"
            role="status"
        >
            <i class="ti mt-0.5 text-base" :class="toast.type === 'error' ? 'ti-circle-x' : 'ti-circle-check'"></i>
            <span class="min-w-0 flex-1 break-words text-sm font-medium" x-text="toast.message"></span>
            <button
                type="button"
                class="ml-1 text-content-tertiary transition-colors duration-150 hover:text-content-primary"
                @click="toasts = toasts.filter(t => t.id !== toast.id)"
                aria-label="Tutup notifikasi"
            >
                <i class="ti ti-x text-sm"></i>
            </button>
        </div>
    </template>
</div>
