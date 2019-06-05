<?php

namespace Nnt\Controller;

use Nnt\Core\Kernel;
use Phalcon\DiInterface;

class Application extends \Phalcon\Mvc\Application
{
    function __construct(DiInterface $dependencyInjector = null)
    {
        parent::__construct($dependencyInjector);

        if (isset($_GET['_profiler']) && $_GET['_profiler']) {
            if ($this->_profiler = Profiler::IsEnabled()) {
                Profiler::Start();
            } else {
                throw new \Exception('当前php运行环境中没有安装tideways_xhprof模块');
            }
        }

        Application::$shared = $this;

        // 创建通用的基础文件夹
        define('TMP_DIR', APP_DIR . '/tmp/');
        Kernel::EnsureDir(TMP_DIR);
        define('RUN_DIR', APP_DIR . '/run/');
        Kernel::EnsureDir(RUN_DIR);
        define('LOG_DIR', APP_DIR . '/logs/');
        Kernel::EnsureDir(LOG_DIR);
    }

    /**
     * @var Application
     */
    static $shared;

    /**
     * Handles a MVC request
     *
     * @param string $uri
     * @return bool|\Phalcon\Http\ResponseInterface
     */
    function handle($uri = null)
    {
        $ret = parent::handle($uri);
        if ($this->_profiler)
            Profiler::Stop();
        return $ret;
    }

    private $_profiler = false;

    function run()
    {
        echo $this->handle()->getContent();
    }

    function config($name, $def = null)
    {
        try {
            return $this->di->getShared('config')[$name];
        } catch (\Exception $err) {
            // pass
        }
        return $def;
    }

    // 统一检查是否登录
    function auth($params)
    {
        // pass
    }
}
