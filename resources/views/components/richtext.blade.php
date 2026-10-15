<?php
/**
 * Bridge Tiptap → Livewire:
 *  x-data="richtext({ prop: '<path>', content: '<html>' })"
 *  sinkron HTML ke Livewire via $wire.set() (debounced).
 */
?>
@props([
    'prop',
    'placeholder' => 'Tulis isi...',
    'content' => null,
    'error' => false,
])

<div
    x-data="richtext({
        prop: {{ \Illuminate\Support\Js::from($prop) }},
        placeholder: @js($placeholder),
        uploadUrl: @js(route('admin.report-assets.store')),
        content: @js($content ?? ''),
    })"
    @keydown.round-dashes.1.stop
    class="rounded-lg border {{ $error ? 'border-negative-500' : 'border-stroke-primary' }} bg-background-primary overflow-hidden"
    {{ $attributes }}
>
    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center gap-0.5 border-b border-stroke-primary bg-background-secondary px-2 py-1.5">
        <button type="button" @mousedown.prevent x-on:click="cmd('toggleBold')" :title="'Bold'" class="flex h-7 w-7 items-center justify-center rounded font-bold text-sm text-content-secondary hover:bg-background-tertiary" :class="{ 'bg-guide-50 text-guide-600': isActive('bold') }"><b>B</b></button>
        <button type="button" @mousedown.prevent x-on:click="cmd('toggleItalic')" title="Italic" class="flex h-7 w-7 items-center justify-center rounded italic text-sm text-content-secondary hover:bg-background-tertiary" :class="{ 'bg-guide-50 text-guide-600': isActive('italic') }"><i>I</i></button>
        <button type="button" @mousedown.prevent x-on:click="cmd('toggleUnderline')" title="Underline" class="flex h-7 w-7 items-center justify-center rounded text-sm text-content-secondary hover:bg-background-tertiary" :class="{ 'bg-guide-50 text-guide-600': isActive('underline') }"><u>U</u></button>

        <div class="mx-1 h-5 border-l border-stroke-primary"></div>

        <button type="button" @mousedown.prevent x-on:click="cmd('toggleHeading', { level: 1 })" title="Heading 1" class="flex h-7 min-w-7 items-center justify-center rounded px-1 text-xs font-semibold text-content-secondary hover:bg-background-tertiary" :class="{ 'bg-guide-50 text-guide-600': isActive('heading') }">H1</button>
        <button type="button" @mousedown.prevent x-on:click="cmd('toggleHeading', { level: 2 })" title="Heading 2" class="flex h-7 min-w-7 items-center justify-center rounded px-1 text-xs font-semibold text-content-secondary hover:bg-background-tertiary" :class="{ 'bg-guide-50 text-guide-600': isHeading(2) }">H2</button>
        <button type="button" @mousedown.prevent x-on:click="cmd('toggleHeading', { level: 3 })" title="Heading 3" class="flex h-7 min-w-7 items-center justify-center rounded px-1 text-xs font-semibold text-content-secondary hover:bg-background-tertiary" :class="{ 'bg-guide-50 text-guide-600': isHeading(3) }">H3</button>

        <div class="mx-1 h-5 border-l border-stroke-primary"></div>

        <button type="button" @mousedown.prevent x-on:click="cmd('toggleBulletList')" title="Bullet List" class="flex h-7 w-7 items-center justify-center rounded text-content-secondary hover:bg-background-tertiary" :class="{ 'bg-guide-50 text-guide-600': isActive('bulletList') }">
            <i class="ti ti-list text-sm"></i>
        </button>
        <button type="button" @mousedown.prevent x-on:click="cmd('toggleOrderedList')" title="Numbered List" class="flex h-7 w-7 items-center justify-center rounded text-content-secondary hover:bg-background-tertiary" :class="{ 'bg-guide-50 text-guide-600': isActive('orderedList') }">
            <i class="ti ti-list-numbers text-sm"></i>
        </button>

        <div class="mx-1 h-5 border-l border-stroke-primary"></div>

        <button type="button" @mousedown.prevent x-on:click="insertTable()" title="Sisipkan Tabel" class="flex h-7 w-7 items-center justify-center rounded text-content-secondary hover:bg-background-tertiary">
            <i class="ti ti-table text-sm"></i>
        </button>
        <button type="button" @mousedown.prevent x-on:click="$refs.file.click()" title="Sisipkan Gambar" class="flex h-7 w-7 items-center justify-center rounded text-content-secondary hover:bg-background-tertiary">
            <i class="ti ti-photo-plus text-sm"></i>
        </button>
        <button type="button" @mousedown.prevent x-on:click="insertLink()" title="Tautan" class="flex h-7 w-7 items-center justify-center rounded text-content-secondary hover:bg-background-tertiary">
            <i class="ti ti-link text-sm"></i>
        </button>
        <button type="button" @mousedown.prevent x-on:click="cmd('unsetAllMarks')" title="Bersihkan Format" class="flex h-7 w-7 items-center justify-center rounded text-content-secondary hover:bg-background-tertiary">
            <i class="ti ti-eraser text-sm"></i>
        </button>
    </div>

    {{-- Editor area --}}
    <div
        x-ref="editor"
        class="rich-text-editor max-h-[28rem] min-h-[9rem] overflow-y-auto px-3 py-2"
        placeholder="{{ $placeholder }}"
    ></div>

    {{-- hidden file input untuk upload gambar --}}
    <input type="file" class="hidden" accept="image/jpeg,image/png,image/webp" x-ref="file" x-on:change="uploadImage($event)">
    {{-- status upload --}}
    <p class="flex items-center gap-1.5 border-t border-stroke-primary px-3 py-1.5 text-xs text-content-tertiary" x-show="uploading">
        <i class="ti ti-loader-2 animate-spin"></i>
        Mengunggah gambar...
    </p>
</div>
