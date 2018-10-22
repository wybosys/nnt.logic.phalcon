<?php

// 只有内部模块可以导向nnt
$INNER_MODULES = ['nnt', 'api'];

// 收集所有的get、post参数
if (isset($_GET['_url'])) {
    // 如果存在url，则按照phalcon标准的keypath来路由
    $phs = array_values(array_filter(explode('/', $_GET['_url'])));
    if (!count($phs)) {
        throw new \Exception("传递的url参数错误");
    }

    if (in_array($phs[0], $INNER_MODULES)) {
        $MODULE_NAME = 'nnt';
    } else {
        $MODULE_NAME = $phs[0];
    }

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
} else if (isset($_GET['action']) || isset($_POST['action'])) {
    if (isset($_GET['action']))
        $action = $_GET['action'];
    else
        $action = $_POST['action'];

    // 按照logic的规则来路由
    $phs = explode('.', $action);

    if (in_array($phs[0], $INNER_MODULES)) {
        $MODULE_NAME = 'nnt';
    } else {
        $MODULE_NAME = $phs[0];
    }

    // 会被Factory动态替换为phalcon支持的路由
    define('LOGIC_ROUTER', $MODULE_NAME);
    define('LOGIC_ACTION', $phs[1]);

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

