<?php

use Nnt\Controller\Api;
use Nnt\Controller\ApiBuilder;
use Nnt\Controller\Config;
use Nnt\Controller\Doc;

class ApidocController extends Api
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

    static function DocExport(Api $self)
    {

    }
}
