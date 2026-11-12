<?php

namespace App\Actions\Reports;

use App\Models\MonthlyReport;
use App\Support\Authorize;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateMonthlyReport
{
    /**
     * Buat satu laraporan bulanan baru per periode — duplikat periode ditolak.
     *
     * @param  array<string, mixed>  $data
     */
    public function __invoke(array $data, ?int $userId): MonthlyReport
    {
        Authorize::permission('monthly-reports.create', 'Tidak berwenang membuat laporan bulanan.');

        return DB::transaction(function () use ($data, $userId): MonthlyReport {
            $exists = MonthlyReport::query()
                ->where('report_month', $data['report_month'])
                ->where('report_year', $data['report_year'])
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'report_period' => 'Sudah ada laporan untuk periode '.$data['report_month'].'/'.$data['report_year'].'.',
                ]);
            }

            return MonthlyReport::create([
                'title' => $data['title'],
                'report_month' => (int) $data['report_month'],
                'report_year' => (int) $data['report_year'],
                'introduction' => null,
                'conclusion' => null,
                'created_by' => $userId,
            ]);
        });
    }
}
