<?php

namespace Test\Model;

class CidrTest
{
    /**
     * @Api(1, [string], [input], "规则 172.0.0.0/24")
     * @var string
     */
    public $rule;

    /**
     * @Api(2, [string], [input], "IP")
     * @var string
     */
    public $ip;

    /**
     * @Api(3, [boolean], [output], "结果")
     * @var boolean
     */
    public $result;
}