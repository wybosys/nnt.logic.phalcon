<?php

namespace App\Controller;

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
    }

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
}