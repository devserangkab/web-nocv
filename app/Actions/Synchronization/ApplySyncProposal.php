<?php

namespace App\Actions\Synchronization;

use App\Actions\Activities\RecordActivity;
use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\SyncProposalAction;
use App\Enums\SyncProposalStatus;
use App\Models\Application;
use App\Models\Panel;
use App\Models\SyncProposal;
use App\Support\Authorize;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Terapkan satu proposal: buat/perbarui application sesuai usulan
 * panel aaPanel, lalu tautkan hostings ke panel terkait.
 */
class ApplySyncProposal
{
    public function __invoke(SyncProposal $proposal): Application
    {
        Authorize::applicationsManage();

        if ($proposal->status !== SyncProposalStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Proposal '.$proposal->site_name.' sudah diproses ('.$proposal->status->label().').',
            ]);
        }

        $application = DB::transaction(function () use ($proposal): Application {
            $application = $proposal->action === SyncProposalAction::Create
                ? $this->createApplication($proposal)
                : $this->updateApplication($proposal);

            $this->ensureHosting($application, $proposal);

            $proposal->update([
                'status' => SyncProposalStatus::Applied,
                'applied_by' => auth()->id(),
                'applied_at' => now(),
            ]);

            // Proposal pending lain untuk situs yang sama tidak lagi relevan.
            SyncProposal::where('source', $proposal->source->value)
                ->where('site_name', $proposal->site_name)
                ->where('status', SyncProposalStatus::Pending)
                ->whereNot('id', $proposal->id)
                ->delete();

            return $application;
        });

        app(RecordActivity::class)->audit(
            $application,
            'Menerapkan proposal sinkronisasi '.$proposal->source->label().': '.$proposal->site_name.' ('.$proposal->action->label().')'
        );

        return $application;
    }

    /**
     * Buat application baru dari snapshot situs aaPanel (panel proposal create).
     */
    private function createApplication(SyncProposal $proposal): Application
    {
        $panelData = $proposal->panel_data;

        return Application::create([
            // Nama situs = domain (di aaPanel `name` unbiased host name)
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
     * Perbarui application yang sudah ada pada baris diff yang disetujui,
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
}
