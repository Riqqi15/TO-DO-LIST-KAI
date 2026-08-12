<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class Wib
{
    public const TIMEZONE = 'Asia/Jakarta';

    public const DATE_FORMAT = 'Y-m-d';

    public const DATE_TIME_FORMAT = 'Y-m-d H:i';

    /**
     * Interpret a user supplied date string as WIB and convert it to UTC.
     */
    public static function toUtc(string $value): Carbon
    {
        return Carbon::parse($value, self::TIMEZONE)->utc();
    }

    public static function format(\DateTimeInterface $value, string $format = self::DATE_TIME_FORMAT): string
    {
        return Carbon::instance($value)->timezone(self::TIMEZONE)->format($format);
    }

    public static function formatDate(\DateTimeInterface $value): string
    {
        return self::format($value, self::DATE_FORMAT);
    }
}
