<?php

namespace Test\Model;

class Call
{
    /**
     * @Api(1, [string], [input], "服务名")
     */
    public $name;

    /**
     * @Api(2, [object], [input], "参数集")
     */
    public $args;

    /**
     * @Api(3, [object], [output], "获得")
     */
    public $output;
}