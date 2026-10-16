<?php

namespace App\Actions\Reports;

use App\Models\MonthlyReportItem;
use App\Support\Authorize;
use Illuminate\Support\Facades\DB;

class RemoveReportItem
{
    /**
     * Hapus item dari uraian kerja; barisan item berbahasa re-sequence.
     */
    public function __invoke(MonthlyReportItem $item): bool
    {
        Authorize::monthlyReportOwn($item->report);

        return DB::transaction(function () use ($item): bool {
            $report = $item->report;
            $sequence = $item->sequence;
            $deleted = $item->delete();

            // Rapatkan urutan item berikutnya dalam laporan
            MonthlyReportItem::query()
                ->where('monthly_report_id', $report->id)
                ->where('sequence', '>', $sequence)
                ->decrement('sequence');

            return $deleted;
        });
    }
}
