<?php

use Nnt\Controller\Api;
use Nnt\Controller\Service;
use Nnt\Model\Code;

class TestController extends Api
{
    /**
     * @Action(\Test\Model\Echoo, [expose, devopsdevelop], "输出")
     */
    function echoo(\Test\Model\Echoo $mdl)
    {
        $mdl->output = $mdl->input;

        $rcd = new \Test\Db\Echoo();
        $rcd->setSource("test1");
        $rcd->input = $mdl->input;
        $rcd->output = $mdl->output;
        $rcd->save();
    }

    /**
     * @Action(\Test\Model\Echoo, [noauth], "输出")
     */
    function echo(\Test\Model\Echoo $mdl)
    {
        $mdl->output = $mdl->input;
        $mdl->status = Code::EXCEPTION;
    }

    /**
     * @Action(\Test\Model\Echoo, [noauth], "输出")
     */
    function callechoo(\Test\Model\Echoo $mdl)
    {
        $ret = Service::Call("framework/phalcon/test/echoo", [
            "input" => $mdl->input
        ]);
        if ($ret->code != 0)
            throw new \Exception(@$ret->error, $ret->code);
        $mdl->output = $ret->data->output;
    }

    /**
     * @Action(\Test\Model\Call, [noauth], "远程调用")
     */
    function svccall(\Test\Model\Call $mdl)
    {
        $ret = Service::Call($mdl->name, $mdl->args);
        $mdl->output = $ret;
    }

    /**
     * @Action(\Test\Model\HostInfo, [noauth], "主机信息")
     */
    function hostinfo(\Test\Model\HostInfo $mdl)
    {
        $mdl->info = gethostbyname($mdl->name);
    }

    /**
     * @Action(\Test\Model\UploadImage, [noauth], "上传图片")
     */
    function uploadimage(\Test\Model\UploadImage $mdl)
    {
        $ret = Service::Call("devops/media", ["action" => "imagestore.upload"], ["file" => $mdl->file]);
        $mdl->path = $ret->data->path;
    }

    /**
     * @Action(\Test\Model\Output, [noauth], "php信息")
     */
    function phpinfo(\Test\Model\Output $mdl)
    {
        $mdl->output = phpinfo();
    }

    /**
     * @Action(\Test\Model\Log, [noauth], "输出日志")
     */
    function mklog(\Test\Model\Log $mdl)
    {
        $this->log("mklog");
        $this->log(\Nnt\Model\Code::OK, $mdl->msg);
        \Nnt\Controller\Log::log($mdl->type, $mdl->msg);
    }

    /**
     * @Action(\Test\Model\Kv, [noauth], "测试redis")
     */
    function redis(\Test\Model\Kv $mdl)
    {
        $this->di->setShared('redis', function () {
            $cfg = new \Phalcon\Cache\Frontend\Data(["lifetime" => 10]);
            $v = $this->getConfig()->redis;
            $r = new \Phalcon\Cache\Backend\Redis($cfg, $this->getConfig()->redis->toArray());
            return $r;
        });
        if ($mdl->value) {
            $this->di->getRedis()->save($mdl->key, $mdl->value);
        } else {
            $mdl->value = $this->di->getRedis()->get($mdl->key);
        }
    }

    /**
     * @Action(\Test\Model\CidrTest, [noauth], "CIDR测试")
     */
    function cidr(\Test\Model\CidrTest $mdl)
    {
        $mdl->result = Service::CidrMatch($mdl->ip, $mdl->rule);
    }

    /**
     * @Action(\Test\Model\Echoo, [auth, cache_10], "测试缓存")
     */
    function cache(\Test\Model\Echoo $mdl)
    {
        $mdl->output = $mdl->input;
        sleep(5);
    }

    /**
     * @Action(\Test\Model\Echoo, [noauth], "测试缓存")
     */
    function apcu(\Test\Model\Echoo $mdl)
    {
        if (apcu_exists($mdl->input)) {
            $mdl->output = apcu_fetch($mdl->input);
        } else {
            throw new \Exception("不存在", \Nnt\Model\Code::TARGET_NOT_FOUND);
        }
    }

    /**
     * @Action(null, [noauth])
     */
    function noargs()
    {
        // pass
    }
}
