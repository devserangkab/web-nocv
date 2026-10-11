import './bootstrap';

import '@tabler/icons-webfont/dist/tabler-icons.min.css';

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
