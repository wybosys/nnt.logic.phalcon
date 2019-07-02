<?php

namespace Test\Controller;

use Nnt\Controller\Api;
use Test\Model\User;

class Application extends \Nnt\Controller\Application
{
    function auth($params)
    {
        $this->di->setShared('user', new User());
    }

    function signature($params): bool
    {
        $inputs = Api::FilterInputParams($params);
        var_dump($inputs);
        die;
    }
}
