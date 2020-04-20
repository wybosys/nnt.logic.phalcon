<?php

use Nnt\Core\Config;

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

$cfg['pg'] = [
    "adapter" => "Postgresql",
    "host" => "localhost",
    "username" => "postgres",
    "port" => "5432",
    "password" => "postgres",
    "dbname" => "postgres",
    "persistent" => true,
    "schema" => "test"
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
