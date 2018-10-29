<?php

namespace Nnt\Model;

class Uuid
{
    static function Generate(): string
    {
        return str_replace("-", "", uuid_create());
    }
}
