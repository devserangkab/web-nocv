<?php

namespace App\Actions\Nas;

use App\Actions\Activities\RecordActivity;
use App\Models\Nas;
use App\Support\Authorize;
use Illuminate\Support\Facades\DB;

class DeleteNas
{
    public function __invoke(Nas $nas): bool
    {
        Authorize::permission('nas.delete', 'Tidak berwenang menghapus NAS.');
        $deleted = DB::transaction(fn () => $nas->delete());

        app(RecordActivity::class)->audit($nas, 'Menghapus NAS '.($nas->name ?? '-').' ('.($nas->nas_id ?? '-').')');

        return $deleted;
    }
}
