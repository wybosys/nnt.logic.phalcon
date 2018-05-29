<?php

namespace Test\Model;

use App\Controller\IAuth;

class User implements IAuth
{
    function userIdentifier(): string
    {
        return null;
    }
}