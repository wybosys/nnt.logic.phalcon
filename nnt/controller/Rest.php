<?php

namespace Nnt\Controller;

use Nnt\Model\Logic;
use Nnt\Model\HttpMethod;
use Phalcon\Http\Request\File;

class Rest extends Session
{
    static function Fetch(Logic &$m)
    {
        $url = "";
        if ($m->host)
            $url .= $m->host;
        $url .= $m->requestUrl();
        if (strpos($url, '?') == -1)
            $url .= "?/";

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
        if (count($files))
            $m->method = HttpMethod::$POST;

        // 根据post和get分别处理
        if ($m->method == HttpMethod::$GET) {
            $p = [];
            foreach ($params->fields as $k => $f) {
                $p[] = $k . "=" . urlencode($f);
            }

            // 添加permission的控制
            if (Service::PermissionEnabled()) {
                $p[KEY_PERMISSIONID] = Service::PermissionId();
            }

            // 添加跳过的标记
            if (!Config::IsDevopsRelease()) {
                $p[KEY_SKIPPERMISSION] = 1;
            }

            if (count($p))
                $url .= "&" . implode('&', $p);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $resp = curl_exec($ch);
            curl_close($ch);
        } else {
            $p = [];

            // 添加permission的控制
            if (Service::PermissionEnabled()) {
                $p[KEY_PERMISSIONID] = Service::PermissionId();
            }

            // 添加跳过的标记
            if (!Config::IsDevopsRelease()) {
                $p[KEY_SKIPPERMISSION] = 1;
            }

            if (count($p))
                $url .= "&" . implode('&', $p);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: multipart/form-data"
            ]);

            // 传输文件
            $data = [];
            if (count($files)) {
                foreach ($files as $key => $file) {
                    $data[$key] = curl_file_create($file->getTempName(), $file->getType(), $file->getName());
                }
            }

            // 传输普通参数
            foreach ($params->fields as $k => $f) {
                $data[$k] = $f;
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            $resp = curl_exec($ch);
            curl_close($ch);
        }

        // 处理获得的数据
    }
}
