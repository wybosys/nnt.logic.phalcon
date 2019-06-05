<?php

namespace Nnt\Store;

use Nnt\Core\ArrayT;
use Nnt\Core\Kernel;
/**
 * {
 *     "and": [
 *         "abc": { "gt": 100 }
 *     ]
 * }
 */

const KEYWORDS = ['and', 'or'];
const OPERATORS = ['gt', 'gte', 'eq', 'not', 'lt', 'lte'];

class Filter
{
    function clear()
    {
        $this->ands = [];
        $this->ors = [];
        $this->key = null;
    }

    // 解析，如果解析全新，需要手动调用clear方法
    function parse(&$jsobj): bool
    {
        foreach ($jsobj as $k => $v) {
            if ($k === 'and') {
                if (is_array($v)) {
                    $suc = ArrayT::Each($v, function ($e) {
                        $sub = new Filter();
                        if ($sub->parse($e)) {
                            $this->ands[] = $sub;
                        } else {
                            return false;
                        }
                        return true;
                    });
                    if (!$suc) {
                        return false;
                    }
                } else {
                    return false;
                }
            } else if ($k === 'or') {
                if (is_array($v)) {
                    $suc = ArrayT::Each($v, function ($e) {
                        $sub = new Filter();
                        if ($sub->parse($e)) {
                            $this->ors[] = $sub;
                        } else {
                            return false;
                        }
                        return true;
                    });
                    if (!$suc) {
                        return false;
                    }
                } else {
                    return false;
                }
            } else if (in_array($k, OPERATORS)) {
                $this->operator = $k;
                $this->value = $v;
            } else {
                $this->key = $k;
                foreach ($v as $sk => $sv) {
                    $sub = new Filter();
                    $sub->operator = $sk;
                    $sub->value = $sv;
                    $this->ands[] = $sub;
                }
            }
        }
        return true;
    }

    /**
     * @var array Filter
     */
    public $ands = [];

    /**
     * @var array Filter
     */
    public $ors = [];

    /**
     * @var string
     */
    public $key = null;

    /**
     * @var string
     */
    public $operator = null;

    /**
     * @var object
     */
    public $value = null;

    /**
     * @param string $str
     * @return Filter|null
     */
    static function ParseString(string $str)
    {
        $jsobj = Kernel::toJsonObj($str);
        if (!$jsobj) {
            return null;
        }
        $r = new Filter();
        if (!$r->parse($jsobj)) {
            return null;
        }
        return $r;
    }

    protected function attachToJsobj(array &$obj)
    {
        if ($this->key === null) {
            if (count($this->ands)) {
                if (!isset($obj['and'])) {
                    $obj['and'] = [];
                }
                foreach ($this->ands as $e) {
                    $ref = [];
                    $obj['and'][] = $ref;

                    $e->attachToJsobj($ref);
                }
            }

            if (count($this->ors)) {
                if (!isset($obj['or'])) {
                    $obj['or'] = [];
                }
                foreach ($this->ors as $e) {
                    $ref = [];
                    $obj['or'][] = $ref;

                    $e->attachToJsobj($ref);
                }
            }

            if ($this->operator !== null && $this->value !== null) {
                $obj[$this->operator] = $this->value;
            }
        }

        if ($this->key) {
            $ref = [];
            $obj[$this->key] = $ref;

            if (count($this->ands)) {
                if (!isset($ref['and'])) {
                    $ref['and'] = [];
                }
                foreach ($this->ands as $e) {
                    $t = [];
                    $ref['and'][] = $t;

                    $e->attachToJsobj($t);
                }
            }

            if (count($this->ors)) {
                if (!isset($ref['or'])) {
                    $ref['or'] = [];
                }
                foreach ($this->ors as $e) {
                    $t = [];
                    $ref['or'][] = $t;

                    $e->attachToJsobj($t);
                }
            }
        }
    }

    function __toString(): string
    {
        $r = [];
        $this->attachToJsobj($r);
        return Kernel::toJson($r);
    }
}
