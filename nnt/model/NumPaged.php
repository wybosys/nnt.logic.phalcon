<?php

namespace Nnt\Model;

class NumPaged
{
    /**
     * @var int
     * @Api(1, [integer], [input, output, optional], '请求的页码')
     */
    public $page = 0;

    /**
     * @var int
     * @Api(2, [integer], [input, optional], '单页多少条数据')
     */
    public $limit = 10;

    /**
     * @var int
     * @Api(3, [integer], [output], '数据总条目数量')
     */
    public $total = 0;

    function skips(): int
    {
        return $this->page * $this->limit;
    }

    function overflow(): bool
    {
        return $this->total > self::PAGED_LIMIT;
    }

    const PAGED_LIMIT = 5000;
}
