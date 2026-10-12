<?php

namespace App\Enums;

enum VmsStatus: string
{
    case On = 'ON';

    case Off = 'OFF';

    case Destroyed = 'DESTROYED';
}
