<?php

namespace Nnt\Controller;

use Nnt\Core\Config;
use Nnt\Store\Cache;
use Phalcon\Annotations\Adapter\Apcu;

class RouterDeclaration
{

}

class ActionDeclaration
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
     * @var bool 是否暴露该接口
     */
    public $expose = false;

    /**
     * @var bool 是否使用缓存
     */
    public $cache = false;

    /**
     * @var bool LOCAL可用
     */
    public $local = false;

    /**
     * @var bool DEVOPS-DEVELOP可用
     */
    public $devopsdevelop = false;

    /**
     * @var bool DEVOPS-RELEASE可用
     */
    public $devopsrelease = false;

    /**
     * @var bool DEVOPS可用
     */
    public $devops = false;

    /**
     * @var int 缓存时间
     */
    public $ttl = 60;
}

function LoadActionAnnotation(ActionDeclaration $decl, \Phalcon\Annotations\Annotation $ann)
{
    $decl->model = $ann->getArgument(0);
    $tmp = $ann->getArgument(1);
    if (is_string($tmp)) {
        $decl->comment = $tmp;
    } else if (is_array($tmp)) {
        foreach ($tmp as $e) {
            if ($e == 'noauth') {
                $decl->needauth = false;
            } else if ($e == 'noexport') {
                $decl->export = false;
            } else if ($e == 'expose') {
                $decl->expose = true;
            } else if (strpos($e, 'cache') !== false) {
                if (preg_match('/cache_(\d+)/', $e, $res) === false)
                    throw new \Exception("缓存配置错误");
                $decl->cache = true;
                $decl->ttl = (int)$res[1];
            }
        }

        // 检测运行环境
        $mit = false;
        if (in_array('devops', $tmp)) {
            $decl->devops = true;
            $mit = true;
        }
        if (in_array('devopsdevelop', $tmp)) {
            $decl->devopsdevelop = true;
            $mit = true;
        }
        if (in_array('devopsrelease', $tmp)) {
            $decl->devopsrelease = true;
            $mit = true;
        }
        if (in_array('local', $tmp)) {
            $decl->local = true;
            $mit = true;
        }
        if (!$mit) {
            $decl->local = true;
            $decl->devops = true;
            $decl->devopsdevelop = true;
            $decl->devopsrelease = true;
        }

        $decl->comment = $ann->getArgument(2);
    }

    // 如果打开了cache，但是当前全局不支持cache，自动关闭
    if ($decl->cache && !Cache::IsEnabled())
        $decl->cache = false;
}

class Router
{

    static function DeclarationOf(string $actnm, $obj): ActionDeclaration
    {
        $clazz = $obj;
        if (is_object($obj)) {
            $clazz = get_class($obj);
        }

        $ver = Application::$shared->config('version', '0.0.0');
        $ck = "::nnt::action::proto::$ver::$clazz::$actnm";
        $ret = apcu_fetch($ck);
        if ($ret)
            return $ret;

        // 生成新的
        $ttl = Config::Use(5, 5, 60 * 5);

        $reader = new Apcu([
            'lifetime' => $ttl,
            'prefix' => "::nnt::proto::$ver"
        ]);

        try {
            $ann = $reader->get($clazz);
        } catch (\Throwable $ex) {
            throw new \Exception("$clazz 获取Annotaions失败");
        }

        $methods = $ann->getMethodsAnnotations();
        if ($methods) {
            if (array_key_exists($actnm, $methods)) {
                $method = $methods[$actnm];
                if ($method->has('Action')) {
                    $act = $method->get('Action');
                    $ret = new ActionDeclaration();
                    $ret->name = $actnm;
                    LoadActionAnnotation($ret, $act);
                    apcu_store($ck, $ret, $ttl);
                }
            }
        }

        return $ret;
    }

    static function IsValid(ActionDeclaration $decl): bool
    {
        $pass = false;
        $mit = false;

        if (!$pass && $decl->local) {
            $mit = true;
            if (Config::IsLocal())
                $pass = true;
        }
        if (!$pass && $decl->devops) {
            $mit = true;
            if (Config::IsDevops())
                $pass = true;
        }
        if (!$pass && $decl->devopsdevelop) {
            $mit = true;
            if (Config::IsDevopsDevelop())
                $pass = true;
        }
        if (!$pass && $decl->devopsrelease) {
            $mit = true;
            if (Config::IsDevopsRelease())
                $pass = true;
        }

        return !$mit || $pass;
    }

}
