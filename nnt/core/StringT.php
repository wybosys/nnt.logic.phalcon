<?php

namespace Nnt\Core;

const CHARS = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
const LEN_CHARS = 62;

class StringT
{
    /**
     * 生成随机字符串
     * @param int $len 随机长度
     * @return string
     */
    public static function Random(int $len = 12): string
    {
        $r = '';
        while (strlen($r) < $len) {
            $r .= substr(CHARS, random_int(0, LEN_CHARS - 1), 1);
        }
        return $r;
    }
}
