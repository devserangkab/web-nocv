<?php

namespace App\Actions\Reports;

use App\Models\MonthlyReportItem;
use Illuminate\Support\Facades\DB;

class MoveReportItem
{
    /**
     * Tukar urutan item dengan tetangganya (up/down).
     */
    public function __invoke(MonthlyReportItem $item, string $direction): bool
    {
        return DB::transaction(function () use ($item, $direction): bool {
            $neighbor = $item->report->items()
                ->orderBy('sequence')
                ->get()
                ->firstWhere('sequence', $direction === 'up' ? $item->sequence - 1 : $item->sequence + 1);

            if ($neighbor !== null) {
                $mySequence = $item->sequence;
                $item->update(['sequence' => $neighbor->sequence]);
                $neighbor->update(['sequence' => $mySequence]);
            }

            return true;
        });
    }
}
