<?php

namespace App\Support;

use App\Models\Application;
use Illuminate\Support\Collection;

/**
 * Heuristik pencocokan database aaPanel ↔ application katalog :
 * aaPanel tidak menyimpan relasinya, jadi nama database (dan keterangan
 * `ps`) dicocokkan dengan pola domain aplikasi yang dinormalisasi
 * (dot/vn → underscore), mis. dishub.serangkab.go.id → dishub_serangkab_go_id,
 * atau dengan label pertama domain (kesra... → kesra).
 */
class AaPanelDbMatcher
{
    /**
     * Cari application kandidat unik untuk satu database. Null bila tidak
     * ada kandidat ATAU lebih dari satu kandidat (ambigu — manual).
     */
    public function matchApplication(string $dbName, ?string $note = null): ?Application
    {
        $patterns = $this->patterns($dbName, $note);
        $matches = collect();

        Application::query()->get(['id', 'application_id', 'name', 'domain'])->each(function (Application $application) use ($patterns, $matches): void {
            $appPatterns = [
                $this->normalize((string) $application->domain),
                $this->normalize((string) $application->name),
            ];
            $labels = $this->domainLabels($application->domain)
                ->map(fn (?string $label) => $this->normalize($label ?? ''))
                ->filter()
                ->unique();

            $combined = collect($appPatterns)->merge($labels)->unique();

            if ($combined->intersect($patterns)->isNotEmpty()) {
                $matches->push($application);
            }
        });

        return $matches->count() === 1 ? $matches->first() : null;
    }

    /**
     * Kunci pencocokan database: nama + keterangan ps, dinormalisasi.
     *
     * @return array<int, string>
     */
    private function patterns(string $dbName, ?string $note): array
    {
        return collect([$dbName, $note ?? ''])
            ->map(fn (string $value) => $this->normalize($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Domain dipisah label: utama, lalu seluruh struktur keseluruhan.
     *
     * @return Collection<int, ?string>
     */
    private function domainLabels(?string $domain): Collection
    {
        if (blank($domain)) {
            return collect([$domain]);
        }

        $parts = collect(explode('.', trim((string) $domain)));

        return collect([$parts->first(), $domain]);
    }

    /**
     * Normalisasi: lowercase + non-alnum → underscore, dipangkas.
     */
    private function normalize(string $value): string
    {
        return preg_replace('/[^a-z0-9_]+/', '', strtolower(str_replace(['.', '-'], '_', trim($value)))) ?? '';
    }
}
