<?php

namespace App\Models;

use App\Models\Concerns\GeneratesCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlyReport extends Model
{
    use GeneratesCode;

    protected $table = 'monthly_reports';

    protected static string $codeColumn = 'report_id';

    protected static string $codePrefix = 'RPT-';

    protected static int $codeLength = 9;

    protected $fillable = [
        'report_id',
        'title',
        'report_month',
        'report_year',
        'introduction',
        'conclusion',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'report_month' => 'integer',
            'report_year' => 'integer',
        ];
    }

    /**
     * Rentang periode (awal & akhir bulan) sebagai batas pencarian aktivitas default.
     */
    public function periodStart(): string
    {
        return sprintf('%04d-%02d-01', $this->report_year, $this->report_month);
    }

    public function periodEnd(): string
    {
        return date('Y-m-t', strtotime($this->periodStart()));
    }

    public static function periodeLabel(int $month, int $year): string
    {
        return [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ][$month].' '.$year;
    }

    public function periodeText(): string
    {
        return self::periodeLabel($this->report_month, $this->report_year);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MonthlyReportItem::class, 'monthly_report_id', 'id')->orderBy('sequence');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $report) {
            if (blank($report->title)) {
                $report->title = 'Laporan Aktivitas '.static::periodeLabel($report->report_month, $report->report_year);
            }
        });
    }
}
