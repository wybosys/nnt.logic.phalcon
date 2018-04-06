<?php
namespace Test\Model;

class Log
{
    /**
     * @Api(1, [string], [input, output])
     * @var string
     */
    public $msg;

    /**
     * @Api(2, [integer], [input, optional, output])
     * @var int
     */
    public $type = \Phalcon\Logger::DEBUG;
}