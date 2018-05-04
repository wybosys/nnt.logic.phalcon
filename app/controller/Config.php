<?php

namespace App\Controller;

class Config extends \Phalcon\Config
{
    static $IS_LOCAL;
    static $IS_DEVOPS;
    static $IS_DEVOPSRELEASE;

    static function Use($local, $devops, $devopsrelease)
    {
        if (self::$IS_LOCAL)
            return $local;
        if (self::$IS_DEVOPS)
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

Config::$IS_LOCAL = Config::IsLocal();
Config::$IS_DEVOPS = Config::IsDevops();
Config::$IS_DEVOPSRELEASE = Config::IsDevopsRelease();