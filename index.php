<?php

error_reporting(E_ALL);

if (!isset($_GET['_url'])) {
    throw new \Exception("没有按照phalcon的要求设置web服务器的url转发规则");
}

$phs = array_values(array_filter(explode('/', $_GET['_url'])));
if (!count($phs)) {
    throw new \Exception("传递的url参数错误");
}

// 加载二级模块
$REDIRECT_MODULE = __DIR__ . '/' . $phs[0] . '/index.php';
if (!file_exists($REDIRECT_MODULE)) {
    throw new \Exception("没有找到模块");
}

(function () {
    global $REDIRECT_MODULE;
    // 加载模块
    require $REDIRECT_MODULE;
})();
