<?php

use App\Actions\Synchronization\ApplySyncProposal;
use App\Actions\Synchronization\CrawlAaPanelDatabases;
use App\Actions\Synchronization\CrawlAaPanelSites;
use App\Actions\Synchronization\RejectSyncProposal;
use App\Enums\SyncProposalAction;
use App\Enums\SyncProposalStatus;
use App\Enums\SyncSource;
use App\Livewire\Concerns\Notifies;
use App\Models\SyncProposal;
use App\Models\SyncRun;
use App\Support\AaPanelSiteMapper;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new class extends Component
{
    use Notifies;

    #[Layout('layouts::admin')]
    #[Title('Sinkronisasi')]

    /**
     * Tab aktif: 'aapanel' (situs & database) atau 'proxmox' (placeholder).
     */
    public string $tab = 'aapanel';

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['aapanel', 'proxmox']) ? $tab : 'aapanel';
    }

    /**
     * Chip panel terpilih per sumber: [sourceKey => panel_id].
     */
    public array $panelSelection = [];

    public function selectPanel(string $sourceKey, string $panelId): void
    {
        $this->panelSelection[$sourceKey] = $panelId;
    }

    /**
     * Panel terpilih untuk satu sumber; fallback ke chip pertama bila
     * pilihan tersimpan sudah tidak memiliki pending proposal.
     */
    public function selectedPanelFor(string $sourceValue): ?string
    {
        $chips = $this->panelChipsFor($sourceValue);

        if ($chips === []) {
            return null;
        }

        $stored = $this->panelSelection[$sourceValue] ?? null;

        return collect($chips)->first(fn (array $chip) => $chip['panelId'] === $stored)['panelId']
            ?? $chips[0]['panelId'];
    }

    /**
     * Daftar tipe sinkronisasi yang tersedia (untuk fallback label).
     *
     * @return array<int, array<string, string>>
     */
    public function sources(): array
    {
        return [
            [
                'key' => SyncSource::AaPanelWebsite->value,
                'label' => 'Situs Web — aaPanel',
                'short' => 'Situs Web',
                'icon' => 'ti ti-world',
                'description' => 'Crawl daftar situs web dari tiap panel aaPanel yang punya API Key, bandingkan dengan data lokal.',
            ],
            [
                'key' => SyncSource::AaPanelDatabase->value,
                'label' => 'Database — aaPanel',
                'short' => 'Database',
                'icon' => 'ti ti-database',
                'description' => 'Crawl daftar database MySQL dari tiap panel aaPanel yang punya API Key, cocokkan ke aplikasi via heuristik domain.',
            ],
            [
                'key' => SyncSource::ProxmoxVms->value,
                'label' => 'Virtual Machine — Proxmox',
                'icon' => 'ti ti-stack-2',
                'description' => 'Sinkronisasi daftar VM yang berjalan pada node Proxmox.',
            ],
        ];
    }

    /**
     * Kartu yang tampil pada tab aaPanel (Proxmox = placeholder di tab-nya).
     *
     * @return array<int, array<string, string>>
     */
    public function activeSources(): array
    {
        $active = [SyncSource::AaPanelWebsite->value, SyncSource::AaPanelDatabase->value];

        return collect($this->sources())
            ->filter(fn (array $source) => in_array($source['key'], $active))
            ->values()
            ->all();
    }

    /**
     * Jalankan sinkronisasi satu sumber.
     */
    public function requestSync(string $sourceKey): void
    {
        if ($sourceKey === SyncSource::AaPanelWebsite->value) {
            $this->crawlAaPanelWebsites();

            return;
        }

        if ($sourceKey === SyncSource::AaPanelDatabase->value) {
            $this->crawlAaPanelDatabases();

            return;
        }

        $source = collect($this->sources())->firstWhere('key', $sourceKey);

        $this->notify('Sinkronisasi '.($source['label'] ?? $sourceKey).' belum tersedia.', 'error');
    }

    /**
     * Crawl sumber situs web — satu toast per panel dicrawl/gagal.
     */
    private function crawlAaPanelWebsites(): void
    {
        $result = app(CrawlAaPanelSites::class)();

        foreach ($result['runs'] as $run) {
            $panelName = $run->panel?->name ?? $run->panel_id ?? '-';

            $toast = match ($run->status) {
                'succeeded' => 'Panel '.$panelName.': '.$run->total_sites.' situs dibaca — '.$run->total_new.' baru, '.$run->total_changed.' berubah, '.$run->total_unchanged.' sama.',
                'failed' => 'Panel '.$panelName.' gagal: '.Str::limit((string) $run->message, 140),
                default => null,
            };

            if ($toast !== null) {
                $this->notify($toast, $run->status === 'succeeded' ? 'success' : 'error');
            }
        }

        if ($result['crawled'] + $result['failed'] === 0 && $result['skipped'] > 0) {
            $this->notify('Tidak ada panel aaPanel dengan API Key — isi API Key di detail panel.', 'error');
        }
    }

    /**
     * Crawl sumber database — instance MySQL dibuat otomatis bila perlu.
     */
    private function crawlAaPanelDatabases(): void
    {
        $result = app(CrawlAaPanelDatabases::class)();

        foreach ($result['runs'] as $run) {
            $panelName = $run->panel?->name ?? $run->panel_id ?? '-';

            $toast = match ($run->status) {
                'succeeded' => 'Panel '.$panelName.': '.$run->total_sites.' database — '.$run->total_changed.' tautan/berubah, '.$run->total_new.' katalog baru, '.$run->total_unchanged.' sama.'
                    .(filled($run->message) ? ' ('.$run->message.')' : ''),
                'failed' => 'Panel '.$panelName.' gagal: '.Str::limit((string) $run->message, 140),
                default => null,
            };

            if ($toast !== null) {
                $this->notify($toast, $run->status === 'succeeded' ? 'success' : 'error');
            }
        }
    }

    /**
     * 10 run terkini suatu sumber (lintas batch urut waktu) —
     * terbaru di belakang supaya urutan tampilan lama → baru.
     */
    public function runsBySource(string $sourceValue)
    {
        return SyncRun::query()
            ->with('panel:panel_id,name')
            ->where('source', $sourceValue)
            ->latest('id')
            ->limit(10)
            ->get()
            ->reverse();
    }

    /**
     * Proposal yang masih menunggu persetujuan superadmin.
     */
    public function proposals()
    {
        return SyncProposal::query()
            ->where('status', SyncProposalStatus::Pending->value)
            ->with(['application:application_id,name', 'panel:panel_id,name'])
            ->latest('id')
            ->limit(200)
            ->get();
    }

    /**
     * Chip panel yang punya proposal pending pada satu sumber.
     *
     * @return array<int, array{panelId: string, panelName: string, count: int}>
     */
    public function panelChipsFor(string $sourceValue): array
    {
        return $this->proposals()
            ->filter(fn (SyncProposal $proposal) => $proposal->source->value === $sourceValue)
            ->groupBy(fn (SyncProposal $proposal) => $proposal->panel_id ?? '-')
            ->map(fn ($items, $panelId): array => [
                'panelId' => $panelId,
                'panelName' => $items->first()->panel?->name ?? (filled($panelId) && $panelId !== '-' ? $panelId : '-'),
                'count' => $items->count(),
            ])
            ->sortBy('panelName')
            ->values()
            ->all();
    }

    /**
     * Jumlah pending proposal untuk panel terpilih pada satu sumber.
     */
    public function selectedPendingCount(string $sourceValue): int
    {
        $selected = $this->selectedPanelFor($sourceValue);

        if ($selected === null) {
            return 0;
        }

        return $this->proposals()
            ->filter(fn (SyncProposal $proposal) => $proposal->source->value === $sourceValue
                && ($proposal->panel_id ?? '-') === $selected)
            ->count();
    }

    /**
     * Proposal satu sumber, dikelompokkan per panel terpilih.
     *
     * @return array<int, array{panelName: string, proposals: \Illuminate\Support\Collection}>
     */
    public function proposalGroupsBySource(string $sourceValue): array
    {
        $selected = $this->selectedPanelFor($sourceValue);

        if ($selected === null) {
            return [];
        }

        return $this->proposals()
            ->filter(fn (SyncProposal $proposal) => $proposal->source->value === $sourceValue
                && ($proposal->panel_id ?? '-') === $selected)
            ->groupBy(fn (SyncProposal $proposal) => $proposal->panel_id ?? '-')
            ->map(function ($items, $panelId): array {
                return [
                    'panelName' => $items->first()->panel?->name ?? (filled($panelId) && $panelId !== '-' ? $panelId : '-'),
                    'proposals' => $items,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Terapkan satu proposal sesuai sumbernya.
     */
    public function approveProposal(int $proposalId): void
    {
        $proposal = SyncProposal::find($proposalId);

        if ($proposal === null) {
            $this->notify('Proposal tidak ditemukan.', 'error');

            return;
        }

        try {
            app(ApplySyncProposal::class)($proposal);
        } catch (Throwable $exception) {
            $this->notify($this->errorMessage($exception), 'error');

            return;
        }

        $this->notify('Proposal '.$proposal->site_name.' diterapkan.');
    }

    /**
     * Tolak satu proposal tanpa mengubah data katalog.
     */
    public function rejectProposal(int $proposalId): void
    {
        $proposal = SyncProposal::find($proposalId);

        if ($proposal === null) {
            $this->notify('Proposal tidak ditemukan.', 'error');

            return;
        }

        try {
            app(RejectSyncProposal::class)($proposal);
        } catch (Throwable $exception) {
            $this->notify($this->errorMessage($exception), 'error');

            return;
        }

        $this->notify('Proposal '.$proposal->site_name.' ditolak.');
    }

    /**
     * Terapkan seluruh proposal pending di satu panel + sumber (per item).
     */
    public function approveAllForSourcePanel(string $sourceKey, string $panelId): void
    {
        $this->bulkProcess($sourceKey, $panelId, true);
    }

    /**
     * Tolak seluruh proposal pending di satu panel + sumber (per item).
     */
    public function rejectAllForSourcePanel(string $sourceKey, string $panelId): void
    {
        $this->bulkProcess($sourceKey, $panelId, false);
    }

    /**
     * Proses massal per item — satu error tidak menggagalkan sisanya.
     */
    private function bulkProcess(string $sourceKey, string $panelId, bool $apply): void
    {
        $proposals = SyncProposal::query()
            ->where('status', SyncProposalStatus::Pending->value)
            ->where('source', $sourceKey)
            ->where(fn ($query) => $panelId === '-'
                ? $query->whereNull('panel_id')
                : $query->where('panel_id', $panelId))
            ->get();

        $applied = 0;
        $failures = [];

        foreach ($proposals as $proposal) {
            try {
                if ($apply) {
                    app(ApplySyncProposal::class)($proposal);
                } else {
                    app(RejectSyncProposal::class)($proposal);
                }

                $applied++;
            } catch (Throwable $exception) {
                $failures[] = $proposal->site_name.': '.Str::limit($this->errorMessage($exception), 60);
            }
        }

        $label = ($apply ? 'Setujui semua' : 'Tolak semua').' — '.$applied.' diterapkan'
            .($failures !== [] ? ', '.count($failures).' gagal' : '');

        if ($failures !== []) {
            $hidden = count($failures) - 2;
            $label .= ': '.implode('; ', array_slice($failures, 0, 2)).($hidden > 0 ? '; …'.$hidden.' lagi' : '');
        }

        $this->notify(Str::limit($label, 180), $failures === [] ? 'success' : 'error');
    }

    /**
     * Pesan error ringkas untuk toast (ValidationException → baris pertama).
     */
    private function errorMessage(Throwable $exception): string
    {
        if ($exception instanceof ValidationException) {
            return (string) collect($exception->errors())->flatten()->first();
        }

        return Str::limit($exception->getMessage(), 140);
    }

    /**
     * Kelas badge jenis proposal: beda warna per aksi.
     */
    public function proposalActionBadgeClass(SyncProposalAction $action): string
    {
        return match ($action) {
            SyncProposalAction::Create => 'ina-badge--info',
            SyncProposalAction::Link => 'ina-badge--brand',
            default => 'ina-badge--warning',
        };
    }

    /**
     * Label tampil status run.
     */
    public function runStatusLabel(SyncRun $run): string
    {
        return match ($run->status) {
            'succeeded' => 'Berhasil',
            'failed' => 'Gagal',
            default => 'Dilewati',
        };
    }

    /**
     * Ringkasan angka per baris run — label mengikuti sumbernya.
     */
    public function runSummaryText(SyncRun $run): string
    {
        if ($run->source === SyncSource::AaPanelDatabase) {
            return $run->total_sites.' database · '.$run->total_changed.' tautan/berubah · '.$run->total_new.' katalog baru · '.$run->total_unchanged.' sama';
        }

        return $run->total_sites.' situs · '.$run->total_new.' baru · '.$run->total_changed.' berubah · '.$run->total_unchanged.' sama';
    }

    /**
     * Label field untuk satu baris diff.
     */
    public function fieldLabel(array $change): string
    {
        return AaPanelSiteMapper::FIELD_LABELS[$change['field']] ?? $change['field'];
    }

    /**
     * Durasi run dalam format ringkas.
     */
    public function runDuration(SyncRun $run): string
    {
        if ($run->started_at === null || $run->finished_at === null) {
            return '-';
        }

        $seconds = $run->started_at->diffInSeconds($run->finished_at);

        return rtrim(rtrim(sprintf('%.2f', $seconds), '0'), '.').' dtk';
    }
};
?>

<div>
    {{-- Header --}}
    <div>
        <h1 class="text-xl font-semibold text-content-primary">Sinkronisasi</h1>
        <p class="mt-1 text-sm text-content-secondary">Halaman khusus superadmin — cocokkan data dari sumber eksternal ke katalog aplikasi melalui proposal yang disetujui satu per satu.</p>
    </div>

    {{-- Tab provider --}}
    <div class="mt-4 flex flex-wrap gap-1 rounded-lg border border-stroke-primary bg-background-primary p-1">
        <button
            type="button"
            wire:click="setTab('aapanel')"
            @class([
                'flex h-9 items-center gap-2 rounded-md px-4 text-[13px] font-medium transition-colors duration-150',
                'bg-guide-50 text-guide-600' => $tab === 'aapanel',
                'text-content-secondary hover:bg-neutral-100 hover:text-content-primary' => $tab !== 'aapanel',
            ])
            @if ($tab === 'aapanel') aria-current="page" @endif
        >
            <i class="ti ti-app-window text-base"></i>
            aaPanel
        </button>
        <button
            type="button"
            wire:click="setTab('proxmox')"
            @class([
                'flex h-9 items-center gap-2 rounded-md px-4 text-[13px] font-medium transition-colors duration-150',
                'bg-guide-50 text-guide-600' => $tab === 'proxmox',
                'text-content-secondary hover:bg-neutral-100 hover:text-content-primary' => $tab !== 'proxmox',
            ])
            @if ($tab === 'proxmox') aria-current="page" @endif
        >
            <i class="ti ti-stack-2 text-base"></i>
            Proxmox
            <span class="rounded-full px-1.5 text-[11px] font-semibold bg-background-tertiary text-content-tertiary">Segera</span>
        </button>
    </div>

    @if ($tab === 'aapanel')
        {{-- Dua kolom: kiri Situs Web, kanan Database --}}
        <div class="mt-4 grid items-start gap-4 md:grid-cols-2">
            @foreach ($this->activeSources() as $source)
                @php
                    $sourceRuns = $this->runsBySource($source['key']);
                    $chips = $this->panelChipsFor($source['key']);
                    $selectedPanelId = $this->selectedPanelFor($source['key']);
                    $pendingCount = $this->selectedPendingCount($source['key']);
                    $proposalGroups = $this->proposalGroupsBySource($source['key']);
                @endphp

                <div wire:key="sync-column-{{ $source['key'] }}" class="flex flex-col gap-4">
                    {{-- Kartu sumber + tombol crawl --}}
                    <div class="ina-card ina-card--stack flex flex-col p-6">
                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-guide-50 text-guide-600">
                                <i class="{{ $source['icon'] }} text-lg"></i>
                            </span>
                            <div class="min-w-0">
                                <h3 class="text-sm font-semibold text-content-primary">{{ $source['label'] }}</h3>
                                <p class="mt-1 text-sm text-content-secondary">{{ $source['description'] }}</p>
                            </div>
                        </div>

                        <div class="mt-auto flex items-center justify-start gap-3 pt-5">
                            <span class="ina-badge ina-badge--soft ina-badge--info ina-badge--sm">Aktif</span>
                            <button
                                type="button"
                                wire:click="requestSync('{{ $source['key'] }}')"
                                wire:loading.attr="disabled"
                                wire:target="requestSync('{{ $source['key'] }}')"
                                class="ina-button ina-button--secondary ina-button--md"
                            >
                                <i class="ti ti-refresh text-base" wire:loading.remove wire:target="requestSync('{{ $source['key'] }}')"></i>
                                <i class="ti ti-loader animate-spin text-base" wire:loading wire:target="requestSync('{{ $source['key'] }}')"></i>
                                Sinkronkan
                            </button>
                        </div>
                    </div>

                    {{-- Riwayat run: sumber ini saja --}}
                    @if ($sourceRuns->isNotEmpty())
                        <div class="ina-card ina-card--stack p-6">
                            <h2 class="text-sm font-semibold text-content-primary">Hasil sinkronisasi terakhir</h2>

                            <div class="mt-3 flex flex-col divide-y divide-stroke-primary">
                                @foreach ($sourceRuns as $run)
                                    <div wire:key="sync-run-{{ $source['key'] }}-{{ $run->id }}" class="flex flex-wrap items-center gap-x-3 gap-y-1 py-2.5 first:pt-0">
                                        <span class="ina-badge ina-badge--soft {{ $run->status === 'succeeded' ? 'ina-badge--success' : ($run->status === 'failed' ? 'ina-badge--error' : 'ina-badge--neutral') }} ina-badge--sm">
                                            {{ $this->runStatusLabel($run) }}
                                        </span>
                                        <span class="min-w-32 text-sm font-medium text-content-primary" title="{{ $run->panel_id }}">
                                            {{ $run->panel?->name ?? $run->panel_id ?? '-' }}
                                        </span>
                                        <span class="text-xs text-content-tertiary">{{ $this->runDuration($run) }}</span>
                                        <span class="flex w-full text-xs text-content-secondary">
                                            @if ($run->status === 'succeeded')
                                                {{ $this->runSummaryText($run) }}
                                                @if (filled($run->message))
                                                    · {{ $run->message }}
                                                @endif
                                            @elseif (! blank($run->message))
                                                {{ Str::limit((string) $run->message, 140) }}
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Proposal sumber ini, dikelompokkan per panel terpilih --}}
                    @if ($proposalGroups !== [])
                        <div class="ina-card ina-card--stack p-6">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="flex flex-col">
                                    <h2 class="text-sm font-semibold text-content-primary">Proposal perubahan</h2>
                                    <p class="mt-0.5 text-sm text-content-secondary">Setujui satu per satu sebelum ditulis ke katalog.</p>
                                </div>
                                <span class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        wire:click="approveAllForSourcePanel('{{ $source['key'] }}', '{{ $selectedPanelId }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="approveAllForSourcePanel('{{ $source['key'] }}', '{{ $selectedPanelId }}')"
                                        wire:confirm="Terapkan {{ $pendingCount }} proposal pada panel ini?"
                                        class="ina-button ina-button--primary ina-button--sm"
                                    >
                                        Setujui semua ({{ $pendingCount }})
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="rejectAllForSourcePanel('{{ $source['key'] }}', '{{ $selectedPanelId }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="rejectAllForSourcePanel('{{ $source['key'] }}', '{{ $selectedPanelId }}')"
                                        wire:confirm="Tolak {{ $pendingCount }} proposal pada panel ini?"
                                        class="ina-button ina-button--secondary ina-button--sm"
                                    >
                                        Tolak semua ({{ $pendingCount }})
                                    </button>
                                </span>
                            </div>

                            {{-- Chip pilih panel --}}
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                @foreach ($chips as $chip)
                                    <button
                                        type="button"
                                        wire:click="selectPanel('{{ $source['key'] }}', '{{ $chip['panelId'] }}')"
                                        wire:key="sync-chip-{{ $source['key'] }}-{{ $chip['panelId'] }}"
                                        @class([
                                            'flex h-8 items-center gap-1.5 rounded-md px-2.5 text-[13px] font-medium transition-colors duration-150',
                                            'bg-guide-50 text-guide-600' => $chip['panelId'] === $selectedPanelId,
                                            'text-content-secondary hover:bg-neutral-100 hover:text-content-primary' => $chip['panelId'] !== $selectedPanelId,
                                        ])
                                        @if ($chip['panelId'] === $selectedPanelId) aria-current="true" @endif
                                    >
                                        {{ $chip['panelName'] }}
                                        <span class="rounded-full px-1.5 text-[11px] font-semibold {{ $chip['panelId'] === $selectedPanelId ? 'bg-guide-100 text-guide-700' : 'bg-background-tertiary text-content-tertiary' }}">{{ $chip['count'] }}</span>
                                    </button>
                                @endforeach
                            </div>

                            {{-- Daftar proposal panel terpilih --}}
                            <div class="mt-4 flex flex-col gap-5">
                                @foreach ($proposalGroups as $group)
                                    <div wire:key="sync-group-{{ $source['key'] }}-{{ $group['panelName'] }}" class="flex flex-col">
                                        {{-- Header grup per panel --}}
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 border-b border-stroke-primary pb-2">
                                            <i class="ti ti-building text-sm text-content-tertiary" aria-hidden="true"></i>
                                            <span class="text-sm font-semibold text-content-primary">Panel {{ $group['panelName'] }}</span>
                                            <span class="ina-badge ina-badge--soft ina-badge--neutral ina-badge--sm">{{ $group['proposals']->count() }}</span>
                                        </div>

                                        <div class="flex flex-col divide-y divide-stroke-primary py-2">
                                            @foreach ($group['proposals'] as $proposal)
                                                <div wire:key="sync-proposal-{{ $proposal->id }}" class="py-4 first:pt-0 last:pb-0">
                                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                                                        <span class="ina-badge ina-badge--soft {{ $this->proposalActionBadgeClass($proposal->action) }} ina-badge--sm">
                                                            {{ $proposal->action->label() }}
                                                        </span>
                                                        <span class="text-sm font-semibold text-content-primary">{{ $proposal->site_name }}</span>
                                                        @if ($proposal->application_id !== null)
                                                            <span class="text-xs text-content-tertiary">
                                                                · APP {{ $proposal->application?->name ?? $proposal->application_id }}
                                                            </span>
                                                        @endif
                                                        <span class="ml-auto flex items-center gap-2">
                                                            <button
                                                                type="button"
                                                                wire:click="approveProposal({{ $proposal->id }})"
                                                                wire:loading.attr="disabled"
                                                                wire:target="approveProposal({{ $proposal->id }})"
                                                                class="ina-button ina-button--primary ina-button--sm"
                                                            >
                                                                <i class="ti ti-circle-check text-base" wire:loading.remove wire:target="approveProposal({{ $proposal->id }})"></i>
                                                                Setujui
                                                            </button>
                                                            <button
                                                                type="button"
                                                                wire:click="rejectProposal({{ $proposal->id }})"
                                                                wire:loading.attr="disabled"
                                                                wire:target="rejectProposal({{ $proposal->id }})"
                                                                class="ina-button ina-button--secondary ina-button--sm"
                                                            >
                                                                Tolak
                                                            </button>
                                                        </span>
                                                    </div>

                                                    {{-- Diff per field: lama → baru --}}
                                                    <div class="mt-2 flex flex-col gap-1">
                                                        @foreach ($proposal->changes as $change)
                                                            <div wire:key="sync-change-{{ $proposal->id }}-{{ $loop->index }}" class="flex items-center gap-2 text-sm text-content-secondary">
                                                                <i class="ti ti-chevron-right text-xs text-content-tertiary" aria-hidden="true"></i>
                                                                <span class="min-w-24 font-medium text-content-primary">{{ $this->fieldLabel($change) }}</span>
                                                                <span>
                                                                    @if (isset($change['old']) && filled($change['old']))
                                                                        <span class="text-content-tertiary line-through">{{ $change['old'] }}</span>
                                                                    @else
                                                                        <span class="text-content-tertiary">—</span>
                                                                    @endif
                                                                    <i class="ti ti-chevron-right text-xs text-content-tertiary" aria-hidden="true"></i>
                                                                    <span class="font-medium text-content-primary">{{ $change['new'] }}</span>
                                                                </span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @elseif ($sourceRuns->isEmpty())
                        {{-- Kolom kosong total: sumber belum pernah dijalankan --}}
                        <div class="ina-card ina-card--stack p-6">
                            <p class="text-sm text-content-secondary">
                                <strong>{{ $source['short'] }}</strong> belum pernah disinkronisasi — jalankan <strong>Sinkronkan</strong> di atas untuk mengisi riwayat & proposal.
                            </p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        {{-- Tab Proxmox: placeholder --}}
        <div class="ina-card ina-card--stack flex flex-col items-start p-6">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-guide-50 text-guide-600">
                    <i class="ti ti-stack-2 text-lg"></i>
                </span>
                <div>
                    <h2 class="text-sm font-semibold text-content-primary">Virtual Machine — Proxmox</h2>
                    <p class="mt-1 text-sm text-content-secondary">Sinkronisasi daftar VM yang berjalan pada node Proxmox — mengikuti pola aaPanel, via proposal yang disetujui. Diterapkan menyusul.</p>
                </div>
            </div>
            <span class="ina-badge ina-badge--soft ina-badge--neutral ina-badge--sm">Segera</span>
        </div>
    @endif
</div>
