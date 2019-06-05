<?php

namespace Nnt\Core;

class Config extends \Phalcon\Config
{
    static function Use($local, $devopsdevelop, $devopsrelease)
    {
        if (self::IsLocal())
            return $local;
        if (self::IsDevopsDevelop())
            return $devopsdevelop;
        return $devopsrelease;
    }

    static function IsLocal(): bool
    {
        return getenv('DEVOPS') == null;
    }

    static function IsDevops(): bool
    {
        return getenv('DEVOPS') != null;
    }

    static function IsDevopsRelease(): bool
    {
        return getenv('DEVOPS_RELEASE') != null;
    }

    static function IsDevopsDevelop(): bool
    {
        return getenv('DEVOPS_RELEASE') == null && getenv('DEVOPS') != null;
    }

    // model含有最大fields的个数
    const MODEL_FIELDS_MAX = 100;
}
