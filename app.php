<?php

use Nnt\Core\Config;

$cfg = [];

$cfg['mysql'] = [
    "adapter" => "mysql",
    "options" => [
        "host" => "localhost",
        "username" => "root",
        "port" => "3306",
        "password" => "root",
        "dbname" => "nnt-logic",
        "charset" => "utf8"
    ]
];

$cfg['pg'] = [
    "adapter" => "postgresql",
    "options" => [
        "host" => "localhost",
        "username" => "postgres",
        "port" => "5432",
        "password" => "postgres",
        "dbname" => "nnt.logic",
        "persistent" => true,
        "schema" => "nnt.logic"
    ]
];

$cfg['redis'] = [
    "host" => "dbproxy",
    "prefix" => "test_",
    "cluster" => true,
    "persistent" => true
];

$cfg['logic'] = [
    "HOST" => Config::Use('http://phalcon.wybosys.com', 'http://proxy', 'http://proxy')
];

$cfg['webproxy'] = [
    "HOST" => 'webproxy'
];

$cfg['version'] = '0.0.1';

if (file_exists('app.dev.php')) {
    require 'app.dev.php';
}

return $cfg;
