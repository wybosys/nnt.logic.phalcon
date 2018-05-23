<?php

namespace App\Controller;

use Phalcon\Http\Request\File;

class Service
{
    const HOST_LOCAL = 'http://develop.91egame.com';
    const HOST_DEVOPSDEVELOP = 'http://develop.91egame.com';
    const HOST_DEVOPSRELEASE = 'http://www.91yigame.com';

    static function RawCall(string $idr, array $args, array $files = null)
    {
        $ch = curl_init();
        $host = Config::Use(Service::HOST_LOCAL, Service::HOST_DEVOPSDEVELOP, Service::HOST_DEVOPSRELEASE);

        if (Service::PermissionEnabled()) {
            $args[KEY_PERMISSIONID] = Service::PermissionId();
        }

        $url = $host . '/' . $idr . '/?' . http_build_query($args);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if ($files && count($files)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: multipart/form-data"
            ]);
            $data = [];
            foreach ($files as $key => $file) {
                if ($file instanceof File) {
                    $data[$key] = curl_file_create($file->getTempName(), $file->getType(), $file->getName());
                }
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $msg = curl_exec($ch);
        curl_close($ch);

        return $msg;
    }

    /**
     * 服务间调用
     * @throws \Exception
     */
    static function Call(string $idr, array $args, array $files = null)
    {
        $msg = self::RawCall($idr, $args, $files);
        $ret = json_decode($msg, true);
        if (!$ret || $ret["code"] !== 0)
            throw new \Exception("执行失败", $ret["code"]);
        return $ret;
    }

    /**
     * 获得自己的当前的许可ID
     */
    static function PermissionId(): string
    {
        $file = APP_DIR . '/run/permission.cfg';
        if (!file_exists($file))
            return null;
        $cfg = json_decode(file_get_contents($file));
        return $cfg->id;
    }

    static function PermissionEnabled(): bool
    {
        // 只有devops环境下才具备检测权限的环境
        return Config::IsDevops();
    }

    /**
     * 判断许可链中是否存在该许可ID
     */
    static function PermissionLocate(string $permissionId)
    {
        $db = new \Redis();
        $db->connect('localhost', 26379);
        $db->select(REDIS_PERMISSIONIDS);
        return $db->get($permissionId);
    }

    /**
     * 是否允许客户端进行访问
     */
    static function AllowClient(): bool
    {
        $cfgph = APP_DIR . '/devops.json';
        $cfg = json_decode(file_get_contents($cfgph));
        return isset($cfg->client) ? $cfg->client : false;
    }
}

const KEY_PERMISSIONID = "_permissionid";
const KEY_SKIPPERMISSION = "_skippermission";
const REDIS_PERMISSIONIDS = 17;