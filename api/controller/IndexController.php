<?php

use App\Controller\Api;
use App\Controller\Doc;
use App\Controller\ApiBuilder;

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
    public function apiexport()
    {
        ApiBuilder::export($this);
        exit;
    }
}
