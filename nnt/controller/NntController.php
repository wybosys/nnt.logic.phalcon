<?php

use Nnt\Controller\Api;
use Nnt\Core\Config;
use Nnt\Core\Devops;
use Nnt\Util\Apidoc;

class ExportApis
{
    /**
     * @Api(1, [boolean], [input, optional], "生成基于 logic.node 项目使用的api")
     */
    public $node;

    /**
     * @Api(2, [boolean], [input, optional], "生成基于 logic.php 项目使用的api")
     */
    public $php;

    /**
     * @Api(3, [boolean], [input, optional], "生成h5g游戏使用api")
     */
    public $h5g;

    /**
     * @Api(4, [boolean], [input, optional], "生成vue项目中使用的api")
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
    function export(ExportApis $mdl)
    {
        Apidoc::DocExport($this, $mdl);
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

        if (Devops::PermissionEnabled() && !Config::IsDevopsRelease()) {
            $output["permission"] = Devops::PermissionId();
        }

        echo json_encode($output);
        exit();
    }
}
