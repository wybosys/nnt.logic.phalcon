<?php

use Phalcon\Loader;

// 本模块的路径
define('MODULE_DIR', __DIR__ . '/');

// 应用根目录
define('APP_DIR', dirname(__DIR__) . '/');

// 应用模式（未定义则为在命令行中使用phalcon-dev-tools)
define('APP_MODE', 'app');

$loader = new Loader();
$loader->registerNamespaces([

    // 基础库
    'Nnt' => MODULE_DIR,
    'Nnt\Model' => MODULE_DIR . 'model',
    'Nnt\Controller' => MODULE_DIR . 'controller',
    'Nnt\Core' => MODULE_DIR . 'core',
    'Nnt\Store' => MODULE_DIR . 'store',
    'Nnt\Util' => MODULE_DIR . 'util',
    'Nnt\Sdks' => APP_DIR . 'nnt/sdks',

    // 第三方库
    'Dust' => APP_DIR . '3rd/dust'
]);

$loader->registerDirs([
    MODULE_DIR . 'controller',
    MODULE_DIR . "model"
]);

$loader->register();

$di = new \Nnt\Controller\Factory();

$di->setShared('config', function () {
    return include 'config/config.php';
});

$app = new \Nnt\Controller\Application($di);
$app->run();
