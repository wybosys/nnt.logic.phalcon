<?php

use \Nnt\Controller\Config;

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

$cfg['redis'] = [
    "host" => "redis",
    "port" => 6379,
    "index" => 0,
    "prefix" => "fp_",
    "persistent" => true
];

return $cfg;
