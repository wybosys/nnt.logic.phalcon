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
            $modelClazz = implode('\\', $cmps);
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
        foreach ($entrys['models'] as $model) {
            // 类名为最后一段
            $cmpsClazz = explode('/', $model);
            $clazzName = $cmpsClazz[count($cmpsClazz) - 1];

            // 解析model的定义
            $decl = Proto::DeclarationOf($model, true, true, true);
            if ($decl->hidden)
                continue;

            // 如果是enum
            // 如果是const
            // 其他
            {
                $clazz = [
                    'name' => $clazzName,
                    'super' => $decl->super ? $decl->super : "ApiModel",
                    'fields' => []
                ];
                foreach ($decl->props as $name => $prop) {
                    if (!$prop->input && !$prop->output)
                        continue;

                    $typ = Proto::FpToTypeDef($prop);
                    $deco = Proto::FpToDecoDef($prop, 'Model.');
                    $clazz['fields'][] = [
                        'name' => $name,
                        'type' => $typ,
                        'optional' => $prop->optional,
                        'file' => $prop->file,
                        'enum' => $prop->enum,
                        'input' => $prop->input,
                        'deco' => $deco
                    ];
                }
                $params['clazzes'][] = $clazz;
            }
        }

        // 遍历所有的路由，生成接口段数据
        foreach ($entrys['routers'] as $router) {
            $routerClazz = ucfirst($router) . 'Controller';
            $decl = Proto::DeclarationOf($routerClazz, false, true, false);
            if (!$decl)
                continue;

            foreach ($decl->members as $name => $method) {
                $d = [];
                $d['name'] = ucfirst($router) . ucfirst($name);
                $d['action'] = "$router.$name";
                if ($vue) {
                    $d['type'] = $method->model;
                } else {
                    $d['type'] = "models." . $method->model;
                }
                $d['comment'] = $method->comment;
                $params['routers'][] = $d;
            }
        }

        // 渲染模板
        $apis = '';
        if ($logic)
            $apis = APP_DIR . "/nnt/view/apidoc/apis-logic.dust";
        else if ($h5g)
            $apis = APP_DIR . "/nnt/view/apidoc/apis-h5g.dust";
        else if ($vue)
            $apis = APP_DIR . "/nnt/view/apidoc//apis-vue.dust";

        $dust = new \Dust\Dust();
        $tpl = $dust->compileFile($apis);
        $result = $dust->renderTemplate($tpl, $params);
        echo $result;
    }
}
