<?php

namespace App\Enums;

/**
 * Status proposal sinkronisasi: menunggu persetujuan, sudah diterapkan, atau ditolak.
 */
enum SyncProposalStatus: string
{
    case Pending = 'pending';

    case Applied = 'applied';

    case Rejected = 'rejected';

    /**
     * Label Indonesia untuk UI (badge).
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Applied => 'Diterapkan',
            self::Rejected => 'Ditolak',
        };
    }
}
