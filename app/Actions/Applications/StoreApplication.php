<?php

namespace App\Actions\Applications;

use App\Actions\Activities\RecordActivity;
use App\Enums\ApplicationFramework;
use App\Enums\ApplicationLanguage;
use App\Enums\ApplicationOwnership;
use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\DeveloperType;
use App\Enums\HostingKind;
use App\Models\Application;
use App\Models\Database;
use App\Models\DatabaseUser;
use App\Models\Panel;
use App\Models\Server;
use App\Models\Vms;
use App\Support\Authorize;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class StoreApplication
{
    /**
     * Create (model baru) maupun update; ikut menyinkronkan repeater developer
     * dan deployment (hosting), mengelola file icon (disk privat), serta
     * mencatat perubahan versi teknologi sebagai aktivitas.
     *
     * @param  Application  $application  model baru untuk create, model existing untuk update
     * @param  array<string, mixed>  $data
     * @param  array<int, array{user_id: string|int, type: string}>  $developerRows
     * @param  array<int, array{kind: string, host: string, name: string, note: string}>  $hostingRows
     */
    public function __invoke(Application $application, array $data): Application
    {
        Authorize::permission(
            $application->exists ? 'applications.update' : 'applications.create',
            $application->exists ? 'Tidak berwenang mengubah application.' : 'Tidak berwenang menambah application.'
        );
        $data = Validator::make($data, static::rules($application->exists ? $application->id : null), static::messages())->validate();

        $developerRows = collect($data['developers'] ?? [])
            ->filter(fn (array $row) => filled($row['user_id']) || filled($row['type']));

        $hostingRows = collect($data['hostings'] ?? [])
            ->filter(fn (array $row) => filled($row['kind']));

        $databaseRows = collect($data['databases'] ?? [])
            ->filter(fn (array $row) => filled($row['db'] ?? null));
        unset($data['developers'], $data['hostings'], $data['databases'], $data['existing_icon_path']);

        return DB::transaction(function () use ($application, $data, $developerRows, $hostingRows, $databaseRows): Application {
            // Nilai teknologi sebelum perubahan — untuk aktivitas riwayat versi
            $before = $application->exists
                ? [
                    'framework' => $application->framework?->value,
                    'framework_version' => $application->framework_version,
                    'language' => $application->language?->value,
                    'language_version' => $application->language_version,
                ]
                : [];

            $oldIcon = filled($data['existing_icon_path'] ?? null) ? $data['existing_icon_path'] : null;

            // Icon: upload baru menggantikan yang lama (disk privat)
            $uploadedIcon = $data['icon'] ?? null;

            if ($uploadedIcon instanceof TemporaryUploadedFile) {
                $data['icon'] = $uploadedIcon->store('applications', 'private');
            } else {
                $data['icon'] = $oldIcon;
            }

            $application->fill($data);
            $application->save();

            // File icon lama dihapus hanya setelah simpan baru sukses
            if ($oldIcon !== null && $oldIcon !== $application->icon) {
                Storage::disk('private')->delete($oldIcon);
            }

            // Sinkronisasi repeater developer: bersihkan lama, tulis ulang sesuai baris valid
            $application->developers()->delete();
            $developerRows->each(fn (array $row) => $application->developers()->create([
                'developer_id' => (int) $row['user_id'],
                'type' => DeveloperType::from($row['type']),
            ]));

            // Sinkronisasi repeater deployment: bersihkan lama, tulis ulang sesuai baris valid
            $application->hostings()->delete();
            $hostingRows->each(fn (array $row) => $application->hostings()->create($this->hostingAttributes($row)));

            // Sinkronisasi repeater database: null user = memakai kredensial root instance
            $application->databases()->detach();
            $databaseRows->each(fn (array $row) => $application->databases()->attach($row['db'], [
                'db_user_id' => filled($row['db_user_id'] ?? null) ? $row['db_user_id'] : null,
                'note' => filled($row['note'] ?? null) ? $row['note'] : null,
            ]));

            // Riwayat versi: perubahan framework/language/database + versinya tercatat sebagai aktivitas
            if ($before !== []) {
                $changes = $this->versionChanges($before, $application);

                if ($changes !== []) {
                    app(RecordActivity::class)->audit(
                        $application,
                        'Pembaruan teknologi pada application '.$application->name.' ('.$application->application_id.')',
                        implode('; ', $changes)
                    );
                }
            }

            app(RecordActivity::class)->audit(
                $application,
                ($application->wasRecentlyCreated ? 'Menambahkan' : 'Mengubah').' application '.$application->name.' ('.$application->application_id.')'
            );

            return $application;
        });
    }

    /**
     * Baris hosting → atribut polymorphic: host internal (Server/Vms/Panel)
     * di-resolve lewat kode unik; host eksternal hanya nama bebas.
     *
     * @param  array{kind: string, host: string, name: string, note: string}  $row
     * @return array{hostable_type: class-string|null, hostable_id: int|null, name: string|null, note: string|null}
     */
    private function hostingAttributes(array $row): array
    {
        $kind = HostingKind::from($row['kind']);

        if ($kind === HostingKind::External) {
            return [
                'hostable_type' => null,
                'hostable_id' => null,
                'name' => filled($row['name']) ? $row['name'] : null,
                'note' => filled($row['note']) ? $row['note'] : null,
            ];
        }

        $hostClass = $kind->hostClass();

        /** @var Vms|Server|Panel $host */
        $host = $hostClass::where($kind->codeColumn(), $row['host'])->first();

        return [
            'hostable_type' => $host !== null ? $host::class : null,
            'hostable_id' => $host?->id,
            'name' => null,
            'note' => filled($row['note']) ? $row['note'] : null,
        ];
    }

    /**
     * Daftar komponen teknologi yang berubah (nilai + versinya), mis.
     * "Framework: Laravel 10.48.4 → Laravel 11.0.2".
     *
     * @param  array<string, mixed>  $before
     * @return list<string>
     */
    private function versionChanges(array $before, Application $after): array
    {
        $changes = [];

        $pairs = [
            ['framework', 'framework', 'framework_version', 'Framework'],
            ['language', 'language', 'language_version', 'Language'],
        ];

        foreach ($pairs as [$valueColumn, $valueKey, $versionKey, $label]) {
            $beforeValue = $before[$valueColumn];
            $beforeVersion = $before[$versionKey];
            $afterValue = $after->{$valueKey}?->value;
            $afterVersion = $after->{$versionKey};

            if ($beforeValue === $afterValue && $beforeVersion === $afterVersion) {
                continue;
            }

            $beforeText = trim(($beforeValue ?? '-').' '.($beforeVersion ?? ''));
            $afterText = trim(($afterValue ?? '-').' '.($afterVersion ?? ''));

            $changes[] = $label.': '.$beforeText.' → '.$afterText;
        }

        return $changes;
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(?int $excludeId = null): array
    {
        $uniqueName = $excludeId !== null
            ? Rule::unique('applications', 'name')->ignore($excludeId)
            : Rule::unique('applications', 'name');

        return [
            'name' => ['required', 'max:255', $uniqueName],
            'abbreviation' => ['nullable', 'max:50'],
            'description' => ['nullable', 'max:1000'],
            'status' => ['required', new Enum(ApplicationStatus::class)],
            'type' => ['required', new Enum(ApplicationType::class)],
            'requested_by' => ['nullable', 'integer', 'exists:institutions,id'],
            'domain' => ['nullable', 'max:255'],
            'repo_url' => ['nullable', 'url', 'max:255'],
            // Icon: nullable agar path lama boleh dipertahankan (string); file baru divalidasi image + max 2MB
            'icon' => [function (string $attribute, $value, $fail) {
                if (! $value instanceof TemporaryUploadedFile) {
                    return;
                }

                $check = Validator::make(
                    ['icon' => $value],
                    ['icon' => ['image', 'max:2048']],
                    [
                        'icon.image' => 'Icon harus berupa gambar (jpg, png, svg, webp).',
                        'icon.max' => 'Icon maksimal 2 MB.',
                    ]
                );

                if ($check->fails()) {
                    $fail($check->errors()->first('icon'));
                }
            }],
            'existing_icon_path' => ['nullable', 'string'],
            'framework' => ['nullable', new Enum(ApplicationFramework::class)],
            'framework_version' => ['nullable', 'max:20'],
            'language' => ['nullable', new Enum(ApplicationLanguage::class)],
            'language_version' => ['nullable', 'max:20'],
            'ownership' => ['nullable', new Enum(ApplicationOwnership::class)],
            'vendor_name' => ['nullable', 'max:255', 'required_if:ownership,Serah Terima Vendor,Dikelola Vendor'],
            'developers' => ['array', function (string $attribute, array $rows, $fail) {
                /** @var Collection<int, array{user_id: string|int, type: string}> $filled */
                $filled = collect($rows)->filter(fn (array $row) => filled($row['user_id']) || filled($row['type']));

                if ($filled->contains(fn (array $row) => blank($row['user_id']) || blank($row['type']))) {
                    $fail('Developer dan jenisnya wajib diisi lengkap.');

                    return;
                }

                if ($filled->pluck('user_id')->duplicates()->isNotEmpty()) {
                    $fail('Developer tidak boleh didaftar dua kali.');
                }
            }],
            'hostings' => ['array', function (string $attribute, array $rows, $fail) {
                /** @var array<int, array{kind: string, host: string, name: string, note: string}> $filled */
                $filled = collect($rows)->filter(fn (array $row) => filled($row['kind'] ?? null));

                if ($filled->contains(fn (array $row) => HostingKind::tryFrom((string) $row['kind']) === null)) {
                    $fail('Jenis host deployment tidak valid.');

                    return;
                }

                if ($filled->contains(fn (array $row) => $row['kind'] !== HostingKind::External->value && blank($row['host'] ?? null))) {
                    $fail('Host deployment wajib dipilih.');

                    return;
                }

                if ($filled->contains(fn (array $row) => $row['kind'] === HostingKind::External->value && blank($row['name'] ?? null))) {
                    $fail('Nama host eksternal wajib diisi.');

                    return;
                }

                $hosts = $filled
                    ->filter(fn (array $row) => $row['kind'] !== HostingKind::External->value)
                    ->map(fn (array $row) => $row['kind'].':'.$row['host']);

                if ($hosts->duplicates()->isNotEmpty()) {
                    $fail('Host deployment tidak boleh didaftar dua kali.');
                }
            }],
            'databases' => ['array', function (string $attribute, array $rows, $fail) {
                /** @var Collection<int, array{db: string, db_user_id: string|null, note: string|null}> $filled */
                $filled = collect($rows)->filter(fn (array $row) => filled($row['db'] ?? null));

                $schemas = $filled
                    ->map(fn (array $row) => Database::query()->with('instance')->where('db_id', $row['db'])->first());

                if ($schemas->contains(fn ($database) => $database === null)) {
                    $fail('Database tidak ditemukan.');

                    return;
                }

                if ($filled->contains(function (array $row) use ($schemas) {
                    if (blank($row['db_user_id'] ?? null)) {
                        return false; // null = memakai kredensial root/admin instance
                    }

                    $user = DatabaseUser::where('dbu_id', $row['db_user_id'])->first();

                    if ($user === null) {
                        return true; // user tidak ditemukan
                    }

                    $schema = $schemas->first(fn ($database) => $database->db_id === $row['db']);

                    return $schema->instance->dbi_id !== $user->dbi_id; // user bukan milik instance schema
                })) {
                    $fail('User database harus milik instance dari database yang dipilih.');

                    return;
                }

                if ($filled->pluck('db')->duplicates()->isNotEmpty()) {
                    $fail('Database tidak boleh didaftar dua kali.');
                }
            }],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'name.required' => 'Name wajib diisi.',
            'name.max' => 'Name maksimal 255 karakter.',
            'name.unique' => 'Nama application sudah dipakai.',
            'abbreviation.max' => 'Singkatan maksimal 50 karakter.',
            'description.max' => 'Description maksimal 1000 karakter.',
            'status.required' => 'Status wajib dipilih.',
            'type.required' => 'Jenis application wajib dipilih.',
            'domain.max' => 'Domain maksimal 255 karakter.',
            'repo_url.url' => 'Repo URL harus berupa URL yang valid (contoh: https://github.com/org/repo).',
            'repo_url.max' => 'Repo URL maksimal 255 karakter.',
            'framework_version.max' => 'Versi framework maksimal 20 karakter.',
            'language_version.max' => 'Versi language maksimal 20 karakter.',
            'vendor_name.required_if' => 'Nama vendor wajib diisi untuk application buatan vendor.',
            'vendor_name.max' => 'Nama vendor maksimal 255 karakter.',
        ];
    }
}
