<?php

namespace App\Enums;

enum WafTech: string
{
    case aaWaf = 'aaWAF';

    case ModSecurity = 'ModSecurity';

    case Coraza = 'Coraza';

    case SafeLine = 'SafeLine';

    case BunkerWeb = 'BunkerWeb';

    case Naxsi = 'NAXSI';

    case OpenWaf = 'OpenWAF';

    case Wallarm = 'Wallarm';
}
