<?php

namespace App\Enums;

enum ApplicationType: string
{
    case Website = 'Website';

    case Mobile = 'Mobile';

    case Desktop = 'Desktop';

    case Api = 'API';
}
