<?php

namespace Manager\Model;

use MongoDB\BSON\Regex;

class Image
{
    /**
     * @Api(1, [string], [output])
     * @var string
     */
    public $repo;

    /**
     * @Api(2, [string], [output])
     * @var string
     */
    public $tag;

    /**
     * @Api(3, [string], [input, output])
     * @var string
     */
    public $id;

    /**
     * @Api(4, [string], [output])
     * @var string
     */
    public $name;

    /**
     * @Api(5, [string], [output])
     * @var string
     */
    public $code;

    /**
     * @Api(6, [string], [output])
     * @var string
     */
    public $volume;
}