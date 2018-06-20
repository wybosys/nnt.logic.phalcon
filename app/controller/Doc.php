<?php

namespace App\Controller;

use App\Model\Proto;
use Phalcon\Annotations\Annotation;
use Phalcon\Mvc\Controller;

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
        $reflect = Proto::Reflect($obj);
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
            if (in_array("local", $ops))
                $ret["local"] = true;
            if (in_array("devops", $ops))
                $ret["devops"] = true;
            if (in_array("devopsdevelop", $ops))
                $ret["devopsdevelop"] = true;
            if (in_array("devopsrelease", $ops))
                $ret["devopsrelease"] = true;
            if (in_array("expose", $ops))
                $ret["needauth"] = false;
            $ret["comment"] = $ann->getArgument(2);
        }

        // 判断环境显示
        $pass = false;
        $mit = false;
        if (!$pass && isset($ret["local"])) {
            $mit = true;
            if (Config::IsLocal())
                $pass = true;
        }
        if (!$pass && isset($ret["devops"])) {
            $mit = true;
            if (Config::IsDevops())
                $pass = true;
        }
        if (!$pass && isset($ret["devopsdevelop"])) {
            $mit = true;
            if (Config::IsDevopsDevelop())
                $pass = true;
        }
        if (!$pass && isset($ret["devopsrelease"])) {
            $mit = true;
            if (Config::IsDevopsRelease())
                $pass = true;
        }

        if (!$mit || $pass)
            return $ret;

        return null;
    }

    static public function ActionParameters(string $act, Controller $obj)
    {
        $reflect = Proto::Reflect($obj);
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