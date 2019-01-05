<?php

namespace Nnt\Sdks;

class SdkUserInfo
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
}
