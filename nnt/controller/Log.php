<?php

namespace Nnt\Controller;

use Nnt\Model\Kernel;
use Phalcon\Logger\Adapter\File;
use Phalcon\Logger\AdapterInterface;
use Phalcon\Logger\FormatterInterface;

define('LOG_DIR', APP_DIR . '/logs/');
Kernel::EnsureDir(LOG_DIR);

class Log
{
    public function __construct()
    {
        if (RedislogAdapter::IsValid()) {
            $this->_log = new RedislogAdapter();
            $this->_batch = true;
        } else if (SeaslogAdapter::IsValid()) {
            $this->_log = new SeaslogAdapter();
            $this->_batch = true;
        } else {
            $this->_log = new DailyFile();
            $this->_batch = true;
        }

        if ($this->_batch)
            $this->_log->begin();
    }

    public function __destruct()
    {
        if ($this->_batch)
            $this->_log->commit();
    }

    /**
     * @var AdapterInterface
     */
    private $_log;

    /*
     * @var bool
     */
    private $_batch;

    static function debug($message)
    {
        self::$_SHARED->_log->debug($message);
    }

    static function error(\Error $err)
    {
        self::$_SHARED_log->error(json_encode([
            "c" => $err->getCode(),
            "f" => $err->getFile(),
            "l" => $err->getLine(),
            "m" => $err->getMessage()
        ]));
    }

    static function info($message)
    {
        self::$_SHARED->_log->info($message);
    }

    static function notice($message)
    {
        self::$_SHARED->_log->notice($message);
    }

    static function warning($message)
    {
        self::$_SHARED->_log->warning($message);
    }

    static function alert($message)
    {
        self::$_SHARED->_log->alert($message);
    }

    static function emergency($message)
    {
        self::$_SHARED->_log->emergency($message);
    }

    static function exception(\Exception $exc)
    {
        self::$_SHARED->_log->emergency(json_encode([
            "c" => $exc->getCode(),
            "f" => $exc->getFile(),
            "l" => $exc->getLine(),
            "m" => $exc->getMessage()
        ]));
    }

    static function log(int $type, string $message)
    {
        self::$_SHARED->_log->log($type, $message);
    }

    /**
     * @var Log
     */
    static $_SHARED;
}

class DailyFile extends File
{
    static function IsValid()
    {
        return true;
    }

    public function __construct()
    {
        // 使用时期时间初始化
        $dt = new \DateTime();
        $name = 'phalcon_' . $dt->format('Y-m-d') . '.log';
        parent::__construct(LOG_DIR . $name);
    }
}

class SeaslogAdapter implements AdapterInterface
{
    public function __construct()
    {
        \SeasLog::setBasePath(LOG_DIR . 'seaslog');
    }

    static function IsValid()
    {
        return extension_loaded("SeasLog");
    }

    private $_formatter;

    function setFormatter(FormatterInterface $formatter)
    {
        $this->_formatter = $formatter;
    }

    function getFormatter(): FormatterInterface
    {
        return $this->_formatter;
    }

    private $_level = \Phalcon\Logger::SPECIAL;

    function setLogLevel($level): AdapterInterface
    {
        $this->_level = $level;
        return $this;
    }

    function getLogLevel(): int
    {
        return $this->_level;
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

    function log($type, $message = null, array $context = null): AdapterInterface
    {
        if ($type > $this->_level)
            return $this;
        $slevel = self::PLevel2SLevel($type);
        \Seaslog::log($slevel, $message);
        return $this;
    }

    function begin(): AdapterInterface
    {
        return $this;
    }

    function commit(): AdapterInterface
    {
        \Seaslog::flushBuffer();
        return $this;
    }

    function rollback(): AdapterInterface
    {
        return $this;
    }

    function close()
    {
        return true;
    }

    function debug($message, array $context = null): AdapterInterface
    {
        \Seaslog::debug($message);
        return $this;
    }

    function error($message, array $context = null): AdapterInterface
    {
        \Seaslog::error($message);
        return $this;
    }

    function info($message, array $context = null): AdapterInterface
    {
        \Seaslog::info($message);
        return $this;
    }

    function notice($message, array $context = null): AdapterInterface
    {
        \Seaslog::notice($message);
        return $this;
    }

    function warning($message, array $context = null): AdapterInterface
    {
        \Seaslog::warning($message);
        return $this;
    }

    function alert($message, array $context = null): AdapterInterface
    {
        \Seaslog::alert($message);
        return $this;
    }

    function emergency($message, array $context = null): AdapterInterface
    {
        \Seaslog::emergency($message);
        return $this;
    }
}

class RedislogAdapter implements AdapterInterface
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

    private $_formatter;

    function setFormatter(FormatterInterface $formatter)
    {
        $this->_formatter = $formatter;
    }

    function getFormatter(): FormatterInterface
    {
        return $this->_formatter;
    }

    private $_level = \Phalcon\Logger::SPECIAL;

    function setLogLevel($level): AdapterInterface
    {
        $this->_level = $level;
        return $this;
    }

    function getLogLevel(): int
    {
        return $this->_level;
    }

    /**
     * @var \Redis
     */
    private $_db;
    private $_key;

    function log($type, $message = null, array $context = null): AdapterInterface
    {
        if ($type > $this->_level)
            return $this;
        $this->_db->select($type);
        $this->_db->lPush($this->_key, $message);
        return $this;
    }

    function begin(): AdapterInterface
    {
        return $this;
    }

    function commit(): AdapterInterface
    {
        return $this;
    }

    function rollback(): AdapterInterface
    {
        return $this;
    }

    function close()
    {
        return true;
    }

    function debug($message, array $context = null): AdapterInterface
    {
        $this->_db->select(\Phalcon\Logger::DEBUG);
        $this->_db->lPush($this->_key, $message);
        return $this;
    }

    function error($message, array $context = null): AdapterInterface
    {
        $this->_db->select(\Phalcon\Logger::ERROR);
        $this->_db->lPush($this->_key, $message);
        return $this;
    }

    function info($message, array $context = null): AdapterInterface
    {
        $this->_db->select(\Phalcon\Logger::INFO);
        $this->_db->lPush($this->_key, $message);
        return $this;
    }

    function notice($message, array $context = null): AdapterInterface
    {
        $this->_db->select(\Phalcon\Logger::NOTICE);
        $this->_db->lPush($this->_key, $message);
        return $this;
    }

    function warning($message, array $context = null): AdapterInterface
    {
        $this->_db->select(\Phalcon\Logger::WARNING);
        $this->_db->lPush($this->_key, $message);
        return $this;
    }

    function alert($message, array $context = null): AdapterInterface
    {
        $this->_db->select(\Phalcon\Logger::ALERT);
        $this->_db->lPush($this->_key, $message);
        return $this;
    }

    function emergency($message, array $context = null): AdapterInterface
    {
        $this->_db->select(\Phalcon\Logger::EMERGENCY);
        $this->_db->lPush($this->_key, $message);
        return $this;
    }
}

Log::$_SHARED = new Log();
