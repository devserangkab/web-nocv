<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new class extends Component
{
    #[Layout('layouts::admin')]
    #[Title('Dashboard')]
    //
};
?>

<div>
    {{-- Judul halaman --}}
    <h1 class="text-xl font-semibold text-content-primary">Dashboard</h1>
    <p class="mt-1 text-sm text-content-secondary">Ringkasan singkat data dan aktivitas aplikasi.</p>

    {{-- Card Data — placeholder, struktur sesuai pola Dashboard IDDS --}}
    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ([
            ['label' => 'Total Data A', 'desc' => 'Seluruh entri terdaftar'],
            ['label' => 'Total Data B', 'desc' => 'Entri pada rentang waktu'],
            ['label' => 'Aktivitas Hari Ini', 'desc' => 'Aksi pengguna terbaru'],
        ] as $card)
            <div class="ina-card ina-card--stack p-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-guide-50 text-guide-600">
                        <i class="ti ti-book text-lg"></i>
                    </div>
                    <p class="text-sm font-medium text-content-secondary">{{ $card['label'] }}</p>
                </div>
                <p class="mt-4 text-2xl font-semibold text-content-primary">—</p>
                <p class="mt-1 text-xs text-content-tertiary">{{ $card['desc'] }}</p>
            </div>
        @endforeach
    </div>
</div>
