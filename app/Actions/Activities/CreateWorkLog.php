<?php

namespace App\Actions\Activities;

use App\Enums\ActivityCategory;
use App\Enums\ActivityType;
use App\Models\Application;
use App\Models\Location;
use App\Models\Nas;
use App\Models\Panel;
use App\Models\Proxmox;
use App\Models\Rack;
use App\Models\Server;
use App\Models\Vms;
use App\Support\Authorize;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateWorkLog
{
    /**
     * Catat pekerjaan teknis manual (pemeliharaan, deployment, gangguan, dll)
     * sebagai Work Log pada modul Aktivitas.
     *
     * @param  array<string, mixed>  $data
     */
    public function __invoke(?Model $subject, array $data, ?int $userId): array
    {
        Authorize::activitiesCreate();

        $data = Validator::make($data, static::rules(), static::messages())->validate();

        $recorder = app(RecordActivity::class);

        $activity = $recorder($subject, $data['activity'], ActivityType::Work, $data['category'], $data['description'], $data['finished_at'], $userId);

        return [$activity, $subject];
    }

    /**
     * Daftar tipe entitas yang valid sebagai subjek work log.
     *
     * @return list<class-string<Model>>
     */
    public static function allowedTypes(): array
    {
        return [
            Application::class,
            Location::class,
            Rack::class,
            Server::class,
            Proxmox::class,
            Vms::class,
            Panel::class,
            Nas::class,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'activity' => ['required', 'max:255'],
            'description' => ['nullable', 'max:2000'],
            'category' => [
                'nullable',
                Rule::enum(ActivityCategory::class),
            ],
            'finished_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'activity.required' => 'Judul aktivitas wajib diisi.',
            'activity.max' => 'Judul aktivitas maksimal 255 karakter.',
            'description.max' => 'Deskripsi maksimal 2000 karakter.',
            'category.in' => 'Kategori tidak valid.',
            'finished_at.date' => 'Waktu selesai tidak valid.',
        ];
    }
}
