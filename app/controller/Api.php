<?php

namespace App\Controller;

use App\Model\Code;
use App\Model\Proto;
use Phalcon\Annotations\Adapter\Memory;
use Phalcon\Mvc\Controller;

// todo 使用APC

class ActionInfo
{
    public $name;
    public $model;
    public $comment;
    public $needauth = true;
    public $export = true;

    function __construct(string $mthnm, \Phalcon\Annotations\Annotation $ann)
    {
        $this->name = $mthnm;
        $this->model = $ann->getArgument(0);
        $tmp = $ann->getArgument(1);
        if (is_string($tmp)) {
            $this->comment = $tmp;
        } else if (is_array($tmp)) {
            $this->needauth = !in_array('noauth', $tmp);
            $this->export = !in_array('noexport', $tmp);
            $this->comment = $ann->getArgument(2);
        }
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
        $reader = new Memory();
        $reflect = $reader->get($this);
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
        $info = $this->_actions[$name];

        // 判断有没有登陆
        if ($info->needauth && !$this->di->has('user')) {
            echo json_encode([
                'code' => Code::NEED_AUTH
            ]);
            return;
        }

        // 收集参数
        $params = Proto::CollectParameters($this->request);

        // 判断访问权限
        if (Service::PermissionEnabled()) {
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

        // 初始化访问的模型
        $model = null;
        $modelclz = $info->model;
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
                    echo json_encode([
                        'code' => Code::OK,
                        'message' => count($out) ? $out : '{}'
                    ]);
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

        Log::log($typ, json_encode($data));
    }

    /**
     * @Action(null, [noauth, noexport])
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
     * @Action(null, [noauth, noexport])
     */
    public function apiexport()
    {
        ApiBuilder::export($this);
        exit;
    }
}
