<?php

namespace App\Controller;

// 性能测试
class Profiler
{
    function __construct()
    {
        Profiler::$_shared = $this;

        $this->config = [
            'mode' => 'development',
            'save.handler' => 'mongodb',
            'db.host' => Config::Use('mongodb://develop.91egame.com:27017', 'mongodb://mongo:27017', 'mongodb://mongo:27017'),
            'db.db' => 'profiler',
            'db.options' => array(),
            'templates.path' => dirname(__DIR__) . '/src/templates',
            'date.format' => 'M jS H:i:s',
            'detail.count' => 6,
            'page.limit' => 25,
            'profiler.enable' => true,
            'profiler.simple_url' => function ($url) {
                return preg_replace('/\=\d+/', '', $url);
            },
            'profiler.options' => array()
        ];
    }

    /**
     * @var Profiler
     */
    private static $_shared;

    static function IsEnabled(): bool
    {
        return extension_loaded('tideways_xhprof');
    }

    // 开始取样
    static function Start()
    {
        if (!self::$_shared)
            self::$_shared = new Profiler();
        self::$_shared->_start();
    }

    // 结束取样
    static function Stop()
    {
        self::$_shared->_stop();
    }

    public $data = [];
    public $config;

    function _start()
    {
        $uri = getenv('PROJECT') . '/' . getenv('HOSTNAME') . '/' . getenv('REQUEST_URI');

        $time = array_key_exists('REQUEST_TIME', $_SERVER)
            ? $_SERVER['REQUEST_TIME']
            : time();

        // In some cases there is comma instead of dot
        $delimiter = (strpos($_SERVER['REQUEST_TIME_FLOAT'], ',') !== false) ? ',' : '.';
        $requestTimeFloat = explode($delimiter, $_SERVER['REQUEST_TIME_FLOAT']);
        if (!isset($requestTimeFloat[1])) {
            $requestTimeFloat[1] = 0;
        }

        $requestTs = new \MongoDB\BSON\UTCDateTime($time * 1000);
        $requestTsMicro = new \MongoDB\BSON\UTCDateTime($requestTimeFloat[0] * 1000 + $requestTimeFloat[1]);

        $this->data['meta'] = array(
            'url' => $uri,
            'SERVER' => $_SERVER,
            'get' => $_GET,
            'env' => $_ENV,
            'simple_url' => Xhgui_Util::simpleUrl($uri, $this->config),
            'request_ts' => $requestTs,
            'request_ts_micro' => $requestTsMicro,
            'request_date' => date('Y-m-d', $time),
        );

        tideways_xhprof_enable(TIDEWAYS_XHPROF_FLAGS_CPU | TIDEWAYS_XHPROF_FLAGS_MEMORY);
    }

    function _stop()
    {
        $this->data['profile'] = tideways_xhprof_disable();

        try {
            $mongo = new \MongoDB\Driver\Manager($this->config['db.host'], $this->config['db.options']);
            $saver = new Xhgui_Saver_Mongo($mongo, $this->config['db.db']);
            $saver->save($this->data);
        } catch (Exception $e) {
            error_log('xhgui - ' . $e->getMessage());
        }
    }
}

class Xhgui_Saver_Mongo
{
    private $_db;
    private $_scheme;
    private static $lastProfilingId;

    public function __construct(\MongoDB\Driver\Manager $db, string $scheme)
    {
        $this->_db = $db;
        $this->_scheme = $scheme;
    }

    public function save(array $data)
    {
        $data['_id'] = self::getLastProfilingId();
        try {
            $this->_db->executeCommand($this->_scheme, new \MongoDB\Driver\Command([
                "insert" => "phalcon",
                "documents" => [$data],
                "writeConcern" => ['w' => 0]
            ]));
        } catch (\Throwable $err) {
            echo $err->getMessage();
        }
    }

    public static function getLastProfilingId()
    {
        if (!self::$lastProfilingId) {
            self::$lastProfilingId = new \MongoDB\BSON\ObjectId();
        }
        return self::$lastProfilingId;
    }
}


class Xhgui_Util
{
    public static function simpleUrl($url, $config)
    {
        $callable = $config['profiler.simple_url'];
        if (is_callable($callable)) {
            return call_user_func($callable, $url);
        }
        return preg_replace('/\=\d+/', '', $url);
    }
}
