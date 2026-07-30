<?php

namespace App\Domain\Reminder\Enums;

enum ReminderStatus: string
{
    case Scheduled = 'scheduled';
    case Processing = 'processing';
    case Sent = 'sent';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
