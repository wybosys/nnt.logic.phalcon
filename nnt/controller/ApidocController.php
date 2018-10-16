<?php

use Nnt\Controller\Api;
use Nnt\Controller\ApiBuilder;
use Nnt\Controller\Config;
use Nnt\Controller\Doc;

class ApidocController extends Api
{
    static function DocView(Api $self)
    {
        $self->response->setContentType('text/html');
        $self->view->setViewsDir(dirname(__DIR__) . '/view');
        $self->view->pick('Apidoc');

        // 处理配置文件中描述的需要导出的结构
        $cfgph = APP_DIR . '/app.json';
        $cfg = json_decode(file_get_contents($cfgph));

        // 读取apiexport的配置
        $cfgexport = $cfg->apidoc->export;
        $cfgrouter = $cfgexport->router;
        $cfgmodel = $cfgexport->model;

        // 加载所有路由
        foreach ($cfgrouter as $router) {
            $routerClazz = ucfirst($router);
            $phpFile = APP_DIR . "/$router/controller/{$routerClazz}Controller.php";
            if (!file_exists($phpFile))
                throw new \Exception("没有找到 $phpFile");
            include $phpFile;
        }

        // 提取所有的actions

        // 组装volt页面需要的数据
        $data = [
            "name" => $self->router->getControllerName(),
            "actions" => Doc::Actions($self)
        ];
        $self->view->router = json_encode($data);
        $self->view->start()->finish();
    }

    static function DocExport(Api $self)
    {

    }
}
