<?php
namespace Test\Model;

class Kv
{
    /**
     * @Api(1, [string], [input, output])
     */
    public $key;

    /**
     * @Api(2, [string], [input, output, optional]
     */
    public $value;
}