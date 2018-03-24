<?php

error_reporting(E_ALL);

if (!isset($_GET['_url']))
    exit();

$phs = array_values(array_filter(explode('/', $_GET['_url'])));
if (!count($phs))
    exit();

// 加载二级模块
$REDIRECT_MODULE = __DIR__ . '/' . $phs[0] . '/index.php';
if (!file_exists($REDIRECT_MODULE))
    exit();

(function () {
    global $REDIRECT_MODULE;
    require $REDIRECT_MODULE;
})();
