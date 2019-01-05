<?php

namespace Nnt\Sdks;

class SdkRechargeInfo
{
    /**
     * @var string
     * @Api(1, [string], [input], "平台用户id")
     */
    public $uid;

    /**
     * @var string
     * @Api(2, [string], [input], "渠道名称")
     */
    public $channel;

    /**
     * @var integer
     * @Api(3, [integer], [input], "金额，分")
     */
    public $money;

    /**
     * @var string
     * @Api(4, [string], [output], "返回的配置数据")
     */
    public $raw;

    /**
     * @var string
     * @Api(5, [string], [output], "订单号")
     */
    public $orderid;

    /**
     * @Api(6, [object], [input, optional], "附加数据")
     */
    public $addition;
}
