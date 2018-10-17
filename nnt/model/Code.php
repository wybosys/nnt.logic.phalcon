<?php

namespace Nnt\Model;

class Code
{
    const UNKNOWN = -1000;
    const EXCEPTION = -999; // 遇到了未处理的异常
    const ROUTER_NOT_FOUND = -998; // 没有找到路由
    const CONTEXT_LOST = -997; // 上下文丢失
    const MODEL_ERROR = -996; // 恢复模型失败
    const PARAMETER_NOT_MATCH = -995; // 参数不符合要求
    const NEED_AUTH = -994; // 需要登陆
    const TYPE_MISMATCH = -993; // 参数类型错误
    const FILESYSTEM_FAILED = -992; // 文件系统失败
    const FILE_NOT_FOUND = -991; // 文件不存在
    const ARCHITECT_DISMATCH = -990; // 代码不符合标准架构
    const SERVER_NOT_FOUND = -989; // 没有找到服务器
    const LENGTH_OVERFLOW = -988; // 长度超过限制
    const TARGET_NOT_FOUND = -987; // 目标对象没有找到
    const PERMISSIO_FAILED = -986; // 没有权限
    const WAIT_IMPLEMENTION = -985; // 等待实现
    const ACTION_NOT_FOUND = -984; // 没有找到动作
    const TARGET_EXISTS = -983; // 已经存在
    const STATE_FAILED = -982; // 状态错误
    const UPLOAD_FAILED = -981; // 上传失败
    const MASK_WORD = -980; // 有敏感词
    const SELF_ACTION = -979; // 针对自己进行操作
    const PASS_FAILED = -978; // 验证码匹配失败
    const OVERFLOW = -977; // 数据溢出
    const AUTH_EXPIRED = -976; // 授权过期
    const SIGNATURE_ERROR = -975; // 签名错误

    const IM_CHECK_FAILED = -899; // IM检查输入的参数失败
    const IM_NO_RELEATION = -898; // IM检查双方不存在关系

    const SOCK_WRONG_PORTOCOL = -860; // SOCKET请求了错误的通讯协议
    const SOCK_AUTH_TIMEOUT = -859; // 因为连接后长期没有登录，所以服务端主动断开了链接
    const SOCK_SERVER_CLOSED = -858; // 服务器关闭

    const THIRD_FAILED = -5; // 第三方出错
    const MULTIDEVICE = -4; // 多端登陆
    const HFDENY = -3; // 高频调用被拒绝（之前的访问还没有结束) high frequency deny
    const TIMEOUT = -2; // 超时
    const FAILED = -1; // 一般失败
    const OK = 0; // 成功
}
