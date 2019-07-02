<?php

namespace Nnt\Core;

class UrlT
{
    static function Encode(array $arr): string
    {
        ksort($arr);
        $t = [];
        foreach ($arr as $k => $v) {
            $t[] = $k . '=' . $v;
        }
        return implode('&', $t);
    }
}