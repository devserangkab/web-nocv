<?php

namespace App\Actions\Assets;

use App\Actions\Activities\RecordActivity;
use App\Models\Server;
use Illuminate\Support\Facades\DB;

class DeleteServer
{
    public function __invoke(Server $server): bool
    {
        // FK related panel nullOnDelete dan proxmox cascadeOnDelete otomatis.
        $deleted = DB::transaction(fn () => $server->delete());

        app(RecordActivity::class)->audit($server, 'Menghapus server '.($server->server_name ?? '-').' ('.($server->server_id ?? '-').')');

        return $deleted;
    }
}
