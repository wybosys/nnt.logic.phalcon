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
    /**
     * 直接访问URL
     */
    static function DirectGet(string $url, array $args)
    {
        if (strpos($url, '?') === false)
            $url .= '/?';
        else
            $url .= '&';
        $url .= http_build_query($args);

        $options = [
            'http' => [
                'method' => 'GET',
                'header' => 'Content-type:application/x-www-form-urlencoded'
            ]
        ];
        $context = stream_context_create($options);
        $msg = file_get_contents($url, false, $context);

        return $msg;
    }

    /**
     * 调用logic实现的微服务
     * @throws \Exception
     */
    static function RawCall(string $idr, array $args, array $files = null)
    {
        // 从配置中读取基础的host地址
        $cfg = Application::$shared->config("logic");
        $host = self::MapHost($cfg["HOST"]);

        // 添加permission的信息
        if (self::PermissionEnabled()) {
            $args[KEY_PERMISSIONID] = self::PermissionId();
        }

        // 添加跳过的标记
        if (!Config::IsDevopsRelease()) {
            $args[KEY_SKIPPERMISSION] = 1;
        }

        $url = $host . '/' . $idr . '/?' . http_build_query($args);

        if ($files && count($files)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

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

            $msg = curl_exec($ch);
            curl_close($ch);
        } else {
            $options = [
                'http' => [
                    'method' => 'GET',
                    'header' => 'Content-type:application/x-www-form-urlencoded'
                ]
            ];
            $context = stream_context_create($options);
            $msg = file_get_contents($url, false, $context);
        }


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
     * 服务间调用
     * @throws \Exception
     */
    static function Fetch(string $idr, array $args, array $files = null)
    {
        $ret = self::Call($idr, $args, $files);
        if ($ret->code != Code::OK) {
            throw new \Exception("API调用失败", $ret->code);
        }
        return $ret->data;
    }

    /**
     * 服务间调用
     */
    static function Get(string $idr, array $args, array $files = null)
    {
        try {
            $ret = self::Call($idr, $args, $files);
            if ($ret->code != Code::OK) {
                return null;
            }
            return $ret->data;
        } catch (\Throwable $err) {
        }
        return null;
    }

    /**
     * 获得自己的当前的许可ID
     * @throws \Exception
     */
    static function PermissionId(): string
    {
        $file = APP_DIR . '/run/permission.cfg';
        if (!file_exists($file)) {
            throw new \Exception("没有找到文件 $file", Code::PERMISSION_FAILED);
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

    // php使用域名访问时有可能过慢，所以需要转换仅通过host来访问的地址
    // 不能转换通过www.xxx.com这类的地址，避免服务器无法通过server_name重定位服务
    static function MapHost(string $host): string
    {
        if (apcu_exists($host))
            return apcu_fetch($host);
        $url = parse_url($host);
        if (strpos($url['host'], '.') !== false) {
            apcu_store($host, $host, 3600); // 一个小时刷新一次
            return $host;
        }
        $ip = gethostbyname($url['host']);
        if (!isset($url['path']))
            $url['path'] = '';
        $new = $url['scheme'] . '://' . $ip . $url['path'];
        echo "logic的host从" . $host . "自动转换为" . $new;
        apcu_store($host, $new, 3600);
        return $new;
    }
}

const KEY_PERMISSIONTIME = "_permission_time";
const KEY_PERMISSIONID = "_permissionid";
const KEY_SKIPPERMISSION = "_skippermission";
const REDIS_PERMISSIONIDS = 17;
