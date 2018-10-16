<?php

// 收集所有的get、post参数
if (isset($_GET['_url'])) {
    // 如果存在url，则按照phalcon标准的keypath来路由
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
} else if (($action = $_GET['action']) || ($action = $_POST['action'])) {
    // 按照logic的规则来路由
    $phs = explode('.', $action);

    // 只有内部模块可以导向nnt
    $INNER_MODULES = ['api'];
    $MODULE_NAME = in_array($phs[0], $INNER_MODULES) ? 'nnt' : $phs[0];

    // 加载二级模块
    $REDIRECT_MODULE = __DIR__ . "/$MODULE_NAME/index.php";
    if (!file_exists($REDIRECT_MODULE)) {
        throw new \Exception("没有找到模块");
    }

    (function () {
        global $REDIRECT_MODULE;
        // 加载模块
        require $REDIRECT_MODULE;
    })();
} else {
    // 所有规则都不满足
    echo "NntLogicPhalcon WebRoot";
    http_response_code(404);
    return;
}

