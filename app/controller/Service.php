<?php

namespace App\Controller;

use Phalcon\Http\Request\File;
use Phalcon\Http\RequestInterface;
use Test\Model\CidrTest;

class Service
{
    const HOST_LOCAL = 'http://develop.91egame.com';
    const HOST_DEVOPSDEVELOP = 'http://develop.91egame.com';
    const HOST_DEVOPSRELEASE = 'http://www.91yigame.com';

    static function RawCall(string $idr, array $args, array $files = null)
    {
        $ch = curl_init();
        $host = Config::Use(self::HOST_LOCAL, self::HOST_DEVOPSDEVELOP, self::HOST_DEVOPSRELEASE);

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

    private static $_DEVOPSCONFIG = null;

    protected static function DevopsConfig()
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
    static function AllowClient(RequestInterface $request): bool
    {
        $cfg = self::DevopsConfig();

        // 全局打开客户端访问
        if (isset($cfg->client) && $cfg->client)
            return true;

        // 是否在白名单内
        $clientip = $request->getClientAddress(true);
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

        return false;
    }

    static function CidrMatch($ip, $cidr)
    {
        return (new CIDRmatch())->match($ip, $cidr);
    }
}

class CIDRmatch
{
    public function match($ip, $cidr)
    {
        list($subnet, $mask) = explode('/', $cidr);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipVersion = 'v4';
        } else {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $ipVersion = 'v6';
            } else {
                return false;
            }
        }
        switch ($ipVersion) {
            case 'v4':
                return $this->IPv4Match($ip, $subnet, $mask);
                break;
            case 'v6':
                return $this->IPv6Match($ip, $subnet, $mask);
                break;
        }
    }

    private function IPv6MaskToByteArray($subnetMask)
    {
        $addr = str_repeat("f", $subnetMask / 4);
        switch ($subnetMask % 4) {
            case 0:
                break;
            case 1:
                $addr .= "8";
                break;
            case 2:
                $addr .= "c";
                break;
            case 3:
                $addr .= "e";
                break;
        }
        $addr = str_pad($addr, 32, '0');
        $addr = pack("H*", $addr);
        return $addr;
    }

    private function IPv6Match($address, $subnetAddress, $subnetMask)
    {
        $subnet = inet_pton($subnetAddress);
        $addr = inet_pton($address);
        $binMask = $this->IPv6MaskToByteArray($subnetMask);
        return ($addr & $binMask) == $subnet;
    }

    private function IPv4Match($address, $subnetAddress, $subnetMask)
    {
        if ((ip2long($address) & ~((1 << (32 - $subnetMask)) - 1)) == ip2long($subnetAddress)) {
            return true;
        }
        return false;
    }
}

const KEY_PERMISSIONID = "_permissionid";
const KEY_SKIPPERMISSION = "_skippermission";
const REDIS_PERMISSIONIDS = 17;