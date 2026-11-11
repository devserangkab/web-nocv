<?php

namespace App\Support;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Models\Application;
use App\Models\Panel;

/**
 * Pemetaan satu situs aaPanel ke atribut Application & daftar
 * perubahan (diff) untuk proposal sinkronisasi.
 */
class AaPanelSiteMapper
{
    /**
     * Nama tampilan field di kartu proposal (situs & database).
     *
     * @var array<string, string>
     */
    public const FIELD_LABELS = [
        'name' => 'Nama',
        'status' => 'Status',
        'domain' => 'Domain',
        'language' => 'Bahasa',
        'language_version' => 'Versi bahasa',
        'description' => 'Keterangan',
        'hosting' => 'Hosting',
        'db_name' => 'Database',
        'db_user' => 'User DB',
        'db_accept' => 'Akses host',
        'db_note' => 'Keterangan',
        'db_link' => 'Relasi aplikasi',
    ];

    /**
     * Cari application lokal yang cocok dengan satu situs aaPanel.
     * Prioritas: domain sama → nama sama (keduanya case-insensitive).
     */
    public function matchApplication(string $siteName): ?Application
    {
        return Application::whereRaw('LOWER(domain) = ?', [$siteName])->first()
            ?? Application::whereRaw('LOWER(name) = ?', [$siteName])->first();
    }

    public function statusFor(array $site): ApplicationStatus
    {
        return ($site['status'] ?? null) === '1' ? ApplicationStatus::Production : ApplicationStatus::Maintenance;
    }

    /**
     * Atribut untuk application baru dari satu situs aaPanel.
     *
     * @param  array<string, mixed>  $site
     * @return array<string, mixed>
     */
    public function attributesNew(array $site): array
    {
        return [
            'name' => (string) $site['name'],
            'description' => filled($site['ps'] ?? null) ? (string) $site['ps'] : null,
            'status' => $this->statusFor($site)->value,
            'domain' => (string) $site['name'],
            'framework' => null,
            'language' => filled($site['php_version'] ?? null) ? 'PHP' : null,
            'language_version' => filled($site['php_version'] ?? null) ? (string) $site['php_version'] : null,
            'type' => ApplicationType::Website->value,
        ];
    }

    /**
     * Status label untuk UI (persis dengan ApplicationStatus).
     *
     * @param  array<string, mixed>  $site
     */
    public function statusLabel(array $site): string
    {
        return $this->statusFor($site)->value;
    }

    /**
     * Diff application existing terhadap satu situs aaPanel. Field
     * keterangan tidak ikut diganti bila application sudah punya catatan
     * (description) — hanya mengisi yang masih kosong.
     *
     * @param  array<string, mixed>  $site
     * @return array<int, array<string, ?string>>
     */
    public function diff(Application $application, array $site, Panel $panel): array
    {
        $changes = [];
        $phpVersion = filled($site['php_version'] ?? null) ? (string) $site['php_version'] : null;

        if ($application->status->value !== $this->statusLabel($site)) {
            $changes[] = ['field' => 'status', 'old' => $application->status->value, 'new' => $this->statusLabel($site)];
        }

        if (($application->domain ?? '') !== (string) $site['name']) {
            $changes[] = ['field' => 'domain', 'old' => $application->domain, 'new' => (string) $site['name']];
        }

        if ($phpVersion !== null && $application->language_version !== $phpVersion) {
            $changes[] = [
                'field' => 'language_version',
                'old' => $application->language_version,
                'new' => $phpVersion,
            ];

            if (($application->language ?? null) !== 'PHP') {
                $changes[] = ['field' => 'language', 'old' => $application->language, 'new' => 'PHP'];
            }
        }

        if (blank($application->description) && filled($site['ps'] ?? null)) {
            $changes[] = ['field' => 'description', 'old' => null, 'new' => (string) $site['ps']];
        }

        // Host menjadi kandidat usulan saat panel aaPanel ini belum terhubung.
        if (! $application->hostings()->where('hostable_type', Panel::class)->where('hostable_id', $panel->id)->exists()) {
            $current = $application->hostings()->get()
                ->map(fn ($hosting) => $hosting->hostable?->name ?? $hosting->name ?? 'Eksternal')
                ->filter()
                ->implode(' / ');

            $changes[] = ['field' => 'hosting', 'old' => $current !== '' ? $current : null, 'new' => 'Panel: '.$panel->name];
        }

        return $changes;
    }

    /**
     * Daftar perubahan untuk proposal application baru (nilai usulan, lama selalu kosong).
     *
     * @param  array<string, mixed>  $site
     * @return array<int, array<string, ?string>>
     */
    public function diffForNewApplication(array $site): array
    {
        $attributes = $this->attributesNew($site);
        $changes = [];

        foreach (['type', 'status', 'language', 'language_version', 'domain', 'description'] as $field) {
            $value = $attributes[$field];

            if (blank($value)) {
                continue;
            }

            $changes[] = ['field' => $field, 'old' => null, 'new' => (string) $value];
        }

        return $changes;
    }
}
