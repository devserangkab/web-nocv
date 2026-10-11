<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Development = 'Development';

    case Production = 'Production';

    case Maintenance = 'Maintenance';

    case Retired = 'Retired';

    case Archived = 'Archived';
}
