<?php

namespace App\Controller;

use App\Model\Code;
use App\Model\Proto;
use Phalcon\Mvc\Controller;

class ActionInfo
{
    /**
     * @var string 动作名
     */
    public $name;

    /**
     * @var mixed 动作绑定的模型
     */
    public $model;

    /**
     * @var string 注释
     */
    public $comment;

    /**
     * @var bool 是否需要登录
     */
    public $needauth = true;

    /**
     * @var bool 是否导出到api
     */
    public $export = true;

    /**
     * @var bool 是否使用缓存
     */
    public $cache = false;

    /**
     * @var int 缓存时间
     */
    public $ttl = 60;

    function __construct(string $mthnm, \Phalcon\Annotations\Annotation $ann)
    {
        $this->name = $mthnm;
        $this->model = $ann->getArgument(0);
        $tmp = $ann->getArgument(1);
        if (is_string($tmp)) {
            $this->comment = $tmp;
        } else if (is_array($tmp)) {
            foreach ($tmp as $e) {
                if ($e == 'noauth') {
                    $this->needauth = false;
                } else if ($e == 'noexport') {
                    $this->export = false;
                } else if (strpos($e, 'cache') !== false) {
                    if (preg_match('/cache_(\d+)/', $e, $res) === false)
                        throw new \Exception("缓存配置错误");
                    $this->cache = true;
                    $this->ttl = (int)$res[1];
                }
            }

            $this->comment = $ann->getArgument(2);
        }

        // 如果打开了cache，但是当前全局不支持cache，自动关闭
        if ($this->cache && !Cache::IsEnabled())
            $this->cache = false;
    }
}

class Api extends Controller
{
    /**
     * @var ActionInfo[]
     */
    private $_actions = [];

    function initialize()
    {
        header('Access-Control-Allow-Origin:*');

        $actnm = $this->router->getActionName();

        // 把简化的action恢复成框架需要的actionAction
        $reflect = Proto::Reflect($this);
        $methods = $reflect->getMethodsAnnotations();
        if ($methods) {
            if (array_key_exists($actnm, $methods)) {
                $method = $methods[$actnm];
                if ($method->has('Action')) {
                    $act = $method->get('Action');
                    $this->_actions[$actnm . 'Action'] = new ActionInfo($actnm, $act);
                }
            }
        }

        // 默认输出为json
        $this->response->setContentType('application/json');
    }


    function indexAction()
    {
        // pass
    }

    /**
     * @throws \Throwable
     */
    function __call($name, $arguments)
    {
        if (!isset($this->_actions[$name])) {
            throw new \Exception("没有找到名为 ${name} 的Action");
        }

        // 动作信息
        $info = $this->_actions[$name];

        // 收集参数
        $params = Proto::CollectParameters($this->request);

        // 判断有没有登陆
        $auth = null;
        if ($info->needauth) {
            if ($this->di->has('user')) {
                $auth = $this->di->get('user');
            }
            if (!$auth) {
                $err = "没有找到登录数据";
            } else {
                $uid = @$auth->userIdentifier();
                if (!$uid) {
                    $auth = null;
                    $err = "没有找到用户的标识";
                }
            }
            if (!$auth) {
                echo json_encode([
                    'code' => Code::NEED_AUTH,
                    'error' => $err
                ]);
                return;
            }
        } else if (Service::PermissionEnabled()) {
            // 对不需要登录的接口进行权限验证
            // local时不判断
            // devops时设置了skip不判断
            // 访问的是apidoc不判断
            while (1) {
                if (Config::IsLocal())
                    break;
                if (Config::IsDevops() && isset($params[KEY_SKIPPERMISSION]) && $params[KEY_SKIPPERMISSION])
                    break;
                if ($name == 'apidocAction')
                    break;

                // 判断代码
                if (!Service::AllowClient($this->request)) {
                    // 使用permission规则
                    if (!isset($params[KEY_PERMISSIONID])) {
                        echo json_encode([
                            'code' => Code::PERMISSION_DISALLOW
                        ]);
                        return;
                    }
                    $permid = $params[KEY_PERMISSIONID];
                    if (!Service::PermissionLocate($permid)) {
                        echo json_encode([
                            'code' => Code::PERMISSION_DISALLOW
                        ]);
                        return;
                    }
                }

                break;
            }
        }

        // 如果开了缓存，则尝试从缓存中恢复数据
        $cache = null;
        if ($info->cache) {
            $cache = new Cache();
        }

        // 初始化访问的模型
        $model = null;
        $modelclz = $info->model;
        $inputs = [
            "__action" => $name
        ];
        if ($modelclz) {
            $model = new $modelclz();

            // 检查数据是否满足模型的定义
            $sta = Proto::Check($params, $model);
            if ($sta != Code::OK) {
                $this->log($sta);
                echo json_encode([
                    'code' => $sta
                ]);
                return;
            }

            if ($cache) {
                // 使用模型信息命中缓存(所有input参数+用户的登录信息)
                $inputs = Proto::Input($model);
            }
        }

        // 如果开了缓存，则尝试从缓存中恢复数据
        if ($info->cache) {
            $cache = new Cache();
            // 如果包含登录信息，则输入参数中加入用户id
            if ($auth) {
                $inputs['__useridentifier'] = $auth->userIdentifier();
            }
            // 将inputs转变为key
            $cachekey = hash("sha256", json_encode($inputs));
            $record = $cache->load($cachekey);
            if ($record) {
                echo $record;
                return;
            }
        }

        // 调用实现的动作
        try {
            if ($model)
                $sta = call_user_func([
                    $this,
                    $info->name
                ], $model);
            else
                $sta = call_user_func_array([
                    $this,
                    $info->name
                ], $arguments);
            if ($sta != Code::OK) {
                $this->log($sta);
                echo json_encode([
                    'code' => $sta
                ]);
            } else {
                $headers = $this->response->getHeaders();
                if ($headers->get('Content-Type') === 'application/json') {
                    $out = Proto::Output($model);
                    $this->log(Code::OK);
                    $json = json_encode([
                        'code' => Code::OK,
                        'message' => count($out) ? $out : '{}'
                    ]);

                    // 如果打开了cache，则自动保存到缓存中
                    if ($cache) {
                        $cache->save($cachekey, $json, $info->ttl);
                    }

                    echo $json;
                }
            }
        } catch (\Throwable $ex) {
            $code = $ex->getCode(); // 逻辑主动抛出的code不可能为0
            if ($code == 0) {
                $this->log(Code::EXCEPTION, $ex);
                echo json_encode([
                    'code' => Code::EXCEPTION,
                    'error' => $ex->getMessage()
                ]);
                throw $ex;
            } else {
                $this->log($code, $ex->getMessage());
                echo json_encode([
                    'code' => $code,
                    'error' => $ex->getMessage()
                ]);
            }
        }
    }

    /**
     * @Action(null, [noauth, noexport])
     */
    function description()
    {
        $output = [
            "configuration" => Config::Use("LOCAL", "DEVOPS", "DEVOPS_RELEASE"),
            "permission" => Service::PermissionEnabled() ? Service::PermissionId() : "disabled",
            "server" => $_SERVER,
            "request" => $_REQUEST
        ];
        echo json_encode($output);
        exit();
    }

    /**
     * @param string|int $codeOrMsg
     * @param string|\Exception $msg
     */
    function log($codeOrMsg, $msg = null)
    {
        if (is_string($codeOrMsg)) {
            $typ = \Phalcon\Logger::INFO;
            $code = \App\Model\Code::OK;
            $data = $codeOrMsg;
        } else {
            $code = $codeOrMsg;
            if (is_string($msg)) {
                $typ = \Phalcon\Logger::INFO;
                $data = $msg;
            } else if ($msg instanceof \Exception) {
                $typ = \Phalcon\Logger::CRITICAL;
                $data = [
                    "f" => $msg->getFile(),
                    "m" => $msg->getMessage(),
                    "l" => $msg->getLine(),
                    "c" => $msg->getCode(),
                    "t" => $msg->getTraceAsString()
                ];
            } else if ($msg instanceof \Error) {
                $typ = \Phalcon\Logger::ERROR;
                $data = [
                    "f" => $msg->getFile(),
                    "m" => $msg->getMessage(),
                    "l" => $msg->getLine(),
                    "c" => $msg->getCode(),
                    "t" => $msg->getTraceAsString()
                ];
            } else {
                $typ = \Phalcon\Logger::INFO;
                $data = $msg;
            }
        }

        $data = [
            'client' => $this->request->getClientAddress(true),
            'code' => $code,
            'msg' => $data
        ];

        Log::log($typ, json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @Action(null, [noauth, noexport], "api文档")
     */
    function apidoc()
    {
        $this->response->setContentType('text/html');
        $this->view->setViewsDir(dirname(__DIR__) . '/view');
        $this->view->pick('Apidoc');

        // 组装volt页面需要的数据
        $data = [
            "name" => $this->router->getControllerName(),
            "actions" => Doc::Actions($this)
        ];
        $this->view->router = json_encode($data);
        $this->view->start()->finish();
    }

    /**
     * @Action(null, [noauth, noexport], "导出api文档")
     */
    public function apiexport()
    {
        ApiBuilder::export($this);
        exit;
    }
}
