<?php

use App\Actions\Reports\AddReportItem;
use App\Actions\Reports\MoveReportItem;
use App\Actions\Reports\RemoveReportItem;
use App\Actions\Reports\UpdateMonthlyReport;
use App\Models\Application;
use App\Models\Location;
use App\Models\MonthlyReport;
use App\Models\Panel;
use App\Models\Proxmox;
use App\Models\Rack;
use App\Models\Server;
use App\Models\Vms;
use App\Enums\ActivityType;
use App\Support\Authorize;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Layout('layouts::admin')]
    #[Title('Editor Laporan')]

    public MonthlyReport $report;

    /** PENDAHULUAN & KESIMPULAN — HTML tiptap, disinkronka via $wire.set() dari editor. */
    public ?string $introduction = null;

    public ?string $conclusion = null;

    /** Uraian tiap item hasil editor (keyed by item id — HTML tiptap). */
    public array $itemUraian = [];

    // Filter kandidat aktivitas
    public string $actSearch = '';

    public string $actTypeFilter = 'ALL';

    public string $actCategoryFilter = 'ALL';

    public string $actUserFilter = 'ALL';

    public string $actEntityFilter = 'ALL';

    public array $actSelected = [];

    public function mount(string $reportId): void
    {
        $this->report = MonthlyReport::query()
            ->with(['items', 'creator'])
            ->where('report_id', $reportId)
            ->firstOrFail();

        // Editor hanya untuk pembuat laporan (atau pemilik view-all).
        Authorize::monthlyReportOwn($this->report);

        $this->introduction = $this->report->introduction;
        $this->conclusion = $this->report->conclusion;

        foreach ($this->report->items as $item) {
            $this->itemUraian[$item->id] = $item->uraian ?? '';
        }
    }

    public function updatedActSearch(): void
    {
        $this->resetPage('candidates_page');
    }

    public function updatedActTypeFilter(): void
    {
        $this->resetPage('candidates_page');
    }

    public function updatedActCategoryFilter(): void
    {
        $this->resetPage('candidates_page');
    }

    public function updatedActUserFilter(): void
    {
        $this->resetPage('candidates_page');
    }

    public function updatedActEntityFilter(): void
    {
        $this->resetPage('candidates_page');
    }

    // ===== Kandidat aktivitas =====

    /**
     * Aktivitas yang belum menjadi item laporan — kandidat uraian kerja.
     */
    public function candidates(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $inReport = $this->report->items->pluck('activity_id')->filter()->all();

        $query = \App\Models\Activity::query()
            ->with(['user', 'subject'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($inReport !== []) {
            $query->whereNotIn('id', $inReport);
        }

        return $query
            ->when($this->actTypeFilter !== 'ALL', fn ($query) => $query->where('type', $this->actTypeFilter))
            ->when($this->actCategoryFilter !== 'ALL', fn ($query) => $query->where('category', $this->actCategoryFilter))
            ->when($this->actUserFilter !== 'ALL', fn ($query) => $query->where('user_id', $this->actUserFilter))
            ->when($this->actEntityFilter !== 'ALL', fn ($query) => $query->where('subject_type', $this->actEntityFilter))
            ->when($this->actSearch !== '', fn ($query) => $query->where(function ($query) {
                $query->where('activity', 'like', "%{$this->actSearch}%")
                    ->orWhere('description', 'like', "%{$this->actSearch}%");
            }))
            ->paginate(10, pageName: 'candidates_page');
    }

    /**
     * Opsi pelaksana yang pernah tercatat.
     */
    public function userFilterOptions(): array
    {
        $options = [['value' => 'ALL', 'label' => 'Semua Pelaksana']];

        \App\Models\User::query()->whereHas('activities')->orderBy('name')->get()
            ->each(fn (\App\Models\User $user) => $options[] = ['value' => (string) $user->id, 'label' => $user->name]);

        return $options;
    }

    public function entityFilterOptions(): array
    {
        return [
            ['value' => 'ALL', 'label' => 'Semua Entitas'],
            ['value' => Application::class, 'label' => 'Application'],
            ['value' => Location::class, 'label' => 'Server Room'],
            ['value' => Rack::class, 'label' => 'Rack'],
            ['value' => Server::class, 'label' => 'Server'],
            ['value' => Proxmox::class, 'label' => 'Proxmox'],
            ['value' => Vms::class, 'label' => 'Virtual Machine'],
            ['value' => Panel::class, 'label' => 'Panel'],
        ];
    }

    public function categoryFilterOptions(): array
    {
        return [
            ['value' => 'ALL', 'label' => 'Semua Kategori'],
            ...collect(\App\Enums\ActivityCategory::cases())
                ->map(fn (\App\Enums\ActivityCategory $category) => ['value' => $category->value, 'label' => $category->value])
                ->all(),
        ];
    }

    public function typeFilterOptions(): array
    {
        return [
            ['value' => 'ALL', 'label' => 'Semua Jenis'],
            ['value' => ActivityType::Audit->value, 'label' => ActivityType::Audit->label()],
            ['value' => ActivityType::Work->value, 'label' => ActivityType::Work->label()],
        ];
    }

    // ===== Struktur laporan: item uraian kerja =====

    public function addSelected(): void
    {
        foreach ($this->actSelected as $activityId) {
            $this->addItem((int) $activityId);
        }

        $this->actSelected = [];
        $this->resetPage('candidates_page');
    }

    public function addItem(int $activityId): void
    {
        $activity = \App\Models\Activity::with('subject')->find($activityId);

        $item = app(AddReportItem::class)($this->report, $activity);

        if ($item !== null) {
            $this->itemUraian[$item->id] = $item->uraian ?? '';
        }

        $this->report->unsetRelation('items');
    }

    public function removeItem(int $itemId): void
    {
        $item = $this->report->items()->findOrFail($itemId);

        app(RemoveReportItem::class)($item);

        unset($this->itemUraian[$itemId]);
        $this->report->unsetRelation('items');
    }

    public function moveItem(int $itemId, string $direction): void
    {
        $item = $this->report->items()->findOrFail($itemId);

        app(MoveReportItem::class)($item, $direction);
        $this->report->unsetRelation('items');
    }

    // ===== Simpan / Preview =====

    public bool $showPreview = false;

    public string $previewUrl = '';

    public ?string $savedAt = null;

    public function save(): void
    {
        $this->syncReport();

        $this->savedAt = now()->format('H:i:s');
        $this->dispatch('notify', message: 'Laporan tersimpan.');
    }

    /**
     * Auto-save lalu buka preview cetak dalam modal.
     */
    public function preview(): void
    {
        $this->syncReport();

        $this->savedAt = now()->format('H:i:s');
        $this->previewUrl = route('admin.reports.print', ['reportId' => $this->report->report_id]).'?t='.microtime(true);
        $this->showPreview = true;
    }

    private function syncReport(): void
    {
        app(UpdateMonthlyReport::class)($this->report, [
            'title' => (string) $this->report->title,
            'introduction' => $this->introduction,
            'conclusion' => $this->conclusion,
            'uraian' => $this->itemUraian,
            'newItems' => [],
        ]);

        $this->report->unsetRelation('items');
        $this->report->refresh();
    }

    /**
     * Label jenis untuk badge (Sistem/Manual).
     */
    public function typeLabel(?string $type): string
    {
        return match ($type) {
            ActivityType::Audit->value => ActivityType::Audit->label(),
            ActivityType::Work->value => ActivityType::Work->label(),
            default => 'Manual',
        };
    }
};
?>

<div>
    <div x-data class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Kolom utama: isi laporan --}}
        <div class="space-y-6 xl:col-span-2">
            {{-- Header laporan --}}
            <div class="ina-card ina-card--stack p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-medium uppercase tracking-wide text-content-tertiary">{{ $report->report_id }}</p>
                        <h1 class="mt-1 text-xl font-semibold text-content-primary">
                            {{ $report->title }}
                            <span class="text-base font-normal text-content-secondary">— {{ $report->periodeText() }}</span>
                        </h1>
                        <p class="mt-1 text-sm text-content-secondary">Penyusun: {{ $report->creator?->name ?? 'Admin' }} · editable kapan pun.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-content-tertiary" wire:loading remove>
                            <i class="ti ti-loader-2 animate-spin align-[-2px]"></i> Menyimpan...
                        </span>
                        <span class="text-xs text-guide-600" x-text="$wire.savedAt ? `Tersimpan ${$wire.savedAt}` : ''" x-cloak></span>
                        <button type="button" wire:click="save" class="ina-button ina-button--primary ina-button--md" wire:loading.attr="disabled">
                            <i class="ti ti-device-floppy text-lg"></i>
                            Simpan
                        </button>
                        <button type="button" wire:click="preview" class="ina-button ina-button--secondary ina-button--md" wire:loading.attr="disabled">
                            <i class="ti ti-printer text-lg"></i>
                            Preview Cetak
                        </button>
                        <a href="{{ route('admin.reports.index') }}" wire:navigate class="ina-button ina-button--secondary ina-button--md">
                            <i class="ti ti-arrow-left text-lg"></i>
                            Daftar
                        </a>
                    </div>
                </div>
            </div>

            {{-- KATA PENGANTAR --}}
            <section class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Kata Pengantar</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Memasukkan konteks laporan — dasar pelaksanaan dan lingkup kegiatan.</p>

                <div class="pt-4">
                    <x-richtext
                        wire:ignore
                        prop="introduction"
                        :content="$introduction"
                        placeholder="Tulis pendahuluan laporan..."
                    />
                </div>
            </section>

            {{-- URAIAN KERJA: daftar item --}}
            <section class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Uraian Kerja</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Item yang dipilih: kontain bebas jenis (Sistem/Manual). Sesuaikan uraian & urutan.</p>

                @php $items = $this->report->items()->orderBy('sequence')->get(); @endphp
                @if ($items->isEmpty())
                    <p class="py-8 text-center text-sm text-content-tertiary">Belum ada uraian kerja — pilih aktivitas di panel kanan.</p>
                @else
                    <div class="mt-4 space-y-3">
                        @foreach ($items as $item)
                            <div class="rounded-lg border border-stroke-primary bg-background-primary p-4" wire:key="item-{{ $item->id }}">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div class="flex min-w-0 items-center gap-2">
                                        <span class="ina-badge ina-badge--soft ina-badge--sm {{ $item->type_snapshot === \App\Enums\ActivityType::Audit->value ? 'ina-badge--info' : 'ina-badge--brand' }}">{{ $this->typeLabel($item->type_snapshot) }}</span>
                                        @if (filled($item->category_snapshot))
                                            <span class="ina-badge ina-badge--soft ina-badge--neutral ina-badge--sm">{{ $item->category_snapshot }}</span>
                                        @endif
                                        <p class="truncate text-sm font-semibold text-content-primary" title="{{ $item->title_snapshot }}">{{ $item->sequence }}. {{ $item->title_snapshot }}</p>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-1">
                                        <button type="button" wire:click="moveItem({{ $item->id }}, 'up')" wire:loading.attr="disabled"
                                            class="flex h-7 w-7 items-center justify-center rounded text-content-tertiary hover:bg-background-tertiary hover:text-content-primary" title="Naik">
                                            <i class="ti ti-arrow-up text-sm"></i>
                                        </button>
                                        <button type="button" wire:click="moveItem({{ $item->id }}, 'down')" wire:loading.attr="disabled"
                                            class="flex h-7 w-7 items-center justify-center rounded text-content-tertiary hover:bg-background-tertiary hover:text-content-primary" title="Turun">
                                            <i class="ti ti-arrow-down text-sm"></i>
                                        </button>
                                        <span class="mx-1 h-5 border-l border-stroke-primary"></span>
                                        <button type="button" wire:click="removeItem({{ $item->id }})" wire:loading.attr="disabled"
                                            class="flex h-7 w-7 items-center justify-center rounded text-content-tertiary hover:bg-negative-50 hover:text-negative-600" title="Hapus">
                                            <i class="ti ti-trash text-sm"></i>
                                        </button>
                                    </div>
                                </div>

                                {{-- snapshot kontekstual --}}
                                <p class="mt-1 text-[11px] text-content-tertiary">
                                    @if (filled($item->subject_name_snapshot))
                                        <i class="ti ti-link text-xs"></i> {{ $item->subject_name_snapshot }}
                                    @endif
                                    @if (filled($item->performer_name_snapshot))
                                        · Pelaksana: {{ $item->performer_name_snapshot }}
                                    @endif
                                </p>

                                {{-- Editor uraian per item --}}
                                <div class="pt-3" wire:key="item-editor-{{ $item->id }}">
                                    <x-richtext wire:ignore prop="itemUraian.{{ $item->id }}" :content="($itemUraian[$item->id] ?? ($item->uraian ?? ''))" placeholder="Tulis deskripsi uraian kerja yang final..." />
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- KESIMPULAN --}}
            <section class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Kesimpulan</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Ranging perencanaan akhir laporan.</p>

                <div class="pt-4">
                    <x-richtext
                        wire:ignore
                        prop="conclusion"
                        :content="$conclusion"
                        placeholder="Tulis kesimpulan..."
                    />
                </div>
            </section>
        </div>

        {{-- Panel kanan: kandidat aktivitas --}}
        <aside class="space-y-4">
            <div class="ina-card ina-card--stack p-6">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <h2 class="text-base font-semibold text-content-primary">Pilih Uraian Kerja</h2>
                        <p class="mt-0.5 text-sm text-content-secondary">Dari aktivitas Sistem/Manual yang tersimpan.</p>
                    </div>
                </div>

                <div class="pt-3">
                    <div class="ina-input-search ina-input-search--size-sm mb-2">
                        <div class="ina-input-search__wrapper">
                            <i class="ti ti-search ina-input-search__prefix-icon"></i>
                            <input type="search" class="ina-input-search__input" placeholder="Cari aktivitas..." wire:model.live.debounce.300ms="actSearch">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <x-idds.select wire-prop="actTypeFilter" :value="$actTypeFilter" :options="$this->typeFilterOptions()" placeholder="Semua Jenis" aria-label="Filter jenis" />
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <x-idds.select wire-prop="actCategoryFilter" :value="$actCategoryFilter" :options="$this->categoryFilterOptions()" placeholder="Kategori" aria-label="Filter kategori" />
                        <x-idds.select wire-prop="actUserFilter" :value="$actUserFilter" :options="$this->userFilterOptions()" placeholder="Pelaksana" aria-label="Filter pelaksana" />
                    </div>
                    <div class="mt-3 w-full">
                        <x-idds.select wire-prop="actEntityFilter" :value="$actEntityFilter" :options="$this->entityFilterOptions()" placeholder="Filter Entitas" aria-label="Filter entitas" />
                    </div>
                </div>

                {{-- Checkbox list kandidat --}}
                @php $candidates = $this->candidates(); @endphp
                <div class="max-h-72 overflow-y-auto my-4 divide-y divide-stroke-primary">
                    @if ($candidates->count() > 0)
                        @foreach ($candidates as $activity)
                            <label class="flex cursor-pointer items-start gap-3 py-2.5 hover:bg-neutral-50" wire:key="cand-{{ $activity->id }}">
                                <input
                                    type="checkbox"
                                    wire:model="actSelected"
                                    value="{{ $activity->id }}"
                                    class="mt-0.5 h-4 w-4 rounded border-stroke-primary text-guide-600 focus:ring-guide-500"
                                >
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-medium text-content-primary truncate" title="{{ $activity->activity }}">{{ $activity->activity }}</span>
                                    <span class="block text-[11px] text-content-tertiary">
                                        {{ $activity->created_at->format('d M') }} ·
                                        @if ($activity->subject !== null)
                                            {{ class_basename($activity->subject_type) }}:
                                            {{ $activity->subject->name ?? $activity->subject->server_name ?? '-' }} ·
                                        @endif
                                        {{ $activity->user?->name ?? 'Sistem' }}
                                    </span>
                                </span>
                                <span class="ina-badge ina-badge--soft ina-badge--sm {{ $activity->type === \App\Enums\ActivityType::Audit ? 'ina-badge--info' : 'ina-badge--brand' }}">{{ $activity->type->label() }}</span>
                            </label>
                        @endforeach
                    @else
                        <p class="py-6 text-center text-sm text-content-tertiary">Semua aktivitas telah terpakai di laporan ini atau tidak ada yang cocok filter.</p>
                    @endif
                </div>

                @if ($candidates->count() > 0)
                    <div class="mt-3 flex items-center justify-between gap-2">
                        <span class="text-xs text-content-tertiary" x-text="`Terpilih ${Array.isArray($wire.actSelected) ? $wire.actSelected.length : 0} aktivitas`"></span>
                        <button type="button" wire:click="addSelected" wire:loading.attr="disabled"
                            class="ina-button ina-button--primary ina-button--sm">
                            <i class="ti ti-plus text-base"></i>
                            Masukkan
                        </button>
                    </div>
                    <div class="mt-2">{{ $candidates->links('vendor.pagination.tailwind') }}</div>
                @endif
            </div>
        </aside>
    </div>

    {{-- Modal Preview Cetak (iframe versi cetak — styling terisolasi) --}}
    <div
        x-data="{ open: $wire.entangle('showPreview') }"
        x-on:keydown.escape.window="if (open) open = false"
        x-cloak
    >
        <div class="fixed inset-0 z-50 bg-black/60" x-show="open" x-transition.opacity @click="open = false" aria-hidden="true"></div>

        <div
            class="fixed left-1/2 top-1/2 z-50 w-full max-w-5xl h-[85vh] -translate-x-1/2 -translate-y-1/2 rounded-xl overflow-hidden border border-stroke-primary bg-background-secondary shadow-lg flex flex-col"
            x-show="open"
            x-transition.opacity.duration.150ms
            role="dialog"
            aria-modal="true"
            aria-label="Preview cetak laporan"
            @click.stop
        >
            {{-- Bar atas (tidak tercetak dari iframe) --}}
            <div class="flex items-center justify-between gap-3 border-b border-stroke-primary bg-background-primary px-4 py-2.5">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-content-primary" title="{{ $report->title }}">{{ $report->title }}</p>
                    <p class="truncate text-[11px] text-content-tertiary">{{ $report->report_id }} · Periode {{ $report->periodeText() }} · Kertas A4</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    @php
                        $downloadUrl = filled($previewUrl)
                            ? $previewUrl.'&download=1'
                            : route('admin.reports.print', $report->report_id).'?download=1';
                    @endphp
                    <a href="{{ $downloadUrl }}" download class="ina-button ina-button--primary ina-button--md">
                        <i class="ti ti-download text-lg"></i>
                        Unduh PDF
                    </a>
                    <button type="button" @click="open = false" class="ina-button ina-button--secondary ina-button--md">
                        <i class="ti ti-x text-lg"></i>
                        Tutup
                    </button>
                </div>
            </div>

            {{-- Isi preview --}}
            <iframe
                x-ref="preview"
                :src="$wire.previewUrl"
                wire:ignore
                class="flex-1 w-full bg-white"
                title="Preview cetak {{ $report->title }}"
                x-show="open && !!$wire.previewUrl"
            ></iframe>
        </div>
    </div>
</div>