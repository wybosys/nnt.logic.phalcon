<?php

namespace App\Controller;

class Config extends \Phalcon\Config
{
    static function Use($local, $devops, $devopsrelease)
    {
        if (self::IsLocal())
            return $local;
        if (self::IsDevops())
            return $devops;
        return $devopsrelease;
    }

    static function IsLocal(): bool
    {
        return getenv('DEVOPS') == null && getenv('DEVOPS_RELEASE') == null;
    }

    static function IsDevopsRelease(): bool
    {
        return getenv('DEVOPS_RELEASE') != null;
    }

    static function IsDevops(): bool
    {
        return getenv('DEVOPS_RELEASE') == null && getenv('DEVOPS') != null;
    }

}