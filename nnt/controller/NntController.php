<?php

use Nnt\Controller\Api;
use Nnt\Controller\Config;
use Nnt\Controller\Service;
use Nnt\Util\Apidoc;

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
     * @Action(null, [noauth, noexport], "导出api文档")
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
