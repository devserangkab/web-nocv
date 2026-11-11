<?php

namespace App\Actions\Synchronization;

use App\Actions\Activities\RecordActivity;
use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\SyncProposalAction;
use App\Enums\SyncProposalStatus;
use App\Enums\SyncSource;
use App\Models\Application;
use App\Models\ApplicationDatabase;
use App\Models\Database;
use App\Models\DatabaseInstance;
use App\Models\DatabaseUser;
use App\Models\Panel;
use App\Models\SyncProposal;
use App\Support\Authorize;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Terapkan satu proposal sesuai sumbernya:
 * - aapanel_website  : buat/perbarui application + hostings,
 * - aapanel_database : katalog Database + DatabaseUser (kredensial snapshot)
 *                      dan/atau tautan application_databases.
 */
class ApplySyncProposal
{
    public function __invoke(SyncProposal $proposal): ?Application
    {
        Authorize::applicationsManage();

        if ($proposal->status !== SyncProposalStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Proposal '.$proposal->site_name.' sudah diproses ('.$proposal->status->label().').',
            ]);
        }

        $application = DB::transaction(function () use ($proposal): ?Application {
            $application = match ($proposal->source) {
                SyncSource::AaPanelDatabase => $this->applyDatabaseProposal($proposal),
                SyncSource::AaPanelWebsite => $this->applyWebsiteProposal($proposal),
                default => throw ValidationException::withMessages([
                    'source' => 'Sumber sinkronisasi '.$proposal->source->label().' belum didukung.',
                ]),
            };

            $proposal->update([
                'status' => SyncProposalStatus::Applied,
                'applied_by' => auth()->id(),
                'applied_at' => now(),
            ]);

            // Proposal pending lain untuk objek yang sama tidak lagi relevan.
            SyncProposal::where('source', $proposal->source->value)
                ->where('site_name', $proposal->site_name)
                ->where('status', SyncProposalStatus::Pending)
                ->whereNot('id', $proposal->id)
                ->delete();

            return $application;
        });

        app(RecordActivity::class)->audit(
            $application ?? Database::where('name', $proposal->site_name)->first(),
            'Menerapkan proposal sinkronisasi '.$proposal->source->label().': '.$proposal->site_name.' ('.$proposal->action->label().')'
        );

        return $application;
    }

    /* ---------------------------------------------------------------------------
     | Sumber: aapanel_website (application + hosting)
     * ------------------------------------------------------------------------- */

    private function applyWebsiteProposal(SyncProposal $proposal): Application
    {
        $application = $proposal->action === SyncProposalAction::Create
            ? $this->createApplication($proposal)
            : $this->updateApplication($proposal);

        $this->ensureHosting($application, $proposal);

        return $application;
    }

    /**
     * Buat application baru dari snapshot situs aaPanel (proposal create).
     */
    private function createApplication(SyncProposal $proposal): Application
    {
        $panelData = $proposal->panel_data;

        return Application::create([
            'name' => (string) $panelData['name'],
            'description' => filled($panelData['ps'] ?? null) ? (string) $panelData['ps'] : null,
            'status' => ($panelData['status'] ?? null) === '1'
                ? ApplicationStatus::Production->value
                : ApplicationStatus::Maintenance->value,
            'domain' => (string) $panelData['name'],
            'language' => filled($panelData['php_version'] ?? null) ? 'PHP' : null,
            'language_version' => filled($panelData['php_version'] ?? null) ? (string) $panelData['php_version'] : null,
            'type' => ApplicationType::Website->value,
        ]);
    }

    /**
     * Perbarui application yang sudah ada sesuai baris diff yang disetujui;
     * kolom `hosting` dikecualikan (relasi, bukan kolom application).
     */
    private function updateApplication(SyncProposal $proposal): Application
    {
        $application = $proposal->application;

        if ($application === null) {
            throw ValidationException::withMessages([
                'application' => 'Application '.$proposal->site_name.' tidak ditemukan.',
            ]);
        }

        foreach ($proposal->changes as $change) {
            if ($change['field'] === 'hosting') {
                continue;
            }

            $value = $change['new'];

            $application->{$change['field']} = match ($change['field']) {
                'status' => ApplicationStatus::from((string) $value),
                default => $value,
            };
        }

        $application->save();

        return $application;
    }

    /**
     * Tautkan application ↔ panel bila proposal mencatat perubahan hosting
     * (atau application baru) dan panel masih ada di master data.
     */
    private function ensureHosting(Application $application, SyncProposal $proposal): void
    {
        if ($proposal->action !== SyncProposalAction::Create
            && ! collect($proposal->changes)->contains(fn (array $change) => $change['field'] === 'hosting')) {
            return;
        }

        $panel = Panel::where('panel_id', $proposal->panel_id)->first();

        if ($panel === null) {
            return;
        }

        $linked = $application->hostings()
            ->where('hostable_type', Panel::class)
            ->where('hostable_id', $panel->id)
            ->exists();

        if ($linked) {
            return;
        }

        $application->hostings()->create([
            'hostable_type' => Panel::class,
            'hostable_id' => $panel->id,
        ]);
    }

    /* ---------------------------------------------------------------------------
     | Sumber: aapanel_database (Database + DatabaseUser + pivot application)
     * ------------------------------------------------------------------------- */

    /**
     * Buat database di katalog (bila belum ada) + user kredensial dari
     * snapshot aaPanel; proposal Tautan juga menyambung ke application.
     */
    private function applyDatabaseProposal(SyncProposal $proposal): ?Application
    {
        $instance = DatabaseInstance::where('panel_id', $proposal->panel_id)->first();

        if ($instance === null) {
            throw ValidationException::withMessages([
                'instance' => 'Instance database untuk panel '.$proposal->panel_id.' tidak ditemukan — jalankan sinkronisasi database dulu.',
            ]);
        }

        $data = $proposal->panel_data;
        $name = (string) ($data['name'] ?? $proposal->site_name);

        $database = Database::where('dbi_id', $instance->dbi_id)
            ->whereRaw('LOWER(name) = ?', [$name])
            ->first();

        if ($database === null) {
            $database = $instance->schemas()->create([
                'name' => $name,
                'note' => blank($data['ps'] ?? null) ? null : (string) $data['ps'],
            ]);
        }

        $this->ensureDatabaseUser($instance, $data, $database);

        if ($proposal->action === SyncProposalAction::Link) {
            $this->ensureApplicationLink($proposal, $database);

            return $proposal->application;
        }

        return null;
    }

    /**
     * Pastikan DatabaseUser (username + password snapshot) ada pada instance.
     */
    private function ensureDatabaseUser(DatabaseInstance $instance, array $data, Database $database): void
    {
        $username = (string) ($data['username'] ?? '');

        if ($username === '') {
            return;
        }

        $user = DatabaseUser::where('dbi_id', $instance->dbi_id)
            ->whereRaw('LOWER(username) = ?', [$username])
            ->first();

        if ($user === null) {
            $instance->users()->create([
                'username' => $username,
                'password' => filled($data['password'] ?? null) ? (string) $data['password'] : null,
                'note' => 'User database dari aaPanel ('.($data['accept'] ?? 'localhost').')',
            ]);
        } elseif (filled($data['password'] ?? null) && blank($user->password)) {
            // Kredensial katalog yang masih kosong diisi dari snapshot.
            $user->password = (string) $data['password'];
            $user->save();
        }
    }

    /**
     * Sambungkan application with database (pivot) + user kredensial terkait.
     */
    private function ensureApplicationLink(SyncProposal $proposal, Database $database): void
    {
        $application = $proposal->application;

        if ($application === null) {
            throw ValidationException::withMessages([
                'application' => 'Application '.$proposal->site_name.' tidak ditemukan.',
            ]);
        }

        $instance = $database->instance;
        $user = DatabaseUser::where('dbi_id', $instance->dbi_id)
            ->whereRaw('LOWER(username) = ?', [strtolower((string) ($proposal->panel_data['username'] ?? ''))])
            ->first();

        ApplicationDatabase::firstOrCreate([
            'application_id' => $application->application_id,
            'db_id' => $database->db_id,
        ], [
            'db_user_id' => $user?->dbu_id,
            'note' => 'Tautan otomatis dari sinkronisasi aaPanel',
        ]);
    }
}
