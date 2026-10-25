<?php

namespace App\Enums;

enum ApplicationOwnership: string
{
    /**
     * Dibuat & dikelola tim NOC internal.
     */
    case InHouse = 'Internal NOC';

    /**
     * Dibuat vendor eksternal, sudah serah terima, kini dikelola tim NOC.
     */
    case VendorHandover = 'Serah Terima Vendor';

    /**
     * Dibuat & dikelola penuh oleh vendor eksternal.
     */
    case VendorManaged = 'Dikelola Vendor';

    /**
     * Aplikasi open source (Nextcloud, Zimbra, Wazuh, TileServer GL, dll).
     */
    case OpenSource = 'Open Source';
}
