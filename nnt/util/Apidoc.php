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

        // 添加基础库
        $BASE_MODELS = [
            'nnt/model/Nil'
            //'nnt/model/Code' // 不输出code定义，避免重复
        ];
        foreach ($BASE_MODELS as $e) {
            if (!in_array($e, $cfgmodel))
                $cfgmodel[] = $e;
        }

        // 加载所有路由
        $routers = [];
        foreach ($cfgrouter as $router) {
            $routerClazz = ucfirst($router) . 'Controller';
            $phpFile = APP_DIR . "/$router/controller/{$routerClazz}.php";
            if (!file_exists($phpFile))
                throw new \Exception("没有找到 $phpFile");
            include_once $phpFile;
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
            include_once $phpFile;
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

    static function DocExport(Api $self, \ExportApis $opts)
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
            $clazzName = Proto::GetClassName($model);

            // 解析model的定义
            $decl = Proto::DeclarationOf($model, true, true, true);
            if ($decl->hidden)
                continue;

            // 如果是enum
            if ($decl->enum) {
                // 静态变量是用类const变量进行的模拟
                $em = [
                    'name' => $clazzName,
                    'defs' => []
                ];
                foreach (Proto::ConstsOfClass($model) as $name => $val) {
                    $em['defs'][] = [
                        'name' => $name,
                        'value' => $val
                    ];
                }
                $params['enums'][] = $em;
            } // 如果是const
            else if ($decl->const) {
                foreach (Proto::ConstsOfClass($model) as $name => $val) {
                    $params['consts'][] = [
                        'name' => strtoupper($clazzName) . '_' . strtoupper($name),
                        'value' => is_string($val) ? ("\"$val\"") : $val
                    ];
                }
            } // 其他
            else {
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
                if ($method->noexport)
                    continue;

                $d = [];
                $d['name'] = ucfirst($router) . ucfirst($name);
                $d['action'] = "$router.$name";

                $cn = Proto::GetClassName($method->model);
                if ($opts->vue) {
                    $d['type'] = $cn;
                } else {
                    $d['type'] = "models." . $cn;
                }

                $d['comment'] = $method->comment;
                $params['routers'][] = $d;
            }
        }

        // 渲染模板
        $apis = APP_DIR . "/nnt/view/apidoc/";
        if ($opts->logic)
            $apis .= "apis-logic.dust";
        else if ($opts->h5g)
            $apis .= "apis-h5g.dust";
        else if ($opts->vue)
            $apis .= "apis-vue.dust";
        else if ($opts->php)
            $apis .= "apis-php.dust";

        // 数据填模板
        $dust = new \Dust\Dust();
        $tpl = $dust->compileFile($apis);
        $result = $dust->renderTemplate($tpl, $params);

        // 构造response
        $resp = $self->response;

        // 特殊的输出
        if ($opts->php) {
            $output = TMP_DIR . '/api.php';
            $result = "<?php\n" . $result;
            $resp->setContentType('text/php');
            $resp->setFileToSend($output, str_replace('/', '-', $params['domain']) . '-api.php');
        } else {
            $output = TMP_DIR . '/api.ts';
            $resp->setContentType('application/javascript');
            $resp->setFileToSend($output, str_replace('/', '-', $params['domain']) . '-api.ts');
        }

        echo $result;
    }
}
