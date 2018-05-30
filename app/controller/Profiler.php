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
        $source = 'phalcon';
        if (Config::IsDevops())
            $source = str_replace('/', '_', getenv('PROJECT'));

        $XHPROF_OUTPUT = sys_get_temp_dir();
        $run = uniqid();
        $wts = 100;

        // 保存到临时文件
        $output = $XHPROF_OUTPUT . "/$run.$source.xhprof";
        file_put_contents(
            $output,
            serialize($data)
        );
    }
}