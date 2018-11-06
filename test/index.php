<?php

use Phalcon\Loader;
use Phalcon\Db\Adapter\Pdo\Factory as DbFactory;
use Phalcon\Session\Factory as SesFactory;

define('MODULE_DIR', __DIR__ . '/');
define('APP_DIR', dirname(__DIR__) . '/');

$loader = new Loader();
$loader->registerNamespaces([
    'Nnt' => APP_DIR . 'nnt',
    'Nnt\Model' => APP_DIR . 'nnt/model',
    'Nnt\Controller' => APP_DIR . 'nnt/controller',
    'Test' => MODULE_DIR,
    'Test\Controller' => MODULE_DIR . 'controller',
    'Test\Model' => MODULE_DIR . 'model',
    'Test\Db' => MODULE_DIR . 'db'
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

$di->setShared('db', function () {
    return DbFactory::load($this->getConfig()->database);
});

$di->setShared('session', function () {
    $hdl = SesFactory::load($this->getConfig()->session);
    $hdl->start();
    return $hdl;
});

$di->setShared('redis', function () {
    return new \Nnt\Controller\KvRedis($this->getConfig()->redis);
});

$app = new \Test\Controller\Application($di);
$app->run();
