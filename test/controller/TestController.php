<?php

use Nnt\Controller\Api;
use Nnt\Controller\Rest;
use Nnt\Controller\Service;
use Nnt\Core\Code;
use Nnt\Core\Log;

class TestController extends Api
{
    /**
     * @Action(\Test\Model\Echoo, [expose, local], "输出")
     */
    function echomysql(\Test\Model\Echoo $mdl)
    {
        $mdl->output = $mdl->input;

        $rcd = new \Test\Db\Echoo();
        // $rcd->setSource("test1");
        $rcd->input = $mdl->input;
        $rcd->output = $mdl->output;
        $rcd->save();
    }

    /**
     * @Action(\Test\Model\Echoo, [expose], "输出")
     */
    function echopg(\Test\Model\Echoo $mdl)
    {
        $mdl->output = $mdl->input;

        $rcd = new \Test\Db\Echoo();
        $rcd->setConnectionService('pg');
        $rcd->input = $mdl->input;
        $rcd->output = $mdl->output;
        $rcd->save();
    }

    /**
     * @Action(\Test\Model\Echoo, [noauth, signature], "输出")
     */
    function echo(\Test\Model\Echoo $mdl)
    {
        $mdl->output = $mdl->input;
        $mdl->status = 0; //random_int(0, 1000);
    }

    /**
     * @Action(\Test\Model\Echoo, [noauth], "输出")
     */
    function callechoo(\Test\Model\Echoo $mdl)
    {
        $m = \Framework\Phalcon\TestEcho();
        $m->input = "S2S:" . $mdl->input;
        Rest::Get($m);
        $mdl->output = $m->output;
        $mdl->status = $m->status;
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
        $this->log(Code::OK, $mdl->msg);
        if (!$mdl->type)
            $mdl->type = 0;
        Log::log($mdl->type, $mdl->msg);
    }

    /**
     * @Action(\Test\Model\Kv, [noauth], "测试redis")
     */
    function redis(\Test\Model\Kv $mdl)
    {
        $redis = $this->di->getRedis();
        if ($mdl->value) {
            $redis->set($mdl->key, $mdl->value, 5);
        } else {
            $mdl->value = $redis->get($mdl->key);
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
            throw new \Exception("不存在", Code::TARGET_NOT_FOUND);
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
