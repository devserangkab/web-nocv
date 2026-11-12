<?php

namespace App\Actions\Assets;

use App\Actions\Activities\RecordActivity;
use App\Models\Server;
use App\Support\Authorize;
use Illuminate\Support\Facades\DB;

class DeleteServer
{
    public function __invoke(Server $server): bool
    {
        Authorize::permission('servers.delete', 'Tidak berwenang menghapus server fisik.');
        // FK related panel nullOnDelete dan proxmox cascadeOnDelete otomatis.
        $deleted = DB::transaction(fn () => $server->delete());

        app(RecordActivity::class)->audit($server, 'Menghapus server '.($server->server_name ?? '-').' ('.($server->server_id ?? '-').')');

        return $deleted;
    }
}
