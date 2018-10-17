<?php

use \Nnt\Controller\Config;

$cfg = [];

$cfg['database'] = [
    "adapter" => "Mysql",
    "host" => "localhost",
    "username" => "root",
    "port" => "3306",
    "password" => "root",
    "dbname" => "test",
    "charset" => "utf8"
];

$cfg['redis'] = [
    "host" => "redis",
    "port" => 6379,
    "index" => 0,
    "prefix" => "fp_",
    "persistent" => true
];

define('SERVICE_HOST', Config::Use('http://localhost', 'http://proxy', 'http://proxy'));

return $cfg;
