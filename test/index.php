<?php

use Phalcon\Loader;
use Phalcon\Db\Adapter\Pdo\Factory as DbFactory;

define('MODULE_DIR', __DIR__ . '/');
define('APP_DIR', dirname(__DIR__) . '/');

$loader = new Loader();
$loader->registerNamespaces([
    'Test' => APP_DIR . 'test',
    'Test\Db' => APP_DIR . 'test/db',
    'Test\Model' => APP_DIR . 'test/model',
    'Test\Controller' => APP_DIR . 'test/controller',
    'Nnt' => APP_DIR . 'nnt',
    'Nnt\Core' => APP_DIR . 'nnt/core',
    'Nnt\Store' => APP_DIR . 'nnt/store',
    'Nnt\Sdks' => APP_DIR . 'nnt/sdks',
    'Nnt\Db' => APP_DIR . 'nnt/db',
    'Nnt\Model' => APP_DIR . 'nnt/model',
    'Nnt\Controller' => APP_DIR . 'nnt/controller'
]);

$loader->registerDirs([
    MODULE_DIR . 'controller',
    MODULE_DIR . 'model'
]);

$loader->register();

$di = new \Nnt\Controller\Factory();
    
$di->setShared('config', function () {
    return include 'config/config.php';
});

$di->setShared('db', function () {
    return DbFactory::load($this->getConfig()->database);
});

$di->setShared('redis', function () {
    return new \Nnt\Store\KvRedis($this->getConfig()->redis);
});

$app = new \Test\Controller\Application($di);
$app->run();
