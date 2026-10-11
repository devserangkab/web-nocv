<?php

namespace App\Enums;

enum DeveloperType: string
{
    case Programmer = 'Programmer';

    case Designer = 'Designer';

    case Fullstack = 'Fullstack';

    case SystemAdministrator = 'System Administrator';
}
