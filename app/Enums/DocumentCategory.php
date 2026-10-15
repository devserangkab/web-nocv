<?php

namespace App\Enums;

enum DocumentCategory: string
{
    case Procurement = 'Dokumen Pengadaan';
    case Design = 'Desain Infrastruktur';
    case Installation = 'Dokumentasi Instalasi';
    case Deployment = 'Dokumentasi Deployment';
    case Sop = 'SOP';
    case Maintenance = 'Dokumen Pemeliharaan';
    case Other = 'Lainnya';
}
