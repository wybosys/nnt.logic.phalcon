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

return $cfg;
