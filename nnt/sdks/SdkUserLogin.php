<?php

namespace Nnt\Sdks;

class SdkUserLogin
{
    /**
     * @var string
     * @Api(1, [string], [input], "sdks返回的原始数据")
     */
    public $raw;

    /**
     * @var string
     * @Api(2, [string], [input], "channel渠道名称")
     */
    public $channel;

    /**
     * @var SdkUserInfo
     * @Api(3, [type, \Nnt\Sdks\SdkUserInfo], [output], "用户信息")
     */
    public $user;

    /**
     * @var string
     * @Api(4, [string], [output], "sid")
     */
    public $sid;
}
