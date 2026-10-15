<?php

namespace App\Enums;

enum ActivityType: string
{
    /**
     * Catatan otomatis operasi aplikasi (create/update/delete/relasi).
     */
    case Audit = 'AUDIT';

    /**
     * Catatan pekerjaan teknis manual (pemeliharaan, deployment, dll).
     */
    case Work = 'WORK';

    /**
     * Label tampilan: menegaskan sumber baris — sistem vs diinput manusia.
     */
    public function label(): string
    {
        return $this === self::Audit ? 'Sistem' : 'Manual';
    }
}
