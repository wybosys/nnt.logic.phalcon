<?php

namespace App\Controller;

use Phalcon\Mvc\Controller;
use App\Model\Proto;

// todo 使用APC
use Phalcon\Annotations\Adapter\Memory;

class Doc
{
    /**
     * 收集所有的action=>parameters数据
     * @return string[]
     */
    static public function Actions(Controller $obj)
    {
        $ret = [];
        $names = self::ActionNames($obj);
        foreach ($names as $name) {
            $ret[] = [
                "name" => $name,
                "params" => self::ActionParameters($name, $obj)
            ];
        }
        return $ret;
    }

    static public function ActionNames(Controller $obj)
    {
        $reader = new Memory();
        $reflect = $reader->get($obj);
        $methods = $reflect->getMethodsAnnotations();
        $ret = [];
        if (!$methods)
            return $ret;
        foreach ($methods as $nm => $method) {
            if (!$method->has('Action'))
                continue;
            $ret[] = $nm;
        }
        return $ret;
    }

    static public function ActionParameters(string $act, Controller $obj)
    {
        $reader = new Memory();
        $reflect = $reader->get($obj);
        $methods = $reflect->getMethodsAnnotations();
        if (!$methods)
            return [];
        if (!array_key_exists($act, $methods))
            return [];
        $method = $methods[$act];
        if (!$method->has('Action'))
            return [];
        $info = $method->get('Action');
        $model = $info->getArgument(0);
        if (!$model)
            return [];
        return Proto::DeclarationOf($model);
    }
}