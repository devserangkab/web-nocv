<?php

namespace App\Actions\Applications;

use App\Actions\Activities\RecordActivity;
use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\DeveloperType;
use App\Models\Application;
use App\Support\Authorize;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class StoreApplication
{
    /**
     * Create (model baru) maupun update; ikut menyinkronkan developer repeater
     * dan mengelola file icon (upload baru menggantikan yang lama).
     *
     * @param  Application  $application  model baru untuk create, model existing untuk update
     * @param  array<string, mixed>  $data
     * @param  array<int, array{user_id: string|int, type: string}>  $developerRows
     */
    public function __invoke(Application $application, array $data): Application
    {
        Authorize::applicationsManage();
        $data = Validator::make($data, static::rules($application->exists ? $application->id : null), static::messages())->validate();

        $developerRows = collect($data['developers'] ?? []);
        unset($data['developers'], $data['existing_icon_path']);

        // Icon: upload baru menggantikan yang lama
        if ($data['icon'] instanceof TemporaryUploadedFile) {
            if (filled($data['existing_icon_path'] ?? null)) {
                Storage::disk('public')->delete($data['existing_icon_path']);
            }

            $data['icon'] = $data['icon']->store('applications', 'public');
        } else {
            $data['icon'] = filled($data['existing_icon_path'] ?? null) ? $data['existing_icon_path'] : null;
        }

        $application->fill($data);
        $application->save();

        // Sinkronisasi repeater developer: bersihkan lama, tulis ulang sesuai baris valid
        $application->developers()->delete();
        $developerRows->each(fn (array $row) => $application->developers()->create([
            'developer_id' => (int) $row['user_id'],
            'type' => DeveloperType::from($row['type']),
        ]));

        app(RecordActivity::class)->audit(
            $application,
            ($application->wasRecentlyCreated ? 'Menambahkan' : 'Mengubah').' application '.$application->name.' ('.$application->application_id.')'
        );

        return $application;
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(?int $excludeId = null): array
    {
        return [
            'name' => ['required', 'max:255', function (string $attribute, string $value, $fail) use ($excludeId) {
                $taken = Application::query()
                    ->where('name', $value)
                    ->when($excludeId !== null, fn ($query) => $query->where('id', '!=', $excludeId))
                    ->exists();

                if ($taken) {
                    $fail('Nama application sudah dipakai.');
                }
            }],
            'abbreviation' => ['nullable', 'max:50'],
            'description' => ['nullable', 'max:1000'],
            'status' => ['required', Rule::enum(ApplicationStatus::class)],
            'type' => ['required', Rule::enum(ApplicationType::class)],
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
            'framework' => ['nullable', 'max:255'],
            'language' => ['nullable', 'max:255'],
            'database' => ['nullable', 'max:255'],
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
            'abbreviation.max' => 'Singkatan maksimal 50 karakter.',
            'description.max' => 'Description maksimal 1000 karakter.',
            'status.required' => 'Status wajib dipilih.',
            'type.required' => 'Jenis application wajib dipilih.',
            'domain.max' => 'Domain maksimal 255 karakter.',
            'repo_url.url' => 'Repo URL harus berupa URL yang valid (contoh: https://github.com/org/repo).',
            'repo_url.max' => 'Repo URL maksimal 255 karakter.',
            'framework.max' => 'Framework maksimal 255 karakter.',
            'language.max' => 'Language maksimal 255 karakter.',
            'database.max' => 'Database maksimal 255 karakter.',
        ];
    }
}
