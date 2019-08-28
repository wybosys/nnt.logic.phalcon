<?php

namespace Nnt\Core;

class DatetimeT
{
    static function Present(): string
    {
        return date('Y-m-d H:i:s', time());
    }

    static function Format(string $fmt, $seconds): string
    {
        return date($fmt, $seconds);
    }

    static function Now(): int
    {
        return time();
    }

    static function Current(): float
    {
        return gettimeofday(true);
    }
}
