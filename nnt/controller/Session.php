<?php

namespace Nnt\Controller;

use Nnt\Model\Logic;

abstract class Session
{
    // 请求失败抛出异常
    abstract static function Fetch(Logic $m);

    /**
     * @param Logic $m
     * @return Logic 请求失败返回 null，成功则返回Logic
     */
    abstract static function Get(Logic $m);

    protected static function ImplGet($clazz, Logic $m)
    {
        try {
            return $clazz::Fetch($m);
        } catch (\Throwable $ex) {
        }
        return null;
    }
}
