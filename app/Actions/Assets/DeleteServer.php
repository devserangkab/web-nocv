<?php

namespace App\Actions\Assets;

use App\Models\Server;
use Illuminate\Support\Facades\DB;

class DeleteServer
{
    public function __invoke(Server $server): bool
    {
        // FK related panel nullOnDelete dan proxmox cascadeOnDelete otomatis.
        return DB::transaction(fn () => $server->delete());
    }
}
