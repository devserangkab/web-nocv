<?php

use App\Actions\Synchronization\ApplySyncProposal;
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
     * Daftar tipe sinkronisasi yang tersedia (aaPanel website aktif,
     * sisanya kerangka untuk waktu yang akan datang).
     *
     * @return array<int, array<string, string>>
     */
    public function sources(): array
    {
        return [
            [
                'key' => SyncSource::AaPanelWebsite->value,
                'label' => 'Situs Web — aaPanel',
                'icon' => 'ti ti-world',
                'description' => 'Crawl daftar situs web dari tiap panel aaPanel yang punya API Key, bandingkan dengan data lokal.',
            ],
            [
                'key' => SyncSource::ProxmoxVms->value,
                'label' => 'Virtual Machine — Proxmox',
                'icon' => 'ti ti-stack-2',
                'description' => 'Sinkronisasi daftar VM yang berjalan pada node Proxmox.',
            ],
            [
                'key' => SyncSource::AaPanelDatabase->value,
                'label' => 'Database — aaPanel',
                'icon' => 'ti ti-database',
                'description' => 'Sinkronisasi database instance yang terdaftar di panel aaPanel.',
            ],
        ];
    }

    /**
     * Jalankan sinkronisasi satu sumber. aaPanel website aktif (crawl
     * setiap panel disertai API Key → proposal untuk review); sumber lain
     * masih placeholder.
     */
    public function requestSync(string $sourceKey): void
    {
        if ($sourceKey === SyncSource::AaPanelWebsite->value) {
            $this->crawlAaPanelWebsites();

            return;
        }

        $source = collect($this->sources())->firstWhere('key', $sourceKey);

        $this->notify('Sinkronisasi '.($source['label'] ?? $sourceKey).' belum tersedia.', 'error');
    }

    /**
     * Crawl semua panel aaPanel — satu toast per panel dicrawl/gagal
     * supaya jelas panel mana yang diproses dan hasilnya apa saja.
     * Panel tanpa API Key tidak dimunculkan (tidak penting).
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
     * Ringkasan batch sinkronisasi terakhir (semua panel, termasuk yang dilewati).
     */
    public function runs()
    {
        $group = SyncRun::query()->latest('id')->value('run_group');

        return SyncRun::query()
            ->with('panel:panel_id,name')
            ->where('run_group', $group)
            ->get();
    }

    /**
     * Terapkan satu proposal — perbarui application & relasi hosting.
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
        } catch (ValidationException $exception) {
            $this->notify((string) collect($exception->errors())->flatten()->first(), 'error');

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
        } catch (ValidationException $exception) {
            $this->notify((string) collect($exception->errors())->flatten()->first(), 'error');

            return;
        }

        $this->notify('Proposal '.$proposal->site_name.' ditolak.');
    }

    /**
     * Badging class utk status proposal/report terseleksi.
     */
    public function proposalActionBadgeClass(SyncProposalAction $action): string
    {
        return $action === SyncProposalAction::Create ? 'ina-badge--info' : 'ina-badge--warning';
    }

    public function proposalStatusBadgeClass(SyncProposalStatus $status): string
    {
        return match ($status) {
            SyncProposalStatus::Applied => 'ina-badge--success',
            SyncProposalStatus::Rejected => 'ina-badge--neutral',
            default => 'ina-badge--warning',
        };
    }

    /**
     * Kelas badge run: succeeded sukses, failed error, skipped netral.
     */
    public function runBadgeClass(SyncRun $run): string
    {
        return match ($run->status) {
            'succeeded' => 'ina-badge--success',
            'failed' => 'ina-badge--error',
            default => 'ina-badge--neutral',
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
     * Utama label tahap field per proposal diff baris.
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

    <div class="mt-4 flex flex-col gap-4">
        {{-- Card grid: sumber sinkronisasi --}}
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($this->sources() as $source)
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

                    <div class="mt-auto flex items-center justify-between gap-3 pt-5">
                        <span class="ina-badge ina-badge--soft {{ $source['key'] === App\Enums\SyncSource::AaPanelWebsite->value ? 'ina-badge--info' : 'ina-badge--neutral' }} ina-badge--sm">
                            {{ $source['key'] === App\Enums\SyncSource::AaPanelWebsite->value ? 'Aktif' : 'Segera' }}
                        </span>
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
            @endforeach
        </div>

        {{-- Card: run terakhir (transparansi panel & apa yang terjadi) --}}
        @if ($this->runs()->isNotEmpty())
            <div class="ina-card p-6">
                <div class="flex flex-col">
                    <h2 class="text-base font-semibold text-content-primary">Hasil sinkronisasi terakhir</h2>
                    <p class="mt-0.5 text-sm text-content-secondary">Satu baris per panel: mana yang diproses, mana yang dilewati.</p>
                </div>

                <div class="mt-4 flex flex-col divide-y divide-stroke-primary">
                    @foreach ($this->runs() as $run)
                        <div wire:key="sync-run-{{ $run->id }}" class="flex flex-wrap items-center gap-x-4 gap-y-1 py-2.5 first:pt-0">
                            <span class="ina-badge ina-badge--soft {{ $this->runBadgeClass($run) }} ina-badge--sm">
                                {{ $this->runStatusLabel($run) }}
                            </span>
                            <span class="min-w-40 text-sm font-medium text-content-primary" title="{{ $run->panel_id }}">
                                {{ $run->panel?->name ?? $run->panel_id ?? '-' }}
                            </span>
                            @if ($run->status === 'succeeded')
                                <span class="text-sm text-content-secondary">
                                    {{ $run->total_sites }} situs · {{ $run->total_new }} baru · {{ $run->total_changed }} berubah · {{ $run->total_unchanged }} sama
                                </span>
                            @elseif (! blank($run->message))
                                <span class="min-w-40 text-sm text-content-secondary" title="{{ $run->message }}">{{ $run->message }}</span>
                            @endif
                            <span class="ml-auto text-xs text-content-tertiary">{{ $this->runDuration($run) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Card: proposal untuk ditinjau --}}
        @if ($this->proposals()->isNotEmpty())
            <div class="ina-card p-6">
                <div class="flex flex-col">
                    <h2 class="text-base font-semibold text-content-primary">Proposal perubahan</h2>
                    <p class="mt-0.5 text-sm text-content-secondary">Perubahan dari panel harus disetujui satu per satu sebelum ditulis ke katalog.</p>
                </div>

                <div class="mt-4 flex flex-col divide-y divide-stroke-primary">
                    @foreach ($this->proposals() as $proposal)
                        <div wire:key="sync-proposal-{{ $proposal->id }}" class="py-4 first:pt-0">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                                <span class="ina-badge ina-badge--soft {{ $this->proposalActionBadgeClass($proposal->action) }} ina-badge--sm">
                                    {{ $proposal->action->label() }}
                                </span>
                                <span class="text-sm font-semibold text-content-primary">{{ $proposal->site_name }}</span>
                                <span class="text-xs text-content-tertiary">
                                    Panel {{ $proposal->panel?->name ?? $proposal->panel_id ?? '-' }}
                                    @if ($proposal->application_id !== null)
                                        · APP {{ $proposal->application?->name ?? $proposal->application_id }}
                                    @endif
                                </span>
                                <span class="ml-auto flex items-center gap-2">
                                    @if ($proposal->status === App\Enums\SyncProposalStatus::Pending)
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
                                    @else
                                        <span class="ina-badge ina-badge--soft {{ $this->proposalStatusBadgeClass($proposal->status) }} ina-badge--sm">
                                            {{ $proposal->status->label() }}
                                        </span>
                                    @endif
                                </span>
                            </div>

                            {{-- Diff per field: lama → baru --}}
                            <div class="mt-2 flex flex-col gap-1">
                                @foreach ($proposal->changes as $change)
                                    <div wire:key="sync-proposal-change-{{ $proposal->id }}-{{ $loop->index }}" class="flex items-center gap-2 text-sm text-content-secondary">
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
        @endif
    </div>
</div>
