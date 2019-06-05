<?php

namespace Nnt\Controller;

use Nnt\Core\Code;
use Nnt\Core\Connector;
use Nnt\Core\Devops;

/*
if (!defined('SERVICE_HOST')) {
    throw new \Exception('请在app.php中配置SERVICE_HOST');
}
*/

class Service
{
    /**
     * 直接访问URL
     * @proxy 传递在app.php中配置的代理配置名称
     */
    static function DirectGet(string $url, array $args, $get = true, $json = false, array $headers = null, string $proxy = null)
    {
        $connect = new Connector($url);
        $connect->args($args);
        $connect->json = $json;
        $connect->headers($headers);
        $connect->method = $get ? Connector::METHOD_GET : Connector::METHOD_POST;

        // 配置代理
        if ($proxy) {
            $cfg = Application::$shared->config($proxy);
            if (!$cfg)
                throw new \Exception('代理配置错误', Code::CONFIG_ERROR);
            $connect->proxy(@$cfg->HOST);
        }

        return $connect->send();
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

        // 从全局的api生成一个链接
        $connect = Api::$shared->instanceConnector();
        $connect->devops = true;
        $connect->url = $host . '/' . $idr;
        $connect->args($args);
        $connect->files($files);

        return $connect->send();
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
                'code' => Code::FORMAT_ERROR,
                'message' => $msg
            ];
        } else if (!isset($ret->code)) {
            $ret->code = Code::FORMAT_ERROR;
            $ret->message = $msg;
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
            $msg = Devops::GetDomain() . "=>$idr: $msg";
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
}
