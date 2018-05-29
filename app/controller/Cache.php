<?php

namespace App\Controller;

class Cache
{

    function __construct()
    {
        $this->_db = new \Redis();
        $this->_db->connect('localhost', 26379);
        $this->_db->select(REDIS_CACHE);
    }

    static function IsEnabled(): bool
    {
        return Config::IsDevops();
    }

    function load($key)
    {
        return $this->_db->get($key);
    }

    function save($key, $str, $ttl)
    {
        if ($ttl != 0) {
            $this->_db->setex($key, $ttl, $str);
        } else {
            $this->_db->set($key, $str);
        }
    }

    private $_db;
}

const REDIS_CACHE = 18;