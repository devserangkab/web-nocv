import './bootstrap';
import './richtext';

import '@tabler/icons-webfont/dist/tabler-icons.min.css';

/**
 * Salin teks ke clipboard dengan fallback untuk konteks non-secure (HTTP).
 * navigator.clipboard hanya tersedia di HTTPS/localhost.
 */
window.copyToClipboard = (value) => {
    if (window.navigator.clipboard?.writeText) {
        return navigator.clipboard.writeText(value);
    }

    const area = document.createElement('textarea');

    area.value = value;
    area.style.position = 'fixed';
    area.style.opacity = '0';
    document.body.appendChild(area);
    area.select();
    document.execCommand('copy');
    area.remove();
};

/**
 * Kanvas topologi: geser (drag) dan perbesar (roda mouse, tombol) via transform CSS.
 * Posisi node & garis dihitung server-side; helper ini hanya mengelola viewport.
 * Dipakai lewat x-data="topologyCanvas()" pada komponen topology/canvas.
 */window.topologyCanvas = () => ({
    x: 0,
    y: 0,
    scale: 1,

    viewportWidth: 0,
    viewportHeight: 0,

    dragging: false,
    dragStartX: 0,
    dragStartY: 0,
    dragBaseX: 0,
    dragBaseY: 0,

    minimapJumping: false,

    contentObserver: null,

    init() {
        this.$nextTick(() => this.fit());

        // Konten berubah (pencarian/filter/collapse) → muat ulang seluruh topologi.
        if (typeof ResizeObserver === 'function') {
            this.$nextTick(() => {
                this.contentObserver = new ResizeObserver(() => this.fit());
                this.contentObserver.observe(this.$refs.content);
            });
        }
    },

    destroy() {
        this.contentObserver?.disconnect();
        this.contentObserver = null;
    },

    fit() {
        const viewport = this.$refs.viewport;
        const content = this.$refs.content;

        if (! viewport || ! content) {
            return;
        }

        const width = content.offsetWidth;
        const height = content.offsetHeight;

        if (width <= 0 || height <= 0) {
            return;
        }

        this.viewportWidth = viewport.clientWidth;
        this.viewportHeight = viewport.clientHeight;

        this.scale = Math.min(2.5, Math.max(0.2, Math.min(viewport.clientWidth / width, viewport.clientHeight / height, 1)));
        this.x = (viewport.clientWidth - width * this.scale) / 2;
        this.y = (viewport.clientHeight - height * this.scale) / 2;
    },

    zoomAt(event, factor) {
        const viewport = this.$refs.viewport;
        const rect = viewport.getBoundingClientRect();
        const next = Math.min(2.5, Math.max(0.2, this.scale * factor));
        const cursorX = event.clientX - rect.left;
        const cursorY = event.clientY - rect.top;

        this.x = cursorX - ((cursorX - this.x) / this.scale) * next;
        this.y = cursorY - ((cursorY - this.y) / this.scale) * next;
        this.scale = next;
    },

    zoomIn() {
        this.zoomAroundCenter(1.2);
    },

    zoomOut() {
        this.zoomAroundCenter(1 / 1.2);
    },

    zoomAroundCenter(factor) {
        const rect = this.$refs.viewport.getBoundingClientRect();

        this.zoomAt({ clientX: rect.left + rect.width / 2, clientY: rect.top + rect.height / 2 }, factor);
    },

    startPan(event) {
        if (event.button !== undefined && event.button !== 0) {
            return;
        }

        // Seret pada tautan/tombol tetap dianggap klik navigasi.
        if (event.target.closest('a, button')) {
            return;
        }

        this.dragging = true;
        this.dragStartX = event.clientX;
        this.dragStartY = event.clientY;
        this.dragBaseX = this.x;
        this.dragBaseY = this.y;
        event.currentTarget.setPointerCapture?.(event.pointerId);
    },

    movePan(event) {
        if (! this.dragging) {
            return;
        }

        this.x = this.dragBaseX + (event.clientX - this.dragStartX);
        this.y = this.dragBaseY + (event.clientY - this.dragStartY);
    },

    endPan() {
        this.dragging = false;
    },

    minimapPoint(event) {
        const minimap = this.$refs.minimap;
        const svg = minimap?.querySelector('svg');
        const content = this.$refs.content;

        if (! svg || ! content) {
            return null;
        }

        const rect = svg.getBoundingClientRect();
        const x = ((event.clientX - rect.left) / rect.width) * content.offsetWidth;
        const y = ((event.clientY - rect.top) / rect.height) * content.offsetHeight;

        return { x, y };
    },

    jumpMinimap(event) {
        const point = this.minimapPoint(event);
        const viewport = this.$refs.viewport;

        if (! point || ! viewport) {
            return;
        }

        this.x = viewport.clientWidth / 2 - point.x * this.scale;
        this.y = viewport.clientHeight / 2 - point.y * this.scale;
    },

    startMinimapJump(event) {
        if (event.button !== undefined && event.button !== 0) {
            return;
        }

        this.minimapJumping = true;
        this.jumpMinimap(event);
        event.currentTarget.setPointerCapture?.(event.pointerId);
    },

    moveMinimapJump(event) {
        if (this.minimapJumping) {
            this.jumpMinimap(event);
        }
    },

    endMinimapJump() {
        this.minimapJumping = false;
    },
});

function getStoredTheme() {
    const theme = localStorage.getItem('theme');

    return theme === 'dark' || theme === 'light' ? theme : null;
}

function applyTheme(theme) {
    document.documentElement.dataset.theme = theme;

    // Samakan cookie agar render server dan navigasi berikutnya memakai tema yang sama.
    document.cookie = 'theme=' + theme + '; path=/; max-age=31536000; samesite=lax';
}

document.addEventListener('livewire:navigating', (event) => {
    if (typeof event.detail?.onSwap !== 'function') {
        return;
    }

    event.detail.onSwap(() => {
        const theme = getStoredTheme();

        if (theme !== null) {
            applyTheme(theme);
        }
    });
});

document.addEventListener('livewire:navigated', () => {
    const theme = getStoredTheme();

    if (theme === null) {
        return;
    }

    applyTheme(theme);

    // Alpine sudah menginisialisasi halaman tujuan; sinkronkan state ikon toggle.
    window.dispatchEvent(new CustomEvent('theme-sync', {
        detail: { dark: theme === 'dark' },
    }));
});
