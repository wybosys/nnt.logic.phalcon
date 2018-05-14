<?php

namespace App\Controller;

use Phalcon\Http\Request\File;

class Service
{
    /**
     * 服务间调用
     * @throws \Exception
     */
    static function Call(string $idr, array $args, array $files = null)
    {
        $ch = curl_init();
        $host = Config::Use('http://develop.91egame.com', 'http://develop.91egame.com', 'http://www.91yigame.com');

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
        $file = APP_DIR . '/tmp/permission.cfg';
        if (!file_exists($file))
            return null;
        $cfg = json_decode(file_get_contents($file));
        return $cfg->id;
    }

    static function PermissionEnabled(): bool
    {
        return extension_loaded('dba') && !Config::IsLocal();
    }

    /**
     * 判断许可链中是否存在该许可ID
     */
    static function PermissionLocate(string $permissionId)
    {
        $dbph = APP_DIR . '/run/permissions';
        $db = dba_open($dbph, 'r');
        return dba_fetch($permissionId, $db);
    }

    /**
     * 是否允许客户端进行访问
     */
    static function AllowClient(): bool
    {
        $cfgph = APP_DIR . '/devops.json';
        $cfg = json_decode($cfgph);
        return isset($cfg->client) ? $cfg->client : false;
    }
}

const KEY_PERMISSIONID = "_permissionid";
const KEY_SKIPPERMISSION = "_skippermission";
