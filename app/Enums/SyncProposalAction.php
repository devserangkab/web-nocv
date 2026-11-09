<?php

namespace App\Enums;

/**
 * Jenis usulan perubahan pada proposal sinkronisasi.
 */
enum SyncProposalAction: string
{
    case Create = 'create';

    case Update = 'update';

    /**
     * Label Indonesia untuk UI (badge).
     */
    public function label(): string
    {
        return match ($this) {
            self::Create => 'Buat baru',
            self::Update => 'Perbarui',
        };
    }
}
