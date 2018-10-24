<?php

// 对接LogicAPI的基础类

namespace Nnt\Model;

class HttpMethod
{
    static $GET = 0;
    static $POST = 1;
}

class HttpContentType
{
    static $MANUAL = 0; // 手动处理
    static $URLENCODED = 1;
    static $JSON = 2;
    static $XML = 3;
}

class RequestParams
{
    // 参数列表
    public $fields = [];

    // 如果是xml这种树形参数，根节点名称
    public $root = "root";
}

class ResponseData
{
    // 响应的code
    public $code;

    // 内容类型
    public $type;

    // 返回的所有数据
    public $body;
}

abstract class Logic
{
    function __construct()
    {
        $this->method = HttpMethod::$GET;
        $this->requestType = HttpContentType::$URLENCODED;
        $this->responseType = HttpContentType::$JSON;
    }

    /**
     * 请求的服务器地址
     * @var string
     */
    public $host;

    // 返回码
    /**
     * @var int
     */
    public $code;

    // 错误消息
    /**
     * @var string
     */
    public $error;

    // 请求动作
    /**
     * @var string
     */
    public $action;

    // 请求方式
    public $method;

    // 默认请求格式
    public $requestType;

    // 返回数据的格式
    public $responseType;

    // 附加的请求数据
    public $additionParams;

    // 组装请求的url
    abstract function requestUrl(): string;

    // 组装请求的参数集
    function requestParams(): RequestParams
    {
        $t = Proto::Input($this);
        $r = new RequestParams();

        // 先设置额外的参数，使得徒手设置的参数优先级最高
        if ($this->additionParams) {
            foreach ($this->additionParams as $k => $v)
                $r->fields[$k] = $v;
        }

        // 设置徒手参数
        foreach ($t as $key => $val) {
            if ($val == null)
                continue;
            $r->fields[$key] = $val;
        }
        return $r;
    }

    static function NewRequest($req)
    {
        $clz = $req[1];
        $r = new $clz();
        $r->action = $req[0];
        return $r;
    }
}
