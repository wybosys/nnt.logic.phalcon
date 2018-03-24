<?php
namespace Test\Model;

class UploadImage
{
    /**
     * @Api(1, [file], [input])
     */
    public $file;

    /**
     * @Api(2, [string], [output])
     */
    public $path;
}