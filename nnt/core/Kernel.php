<?php

namespace Nnt\Core;

class Kernel
{
    public static function UUID(): string
    {
        return str_replace("-", "", uuid_create());
    }
}
