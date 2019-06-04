<?php

namespace Nnt\Core;

class DatetimeT
{
    public static function Present(): string
    {
        return date('Y-m-d H:i:s', time());
    }
}
