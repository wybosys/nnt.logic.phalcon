<?php

namespace Nnt\Controller;

use Nnt\Model\Code;

class Devops
{
    static function PermissionEnabled(): bool
    {
        // 只有devops环境下才具备检测权限的环境
        return Config::IsDevops();
    }

    /**
     * 获得自己的当前的许可ID
     * @throws \Exception
     */
    static function PermissionId(): string
    {
        // 从apcu中读取缓存的pid
        if (apcu_exists(KEY_PERMISSIONID)) {
            $pid = apcu_fetch(KEY_PERMISSIONID);
            if ($pid)
                return $pid;
        }

        $file = APP_DIR . '/run/permission.cfg';
        if (!file_exists($file)) {
            throw new \Exception("没有找到文件 $file", Code::PERMISSION_FAILED);
        }

        $cfg = json_decode(file_get_contents($file));
        $pid = $cfg->id;
        apcu_store(KEY_PERMISSIONID, $pid, 60);

        return $pid;
    }

}
