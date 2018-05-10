<?php

namespace App\Controller;

use App\Model\Proto;
use Phalcon\Annotations\Adapter\Memory;
use Phalcon\Annotations\Annotation;
use Phalcon\Mvc\Controller;

// todo 使用APC

class Doc
{
    /**
     * 收集所有的action=>parameters数据
     * @return string[]
     */
    static public function Actions(Controller $obj)
    {
        $ret = [];
        $infos = self::ActionInfos($obj);
        foreach ($infos as $info) {
            $ret[] = array_merge($info, [
                "params" => self::ActionParameters($info['name'], $obj)
            ]);
        }
        return $ret;
    }

    static public function ActionInfos(Controller $obj)
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
            $info = self::ActionInfo($nm, $method->get('Action'));
            if ($info)
                $ret[] = $info;
        }
        return $ret;
    }

    static public function ActionInfo($name, Annotation $ann)
    {
        $ret = [
            "name" => $name
        ];
        $ops = $ann->getArgument(1);
        if (is_string($ops)) {
            $ret["comment"] = $ops;
            $ret["needauth"] = true;
        } else if (is_array($ops)) {
            $ret["needauth"] = !in_array("noauth", $ops);
            $ret["local"] = in_array("local", $ops);
            $ret["devops"] = in_array("devops", $ops);
            $ret["devopsrelease"] = in_array("devopsrelease", $ops);
            $ret["comment"] = $ann->getArgument(2);
        }

        // 判断action显示与否
        $check_env = isset($ret["local"]) || isset($ret["devops"]) || isset($ret["devopsrelease"]);
        if ($check_env) {
            // 检查当前环境是否匹配
            if (isset($ret["local"]) && !Config::IsLocal())
                return null;
            else if (isset($ret["devops"]) && !Config::IsDevops())
                return null;
            else if (!Config::IsDevopsRelease())
                return null;
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