<?php

namespace App\Actions\Reports;

use App\Models\MonthlyReport;
use Illuminate\Support\Facades\DB;

class DeleteMonthlyReport
{
    public function __invoke(MonthlyReport $report): bool
    {
        return DB::transaction(fn () => $report->delete());
    }
}
