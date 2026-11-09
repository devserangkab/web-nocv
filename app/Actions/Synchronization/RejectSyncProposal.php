<?php

namespace App\Actions\Synchronization;

use App\Enums\SyncProposalStatus;
use App\Models\SyncProposal;
use App\Support\Authorize;
use Illuminate\Validation\ValidationException;

/**
 * Tolak proposal sinkronisasi tanpa mengubah data katalog.
 */
class RejectSyncProposal
{
    public function __invoke(SyncProposal $proposal): SyncProposal
    {
        Authorize::applicationsManage();

        if ($proposal->status !== SyncProposalStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Proposal '.$proposal->site_name.' sudah diproses ('.$proposal->status->label().').',
            ]);
        }

        $proposal->update(['status' => SyncProposalStatus::Rejected]);

        return $proposal;
    }
}
