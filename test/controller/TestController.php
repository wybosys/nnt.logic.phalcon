<?php

use App\Controller\Api;
use App\Controller\Service;

class TestController extends Api
{
    /**
     * @Action(\Test\Model\Echoo)
     */
    function echoo(\Test\Model\Echoo $mdl)
    {
        $mdl->output = $mdl->input;
    }

    /**
     * @Action(\Test\Model\HostInfo)
     */
    function hostinfo(\Test\Model\HostInfo $mdl)
    {
        $mdl->info = gethostbyname($mdl->name);
    }

    /**
     * @Action(\Test\Model\UploadImage)
     */
    function uploadimage(\Test\Model\UploadImage $mdl)
    {
        $ret = Service::Call("devops/image", ["action" => "imagestore.upload"], ["file" => $mdl->file]);
        $mdl->path = $ret->data->path;
    }

    /**
     * @Action(\Test\Model\Output)
     */
    function phpinfo(\Test\Model\Output $mdl)
    {
        $mdl->output = phpinfo();
    }
}
