<?php

namespace App\Actions\Databases;

use App\Actions\Activities\RecordActivity;
use App\Models\Database;
use App\Support\Authorize;
use Illuminate\Support\Facades\Validator;

class UpdateDatabaseSchema
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(Database $database, array $data): Database
    {
        Authorize::infrastructureManage();
        $data = Validator::make($data, CreateDatabaseSchema::rules($database->instance, $database->id), CreateDatabaseSchema::messages())->validate();

        $oldName = $database->name;

        $database->update([
            'name' => $data['name'],
            'note' => filled($data['note'] ?? null) ? $data['note'] : null,
        ]);

        app(RecordActivity::class)->audit($database->instance, 'Mengubah database '.$oldName.' → '.$database->name.' ('.$database->db_id.') pada '.$database->instance->name.' ('.$database->instance->dbi_id.')');

        return $database;
    }
}
