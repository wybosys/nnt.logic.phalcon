<?php

use Nnt\Controller\Api;
use Nnt\Controller\Config;
use Nnt\Controller\Service;
use Nnt\Util\Apidoc;

class ExportApis
{
    /**
     * @Api(1, [boolean], [input, optional], "生成logic客户端使用的api")
     */
    public $logic;

    /**
     * @Api(2, [boolean], [input, optional], "生成h5g游戏使用api")
     */
    public $h5g;

    /**
     * @Api(3, [boolean], [input, optional], "生成vue项目中使用的api")
     */
    public $vue;
}

class NntController extends Api
{
    /**
     * @Action(null, [noauth, noexport], "api文档")
     */
    function doc()
    {
        Apidoc::DocView($this);
    }

    /**
     * @Action(ExportApis, [noauth, noexport], "导出api文档")
     */
    function export()
    {
        Apidoc::DocExport($this);
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
