<?php

use Nnt\Controller\Api;
use Nnt\Controller\ApiBuilder;
use Nnt\Controller\Config;
use Nnt\Controller\Doc;

// 使用logic规则访问（通过action传数据）将路由到Index
class IndexController extends Api
{
    /**
     * @Action(null, [noauth, noexport], "api文档")
     */
    function doc()
    {
        $this->response->setContentType('text/html');
        $this->view->setViewsDir(dirname(__DIR__) . '/view');
        $this->view->pick('Apidoc');

        // 处理配置文件中描述的需要导出的结构
        $cfgph = APP_DIR . '/app.json';
        $cfg = json_decode(file_get_contents($cfgph));

        // 读取apiexport的配置
        $cfgexport = $cfg->apidoc->export;
        $cfgrouter = $cfgexport->router;
        $cfgmodel = $cfgexport->model;

        // 提取所有的models

        // 提取所有的actions

        // 组装volt页面需要的数据
        $data = [
            "name" => $this->router->getControllerName(),
            "actions" => Doc::Actions($this)
        ];
        $this->view->router = json_encode($data);
        $this->view->start()->finish();
    }

    /**
     * @Action(null, [noauth, noexport], "导出api文档")
     */
    public function export()
    {
        ApiBuilder::export($this);
        exit;
    }

    /**
     * @Action(null, [noauth, noexport])
     */
    function description()
    {
        $output = [
            "configuration" => Config::Use("LOCAL", "DEVOPS", "DEVOPS_RELEASE"),
            "server" => $_SERVER,
            "request" => $_REQUEST
        ];

        if (Service::PermissionEnabled() && !Config::IsDevopsRelease()) {
            $output["permission"] = Service::PermissionId();
        }

        echo json_encode($output);
        exit();
    }
}
