<?php

namespace Nnt\Controller;

use Nnt\Model\Code;
use Phalcon\Http\Request\File;

/*
if (!defined('SERVICE_HOST')) {
    throw new \Exception('请在app.php中配置SERVICE_HOST');
}
*/

class Service
{
    static function RawCall(string $idr, array $args, array $files = null)
    {
        $ch = curl_init();
        $host = SERVICE_HOST;

        if (self::PermissionEnabled()) {
            $args[KEY_PERMISSIONID] = self::PermissionId();
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
        $ret = json_decode($msg);
        if (!$ret) {
            $ret = (object)[
                'code' => Code::FORMAT_ERROR
            ];
        } else if (!isset($ret->code)) {
            $ret->code = Code::FORMAT_ERROR;
        } else {
            if (isset($ret->message) && !isset($ret->data))
                $ret->data = $ret->message;
            else if (isset($ret->data) && !isset($ret->message))
                $ret->message = $ret->data;
        }
        return $ret;
    }

    /**
     * 获得自己的当前的许可ID
     */
    static function PermissionId(): string
    {
        $file = APP_DIR . '/run/permission.cfg';
        if (!file_exists($file)) {
            throw new \Exception("没有找到文件 $file", Code::PERMISSIO_FAILED);
            return null;
        }

        // 从apcu中读取缓存的pid
        if (apcu_exists(KEY_PERMISSIONTIME)) {
            $time = apcu_fetch(KEY_PERMISSIONTIME);
            $ftime = filemtime($file);
            if ($time != $ftime) {
                $cfg = json_decode(file_get_contents($file));
                $pid = $cfg->id;
                apcu_store(KEY_PERMISSIONTIME, $ftime);
                apcu_store(KEY_PERMISSIONID, $pid);
                return $pid;
            } else {
                $pid = apcu_fetch(KEY_PERMISSIONID);
                return $pid;
            }
        }

        $ftime = filemtime($file);
        $cfg = json_decode(file_get_contents($file));
        $pid = $cfg->id;
        apcu_store(KEY_PERMISSIONTIME, $ftime);
        apcu_store(KEY_PERMISSIONID, $pid);

        return $pid;
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

    private static $_DEVOPSCONFIG = null;

    static function DevopsConfig()
    {
        if (self::$_DEVOPSCONFIG == null) {
            $cfgph = APP_DIR . '/devops.json';
            self::$_DEVOPSCONFIG = json_decode(file_get_contents($cfgph));
        }
        return self::$_DEVOPSCONFIG;
    }

    /**
     * 是否允许客户端进行访问
     */
    static function AllowClient($cfg, $clientip): bool
    {
        // 是否在白名单内
        if (isset($cfg->allow)) {
            foreach ($cfg->allow as $each) {
                if (self::CidrMatch($clientip, $each))
                    return true;
            }
        }

        // 是否在黑名单内
        if (isset($cfg->deny)) {
            foreach ($cfg->deny as $each) {
                if (self::CidrMatch($clientip, $each))
                    return false;
            }
        }

        return true;
    }

    static function CidrMatch($ip, $cidr)
    {
        list($subnet, $mask) = explode('/', $cidr);
        return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) == ip2long($subnet);
    }

    static function GetDomain()
    {
        $path = self::DevopsConfig()->path;
        $domain = substr($path, 16);
        return $domain;
    }
}

const KEY_PERMISSIONTIME = "_permission_time";
const KEY_PERMISSIONID = "_permissionid";
const KEY_SKIPPERMISSION = "_skippermission";
const REDIS_PERMISSIONIDS = 17;
