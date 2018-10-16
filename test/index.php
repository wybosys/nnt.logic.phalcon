<?php
use Phalcon\Loader;
use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\View;
use Phalcon\Db\Adapter\Pdo\Factory as DbFactory;
use Phalcon\Session\Factory as SesFactory;

define('MODULE_DIR', __DIR__ . '/');
define('NNT_DIR', dirname(__DIR__) . '/');

$loader = new Loader();
$loader->registerNamespaces([
    'Nnt' => NNT_DIR . 'nnt',
    'Nnt\Model' => NNT_DIR . 'nnt/model',
    'Nnt\Controller' => NNT_DIR . 'nnt/controller',
    'Test' => MODULE_DIR,
    'Test\Model' => MODULE_DIR . 'model',
    'Test\Db' => MODULE_DIR . 'db'
]);

$loader->registerDirs([
    MODULE_DIR . 'controller',
    MODULE_DIR . "model"
]);
$loader->register();

$di = new FactoryDefault();

$di->setShared('config', function () {
    return include 'config/config.php';
});

$di->setShared('url', function () {
    return null;
});

$di->setShared('view', function () {
    $view = new View();
    $view->setDI($this);
    $view->registerEngines([
        ".volt" => "Phalcon\Mvc\View\Engine\Volt"
    ]);
    return $view;
});

$di->setShared('db', function () {
    return DbFactory::load($this->getConfig()->database);
});

$di->setShared('session', function () {
    $hdl = SesFactory::load($this->getConfig()->session);
    $hdl->start();
    return $hdl;
});

$di->setShared('user', function() {
    return new \Test\Model\User();
});

$app = new \Nnt\Controller\Application($di);
echo $app->handle()->getContent();
