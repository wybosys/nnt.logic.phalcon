<?php

namespace Nnt\Model;

class Kernel
{
    static function toJsonObj(string $str, $def = null, $asso = false)
    {
        try {
            return json_decode($str, $asso);
        } catch (\Throwable $ex) {
        }
        return $def;
    }

    static function toJson($obj, $def = "")
    {
        try {
            return json_encode($obj);
        } catch (\Throwable $ex) {
        }
        return $def;
    }

    static function EnsureDir($path)
    {
        if (is_dir($path))
            return;
        if (!mkdir($path)) {
            throw new \Exception("创建 $path 失败 ");
        }
    }
}
