<?php

use App\Controller\Api;

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
}
