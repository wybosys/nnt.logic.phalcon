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
    "prefix" => "test_",
    "persistent" => true
];

$cfg['logic'] = [
    "HOST" => Config::Use('http://phalcon.wybosys.com', 'http://proxy', 'http://proxy')
];

return $cfg;
