<?php

namespace App\Actions\Assets;

use App\Actions\Activities\RecordActivity;
use App\Models\Waf;
use App\Support\Authorize;

class DeleteWaf
{
    public function __invoke(Waf $waf): bool
    {
        Authorize::infrastructureManage();
        $deleted = $waf->delete();

        app(RecordActivity::class)->audit($waf, 'Menghapus WAF '.($waf->name ?? '-').' ('.($waf->waf_id ?? '-').')');

        return $deleted;
    }
}
