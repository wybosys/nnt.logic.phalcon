<?php

namespace App\Model;

class Code
{
    const OK = 0;
    const FAILED = -1;
    const EXCEPTION = -2;
    const PARAMETERS = -3; // 参数错误
    const NEED_AUTH = -4; // 需要登陆
    const TARGET_NOT_FOUND = -5; // 没有找到指定的对象
    const OVERFLOW = -6; // 溢出
    const DB_ERROR = -7; // 数据库错误错误
    const TARGET_EXISTS = -8; // 目标已经存在
    const USER_BLOCK = -9; // 账号被锁定
    const NO_USER = -10; // 用户不存在
    const PERMISSION_DISALLOW = -11; // 不允许访问
}