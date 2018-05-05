<?php

namespace App\Controller;

define("CONFIG_IS_LOCAL", Config::IsLocal());
define("CONFIG_IS_DEVOPS", Config::IsDevops());
define("CONFIG_IS_DEVOPSRELEASE", Config::IsDevopsRelease());

class Config extends \Phalcon\Config
{
    static function Use($local, $devops, $devopsrelease)
    {
        if (CONFIG_IS_DEVOPS)
            return $local;
        if (CONFIG_IS_DEVOPS)
            return $devops;
        return $devopsrelease;
    }

    static function IsLocal(): bool
    {
        return !getenv('DEVOPS') != null && !getenv('DEVOPS_RELEASE') != null;
    }

    static function IsDevopsRelease(): bool
    {
        return getenv('DEVOPS_RELEASE') != null;
    }

    static function IsDevops(): bool
    {
        return !self::IsDevopsRelease() && getenv('DEVOPS') != null;
    }

}