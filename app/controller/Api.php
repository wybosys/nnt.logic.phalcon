<?php

namespace App\Controller;

use Phalcon\Mvc\Controller;
use App\Model\Proto;
use App\Model\Code;

// todo 使用APC
use Phalcon\Annotations\Adapter\Memory;

class ActionInfo
{
    public $name;
    public $model;
    public $comment;
    public $needauth = true;

    function __construct(\Phalcon\Annotations\Annotation $ann)
    {
        $this->name = $ann->getName();
        $this->model = $ann->getArgument(0);
        $tmp = $ann->getArgument(1);
        if (is_string($tmp)) {
            $this->comment = $tmp;
        } else {
            $this->needauth = in_array('noauth', $tmp);
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
                    $this->_actions[$actnm . 'Action'] = new ActionInfo($act);
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
        if ($info->needauth) {
            echo json_encode([
                'code' => Code::NEED_AUTH
            ]);
            return;
        }

        // 初始化访问的模型
        $model = null;
        $modelclz = $info->model;
        if ($modelclz) {
            $model = new $modelclz();
            // 检查数据是否满足模型的定义
            $sta = Proto::Check($this->request, $model);
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
                $this->log($code);
                echo json_encode([
                    'code' => $code
                ]);
            }
        }
    }

    /**
     * @Api([noAuth, noExport])
     * @Action
     */
    function description()
    {
        // pass
    }

    /**
     * @Api([noAuth, noExport])
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
            'client' => $this->request->getClientAddress(),
            'code' => $code,
            'msg' => $data
        ];

        Log::log($typ, json_encode($data));
    }

    /**
     * @Api([noAuth, noExport])
     * @Action
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
     * @Api([noAuth, noExport])
     * 导出api
     * @Action
     */
    public function apiexport()
    {
        ApiBuilder::export($this);
        exit;
    }
}
