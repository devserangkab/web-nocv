<?php

namespace App\Enums;

/**
 * Tipe sinkronisasi — kunci per sumber data eksternal. Nilai dipakai
 * sebagai penghubung halaman, kartu, run, dan proposal (sink_indexes).
 */
enum SyncSource: string
{
    case AaPanelWebsite = 'aapanel_website';

    case ProxmoxVms = 'proxmox_vms';

    case AaPanelDatabase = 'aapanel_database';

    /**
     * Label Indonesia untuk UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::AaPanelWebsite => 'Situs Web — aaPanel',
            self::ProxmoxVms => 'Virtual Machine — Proxmox',
            self::AaPanelDatabase => 'Database — aaPanel',
        };
    }
}
