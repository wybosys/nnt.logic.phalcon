<?php

namespace Nnt\Core;

class ArrayT
{
    static function IndexOf(&$arr, $obj): int
    {
        $fnd = array_search($obj, $arr);
        return $fnd === false ? -1 : $fnd;
    }

    /**
     * @param $arr array
     * @param $obj
     * @return bool
     */
    static function RemoveObject(&$arr, $obj): bool
    {
        if ($obj == null || $arr == null) {
            return false;
        }
        $idx = self::IndexOf($arr, $obj);
        if ($idx == -1) {
            return false;
        }
        array_splice($arr, $idx, 1);
        return true;
    }

    /**
     * @param $arr array
     * @param callable $conv (v, i)
     * @param bool $skipnull
     * @return array
     */
    static function Convert(&$arr, callable $conv, $skipnull = false): array
    {
        $r = [];
        if ($arr) {
            foreach ($arr as $i => $v) {
                $t = $conv($v, $i);
                if ($t === null && $skipnull)
                    continue;
                $r[] = $t;
            }
        }
        return $r;
    }

    /**
     * @param $arr array
     * @param callable $filter (v, i)
     * @return mixed|null
     */
    static function QueryObject(&$arr, callable $filter)
    {
        if ($arr) {
            foreach ($arr as $i => $v) {
                if ($filter($v, $i)) {
                    return $v;
                }
            }
        }
        return null;
    }

    /**
     * @param $arr array
     * @param callable $func (v, i)
     * @param int $def
     * @return int
     */
    static function QueryIndex(&$arr, callable $func, $def = -1): int
    {
        $fnd = $def;
        if ($arr) {
            foreach ($arr as $i => $v) {
                if ($func($v, $i)) {
                    $fnd = $i;
                    break;
                }
            }
        }
        return $fnd;
    }

    /**
     * @param $arr array
     * @param $proc callable $proc(v, i)
     * @return bool
     */
    static function Each(&$arr, callable $proc): bool
    {
        if ($arr) {
            foreach ($arr as $i => $v) {
                if (!$proc($v, $i)) {
                    return false;
                }
            }
        }
        return true;
    }
}
