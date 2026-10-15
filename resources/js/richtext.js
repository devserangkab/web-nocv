// Bridge Tiptap → Livewire/Alpine
// Gunakan: <div x-data="richtext({prop: 'path'})"></div> — sinkron via $wire.set()
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';
import { TextStyle } from '@tiptap/extension-text-style';
import { Table } from '@tiptap/extension-table';
import { TableRow } from '@tiptap/extension-table-row';
import { TableCell } from '@tiptap/extension-table-cell';
import { TableHeader } from '@tiptap/extension-table-header';

function debounce(fn, delay) {
    let timer = null;

    return function () {
        clearTimeout(timer);
        timer = setTimeout(fn, delay);
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('richtext', (config) => ({
        editor: null,
        uploading: false,

        init() {
            const element = this.$refs.editor;

            this.editor = new Editor({
                element,
                extensions: [
                    StarterKit.configure({
                        heading: { levels: [1, 2, 3] },
                    }),
                    Underline,
                    Image.configure({ inline: false, allowBase64: false }),
                    Link.configure({ openOnClick: false, autolink: true }),
                    TextStyle,
                    Table.configure({ resizable: false }).configure({ resizable: false }),
                    TableRow,
                    TableCell,
                    TableHeader,
                ],
                content: typeof config.content === 'string' && config.content.trim() !== ''
                    ? config.content
                    : '<p></p>',
                onUpdate: debounce(() => {
                    if (config.prop) {
                        this.$wire.set(config.prop, this.editor.getHTML());
                    }
                }, 500),
                onSelectionUpdate: /* Hidupkan retak toolbar */ () => this.$el.dispatchEvent(new CustomEvent('rich-sync')),
                editorProps: {
                    attributes: {
                    class: 'tn-content mx-content focus:outline-none',
                    },
                },
            });
        },

        destroy() {
            if (this.editor) {
                this.editor.destroy();
                this.editor = null;
            }
        },

        cmd(name, args = {}) {
            this.editor?.chain().focus()[name](args).run();
        },

        isActive(name, value) {
            if (this.editor === null) { return false; }
            return this.editor.isActive(name, value ?? {});
        },

        /** Status heading bersifat level — dipakai H2/H3 per level. */
        isHeading(level) {
            return this.isActive('heading', { level });
        },

        /** Sisipkan tabel 3x3 kosong */
        insertTable() {
            this.editor?.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
        },

        /** Prompt sekali + isi href anchor selection */
        insertLink() {
            const href = window.prompt('Masukkan tautan (https://...):', 'https://');
            if (href === null || href.trim() === '') { return; }
            this.editor?.chain().focus().extendMarkRange('link').setLink({ href }).run();
        },

        /** Upload gambar ke disk private lalu sisipkan sebagai <img src="/admin/report-assets/{path}"> */
        async uploadImage(event) {
            const file = event.target.files?.[0];

            if (!file) { return; }

            this.uploading = true;

            try {
                const form = new FormData();
                form.append('file', file);
                form.append('_token', window.document.querySelector('meta[name="csrf-token"]')?.content ?? '');

                const response = await fetch(config.uploadUrl, { method: 'POST', body: form });
                const json = await response.json();

                if (json.url) {
                    this.editor?.chain().focus().setImage({ src: json.url, alt: file.name }).run();
                    this.editor?.commands?.setImage({ src: json.url, alt: file.name });
                }
            } catch (error) {
                console.error('Gagal mengunggah gambar:', error);
            } finally {
                this.uploading = false;
                event.target.value = '';
            }
        },
    }));
});
