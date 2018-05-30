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
        tideways_xhprof_enable();
    }

    // 结束取样
    static function Stop()
    {
        $data = tideways_xhprof_disable();
        $name = 'phalcon';
        if (Config::IsDevops())
            $name = str_replace('/', '_', getenv('PROJECT'));
        $output = sys_get_temp_dir() . "/" . uniqid() . ".$name.xhprof";
        file_put_contents(
            $output,
            serialize($data)
        );
    }
}