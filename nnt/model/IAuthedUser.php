<?php

namespace Nnt\Model;

interface IAuthedUser
{
    // 返回用户的标识
    function userIdentifier(): string;
}
