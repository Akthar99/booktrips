<?php

namespace App\Enums;

enum ScheduleType: string
{
    case Always = 'always';
    case Range = 'range';
}
