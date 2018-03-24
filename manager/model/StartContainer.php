<?php
namespace Manager\Model;

class StartContainer
{
    /**
     * @Api(1, [string], [output])
     * @var string
     */
    public $id;

    /**
     * @Api(2, [string], [input])
     * @var string
     */
    public $image;

    /**
     * @Api(3, [string], [input, optional])
     * @var string
     */
    public $names;
}