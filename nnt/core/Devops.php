<?php

namespace Nnt\Core;

use Nnt\Model\Code;

class Devops
{
    const KEY_PERMISSIONTIME = "_permission_time";
    const KEY_PERMISSIONID = "_permissionid";
    const KEY_SKIPPERMISSION = "_skippermission";
    const REDIS_PERMISSIONIDS = 17;

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
        if (apcu_exists(self::KEY_PERMISSIONID)) {
            $pid = apcu_fetch(self::KEY_PERMISSIONID);
            if ($pid)
                return $pid;
        }

        $file = APP_DIR . '/run/permission.cfg';
        if (!file_exists($file)) {
            throw new \Exception("没有找到文件 $file", Code::PERMISSION_FAILED);
        }

        $cfg = json_decode(file_get_contents($file));
        $pid = $cfg->id;

        // register.py 会让老的pid继续使用5s
        apcu_store(self::KEY_PERMISSIONID, $pid, 5);

        return $pid;
    }

    /**
     * 判断许可链中是否存在该许可ID
     */
    static function PermissionLocate(string $permissionId)
    {
        $db = new \Redis();
        $db->connect('localhost', 26379);
        $db->select(self::REDIS_PERMISSIONIDS);
        return $db->get($permissionId);
    }

    private static $_DEVOPSCONFIG = null;

    static function GetConfig()
    {
        if (self::$_DEVOPSCONFIG == null) {
            $cfgph = APP_DIR . '/devops.json';
            self::$_DEVOPSCONFIG = json_decode(file_get_contents($cfgph));
        }
        return self::$_DEVOPSCONFIG;
    }

    static function GetDomain()
    {
        $path = self::GetConfig()->path;
        $domain = substr($path, 16);
        return $domain;
    }
}
