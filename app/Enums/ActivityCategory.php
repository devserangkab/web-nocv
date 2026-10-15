<?php

namespace App\Enums;

enum ActivityCategory: string
{
    case Maintenance = 'Pemeliharaan';
    case Deployment = 'Deployment';
    case OsUpdate = 'Update OS';
    case ConfigurationChange = 'Perubahan Konfigurasi';
    case Incident = 'Penanganan Gangguan';
    case Inspection = 'Pemeriksaan Infrastruktur';
    case Other = 'Lainnya';
}
