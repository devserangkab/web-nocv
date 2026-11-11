<?php

namespace App\Actions\Synchronization;

use App\Actions\Activities\RecordActivity;
use App\Enums\DatabaseTech;
use App\Enums\PanelType;
use App\Enums\SyncProposalAction;
use App\Enums\SyncProposalStatus;
use App\Enums\SyncSource;
use App\Models\Application;
use App\Models\Database;
use App\Models\DatabaseInstance;
use App\Models\Panel;
use App\Models\SyncProposal;
use App\Models\SyncRun;
use App\Support\AaPanelClient;
use App\Support\AaPanelDbMatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Crawl daftar database dari semua panel aaPanel yang punya API Key.
 * Instance MySQL dibuat otomatis bila panel belum terdaftar sebagai
 * DatabaseInstance (password root via aaPanel getKey mysql_root).
 * Database dicocokkan ke application via heuristik domain, hasilnya
 * disimpan sebagai proposal (Tautan/Buat baru) untuk ditinjau superadmin.
 */
class CrawlAaPanelDatabases
{
    /**
     * @return array{runs: Collection<int, SyncRun>, crawled: int, failed: int, skipped: int}
     */
    public function __invoke(): array
    {
        $matcher = new AaPanelDbMatcher;
        $runGroup = (string) Str::uuid();
        $source = SyncSource::AaPanelDatabase;

        $panels = Panel::where('type', PanelType::AaPanel)->orderBy('panel_id')->get();
        $withApiKey = $panels->filter(fn (Panel $panel) => filled($panel->api_key));
        $skipped = $panels->count() - $withApiKey->count();

        $runs = collect();

        // Panel tanpa API Key tidak dicatat & tidak memunculkan notifikasi.
        foreach ($withApiKey as $panel) {
            $address = filled($panel->internal_address) ? $panel->internal_address : $panel->internet_address;

            if (blank($address)) {
                $runs->push($this->failedRun($runGroup, $source, $panel, 'Address internal & internet kosong.'));

                continue;
            }

            $start = now();

            try {
                $client = app(AaPanelClient::class, [
                    'address' => AaPanelClient::normalizeAddress((string) $address),
                    'secretKey' => (string) $panel->api_key,
                ]);

                [$instance, $instanceCreated, $instanceNote] = $this->ensureInstance($panel, $client);

                $rows = $client->fetchTable('databases');
            } catch (Throwable $exception) {
                $runs->push($this->failedRun($runGroup, $source, $panel, $exception->getMessage()));

                continue;
            }

            $runs->push($this->storeDatabases($runGroup, $source, $panel, $instance, $matcher, $rows, $start, $instanceCreated, $instanceNote));
        }

        return [
            'runs' => $runs,
            'crawled' => $runs->filter(fn (SyncRun $run) => $run->status === 'succeeded')->count(),
            'failed' => $runs->filter(fn (SyncRun $run) => $run->status === 'failed')->count(),
            'skipped' => $skipped,
        ];
    }

    /**
     * Instance database utk panel: pakai yang sudah ada, atau buat otomatis
     * (MySQL, host = asal panel, kredensial root dari aaPanel getKey bila
     * tersedia; panel tanpa getKey tetap melangkah dengan password kosong).
     *
     * @return array{0: DatabaseInstance, 1: bool, 2: ?string}
     */
    private function ensureInstance(Panel $panel, AaPanelClient $client): array
    {
        $instance = DatabaseInstance::where('panel_id', $panel->panel_id)->first();

        if ($instance !== null) {
            return [$instance, false, null];
        }

        $rootPassword = $client->getKey('mysql_root');
        $note = blank($rootPassword) ? 'mysql_root tidak tersedia dari panel — password instance diisi manual' : null;

        $instance = DatabaseInstance::create([
            'panel_id' => $panel->panel_id,
            'name' => 'MySQL — '.$panel->name,
            'tech' => DatabaseTech::MySql->value,
            'internal_address' => $client->hostAddress(),
            'username' => 'root',
            'password' => blank($rootPassword) ? null : $rootPassword,
        ]);

        app(RecordActivity::class)->audit($instance, 'Instance database dibuat otomatis dari sinkronisasi aaPanel: '.$instance->name.' ('.$instance->dbi_id.')');

        return [$instance, true, $note];
    }

    /**
     * Bandingkan seluruh baris database panel → refresh proposal pending.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function storeDatabases(
        string $runGroup,
        SyncSource $source,
        Panel $panel,
        DatabaseInstance $instance,
        AaPanelDbMatcher $matcher,
        Collection $rows,
        Carbon $start,
        bool $instanceCreated,
        ?string $instanceNote = null,
    ): SyncRun {
        $totals = ['catalog' => 0, 'linked' => 0, 'unchanged' => 0, 'manual' => 0];
        $proposals = [];

        foreach ($rows as $row) {
            $row = $this->normalizeRow($row);
            $name = (string) ($row['name'] ?? '');

            if ($name === '') {
                continue;
            }

            $db = Database::where('dbi_id', $instance->dbi_id)
                ->whereRaw('LOWER(name) = ?', [$name])
                ->first();

            if ($db !== null) {
                $note = blank($row['ps'] ?? null) ? null : (string) $row['ps'];

                if (($db->note ?? null) === $note) {
                    $totals['unchanged']++;

                    continue;
                }

                // Keterangan berubah → proposal perbarui cache katalog.
                $totals['linked']++;
                $proposals[] = [
                    'source' => $source,
                    'panel_id' => $panel->panel_id,
                    'application_id' => null,
                    'site_name' => $name,
                    'action' => SyncProposalAction::Update,
                    'panel_data' => $row,
                    'changes' => [['field' => 'db_note', 'old' => $db->note, 'new' => $note]],
                    'status' => SyncProposalStatus::Pending,
                ];

                continue;
            }

            $application = $matcher->matchApplication($name, blank($row['ps'] ?? null) ? null : (string) $row['ps']);

            if ($application !== null) {
                $totals['linked']++;
                $proposals[] = [
                    'source' => $source,
                    'panel_id' => $panel->panel_id,
                    'application_id' => $application->application_id,
                    'site_name' => $name,
                    'action' => SyncProposalAction::Link,
                    'panel_data' => $row,
                    'changes' => [['field' => 'db_link', 'old' => $this->dbLabels($application), 'new' => 'Database: '.$name.' (MySQL)']],
                    'status' => SyncProposalStatus::Pending,
                ];

                continue;
            }

            $totals['catalog']++;
            $totals['manual']++;
            $proposals[] = [
                'source' => $source,
                'panel_id' => $panel->panel_id,
                'application_id' => null,
                'site_name' => $name,
                'action' => SyncProposalAction::Create,
                'panel_data' => $row,
                'changes' => [
                    ['field' => 'db_name', 'old' => null, 'new' => $name],
                    ['field' => 'db_user', 'old' => null, 'new' => (string) ($row['username'] ?? '')],
                    ['field' => 'db_accept', 'old' => null, 'new' => (string) ($row['accept'] ?? '')],
                    ['field' => 'db_note', 'old' => null, 'new' => (string) ($row['ps'] ?? '')],
                ],
                'status' => SyncProposalStatus::Pending,
            ];
        }

        $notes = [];

        if ($instanceCreated) {
            $notes[] = 'Instance DB dibuat: '.$instance->name.' ('.$instance->dbi_id.')';
        }

        if ($instanceNote !== null) {
            $notes[] = $instanceNote;
        }

        if ($totals['manual'] > 0) {
            $notes[] = $totals['manual'].' database tanpa kandidat — tautan manual lewat modul Aplikasi';
        }

        $message = $notes === [] ? null : implode(' · ', $notes);

        DB::transaction(function () use ($proposals, &$run, $runGroup, $source, $panel, $totals, $rows, $start, $message): void {
            // Proposal pending lama milik panel sumber ini digantikan crawl baru.
            SyncProposal::where('source', $source->value)
                ->where('panel_id', $panel->panel_id)
                ->where('status', SyncProposalStatus::Pending)
                ->delete();

            $run = SyncRun::create([
                'run_group' => $runGroup,
                'source' => $source,
                'panel_id' => $panel->panel_id,
                'status' => 'succeeded',
                'total_sites' => $rows->count(),
                'total_new' => $totals['catalog'],
                'total_changed' => $totals['linked'],
                'total_unchanged' => $totals['unchanged'],
                'message' => $message,
                'triggered_by' => auth()->id(),
                'started_at' => $start,
                'finished_at' => now(),
            ]);

            foreach ($proposals as $proposal) {
                $run->proposals()->create($proposal);
            }
        });

        return $run;
    }

    /**
     * Ringkasan database milik application saat ini (untuk kolom diff relasi).
     */
    private function dbLabels(Application $application): ?string
    {
        $labels = $application->databases()->pluck('database_schemas.name');

        return $labels->isNotEmpty() ? $labels->implode(' / ') : null;
    }

    /**
     * Snapshot field database yang relevan (tanpa quota dsb.).
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        return [
            'name' => $row['name'] ?? null,
            'username' => $row['username'] ?? null,
            'password' => $row['password'] ?? null,
            'accept' => $row['accept'] ?? null,
            'ps' => $row['ps'] ?? null,
            'addtime' => $row['addtime'] ?? null,
            'db_type' => $row['db_type'] ?? null,
            'backup_count' => $row['backup_count'] ?? null,
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
