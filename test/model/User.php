<?php

namespace Test\Model;

use Nnt\Model\IAuthedUser;

class User implements IAuthedUser
{
    function userIdentifier(): string
    {
        return "test";
    }
}
