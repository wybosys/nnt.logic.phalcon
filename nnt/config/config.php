<?php

// 用来配合phalcon工具生成模型

defined('MODULE_DIR') || define('MODULE_DIR', dirname(__DIR__) . '/');
defined('APP_DIR') || define('APP_DIR', dirname(dirname(__DIR__)) . '/');

$cfg = [];

$cfg['database'] = [
    "adapter" => "Mysql",
    "host" => "develop.91egame.com",
    "username" => "root",
    "port" => "3306",
    "password" => "root",
    "dbname" => "test",
    "charset" => "utf8"
];

$cfg['application'] = [
    "controllersDir" => MODULE_DIR . "controller",
    "modelsDir" => MODULE_DIR . "model",
    "viewsDir" => MODULE_DIR . "view"
];

return new \Phalcon\Config($cfg);