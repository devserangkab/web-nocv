<?php

namespace App\Actions\Databases;

use App\Actions\Activities\RecordActivity;
use App\Models\DatabaseUser;
use App\Support\Authorize;
use Illuminate\Support\Facades\Validator;

class UpdateDatabaseUser
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(DatabaseUser $user, array $data): DatabaseUser
    {
        Authorize::infrastructureManage();
        $data = Validator::make($data, CreateDatabaseUser::rules($user->instance, $user->id), CreateDatabaseUser::messages())->validate();

        $user->update([
            'username' => $data['username'],
            // Password: kosong saat edit = biarkan nilai lama
            'password' => blank($data['password'] ?? null) ? $user->password : $data['password'],
            'note' => filled($data['note'] ?? null) ? $data['note'] : null,
        ]);

        app(RecordActivity::class)->audit($user->instance, 'Mengubah user database '.$user->username.' ('.$user->dbu_id.') pada '.$user->instance->name.' ('.$user->instance->dbi_id.')');

        return $user;
    }
}
