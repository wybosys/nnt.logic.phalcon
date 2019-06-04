<?php

namespace Nnt\Core;

const CHARS = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

class StringT
{
    public static function Random(int $len): string
    {
        $r = '';
        while (strlen($r) < $len) {
            $r .= substr(CHARS, mt_rand() % strlen($r), 1);
        }
        return $r;
    }
}
