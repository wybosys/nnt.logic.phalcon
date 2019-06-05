<?php

namespace Nnt\Store;

class State
{
    /**
     * @var bool
     */
    public $and = false;

    /**
     * @var bool
     */
    public $or = false;

    /**
     * @var string
     */
    public $key;
}

// 和Filter中定义的OPERATORS保持一一对应
const MYSQL_OPERATORS = ['>', '>=', '=', '!=', '<', '<='];

class RMysql
{
    static function ConvertOperator(string $cmp): string
    {
        return MYSQL_OPERATORS[array_search($cmp, OPERATORS)];
    }

    /**
     * @param Filter $f
     * @param \Phalcon\Mvc\Model\Criteria $q
     * @param State $state
     */
    static function Translate(Filter $f, \Phalcon\Mvc\Model\Criteria $q, $state = null)
    {
        if ($f->key === null) {
            if ($f->ands) {
                $st = new State();
                $st->and = true;
                foreach ($f->ands as $e) {
                    self::Translate($e, $q, $st);
                }
            }

            if ($f->ors) {
                $st = new State();
                $st->or = true;
                foreach ($f->ors as $e) {
                    self::Translate($e, $q, $st);
                }
            }

            if ($f->operator !== null && $f->value !== null) {
                $oper = self::ConvertOperator($f->operator);
                if ($state->and) {
                    $q->andWhere("$state->key $oper :$state->key:", [
                        $state->key => $f->value
                    ]);
                } else {
                    $q->orWhere("$state->key $oper :$state->key:", [
                        $state->key => $f->value
                    ]);
                }
            }
        }

        if ($f->key) {
            $st = new State();
            $st->key = $f->key;

            if ($f->ands) {
                $st->and = true;
                foreach ($f->ands as $e) {
                    self::Translate($e, $q, $st);
                }
            }
        }
    }
}
