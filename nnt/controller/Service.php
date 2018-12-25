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
     * @jsonobj 如果！=null并且是post请求，则代表jsonobj需要按照json形式请求
     * @proxy 传递在app.php中配置的代理配置名称
     */
    static function DirectGet(string $url, array $args, $get = true, $jsonobj = null, $proxy = null)
    {
        if ($get || $jsonobj) {
            if (strpos($url, '?') === false)
                $url .= '/?';
            else
                $url .= '&';
            $url .= http_build_query($args);
        }

        // 初始化curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // 解决curl卡顿的问题
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        //
        if ($proxy) {
            $cfg = Application::$shared->config($proxy);
            if (!$cfg)
                throw new \Exception('代理配置错误', Code::CONFIG_ERROR);
            curl_setopt($ch, CURLOPT_PROXY, @$cfg->HOST);
        }

        // 如果是post请求，则填充参数
        if (!$get) {
            curl_setopt($ch, CURLOPT_POST, 1);
            if ($jsonobj) {
                $str = json_encode($jsonobj);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $str);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json; charset=utf-8',
                    'Content-Length: ' . strlen($str)
                ]);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Content-Type: multipart/form-data"
                ]);
            }
        }

        $msg = curl_exec($ch);
        curl_close($ch);

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
        $host = $cfg["HOST"];

        // 添加permission的信息
        if (self::PermissionEnabled()) {
            $args[KEY_PERMISSIONID] = self::PermissionId();
        }

        // 添加跳过的标记
        if (!Config::IsDevopsRelease()) {
            $args[KEY_SKIPPERMISSION] = 1;
        }

        // 组装url
        $url = $host . '/' . $idr . '/?' . http_build_query($args);

        // 初始化curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // 解决curl卡顿的问题
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        if ($files && count($files)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            $data = [];
            foreach ($files as $key => $file) {
                if ($file instanceof File) {
                    $data[$key] = curl_file_create($file->getTempName(), $file->getType(), $file->getName());
                }
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: multipart/form-data"
            ]);
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
     * 服务间调用
     * @throws \Exception
     */
    static function Fetch(string $idr, array $args, array $files = null)
    {
        $ret = self::Call($idr, $args, $files);
        if ($ret->code != Code::OK) {
            if (@$ret->error)
                $msg = $ret->error;
            else if (@$ret->message)
                $msg = $ret->message;
            else
                $msg = 'API FAILED';
            throw new \Exception($msg, $ret->code);
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
        if (apcu_exists(KEY_PERMISSIONID)) {
            $pid = apcu_fetch(KEY_PERMISSIONID);
            return $pid;
        }

        $cfg = json_decode(file_get_contents($file));
        $pid = $cfg->id;
        apcu_store(KEY_PERMISSIONID, $pid, 60);

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
