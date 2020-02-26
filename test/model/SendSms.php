<?php

namespace Test\Model;

class SendSms
{
    /**
     * @var string
     * @Api(1, [string], [input], "电话号码")
     */
    public $phone;

    /**
     * @var string
     * @Api(2, [string], [input], "验证码")
     */
    public $code;

    /**
     * @var string
     * @Api(3, [string], [output], "返回结果")
     */
    public $result;
}