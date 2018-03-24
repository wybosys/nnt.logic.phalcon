<?php
namespace Manager\Model;

class NewImage
{
    /**
     * @Api(1, [string], [input])
     * @var string
     */
    public $name;

    /**
     * @Api(2, [string], [input])
     * @var string
     */
    public $code;

    /**
     * @Api(3, [string], [input])
     * @var string
     */
    public $image;

    /**
     * @Api(4, [string], [output])
     * @var string
     */
    public $id;

    /**
     * @Api(5, [string], [input, optional])
     * @var string
     */
    public $volume;
}