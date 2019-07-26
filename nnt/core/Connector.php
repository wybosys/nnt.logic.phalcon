<?php

namespace Nnt\Core;

use Phalcon\Http\Request\File;

class Connector
{
    const METHOD_GET = 0;
    const METHOD_POST = 1;

    // http验证
    const OPTION_AUTH = '::nnt::connector::auth'; // value = ['Method', 'Content'] 如果只传了 'Content' 则默认 Method 为 Basic

    // 代理服务器
    const OPTION_PROXY = '::nnt::connector::proxy';

    public function __construct($url = '')
    {
        $this->url = $url;
        $this->method = self::METHOD_GET;
    }

    function arg($k, $v)
    {
        $this->_args[$k] = $v;
    }

    /**
     * @param array $args
     */
    function args($args)
    {
        if (!$args) {
            return;
        }

        // 设置了授权信息
        if (isset($args[self::OPTION_AUTH])) {
            $v = $args[self::OPTION_AUTH];
            unset($args[self::OPTION_AUTH]);

            if (is_string($v)) {
                // 如果是basic格式，需要对值进行base64编码
                $this->auth = 'Basic';
                $this->userpwd = base64_encode($v);
            } else {
                $this->auth = $v[0];
                $this->userpwd = $v[1];
                if ($this->auth === 'Basic') {
                    $this->userpwd = base64_encode($v[1]);
                }
                // 其他格式暂不处理
            }
        }

        // 设置了代理
        if (isset($args[self::OPTION_PROXY])) {
            $this->proxy($args[self::OPTION_PROXY]);
            unset($args[self::OPTION_PROXY]);
        }

        $this->_args = array_merge($this->_args, $args);
    }

    /**
     * @param string $k
     */
    function header($k, $v)
    {
        $this->_headers[$k] = $v;
    }

    /**
     * @param array $headers
     */
    function headers($headers)
    {
        if (!$headers) {
            return;
        }

        // 合并头
        $this->_headers = array_merge($this->_headers, $headers);
    }

    /**
     * @param string $addr
     */
    function proxy($addr)
    {
        $this->_proxy = $addr;
    }

    /**
     * @param string $ua
     */
    function ua($ua)
    {
        $this->_ua = $ua;
    }

    /**
     * @param string $k
     * @param File $f
     */
    function file($k, $f)
    {
        $this->method = self::METHOD_POST;
        $this->arg($k, curl_file_create($f->getTempName(), $f->getType(), $f->getName()));
    }

    /**
     * @param array $files
     */
    function files($files)
    {
        if ($files) {
            $data = [];
            foreach ($files as $k => $f) {
                if ($f instanceof File) {
                    $data[$k] = curl_file_create($f->getTempName(), $f->getType(), $f->getName());
                }
            }

            if ($data) {
                $this->method = self::METHOD_POST;
                $this->args($data);
            }
        }
    }

    // auth方法
    public $auth;

    // auth内容
    public $userpwd;

    // 是否是json请求
    public $json;

    // 是否时xml请求
    public $xml;

    // 是否时devops请求
    public $devops;

    // 代理
    private $_proxy;

    // 请求参数
    private $_args = [];

    // 请求使用的头
    private $_headers = [];

    // 请求的方法
    public $method;

    // 请求的基础url
    public $url;

    // 用户
    private $_ua;

    // 是否完整获取返回数据
    public $full = false;

    function send(): string
    {
        $url = $this->url;

        // 如果特殊定义了输入格式
        if ($this->json || $this->xml)
            $this->method = self::METHOD_POST;

        // 如果时devops请求
        if ($this->devops) {
            // 添加permission的信息
            if (Devops::PermissionEnabled()) {
                $this->_args[Devops::KEY_PERMISSIONID] = Devops::PermissionId();
            }

            // 添加跳过的标记
            if (!Config::IsDevopsRelease()) {
                $this->_args[Devops::KEY_SKIPPERMISSION] = 1;
            }
        }

        // 处理get
        if ($this->method == self::METHOD_GET) {
            if ($this->_args) {
                if (strpos($url, '?') === false)
                    $url .= '/?';
                else
                    $url .= '&';
                $url .= http_build_query($this->_args);
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, $this->full ? 1 : 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        // 处理post
        if ($this->method == self::METHOD_POST) {
            curl_setopt($ch, CURLOPT_POST, 1);
            if ($this->json) {
                $str = json_encode($this->_args);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $str);
                $this->_headers['Content-Type'] = 'application/json; charset=utf-8';
                $this->_headers['Content-Length'] = (string)strlen($str);
            } else if ($this->xml) {
                $str = Kernel::toXml($this->_args, [
                    'root' => 'xml'
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $str);
                $this->_headers['Content-Type'] = 'application/xml; charset=utf-8';
                $this->_headers['Content-Length'] = (string)strlen($str);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_args);
                $this->_headers['Content-Type'] = 'multipart/form-data';
            }
        }

        if (!$this->_ua || $this->_ua == 'unknown') {
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux) AppleWebKit/600.1.4 (KHTML, like Gecko) NetType/WIFI');
        } else {
            curl_setopt($ch, CURLOPT_USERAGENT, $this->_ua);
        }

        // 收集请求头
        $reqheaders = [];

        // 设置验证等信息
        if ($this->auth) {
            $reqheaders[] = "Authorization: $this->auth $this->userpwd";
        }

        // 处理自定义头
        if ($this->_headers) {
            foreach ($this->_headers as $k => $v) {
                $reqheaders[] = $k . ': ' . $v;
            }
        }

        // 设置请求代理
        if ($this->_proxy) {
            curl_setopt($ch, CURLOPT_PROXY, $this->_proxy);
        }

        // 设置请求头
        if ($reqheaders) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $reqheaders);
        }

        // 解决curl卡顿的问题
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        $this->_response = curl_exec($ch);
        if ($this->_response === false) {
            $this->errno = Kernel::$LastErrorCode = curl_errno($ch);
            $this->errmsg = Kernel::$LastErrorMessage = curl_error($ch);
        }

        if ($this->full) {
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $this->respheaders = array_filter(explode("\r\n", substr($this->_response, 0, $header_size)));
            $this->body = substr($this->_response, $header_size);
        } else {
            $this->body = $this->_response;
        }

        // 发送结束自动关闭
        @curl_close($ch);

        return $this->body;
    }

    // 返回的内容
    private $_response;

    // 如果请求错误，保存错误信息
    public $errno = 0;
    public $errmsg = '';

    // 返回的消息主体
    public $body;

    // 返回的头
    public $respheaders;

}
