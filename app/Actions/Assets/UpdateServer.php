<?php

namespace App\Actions\Assets;

use App\Models\Server;
use Illuminate\Support\Facades\Validator;

class UpdateServer
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(Server $server, array $data): Server
    {
        $data = Validator::make($data, CreateServer::rules(), CreateServer::messages())->validate();

        $normalized = CreateServer::normalize($data);

        // Kredensial: kosong saat edit = biarkan nilai lama
        foreach (['username', 'password'] as $credential) {
            if (blank($normalized[$credential])) {
                unset($normalized[$credential]);
            }
        }

        $server->update($normalized);

        return $server;
    }
}
