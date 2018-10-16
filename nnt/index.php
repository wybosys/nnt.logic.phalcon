<?php
use Phalcon\Loader;
use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\View;
use Phalcon\Db\Adapter\Pdo\Factory as DbFactory;
use Phalcon\Session\Factory as SesFactory;

define('MODULE_DIR', __DIR__ . '/');
define('APP_DIR', dirname(__DIR__) . '/');

$loader = new Loader();
$loader->registerNamespaces([
    // 基础库
    'Nnt' => MODULE_DIR,
    'Nnt\Model' => MODULE_DIR . 'model',
    'Nnt\Controller' => MODULE_DIR . 'controller',
    'Nnt\Util' => MODULE_DIR . 'util',
    // 第三方库
    'Dust' => MODULE_DIR . '3rd/dust'
]);

$loader->registerDirs([
    MODULE_DIR . 'controller',
    MODULE_DIR . "model"
]);
$loader->register();

$di = new \Nnt\Controller\Factory();

$di->setShared('config', function () {
    return include 'config/appconfig.php';
});

$di->setShared('db', function () {
    return DbFactory::load($this->getConfig()->database);
});

$di->setShared('session', function () {
    $hdl = SesFactory::load($this->getConfig()->session);
    $hdl->start();
    return $hdl;
});

$app = new \Nnt\Controller\Application($di);
$app->run();
