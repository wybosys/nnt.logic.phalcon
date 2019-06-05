<?php

namespace Nnt\Core;

class MapT
{
    static function Convert(&$arr, callable $conv, $skipnull = false): array
    {
        $r = [];
        if ($arr) {
            foreach ($arr as $k => $v) {
                $t = $conv($v, $k);
                if ($t === null && $skipnull)
                    continue;
                $r[$k] = $t;
            }
        }
        return $r;
    }
}
