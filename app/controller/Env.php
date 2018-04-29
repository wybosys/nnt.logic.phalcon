<?php

namespace App\Controller;

define('IS_LOCAL', Env::IsLocal());
define('IS_DEVOPS', Env::IsDevops());
define('IS_DEVOPSRELEASE', Env::IsDevopsRelease());

class Env
{
    static function Use($local, $devops, $devopsrelease)
    {
        if (IS_LOCAL)
            return $local;
        if (IS_DEVOPS)
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