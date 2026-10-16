<?php

use App\Actions\Reports\CreateMonthlyReport;
use App\Actions\Reports\DeleteMonthlyReport;
use App\Models\MonthlyReport;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Layout('layouts::admin')]
    #[Title('Laporan Bulanan')]

    public string $search = '';

    public bool $showCreateModal = false;

    public bool $showDeleteModal = false;

    public ?int $deletingId = null;

    public string $report_month = '';

    public string $report_year = '';

    public string $title = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetValidation();
        $this->report_month = '';
        $this->report_year = '';
        $this->title = '';
        $this->showCreateModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'report_month' => ['required', 'integer', 'between:1,12'],
            'report_year' => ['required', 'integer', 'between:2020,2100'],
            'title' => ['required', 'string', 'max:255'],
        ], [
            'report_month.required' => 'Periode bulan wajib dipilih.',
            'report_year.required' => 'Periode tahun wajib diisi.',
            'title.required' => 'Judul laporan wajib diisi.',
        ]);

        try {
            $report = app(CreateMonthlyReport::class)([
                'report_month' => (int) $this->report_month,
                'report_year' => (int) $this->report_year,
                'title' => $this->title,
            ], auth()->id());
        } catch (ValidationException $e) {
            foreach ($e->errors() as $attribute => $errors) {
                foreach ($errors as $message) {
                    $this->addError('report_period', $message);
                }
            }

            return;
        }

        $this->showCreateModal = false;

        $this->redirectRoute('admin.reports.edit', $report->report_id, navigate: true);
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if ($this->deletingId !== null) {
            app(DeleteMonthlyReport::class)(MonthlyReport::findOrFail($this->deletingId));
        }

        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function reports(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return MonthlyReport::query()
            ->with(['creator', 'items'])
            ->withCount('items')
            // Laporan hanya milik pembuat — kecuali yang berhak melihat semua
            // (auth()->user() null-safe untuk pemanggilan di luar request HTTP).
            ->when(
                ! auth()->user()?->can('monthly-reports.view-all'),
                fn ($query) => $query->where('created_by', auth()->id())
            )
            ->when($this->search !== '', fn ($query) => $query->where(function ($query) {
                $query->where('title', 'like', "%{$this->search}%")
                    ->orWhere('report_id', 'like', "%{$this->search}%");
            }))
            ->orderByDesc('report_year')
            ->orderByDesc('report_month')
            ->paginate(10);
    }

    public function deletingReport(): ?MonthlyReport
    {
        return $this->deletingId !== null ? MonthlyReport::withCount('items')->find($this->deletingId) : null;
    }

    /**
     * Opsi 1..12 dengan label nama bulan Indonesia.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function monthOptions(): array
    {
        $names = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return collect(range(1, 12))
            ->map(fn (int $month) => ['value' => (string) $month, 'label' => $names[$month]])
            ->all();
    }
};
?>

<div>
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-content-primary">Laporan Bulanan</h1>
            <p class="mt-1 text-sm text-content-secondary">Kompilasi laporan aktivitas resmi: pilih uraian kerja dari audit & work log, tulis laporan lengkap, dan cetak.</p>
        </div>
        @can('monthly-reports.create')
        <button type="button" wire:click="openCreate" class="ina-button ina-button--primary ina-button--md">
            <i class="ti ti-file-plus text-lg" aria-hidden="true"></i>
            Buat Laporan
        </button>
        @endcan
    </div>

    {{-- Search --}}
    <div class="mt-6 max-w-sm">
        <div class="ina-input-search ina-input-search--size-md">
            <div class="ina-input-search__wrapper">
                <i class="ti ti-search ina-input-search__prefix-icon" aria-hidden="true"></i>
                <input
                    type="search"
                    class="ina-input-search__input"
                    placeholder="Cari judul atau kode laporan..."
                    wire:model.live.debounce.300ms="search"
                    aria-label="Cari laporan"
                >
            </div>
        </div>
    </div>

    {{-- Table --}}
    @php $reports = $this->reports(); @endphp
    @if ($reports->count() > 0)
        <div class="mt-4 overflow-hidden rounded-lg border border-stroke-primary bg-background-primary">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-stroke-primary bg-background-secondary text-left text-[12px] font-medium text-content-secondary">
                            <th scope="col" class="h-10 px-4 font-medium">Periode</th>
                            <th scope="col" class="h-10 px-4 font-medium">Judul</th>
                            <th scope="col" class="h-10 px-4 font-medium text-right">Uraian Kerja</th>
                            <th scope="col" class="h-10 px-4 font-medium">Penyusun</th>
                            <th scope="col" class="h-10 px-4 font-medium">Diperbarui</th>
                            <th scope="col" class="h-10 px-4 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reports as $report)
                            <tr class="border-b border-stroke-primary transition-colors duration-150 h-14 last:border-b-0 hover:bg-neutral-50" wire:key="rpt-{{ $report->id }}">
                                <td class="h-14 px-4 font-medium text-content-primary">{{ $report->periodeText() }}</td>
                                <td class="h-14 max-w-xs truncate px-4 text-content-secondary" title="{{ $report->title }}">{{ $report->title }}</td>
                                <td class="h-14 px-4 text-right text-content-primary">{{ $report->items_count }} item</td>
                                <td class="h-14 px-4 text-content-secondary">{{ $report->creator?->name ?? 'Admin' }}</td>
                                <td class="h-14 px-4 text-content-secondary">{{ $report->updated_at->format('d M Y') }}</td>
                                <td class="h-14 px-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.reports.edit', $report->report_id) }}" wire:navigate
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                            aria-label="Buka {{ $report->title }}" title="Buka">
                                            <i class="ti ti-pencil text-base"></i>
                                        </a>
                                        <a href="{{ route('admin.reports.print', $report->report_id) }}" target="_blank" rel="noopener noreferrer"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                            aria-label="Cetak {{ $report->title }}" title="Cetak">
                                            <i class="ti ti-printer text-base"></i>
                                        </a>
                                        <button type="button" wire:click="confirmDelete({{ $report->id }})"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-negative-50 hover:text-negative-600"
                                            aria-label="Hapus {{ $report->title }}" title="Hapus">
                                            <i class="ti ti-trash text-base"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $reports->links('vendor.pagination.tailwind') }}</div>
    @else
        {{-- Empty state (IDDS) --}}
        <div class="ina-card ina-card--stack mt-4 px-6 py-12 text-center">
            <x-asset.illustration name="{{ $search !== '' ? 'noresult' : 'nodata' }}" size="sm" />
            <h2 class="mt-4 text-base font-semibold text-content-primary">
                {{ $search !== '' ? 'Tidak ada laporan untuk "'.$search.'"' : 'Belum ada laporan bulanan' }}
            </h2>
            <p class="mt-2 mx-auto max-w-sm text-sm text-content-secondary">
                {{ $search !== '' ? 'Coba kata kunci lain.' : 'Buat laporan untuk suatu periode, pilih uraian kerja dari aktivitas, tulis pendahuluan & kesimpulan, lalu cetak.' }}
            </p>
        </div>
    @endif

    {{-- Modal Create (IDDS Task Modal) --}}
    <div
        x-data="{ open: $wire.entangle('showCreateModal') }"
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
            aria-labelledby="report-create-title"
            @click.stop
        >
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-content-primary" id="report-create-title">Buat Laporan Bulanan</h2>
                    <p class="mt-1 text-sm text-content-secondary">Satu laporan per periode bulan-tahun.</p>
                </div>
                <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="$set('showCreateModal', false)" aria-label="Tutup">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <form class="mt-6 space-y-5" wire:submit="save">
                <div class="grid grid-cols-2 gap-4">
                    <div class="ina-text-field">
                        <label for="report-month" class="ina-text-field__label">
                            Bulan
                            <span class="ina-text-field__required">*</span>
                        </label>
                        <x-idds.select
                            wire-prop="report_month"
                            :value="$report_month"
                            :options="$this->monthOptions()"
                            placeholder="Pilih bulan..."
                            aria-label="Pilih bulan"
                            :error="$errors->has('report_month')"
                        />
                        @error('report_month')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="report-year" class="ina-text-field__label">
                            Tahun
                            <span class="ina-text-field__required">*</span>
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('report_year') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="number"
                                min="2020"
                                max="2100"
                                id="report-year"
                                wire:model="report_year"
                                class="ina-text-field__input"
                                placeholder="{{ date('Y') }}"
                                required
                            >
                        </div>
                        @error('report_year')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="ina-text-field">
                    <label for="report-title" class="ina-text-field__label">
                        Judul
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('title') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="report-title"
                            wire:model="title"
                            class="ina-text-field__input"
                            placeholder="Laporan Aktivitas Oktober {{ date('Y') }}"
                            required
                            autofocus
                        >
                    </div>
                    @error('title')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                @error('report_period')
                    <p class="text-sm text-negative-500">{{ $message }}</p>
                @enderror

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="ina-button ina-button--secondary ina-button--md" wire:click="$set('showCreateModal', false)">
                        Batal
                    </button>
                    <button type="submit" class="ina-button ina-button--primary ina-button--md" wire:loading.attr="disabled">
                        Buat & Buka Editor
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Konfirmasi Hapus --}}
    <div
        x-data="{ open: $wire.entangle('showDeleteModal') }"
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
            aria-labelledby="report-delete-title"
            @click.stop
        >
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-negative-50 text-negative-600">
                        <i class="ti ti-alert-triangle text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-content-primary" id="report-delete-title">Hapus Laporan</h2>
                        <p class="mt-1 text-sm text-content-secondary">
                            Laporan <span class="font-medium text-content-primary">{{ $this->deletingReport()?->title ?? '-' }}</span> beserta uraian kerjanya akan dihapus. Tindakan ini tidak bisa dibatalkan.
                        </p>
                    </div>
                </div>
                <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="$set('showDeleteModal', false)" aria-label="Tutup">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="ina-button ina-button--secondary ina-button--md" wire:click="$set('showDeleteModal', false)">
                    Batal
                </button>
                <button
                    type="button"
                    class="ina-button ina-button--secondary ina-button--md bg-negative-600! text-white! hover:bg-negative-700!"
                    wire:loading.attr="disabled"
                    wire:click="delete"
                >
                    Hapus
                </button>
            </div>
        </div>
    </div>
</div>
