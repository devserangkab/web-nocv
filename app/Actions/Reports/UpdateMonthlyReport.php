<?php

namespace App\Actions\Reports;

use App\Models\Activity;
use App\Models\MonthlyReport;
use App\Models\MonthlyReportItem;
use App\Support\Authorize;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UpdateMonthlyReport
{
    /**
     * Sync konten laporan:
     *  - Pendahuluan & Kesimpulan (tiptap HTML)
     *  - uraian per item (tiptap HTML), termasuk yang masih pending
     *  - item baru (hasil seleksi dari halaman editor)
     */
    public function __invoke(MonthlyReport $report, array $data): MonthlyReport
    {
        Authorize::monthlyReportOwn($report);

        return DB::transaction(function () use ($report, $data): MonthlyReport {
            $data = Validator::make($data, static::rules(), static::messages())->validate();

            $report->update([
                'title' => $data['title'],
                'introduction' => blank($data['introduction']) ? null : $data['introduction'],
                'conclusion' => blank($data['conclusion']) ? null : $data['conclusion'],
            ]);

            $nextSequence = (int) ($report->items()->max('sequence') ?? 0) + 1;

            foreach ($data['newItems'] ?? [] as $row) {
                /** @var Activity|null $activity */
                $activity = Activity::with('subject')->find($row['activity_id'] ?? null);

                app(AddReportItem::class)($report, $activity, $row['uraian'] ?? null, $nextSequence++);
            }

            // Uraian per item eksisting
            foreach ($data['uraian'] ?? [] as $itemId => $uraian) {
                MonthlyReportItem::query()
                    ->where('monthly_report_id', $report->id)
                    ->where('id', $itemId)
                    ->update(['uraian' => blank($uraian) ? null : $uraian]);
            }

            return $report;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'title' => ['required', 'max:255'],
            'introduction' => ['nullable', 'string'],
            'conclusion' => ['nullable', 'string'],
            'uraian' => ['array'],
            'uraian.*' => ['nullable', 'string'],
            'newItems' => ['array'],
            'newItems.*.activity_id' => ['required', 'integer'],
            'newItems.*.uraian' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'title.required' => 'Judul laporan wajib diisi.',
            'title.max' => 'Judul laporan maksimal 255 karakter.',
        ];
    }
}
