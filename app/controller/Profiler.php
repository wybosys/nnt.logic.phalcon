<?php

namespace App\Controller;

// 性能测试
class Profiler
{
    static function IsEnabled(): bool
    {
        return extension_loaded('tideways_xhprof');
    }

    // 开始取样
    static function Start()
    {
        putenv('XHGUI_MONGO_URI=' . Config::Use('develop.91egame.com:27017', 'mongo:27017', 'mongo:27017'));
        putenv("XHGUI_PROFILING=enabled");
        putenv("XHGUI_MONGO_DB=profiler");
        include APP_DIR . '/app/profiler/src/Xhgui/Saver.php';
        include APP_DIR . '/app/profiler/src/Xhgui/Saver/Interface.php';
        include APP_DIR . '/app/profiler/src/Xhgui/Saver/Mongo.php';
        include APP_DIR . '/app/profiler/src/Xhgui/Util.php';
        include APP_DIR . '/app/profiler/external/header.php';
    }

    // 结束取样
    static function Stop()
    {
        // pass 自动结束
    }
}