<?php

namespace App\Actions\Assets;

use App\Actions\Activities\RecordActivity;
use App\Models\Server;
use App\Support\Authorize;
use Illuminate\Support\Facades\Validator;

class UpdateServer
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(Server $server, array $data): Server
    {
        Authorize::infrastructureManage();
        $data = Validator::make($data, CreateServer::rules(), CreateServer::messages())->validate();

        $normalized = CreateServer::normalize($data);

        // Kredensial: kosong saat edit = biarkan nilai lama
        foreach (['username', 'password'] as $credential) {
            if (blank($normalized[$credential])) {
                unset($normalized[$credential]);
            }
        }

        $server->update($normalized);

        app(RecordActivity::class)->audit($server, 'Mengubah server '.$server->server_name.' ('.$server->server_id.')');

        return $server;
    }
}
