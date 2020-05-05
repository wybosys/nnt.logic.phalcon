<?php

namespace Nnt\Core;

use Phalcon\Logger;
use Phalcon\Logger\Adapter\AbstractAdapter;
use Phalcon\Logger\Adapter\AdapterInterface;
use Phalcon\Logger\Adapter\Noop;

class Log
{
    public function __construct()
    {
        $this->_logger = new Logger('nnt');

        if (RedislogAdapter::IsValid()) {
            $t = new RedislogAdapter();
            $this->_logger->addAdapter('redis', $t);
        }

        if (SeaslogAdapter::IsValid()) {
            $t = new SeaslogAdapter();
            $this->_logger->addAdapter('seaslog', $t);
        }

        $t = new Noop();
        $this->_logger->addAdapter('noop', $t);
    }

    /**
     * @var Logger
     */
    private $_logger;

    static function debug($msg)
    {
        self::$_SHARED->_logger->debug($msg);
    }

    static function error(\Error $err)
    {
        self::$_SHARED->_logger->error(json_encode([
            'c' => $err->getCode(),
            'f' => $err->getFile(),
            'l' => $err->getLine(),
            'm' => $err->getMessage()
        ]));
    }

    static function info(string $msg)
    {
        self::$_SHARED->_logger->info($msg);
    }

    static function notice(string $msg)
    {
        self::$_SHARED->_logger->notice($msg);
    }

    static function warning(string $msg)
    {
        self::$_SHARED->_logger->warning($msg);
    }

    static function alert(string $msg)
    {
        self::$_SHARED->_logger->alert($msg);
    }

    static function emergency(string $msg)
    {
        self::$_SHARED->_logger->emergency($msg);
    }

    static function exception(\Throwable $exc)
    {
        self::$_SHARED->_logger->emergency(json_encode([
            'c' => $exc->getCode(),
            'f' => $exc->getFile(),
            'l' => $exc->getLine(),
            'm' => $exc->getMessage()
        ]));
    }

    static function log(int $type, string $msg)
    {
        self::$_SHARED->_logger->log($type, $msg);
    }

    /**
     * @var Log
     */
    static $_SHARED;
}

abstract class CustomAdapter extends AbstractAdapter
{
    function close(): bool
    {
        return true;
    }
}

class SeaslogAdapter extends CustomAdapter
{
    public function __construct()
    {
        \SeasLog::setBasePath(LOG_DIR . 'seaslog');
    }

    static function IsValid()
    {
        return extension_loaded("SeasLog");
    }

    private static function PLevel2SLevel(int $level): string
    {
        switch ($level) {
            case \Phalcon\Logger::DEBUG:
                return 'DEBUG';
            case \Phalcon\Logger::INFO:
                return 'INFO';
            case \Phalcon\Logger::NOTICE:
                return 'NOTICE';
            case \Phalcon\Logger::WARNING:
                return 'WARNING';
            case \Phalcon\Logger::ERROR:
                return 'ERROR';
            case \Phalcon\Logger::ALERT:
                return 'ALERT';
            case \Phalcon\Logger::CRITICAL:
                return 'CRITICAL';
            case \Phalcon\Logger::EMERGENCY:
                return 'EMERGENCY';
            default:
                return 'ALL';
        }
    }

    function commit(): AdapterInterface
    {
        \Seaslog::flushBuffer();
        return $this;
    }

    function process(\Phalcon\Logger\Item $item): void
    {
        $slevel = self::PLevel2SLevel($item->getType());
        \Seaslog::log($slevel, $item->getMessage());
    }
}

class RedislogAdapter extends CustomAdapter
{
    public function __construct()
    {
        $this->_key = gethostname();
        $this->_db = new \Redis();
        $this->_db->pconnect('logs', 6379, 1, "::phalcon::logs");
    }

    static function IsValid()
    {
        return !Config::IsLocal();
    }

    /**
     * @var \Redis
     */
    private $_db;
    private $_key;

    function process(\Phalcon\Logger\Item $item): void
    {
        $this->_db->select($item->getType());
        $this->_db->lPush($this->_key, $item->getMessage());
    }
}

Log::$_SHARED = new Log();
