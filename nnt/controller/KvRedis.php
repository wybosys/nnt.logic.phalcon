<?php

namespace Nnt\Controller;
require_once dirname(dirname(__DIR__)) . '/3rd/predis/Predis.php';

class KvRedis
{
    public function __construct($opts)
    {
        $host = $opts['host'];
        $port = isset($opts['port']) ? $opts['port'] : 6379;
        $prefix = isset($opts['prefix']) ? $opts['prefix'] : '';
        $index = isset($opts['index']) ? $opts['index'] : 0;
        $ops = [
            'prefix' => $prefix,
            'parameters' => [
                'database' => $index
            ]
        ];
        if (isset($opts['cluster']) && $opts['cluster'])
            $ops['cluster'] = 'redis';

        $this->_hdl = new \Predis\Client([
            'scheme' => 'tcp',
            'host' => $host,
            'port' => $port,
        ], $ops);
    }

    private $_hdl;

    function psetex($key, $ttl, $value)
    {
        return $this->_hdl->psetex($key, $ttl, $value);
    }

    function get($key)
    {
        return $this->_hdl->get($key);
    }

    function set($key, $value, $timeout = 0)
    {
        return $this->_hdl->set($key, $value, $timeout);
    }

    function setex($key, $ttl, $value)
    {
        return $this->_hdl->setex($key, $ttl, $value);
    }

    function setnx($key, $value)
    {
        return $this->_hdl->setnx($key, $value);
    }

    function del($key)
    {
        return $this->_hdl->del($key);
    }

    function exists($key)
    {
        return $this->_hdl->exists($key);
    }

    function incr($key)
    {
        return $this->_hdl->incr($key);
    }

    function incrByFloat($key, $increment)
    {
        return $this->_hdl->incrbyfloat($key, $increment);
    }

    function incrBy($key, $value)
    {
        return $this->_hdl->incrby($key, $value);
    }

    function decr($key)
    {
        return $this->_hdl->decr($key);
    }

    function decrBy($key, $value)
    {
        return $this->_hdl->decrby($key, $value);
    }

    function lPush($key, ...$values)
    {
        return $this->_hdl->lpush($key, $values);
    }

    function rPush($key, ...$values)
    {
        return $this->_hdl->rpush($key, $values);
    }

    function lPushx($key, $value)
    {
        return $this->_hdl->lpushx($key, $value);
    }

    function rPushx($key, $value)
    {
        return $this->_hdl->rpushx($key, $value);
    }

    function lPop($key)
    {
        return $this->_hdl->lpop($key);
    }

    function rPop($key)
    {
        return $this->_hdl->rpop($key);
    }

    function blPop(array $keys, $timeout)
    {
        return $this->_hdl->blpop($keys, $timeout);
    }

    function brPop(array $keys, $timeout)
    {
        return $this->_hdl->brpop($keys, $timeout);
    }

    function lLen($key)
    {
        return $this->_hdl->llen($key);
    }

    function lIndex($key, $index)
    {
        return $this->_hdl->lindex($key, $index);
    }

    function lSet($key, $index, $value)
    {
        return $this->_hdl->lset($key, $index, $value);
    }

    function lRange($key, $start, $end)
    {
        return $this->_hdl->lrange($key, $start, $end);
    }

    function lTrim($key, $start, $stop)
    {
        return $this->_hdl->ltrim($key, $start, $stop);
    }

    function lInsert($key, $position, $pivot, $value)
    {
        return $this->_hdl->linsert($key, $position, $pivot, $value);
    }

    function getSet($key, $value)
    {
        return $this->_hdl->getset($key, $value);
    }

    function randomKey()
    {
        return $this->_hdl->randomkey();
    }

    function select($dbindex)
    {
        return $this->_hdl->select($dbindex);
    }

    function expire($key, $ttl)
    {
        return $this->_hdl->expire($key, $ttl);
    }

    function pExpire($key, $ttl)
    {
        return $this->_hdl->pexpire($key, $ttl);
    }

    function expireAt($key, $timestamp)
    {
        return $this->_hdl->expireat($key, $timestamp);
    }

    function pExpireAt($key, $timestamp)
    {
        return $this->_hdl->pexpireat($key, $timestamp);
    }

    function auth($password)
    {
        return $this->_hdl->auth($password);
    }

    function type($key)
    {
        return $this->_hdl->type($key);
    }

    function append($key, $value)
    {
        return $this->_hdl->append($key, $value);
    }

    function getRange($key, $start, $end)
    {
        return $this->_hdl->getrange($key, $start, $end);
    }

    function strlen($key)
    {
        return $this->_hdl->strlen($key);
    }

    function ttl($key)
    {
        return $this->_hdl->ttl($key);
    }

    function pttl($key)
    {
        return $this->_hdl->pttl($key);
    }

    function persist($key)
    {
        return $this->_hdl->persist($key);
    }

    function mset(array $array)
    {
        return $this->_hdl->mset($array);
    }
}
