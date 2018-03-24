<?php
namespace Test\Model;

class HostInfo
{
    /**
     * @Api(1, [string], [input])
     * @var string
     */
    public $name;


    /**
     * @Api(2, [string], [output])
     * @var string
     */
    public $info;
}