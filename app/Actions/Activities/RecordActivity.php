<?php

namespace App\Actions\Activities;

use App\Enums\ActivityType;
use App\Models\Activity;
use Illuminate\Database\Eloquent\Model;

class RecordActivity
{
    /**
     * Catat satu entri aktivitas (audit otomatis atau work log manual).
     *
     * @param  Model|null  $subject  Entitas terkait (polymorphic), null bila bebas.
     */
    public function __invoke(
        ?Model $subject,
        string $activity,
        ?ActivityType $type = null,
        ?string $category = null,
        ?string $description = null,
        ?string $finishedAt = null,
        ?int $userId = null,
    ): Activity {
        return Activity::create([
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->id,
            'user_id' => $userId ?? auth()->id(),
            'activity' => $activity,
            'description' => $description,
            'type' => $type ?? ActivityType::Work,
            'category' => $category,
            'finished_at' => $finishedAt,
        ]);
    }

    /**
     * Audit log otomatis: operasi create/update/delete oleh pengguna saat ini.
     */
    public function audit(Model $subject, string $activity, ?string $description = null): Activity
    {
        return $this($subject, $activity, ActivityType::Audit, null, $description);
    }
}
