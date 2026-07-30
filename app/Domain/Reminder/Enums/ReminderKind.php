<?php

namespace App\Domain\Reminder\Enums;

enum ReminderKind: string
{
    case AutomaticSevenDays = 'automatic_7_days';
    case AutomaticThreeDays = 'automatic_3_days';
    case Manual = 'manual';
}
