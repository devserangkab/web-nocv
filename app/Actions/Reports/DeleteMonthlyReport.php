<?php

namespace App\Actions\Reports;

use App\Models\MonthlyReport;
use App\Support\Authorize;
use Illuminate\Support\Facades\DB;

class DeleteMonthlyReport
{
    public function __invoke(MonthlyReport $report): bool
    {
        Authorize::monthlyReportOwn($report);

        return DB::transaction(fn () => $report->delete());
    }
}
