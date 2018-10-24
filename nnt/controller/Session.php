<?php

namespace Nnt\Controller;

use Nnt\Model\Logic;

abstract class Session
{
    // 请求失败抛出异常
    abstract static function Fetch(Logic &$m);

    // 请求失败返回null
    static function Get(Logic &$m)
    {
        try {
            return self::Fetch($m);
        } catch (\Throwable $ex) {
        }
        return null;
    }
}
