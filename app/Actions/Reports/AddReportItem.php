<?php

namespace App\Actions\Reports;

use App\Models\Activity;
use App\Models\MonthlyReport;
use App\Models\MonthlyReportItem;

class AddReportItem
{
    /**
     * Tambah satu item ke uraian kerja laporan dari aktivitas (Sistem/Manual)
     * — dengan uraian default dari deskripsi/judul aktivitas, urutan akhir.
     */
    public function __invoke(MonthlyReport $report, ?Activity $activity, ?string $uraian = null, ?int $sequence = null): ?MonthlyReportItem
    {
        if ($activity === null) {
            return null;
        }

        // Snapshot identifikasi jenis & kategori
        $typeSnapshot = $activity->type?->value ?? 'WORK';

        $existing = $report->items()->count();

        $item = $report->items()->create([
            'activity_id' => $activity->id,
            'sequence' => $sequence ?? ($existing + 1),
            'uraian' => blank($uraian) ? ($activity->description ?? $activity->activity) : $uraian,
            'title_snapshot' => $activity->activity,
            'subject_name_snapshot' => $activity->subject?->name ?? $activity->subject?->server_name ?? null,
            'performer_name_snapshot' => $activity->user?->name ?? null,
            'type_snapshot' => $typeSnapshot,
            'category_snapshot' => $activity->category,
        ]);

        return $item;
    }
}
