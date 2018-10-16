<?php

namespace Nnt\Controller;

use Nnt\Model\Proto;
use Phalcon\Annotations\Annotation;
use Phalcon\Mvc\Controller;

class Doc
{
    /**
     * 收集所有的action=>parameters数据
     * @return string[]
     */
    static public function ActionsInfo($entrys)
    {
        $ret = [];
        foreach ($entrys["routers"] as $router) {
            $ret = array_merge($ret, self::RouterAction($router, $entrys));
        }
        return $ret;
    }

    static public function RouterAction(string $router, $entrys)
    {
        $name = $router;

        $reflect = Proto::Reflect(ucfirst($router) . 'Controller');
        $methods = $reflect->getMethodsAnnotations();

        $as = self::GetAllActionNames($methods);

        // 读取每个action的信息
        $ret = [];
        foreach ($as as $action) {
            $decl = $methods[$action];
            $actdecl = $decl->get('Action');
            $t = [];
            $t["name"] = $t["action"] = "$name.$action";
            $t["comment"] = $actdecl->getArgument(2);
            $modelClazz = $actdecl->getArgument(0);
            $t["params"] = self::ParametersInfo($modelClazz);
            $ret[] = $t;
        }

        return $ret;
    }

    static function GetAllActionNames($methods)
    {
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

    static public function ParametersInfo(string $model)
    {
        $reflect = Proto::Reflect($model);
        $props = $reflect->getPropertiesAnnotations();
        $ret = [];
        if (!$props)
            return $ret;

        foreach ($props as $name => $prop) {
            if (!$prop->has('Api'))
                continue;
            $api = $prop->get('Api');
            if (!$api)
                continue;

            $idx = $api->getArgument(0);
            $typs = $api->getArgument(1);
            $ops = $api->getArgument(2);

            $t = [];
            $t['name'] = $name;

            $t['index'] = (int)$idx;
            $t['input'] = in_array('input', $ops);
            $t['output'] = in_array('output', $ops);
            $t['optional'] = in_array('optional', $ops);
            $t['comment'] = $api->getArgument(3) ? $api->getArgument(3) : "";

            switch ($typs[0]) {
                case 'string':
                    $t['string'] = true;
                    break;
                case 'integer':
                    $t['integer'] = true;
                    break;
                case 'double':
                    $t['double'] = true;
                    break;
                case 'boolean':
                    $t['boolean'] = true;
                    break;
                case 'file':
                    $t['file'] = true;
                    break;
                case 'array':
                    $t['array'] = true;
                    $t['valtyp'] = $typs[1];
                    break;
                case 'map':
                    $t['map'] = true;
                    $t['keytyp'] = $typs[1];
                    $t['valtyp'] = $typs[2];
                    break;
                default:
                    $t['object'] = true;
                    $t['valtyp'] = $typs[0];
                    break;
            }

            $ret[$name] = $t;
        }

        return $ret;
    }
}
