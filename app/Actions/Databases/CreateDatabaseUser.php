<?php

namespace App\Actions\Databases;

use App\Actions\Activities\RecordActivity;
use App\Models\DatabaseInstance;
use App\Models\DatabaseUser;
use App\Support\Authorize;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Unique;

class CreateDatabaseUser
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(DatabaseInstance $instance, array $data): DatabaseUser
    {
        Authorize::infrastructureManage();

        $data = Validator::make($data, static::rules($instance), static::messages())->validate();

        $user = $instance->users()->create([
            'username' => $data['username'],
            'password' => filled($data['password'] ?? null) ? $data['password'] : null,
            'note' => filled($data['note'] ?? null) ? $data['note'] : null,
        ]);

        app(RecordActivity::class)->audit($instance, 'Menambahkan user database '.$user->username.' ('.$user->dbu_id.') pada '.$instance->name.' ('.$instance->dbi_id.')');

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(DatabaseInstance $instance, ?int $excludeId = null): array
    {
        return [
            'username' => [
                'required',
                'max:255',
                (new Unique('database_users', 'username'))
                    ->where('dbi_id', $instance->dbi_id)
                    ->ignore($excludeId),
            ],
            'password' => ['nullable', 'max:255'],
            'note' => ['nullable', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'username.required' => 'Username wajib diisi.',
            'username.max' => 'Username maksimal 255 karakter.',
            'username.unique' => 'Username sudah dipakai di instance ini.',
            'password.max' => 'Password maksimal 255 karakter.',
            'note.max' => 'Keterangan maksimal 1000 karakter.',
        ];
    }
}
