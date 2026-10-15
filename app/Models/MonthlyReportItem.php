<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyReportItem extends Model
{
    protected $table = 'monthly_report_items';

    protected $fillable = [
        'monthly_report_id',
        'activity_id',
        'sequence',
        'uraian',
        'title_snapshot',
        'subject_name_snapshot',
        'performer_name_snapshot',
        'type_snapshot',
        'category_snapshot',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(MonthlyReport::class, 'monthly_report_id', 'id');
    }

    /**
     * Aktivitas asal item (bisa null bila sudah terhapus — snapshot tetap utuh).
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id', 'id');
    }
}
