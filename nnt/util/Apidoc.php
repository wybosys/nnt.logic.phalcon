<?php

namespace Nnt\Util;

use Nnt\Controller\Api;
use Nnt\Controller\Service;
use Nnt\Model\Proto;

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
        $controller = ucfirst($router) . 'Controller';
        $decl = Proto::DeclarationOf($controller, true, true, true);

        // 读取每个action的信息
        $ret = [];
        foreach ($decl->members as $action => $info) {
            $t = [];
            $t["name"] = $t["action"] = "$name.$action";
            $t["comment"] = $info->comment;
            $modelClazz = $info->model;
            if (!$modelClazz)
                $t["params"] = [];
            else
                $t["params"] = self::ParametersInfo($modelClazz);
            $ret[] = $t;
        }

        return $ret;
    }

    static public function ParametersInfo(string $model)
    {
        $decl = Proto::DeclarationOf($model, true, true, true);
        if (!$decl)
            return [];

        $ret = [];
        foreach ($decl->props as $name => $prop) {
            $t = [];
            $t['name'] = $name;

            $t['index'] = $prop->index;
            $t['input'] = $prop->input;
            $t['output'] = $prop->output;
            $t['optional'] = $prop->optional;
            $t['comment'] = $prop->comment;
            $t['string'] = $prop->string;
            $t['integer'] = $prop->integer;
            $t['double'] = $prop->double;
            $t['boolean'] = $prop->boolean;
            $t['file'] = $prop->file;
            $t['array'] = $prop->array;
            $t['map'] = $prop->map;
            $t['keytyp'] = $prop->keytyp;
            $t['valtyp'] = $prop->valtyp;
            $t['object'] = $prop->object;

            $ret[] = $t;
        }

        return $ret;
    }
}

class Apidoc
{
    // 将配置文件中配置的对象加载到环境中, 返回类列表
    static function LoadEntrys()
    {
        // 处理配置文件中描述的需要导出的结构
        $cfgph = APP_DIR . '/app.json';
        $cfg = json_decode(file_get_contents($cfgph));

        // 读取apiexport的配置
        $cfgexport = $cfg->apidoc->export;
        $cfgrouter = $cfgexport->router;
        $cfgmodel = $cfgexport->model;

        // 加载所有路由
        $routers = [];
        foreach ($cfgrouter as $router) {
            $routerClazz = ucfirst($router) . 'Controller';
            $phpFile = APP_DIR . "/$router/controller/{$routerClazz}.php";
            if (!file_exists($phpFile))
                throw new \Exception("没有找到 $phpFile");
            include $phpFile;
            $routers[] = $router;
        }
        $routers[] = 'nnt';

        // 加载所有的模型
        $models = [];
        foreach ($cfgmodel as $model) {
            $cmps = explode('/', $model);
            for ($i = 0, $l = count($cmps); $i < $l; ++$i)
                $cmps[$i] = ucfirst($cmps[$i]);
            $modelClazz = implode('/', $cmps);
            $phpFile = APP_DIR . "/{$model}.php";
            if (!file_exists($phpFile))
                throw new \Exception("没有找到 $phpFile");
            include $phpFile;
            $models[] = $modelClazz;
        }

        return [
            "routers" => $routers,
            "models" => $models
        ];
    }

    static function DocView(Api $self)
    {
        $self->response->setContentType('text/html');
        $self->view->setViewsDir(dirname(__DIR__) . '/view');
        $self->view->pick('Apidoc');

        // 加载实体
        $entrys = self::LoadEntrys();

        // 提取所有的actions
        $infos = Doc::ActionsInfo($entrys);

        // 组装volt页面需要的数据
        $self->view->actions = json_encode($infos);
        $self->view->start()->finish();
    }

    static function DocExport(Api $self, $logic, $h5g, $vue)
    {
        // 加载实体
        $entrys = self::LoadEntrys();

        $params = [
            'domain' => Service::GetDomain(),
            'clazzes' => [],
            'enums' => [],
            'consts' => [],
            'routers' => []
        ];

        // 遍历所有的模型，生成模型段
        foreach ($entrys . models as $model) {
            // 类名为最后一段
            $cmpsClazz = explode('/', $model);
            $clazzName = $cmpsClazz[count($cmpsClazz) - 1];

            // 解析model的定义
            $reflect = Proto::Reflect($model);
            $rclazz = $reflect->getClassAnnotations();
            if ($rclazz->has('Model')) {
                $aclazz = $rclazz->get('Model');
                $ops = $aclazz->getArgument(0);
                $super = $aclazz->getArgument(1);
                if (in_array($ops, 'hidden'))
                    continue;
                // 如果是enum
                // 如果是const
                // 其他
                {
                    $clazz = [
                        'name' => $clazzName,
                        'super' => $super ? $super : "ApiModel",
                        'fields' => []
                    ];
                    $params['clazzes'][] = $clazz;
                }
            }
        }
    }
}
