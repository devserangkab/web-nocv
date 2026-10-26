<?php

namespace App\Actions\Databases;

use App\Actions\Activities\RecordActivity;
use App\Models\Database;
use App\Models\DatabaseInstance;
use App\Support\Authorize;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateDatabaseSchema
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(DatabaseInstance $instance, array $data): Database
    {
        Authorize::infrastructureManage();

        $data = Validator::make($data, static::rules($instance), static::messages())->validate();

        $database = $instance->schemas()->create([
            'name' => $data['name'],
            'note' => filled($data['note'] ?? null) ? $data['note'] : null,
        ]);

        app(RecordActivity::class)->audit($instance, 'Menambahkan database '.$database->name.' ('.$database->db_id.') pada '.$instance->name.' ('.$instance->dbi_id.')');

        return $database;
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(DatabaseInstance $instance, ?int $excludeId = null): array
    {
        return [
            'name' => [
                'required',
                'max:255',
                Rule::unique('databases', 'name')
                    ->where('dbi_id', $instance->dbi_id)
                    ->ignore($excludeId),
            ],
            'note' => ['nullable', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'name.required' => 'Nama database wajib diisi.',
            'name.max' => 'Nama database maksimal 255 karakter.',
            'name.unique' => 'Nama database sudah dipakai di instance ini.',
            'note.max' => 'Keterangan maksimal 1000 karakter.',
        ];
    }
}
