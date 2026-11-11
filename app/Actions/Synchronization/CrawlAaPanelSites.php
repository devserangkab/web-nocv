<?php

namespace App\Actions\Synchronization;

use App\Enums\PanelType;
use App\Enums\SyncProposalAction;
use App\Enums\SyncProposalStatus;
use App\Enums\SyncSource;
use App\Models\Panel;
use App\Models\SyncProposal;
use App\Models\SyncRun;
use App\Support\AaPanelClient;
use App\Support\AaPanelSiteMapper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Crawl daftar situs web dari semua panel aaPanel yang punya API Key.
 * Panel hasil dibandingkan dengan data lokal → disimpan sebagai proposal
 * (create/update) untuk ditinjau superadmin. Tidak ada penulisan
 * langsung ke application dari action ini.
 */
class CrawlAaPanelSites
{
    /**
     * @return array{runs: Collection<int, SyncRun>, crawled: int, failed: int, skipped: int}
     */
    public function __invoke(): array
    {
        $mapper = new AaPanelSiteMapper;
        $runGroup = (string) Str::uuid();
        $source = SyncSource::AaPanelWebsite;

        $panels = Panel::where('type', PanelType::AaPanel)->orderBy('panel_id')->get();
        $withApiKey = $panels->filter(fn (Panel $panel) => filled($panel->api_key));

        // Panel tanpa API Key tidak dicatat & tidak memunculkan notifikasi.
        $skipped = $panels->count() - $withApiKey->count();

        $runs = collect();

        foreach ($withApiKey as $panel) {
            $address = filled($panel->internal_address) ? $panel->internal_address : $panel->internet_address;

            if (blank($address)) {
                $runs->push($this->failedRun($runGroup, $source, $panel, 'Address internal & internet kosong.'));

                continue;
            }

            $start = now();

            try {
                $sites = app(AaPanelClient::class, [
                    'address' => AaPanelClient::normalizeAddress((string) $address),
                    'secretKey' => (string) $panel->api_key,
                ])->fetchTable('sites');
            } catch (Throwable $exception) {
                $runs->push($this->failedRun($runGroup, $source, $panel, $exception->getMessage()));

                continue;
            }

            $run = $this->storeSites($runGroup, $source, $panel, $mapper, $sites, $start);
            $runs->push($run);
        }

        return [
            'runs' => $runs,
            'crawled' => $runs->where('status', 'succeeded')->count(),
            'failed' => $runs->where('status', 'failed')->count(),
            'skipped' => $skipped,
        ];
    }

    /**
     * Bandingkan seluruh situs satu panel → refresh proposal pending untuk sumber ini.
     *
     * @param  Collection<int, array<string, mixed>>  $sites
     */
    private function storeSites(
        string $runGroup,
        SyncSource $source,
        Panel $panel,
        AaPanelSiteMapper $mapper,
        Collection $sites,
        Carbon $start,
    ): SyncRun {
        $totals = ['new' => 0, 'changed' => 0, 'unchanged' => 0];

        $proposals = [];

        foreach ($sites as $site) {
            $siteName = (string) $site['name'];
            $application = $mapper->matchApplication($siteName);

            if ($application === null) {
                $totals['new']++;
                $proposals[] = [
                    'source' => $source,
                    'panel_id' => $panel->panel_id,
                    'site_name' => $siteName,
                    'action' => SyncProposalAction::Create,
                    'panel_data' => $this->panelData($site),
                    'changes' => $mapper->diffForNewApplication($site),
                    'status' => SyncProposalStatus::Pending,
                ];

                continue;
            }

            $changes = $mapper->diff($application, $site, $panel);

            if ($changes === []) {
                $totals['unchanged']++;

                continue;
            }

            $totals['changed']++;
            $proposals[] = [
                'source' => $source,
                'panel_id' => $panel->panel_id,
                'application_id' => $application->application_id,
                'site_name' => $siteName,
                'action' => SyncProposalAction::Update,
                'panel_data' => $this->panelData($site),
                'changes' => $changes,
                'status' => SyncProposalStatus::Pending,
            ];
        }

        DB::transaction(function () use ($proposals, &$run, $runGroup, $source, $panel, $totals, $sites, $start): void {
            // Proposal pending lama milik panel sumber ini digantikan data crawl baru.
            SyncProposal::where('source', $source->value)
                ->where('panel_id', $panel->panel_id)
                ->where('status', SyncProposalStatus::Pending)
                ->delete();

            $finished = now();

            $run = SyncRun::create([
                'run_group' => $runGroup,
                'source' => $source,
                'panel_id' => $panel->panel_id,
                'status' => 'succeeded',
                'total_sites' => $sites->count(),
                'total_new' => $totals['new'],
                'total_changed' => $totals['changed'],
                'total_unchanged' => $totals['unchanged'],
                'triggered_by' => auth()->id(),
                'started_at' => $start,
                'finished_at' => $finished,
            ]);

            foreach ($proposals as $proposal) {
                $run->proposals()->create($proposal);
            }
        });

        return $run;
    }

    /**
     * Snapshot site fields minimal (tanpa thumbnail base64 yang berat).
     *
     * @param  array<string, mixed>  $site
     * @return array<string, mixed>
     */
    private function panelData(array $site): array
    {
        return [
            'name' => $site['name'] ?? null,
            'status' => $site['status'] ?? null,
            'ps' => $site['ps'] ?? null,
            'project_type' => $site['project_type'] ?? null,
            'php_version' => $site['php_version'] ?? null,
            'path' => $site['path'] ?? null,
            'addtime' => $site['addtime'] ?? null,
        ];
    }

    private function failedRun(string $runGroup, SyncSource $source, Panel $panel, string $message): SyncRun
    {
        $start = now();

        return SyncRun::create([
            'run_group' => $runGroup,
            'source' => $source,
            'panel_id' => $panel->panel_id,
            'status' => 'failed',
            'message' => $message,
            'triggered_by' => auth()->id(),
            'started_at' => $start,
            'finished_at' => now(),
        ]);
    }
}
