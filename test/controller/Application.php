<?php

namespace Test\Controller;

use Nnt\Controller\Api;
use Nnt\Core\UrlT;
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
        $sign1 = @$params['_sign'];
        $sign2 = md5(UrlT::Encode($inputs) . '12345');
        return $sign1 == $sign2;
    }
}
