<?php
use Phalcon\Loader;
use Phalcon\Mvc\Application;
use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\View;
use Phalcon\Db\Adapter\Pdo\Factory as DbFactory;
use Phalcon\Session\Factory as SesFactory;

define('MODULE_DIR', __DIR__ . '/');
define('APP_DIR', dirname(__DIR__) . '/');

$loader = new Loader();
$loader->registerNamespaces([
    'App' => APP_DIR . 'app',
    'App\Model' => APP_DIR . 'app/model',
    'App\Controller' => APP_DIR . 'app/controller',
    'Test' => MODULE_DIR,
    'Test\Model' => MODULE_DIR . 'model'
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

$di->setShared('logger', function () {
    return "fafdsafads";
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

$di->setShared('auth', function() {
    return new \Test\Model\User();
});

$app = new Application($di);
echo $app->handle()->getContent();
