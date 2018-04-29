<?php

use App\Controller\Config;

defined('MODULE_DIR') || define('MODULE_DIR', dirname(__DIR__) . '/');
defined('APP_DIR') || define('APP_DIR', dirname(dirname(__DIR__)) . '/');

$cfg = [];

$cfg['database'] = [
    "adapter" => "Mysql",
    "host" => "develop.91egame.com",
    "username" => "root",
    "port" => "3306",
    "password" => "root",
    "dbname" => "devops",
    "charset" => "utf8"
];

$cfg['redis'] = [
    "host" => "redis",
    "port" => 6379,
    "index" => 0,
    "auth" => "root",
    "prefix" => "fp_",
    "persistent" => true
];

$cfg['application'] = [
    "controllersDir" => MODULE_DIR . "controller",
    "modelsDir" => MODULE_DIR . "model",
    "viewsDir" => MODULE_DIR . "view"
];

return new Config($cfg);