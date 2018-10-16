<?php

use Phalcon\Loader;
use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\View;

define('MODULE_DIR', __DIR__ . '/');
define('APP_DIR', dirname(__DIR__) . '/');

$loader = new Loader();
$loader->registerNamespaces([
    'App' => APP_DIR . 'app',
    'App\Model' => APP_DIR . 'app/model',
    'App\Controller' => APP_DIR . 'app/controller',
    'Api' => MODULE_DIR
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
        ".phtml" => "Phalcon\Mvc\View\Engine\Php",
        ".volt" => "Phalcon\Mvc\View\Engine\Volt"
    ]);
    return $view;
});

$app = new \App\Controller\Application($di);
echo $app->handle()->getContent();
