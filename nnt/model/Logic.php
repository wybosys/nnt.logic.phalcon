<?php

// 对接LogicAPI的基础类

namespace Nnt\Model;

class Logic
{
    static function NewRequest($req)
    {
        $clz = $req[1];
        $r = new $clz();
        $r->action = $req[0];
        return $r;
    }
}
