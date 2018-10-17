<?php

// 用来配合phalcon工具生成模型
defined('MODULE_DIR') || define('MODULE_DIR', dirname(__DIR__) . '/');
defined('APP_DIR') || define('APP_DIR', dirname(dirname(__DIR__)) . '/');

if (!defined(APP_DIR) || APP_DIR != 'app') {
    // cli等形式访问
    include APP_DIR . "nnt/controller/Config.php";
}

// 加载根目录中的配置
$cfg = include APP_DIR . 'app.php';

if (!defined(APP_DIR) || APP_DIR != 'app') {
    // 额外设置环境数据
    $cfg['application'] = [
        "controllersDir" => APP_DIR,
        "modelsDir" => APP_DIR,
        "viewsDir" => APP_DIR,
    ];
}

return new \Phalcon\Config($cfg);
