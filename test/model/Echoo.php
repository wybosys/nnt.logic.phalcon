<?php

namespace Test\Model;

class Echoo
{
    /**
     * @Api(1, [string], [input], "输入")
     * @var string
     */
    public $input;

    /**
     * @Api(2, [string], [output], "输出")
     * @var string
     */
    public $output;
}