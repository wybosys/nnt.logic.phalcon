<?php

namespace Manager\Model;

class ContainerOutput
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
    public $output;
}