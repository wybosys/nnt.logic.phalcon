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
    // 第三方库
    'Dust' => MODULE_DIR . '3rd/dust'
]);

$loader->registerDirs([
    MODULE_DIR . 'controller',
    MODULE_DIR . "model"
]);
$loader->register();

$di = new FactoryDefault();

$di->setShared('config', function () {
    return include 'config/appconfig.php';
});

$di->setShared('url', function () {
    return null;
});

$di->setShared('view', function () {
    $view = new View();
    $view->setDI($this);
    $view->registerEngines([
        ".phtml" => "Phalcon\Mvc\View\Engine\Php",
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

$app = new \Nnt\Controller\Application($di);
echo $app->handle()->getContent();
