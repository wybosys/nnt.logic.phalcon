<?php

namespace Nnt\Sdks;

use Nnt\Model\IAuthedUser;

class SdkUserInfo implements IAuthedUser
{
    /**
     * @var string
     * @Api(1, [string], [output], "平台中的用户id")
     */
    public $userid;

    /**
     * @var string
     * @Api(2, [string], [output], "昵称")
     */
    public $nickname;

    /**
     * @var int
     * @Api(3, [integer], [output], "性别")
     */
    public $gender;

    /**
     * @var string
     * @Api(4, [string], [output], "头像")
     */
    public $avatar;

    function userIdentifier(): string
    {
        return $this->userid;
    }
}
