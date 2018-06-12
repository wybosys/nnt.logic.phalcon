<?php

namespace App\Model;

use App\Controller\Config;
use Phalcon\Annotations\Adapter\Apcu;
use Phalcon\Http\Request\File;

class MemberDeclaration
{

    /**
     *
     * @var string
     */
    public $name;

    /**
     *
     * @var boolean
     */
    public $string;

    /**
     *
     * @var boolean
     */
    public $integer;

    /**
     *
     * @var boolean
     */
    public $double;

    /**
     *
     * @var boolean
     */
    public $boolean;

    /**
     *
     * @var boolean
     */
    public $file;

    /**
     * @var boolean
     */
    public $enum;

    /**
     *
     * @var boolean
     */
    public $array;

    /**
     *
     * @var boolean
     */
    public $map;

    /**
     *
     * @var boolean
     */
    public $object;

    /**
     *
     * @var string
     */
    public $valtyp;

    /**
     *
     * @var string
     */
    public $keytyp;

    /**
     *
     * @var boolean
     */
    public $optional;

    /**
     *
     * @var integer
     */
    public $index;

    /**
     *
     * @var boolean
     */
    public $input;

    /**
     *
     * @var boolean
     */
    public $output;

    /**
     *
     * @var string
     */
    public $comment = '';
}

class Proto
{

    /**
     * @param string|object $className
     * @return \Phalcon\Annotations\Reflection
     */
    static function Reflect($model)
    {
        $reader = new Apcu([
            "lifetime" => Config::Use(5, 60 * 5, 60 * 5),
            "prefix" => "_proto_"
        ]);
        return $reader->get($model);
    }

    /**
     *
     * @param $request \Phalcon\Http\Request|\Phalcon\Http\RequestInterface
     * @param $model object
     * @return integer Code中定义的错误码
     */
    static function Check($params, $model)
    {
        // 填充，如果遇到不符合的，返回错误
        $reflect = self::Reflect($model);
        $props = $reflect->getPropertiesAnnotations();
        if ($props) {
            foreach ($props as $name => $prop) {
                if (!$prop->has('Api'))
                    continue;
                $api = $prop->get('Api');
                if ($api) {
                    $ops = $api->getArgument(2);
                    if (array_search('input', $ops) === false)
                        continue;
                    if (!isset($params[$name])) {
                        if (array_search('optional', $ops) !== false)
                            continue;
                        return Code::PARAMETERS;
                    }
                    // 根据设置，提取输入数据
                    $typs = $api->getArgument(1);
                    $model->{$name} = self::GetValue($params[$name], isset($typs[0]) ? $typs[0] : NULL, isset($typs[1]) ? $typs[1] : NULL, isset($typs[2]) ? $typs[2] : NULL);
                }
            }
        }

        return Code::OK;
    }

    /**
     * 把参数的数据写入模型中
     */
    static function Decode($model, $params)
    {
        $reflect = self::Reflect($model);
        $props = $reflect->getPropertiesAnnotations();
        if ($props) {
            foreach ($props as $name => $prop) {
                if (!$prop->has('Api'))
                    continue;
                $api = $prop->get('Api');
                if ($api) {
                    if (!isset($params[$name]))
                        continue;
                    // 根据设置，提取输入数据
                    $typs = $api->getArgument(1);
                    $model->{$name} = self::GetValue($params[$name], isset($typs[0]) ? $typs[0] : NULL, isset($typs[1]) ? $typs[1] : NULL, isset($typs[2]) ? $typs[2] : NULL);
                }
            }
        }
    }

    /**
     * 输出模型的数据到基本对象
     * @return array
     */
    static function Output($model)
    {
        $ret = [];
        if ($model == null)
            return $ret;
        $reflect = self::Reflect($model);
        $props = $reflect->getPropertiesAnnotations();
        if ($props) {
            foreach ($props as $name => $prop) {
                if (!$prop->has('Api'))
                    continue;
                $api = $prop->get('Api');
                if ($api) {
                    $ops = $api->getArgument(2);
                    if (!in_array('output', $ops))
                        continue;
                    $typs = $api->getArgument(1);
                    $ret[$name] = self::OutputValue($model->{$name}, isset($typs[0]) ? $typs[0] : NULL, isset($typs[1]) ? $typs[1] : NULL, isset($typs[2]) ? $typs[2] : NULL);
                }
            }
        }
        return $ret;
    }

    /**
     * 获取模型的输入参数
     * @return array
     */
    static function Input($model)
    {
        $ret = [];
        if ($model == null)
            return $ret;
        $reflect = self::Reflect($model);
        $props = $reflect->getPropertiesAnnotations();
        if ($props) {
            foreach ($props as $name => $prop) {
                if (!$prop->has('Api'))
                    continue;
                $api = $prop->get('Api');
                if ($api) {
                    $ops = $api->getArgument(2);
                    if (!in_array('input', $ops))
                        continue;
                    $typs = $api->getArgument(1);
                    $ret[$name] = self::OutputValue($model->{$name}, isset($typs[0]) ? $typs[0] : NULL, isset($typs[1]) ? $typs[1] : NULL, isset($typs[2]) ? $typs[2] : NULL);
                }
            }
        }
        return $ret;
    }

    /**
     * @param $ann \Phalcon\Annotations\Annotation
     */
    protected static function GetValue($val, $typ, $styp0, $styp1)
    {
        // 定义为[type, subtype, subtype]
        switch ($typ) {
            case 'string':
                return (string)$val;
            case 'integer':
                return (int)$val;
            case 'double':
                return (double)$val;
            case 'boolean':
                return $val ? true : false;
            case 'file':
                return $val instanceof File ? $val : null;
            case 'object':
                return json_decode($val, true);
            case 'array':
                $ret = [];
                $valtyp = $styp0;
                foreach (explode(',', $val) as $each) {
                    array_push($ret, self::GetValue($each, $valtyp, null, null));
                }
                return $ret;
            case 'map':
                $ret = [];
                $keytyp = $styp0;
                $valtyp = $styp1;
                $obj = json_decode($val, true);
                foreach ($obj as $k => $v) {
                    $k = self::GetValue($k, $keytyp, null, null);
                    $v = self::GetValue($v, $valtyp, null, null);
                    $ret[$k] = $v;
                }
                return $ret;
            default:
                // 传入了对象
                $tgt = new $typ();
                $obj = json_decode($val, true);
                self::Decode($tgt, $obj);
                return $tgt;
        }
    }

    protected static function OutputValue($val, $typ, $styp0 = NULL, $styp1 = NULL)
    {
        switch ($typ) {
            case 'string':
                return (string)$val;
            case 'integer':
                return (int)$val;
            case 'double':
                return (double)$val;
            case 'boolean':
                return $val ? true : false;
            case 'array':
                $arr = [];
                if ($val) {
                    foreach ($val as $e) {
                        $obj = self::OutputValue($e, $styp0);
                        array_push($arr, $obj);
                    }
                }
                return $arr;
            case 'map':
                $arr = [];
                foreach ($val as $k => $v) {
                    $k = self::OutputValue($k, $styp0);
                    $v = self::OutputValue($v, $styp1);
                    $arr[$k] = $v;
                }
                return $arr;
            case 'object':
                return json_encode($val);
            default:
                return self::Output($val);
        }
    }

    static function CollectParameters(\Phalcon\Http\RequestInterface $request)
    {
        $posts = $request->getPost();
        $gets = $request->getQuery();
        $files = $request->getUploadedFiles();

        // 合并到同一个集合
        $ret = array_merge($gets, $posts);
        foreach ($files as $file) {
            if (!$file->getKey())
                continue;
            $ret[$file->getKey()] = $file;
        }

        // 去除phalcon默认的_url参数
        unset($ret['_url']);

        return $ret;
    }

    /**
     * 获得model的参数描述
     *
     * @return MemberDeclaration[]
     */
    public static function DeclarationOf($model)
    {
        $ret = [];
        $reflect = self::Reflect($model);
        $props = $reflect->getPropertiesAnnotations();
        if ($props) {
            foreach ($props as $name => $prop) {
                if (!$prop->has('Api'))
                    continue;
                $api = $prop->get('Api');
                if (!$api)
                    continue;
                $idx = $api->getArgument(0);
                $typs = $api->getArgument(1);
                $ops = $api->getArgument(2);
                $decl = new MemberDeclaration();
                $decl->name = $name;
                $decl->index = (int)$idx;
                $decl->input = in_array('input', $ops);
                $decl->output = in_array('output', $ops);
                $decl->optional = in_array('optional', $ops);
                $decl->comment = $api->getArgument(3) ? $api->getArgument(3) : "";
                switch ($typs[0]) {
                    case 'string':
                        $decl->string = true;
                        break;
                    case 'integer':
                        $decl->integer = true;
                        break;
                    case 'double':
                        $decl->double = true;
                        break;
                    case 'boolean':
                        $decl->boolean = true;
                        break;
                    case 'file':
                        $decl->file = true;
                        break;
                    case 'array':
                        $decl->array = true;
                        $decl->valtyp = $typs[1];
                        break;
                    case 'map':
                        $decl->map = true;
                        $decl->keytyp = $typs[1];
                        $decl->valtyp = $typs[2];
                        break;
                    default:
                        $decl->object = true;
                        $decl->valtyp = $typs[0];
                        break;
                }
                $ret[] = $decl;
            }
        }
        return $ret;
    }
}