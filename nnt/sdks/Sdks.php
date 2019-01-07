<?php

namespace Nnt\Sdks;

use App\Controller\Application;
use Nnt\Controller\Api;
use Nnt\Controller\Service;
use Nnt\Model\Proto;

class Sdks
{

    static function UserLogin(SdkUserLogin $m): SdkUserLogin
    {
        $ret = Service::Fetch('platform/open', [
            "action" => 'user.login',
            "raw" => $m->raw,
            "channel" => $m->channel
        ]);

        $m->user = Proto::Decode(new SdkUserInfo(), $ret->user);
        $m->sid = $ret->sid;

        return $m;
    }

    static function UserVerify(SdkUserVerify $m): SdkUserInfo
    {
        $ret = Service::Fetch('platform/users', [
            "action" => 'user.info',
            "_sid" => $m->sid
        ]);
        return Proto::Decode(new SdkUserInfo(), $ret->user);
    }

    static function RechargeInfo(SdkRechargeInfo $m): SdkRechargeInfo
    {
        $ret = Service::Fetch('platform/open', [
            "action" => 'shop.rechargeinfo',
            "money" => $m->money,
            "channel" => $m->channel,
            "gameid" => Application::$shared->config("app")["gameid"],
            "uid" => $m->uid,
            "addition" => $m->addition ? json_encode($m->addition) : '',
            "clientip" => Api::$shared->clientIp()
        ]);

        $m->orderid = $ret->orderid;
        $m->raw = $ret->raw;
        return $m;
    }

}
