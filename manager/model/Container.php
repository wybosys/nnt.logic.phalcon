<?php

namespace Manager\Model;

class Container
{
    /**
     * @Api(1, [string], [input, output])
     * @var string
     */
    public $id;

    /**
     * @Api(2, [string], [output])
     * @var string
     */
    public $image;

    /**
     * @Api(3, [string], [output])
     * @var string
     */
    public $names;
}
