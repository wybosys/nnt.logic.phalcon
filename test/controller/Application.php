<?php

namespace Test\Controller;

use Test\Model\User;

class Application extends \Nnt\Controller\Application
{
    function auth($params)
    {
        $this->di->setShared('user', new User());
    }
}
