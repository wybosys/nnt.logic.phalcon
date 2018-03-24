<?php

namespace Test\Model;

class Echoo
{
    /**
     * @Api(1, [string], [input])
     * @var string
     */
    public $input;

    /**
     * @Api(2, [string], [output])
     * @var string
     */
    public $output;
}