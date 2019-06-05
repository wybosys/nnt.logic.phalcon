<?php

namespace Nnt\Controller;

use Nnt\Core\Code;
use Nnt\Core\Connector;
use Nnt\Core\Kernel;
use Nnt\Model\HttpContentType;
use Nnt\Model\HttpMethod;
use Nnt\Model\Logic;
use Nnt\Model\ResponseData;
use Phalcon\Http\Request\File;

class Rest extends Session
{
    static function Fetch(Logic $m)
    {
        $connect = Api::$shared->instanceConnector();
        $connect->url = $m->requestUrl();

        // 所有请求参数
        $params = $m->requestParams();

        // 从params里提取出文件
        $files = [];
        foreach ($params->fields as $key => $val) {
            if ($val instanceof File) {
                $files[$key] = $val;
                unset($params->fields[$key]);
            }
        }

        $connect->args($params->fields);
        $connect->files($files);
        $connect->method = $m->method == HttpMethod::$GET ? Connector::METHOD_GET : Connector::METHOD_POST;
        $connect->devops = true;
        $connect->full = true;
        $connect->send();

        // 处理获得的数据
        self::ProcessResponse($m, $connect->body, $connect->respheaders);
    }

    static function Get(Logic $m)
    {
        return self::ImplGet("\Nnt\Controller\Rest", $m);
    }

    static function ProcessResponse(&$m, &$body, &$headers)
    {
        if ($headers[0] != "HTTP/1.1 200 OK") {
            throw new \Exception($headers[0], Code::FAILED);
        }

        $rd = new ResponseData();

        // 根据返回的类型分别处理
        if ($m->responseType == HttpContentType::$JSON) {
            $rd->body = Kernel::toJsonObj($body, null, true);
            if (!$rd->body) {
                throw new \Exception("收到的数据不符合定义", Code::FORMAT_ERROR);
            }

            $m->parseData($rd);
        } else {
            $m->parseData($rd);
        }
    }
}
