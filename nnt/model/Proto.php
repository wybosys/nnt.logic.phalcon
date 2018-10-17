<?php

namespace Nnt\Model;

use Nnt\Controller\Config;
use Phalcon\Annotations\Adapter\Apcu;
use Phalcon\Http\Request\File;

class ClazzDeclaration
{
    /**
     *
     * @var boolean
     */
    public $hidden;

    /**
     *
     * @var boolean
     */
    public $enum;

    /**
     *
     * @var boolean
     */
    public $const;

    /**
     *
     * @var string
     */
    public $super;

    /**
     * @var MemberDeclaration[]
     * Map<string, MemberDeclaration>
     */
    public $members;

    /**
     * @var PropDeclaration[]
     * Map<string, PropDeclaration>
     */
    public $props;
}

class MemberDeclaration
{
    /**
     *
     * @var string
     */
    public $name;

    /**
     *
     * @var string
     */
    public $model;

    /**
     *
     * @var boolean
     */
    public $optional;

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
     * @var boolean
     */
    public $noauth;

    /**
     *
     * @var boolean
     */
    public $noexport;

    /**
     *
     * @var boolean
     */
    public $expose;

    /**
     *
     * @var string
     */
    public $comment = '';
}

class PropDeclaration
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
    public $multimap;

    /**
     *
     * @var boolean
     */
    public $object;

    /**
     *
     * @var boolean
     */
    public $json;

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
            "lifetime" => Config::Use(5, 5, 60 * 5),
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
                return $val != "false";
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

    static function LoadClassDeclarationOf(\Phalcon\Annotations\Reflection $reflect, ClazzDeclaration $decl)
    {
        $annClass = $reflect->getClassAnnotations();
        if (!$annClass)
            return;
        if (!$annClass->has('Model'))
            return;
        $mdl = $annClass->get('Model');
        $ops = $mdl->getArgument(0);
        $super = $mdl->getArgument(1);
        if (in_array('hidden', $ops))
            $decl->hidden = true;
        if ($super)
            $decl->super = $super;
    }

    static function LoadMembersDeclarationOf(\Phalcon\Annotations\Reflection $reflect, ClazzDeclaration $decl)
    {
        $decl->members = [];
        $annMethods = $reflect->getMethodsAnnotations();
        if (!$annMethods)
            return;
        foreach ($annMethods as $name => $method) {
            if (!$method->has('Action'))
                continue;

            $action = $method->get('Action');
            $model = $action->getArgument(0);
            if ($model == null)
                $model = "\Nnt\Model\Nil";
            $ops = $action->getArgument(1);

            $mem = new MemberDeclaration();
            $mem->name = $name;
            $mem->model = $model;
            $mem->input = in_array('input', $ops);
            $mem->output = in_array('output', $ops);
            $mem->optional = in_array('optional', $ops);
            $mem->noauth = in_array('noauth', $ops);
            $mem->noexport = in_array('noexport', $ops);
            $mem->expose = in_array('expose', $ops);
            $mem->comment = $action->getArgument(2);

            $decl->members[$mem->name] = $mem;
        }
    }

    static function LoadPropsDeclarationOf(\Phalcon\Annotations\Reflection $reflect, ClazzDeclaration $decl)
    {
        $decl->props = [];
        $annProps = $reflect->getPropertiesAnnotations();
        if (!$annProps)
            return;
        foreach ($annProps as $name => $prop) {
            if (!$prop->has('Api'))
                continue;

            $api = $prop->get('Api');
            $idx = $api->getArgument(0);
            $typs = $api->getArgument(1);
            $ops = $api->getArgument(2);

            $mem = new PropDeclaration();
            $mem->name = $name;
            $mem->index = (int)$idx;
            $mem->input = in_array('input', $ops);
            $mem->output = in_array('output', $ops);
            $mem->optional = in_array('optional', $ops);
            $mem->comment = $api->getArgument(3) ? $api->getArgument(3) : "";
            switch ($typs[0]) {
                case 'string':
                    $mem->string = true;
                    break;
                case 'integer':
                    $mem->integer = true;
                    break;
                case 'double':
                    $mem->double = true;
                    break;
                case 'boolean':
                    $mem->boolean = true;
                    break;
                case 'file':
                    $mem->file = true;
                    break;
                case 'array':
                    $mem->array = true;
                    $mem->valtyp = $typs[1];
                    break;
                case 'map':
                    $mem->map = true;
                    $mem->keytyp = $typs[1];
                    $mem->valtyp = $typs[2];
                    break;
                default:
                    $mem->object = true;
                    $mem->valtyp = $typs[0];
                    break;
            }

            $decl->props[$mem->name] = $mem;
        }
    }

    /**
     * 获得model的参数描述
     */
    static function DeclarationOf($model, $includeClass, $includeMembers, $includeProps): ClazzDeclaration
    {
        $reflect = self::Reflect($model);
        if (!$reflect)
            return null;

        $ret = new ClazzDeclaration();

        if ($includeClass) {
            self::LoadClassDeclarationOf($reflect, $ret);
        }

        if ($includeMembers) {
            self::LoadMembersDeclarationOf($reflect, $ret);
        }

        if ($includeProps) {
            self::LoadPropsDeclarationOf($reflect, $ret);
        }

        return $ret;
    }

    static function FpToTypeDef(PropDeclaration $fp): string
    {
        if ($fp->string) {
            $typ = "string";
        } else if ($fp->integer) {
            $typ = "number";
        } else if ($fp->double) {
            $typ = "number";
        } else if ($fp->boolean) {
            $typ = "boolean";
        } else if ($fp->array) {
            $typ = "Array<";
            switch ($fp->valtyp) {
                case "string":
                    $vt = "string";
                    break;
                case "double":
                case "integer":
                    $vt = "number";
                    break;
                case "boolean":
                    $vt = "boolean";
                    break;
                default:
                    $vt = $fp->valtyp;
                    break;
            }
            $typ .= $vt;
            $typ .= ">";
        } else if ($fp->map) {
            $typ = "Map<" . self::ValtypeDefToDef($fp->keytyp) . ", " . self::ValtypeDefToDef($fp->valtyp) . ">";
        } else if ($fp->multimap) {
            $typ = "Multimap<" . self::ValtypeDefToDef($fp->keytyp) . ", " . self::ValtypeDefToDef($fp->valtyp) . ">";
        } else if ($fp->enum) {
            $typ = $fp->valtyp;
        } else if ($fp->file) {
            if ($fp->input)
                $typ = "any";
            else
                $typ = "string";
        } else if ($fp->json || $fp->object) {
            $typ = "Object";
        } else {
            $typ = $fp->valtyp;
        }
        return $typ;
    }

    static function FpToOptionsDef(PropDeclaration $fp, $ns = ""): string
    {
        $r = [];
        if ($fp->input)
            $r[] = $ns . 'input';
        if ($fp->output)
            $r[] = $ns . 'output';
        if ($fp->optional)
            $r[] = $ns . 'optional';
        return "[" . implode(', ', $r) . "]";
    }

    static function FpToValtypeDef(PropDeclaration $fp, $ns = ""): string
    {
        $t = [];
        if ($fp->keytyp) {
            $t[] = self::ValtypeDefToDefType($fp->keytyp, $ns);
        }
        if ($fp->valtyp) {
            $t[] = self::ValtypeDefToDefType($fp->valtyp, $ns);
        }
        return implode(', ', $t);
    }

    static function ValtypeDefToDef($def): string
    {
        switch ($def) {
            case "string":
                return "string";
            case "double":
            case "integer":
                return "number";
            case "boolean":
                return "boolean";
        }
        return $def;
    }

    static function ValtypeDefToDefType($def, $ns = ''): string
    {
        switch ($def) {
            case "string":
                return $ns . "string_t";
            case "double":
            case "integer":
                return $ns . "number_t";
            case "boolean":
                return $ns . "boolean_t";
            case "object":
                return "Object";
        }
        return $def;
    }

    static function FpToCommentDef(PropDeclaration $fp): string
    {
        return $fp->comment ? (', "' . $fp->comment . '"') : "";
    }

    static function FpToDecoDef(PropDeclaration $fp, $ns = ""): string
    {
        $deco = null;
        if ($fp->string)
            $deco = "@" . $ns . "string(" . $fp->index . ", " . self::FpToOptionsDef($fp, $ns) . self::FpToCommentDef($fp) . ")";
        else if ($fp->integer)
            $deco = "@" . $ns . "integer(" . $fp->index . ", " . self::FpToOptionsDef($fp, $ns) . self::FpToCommentDef($fp) . ")";
        else if ($fp->double)
            $deco = "@" . $ns . "double(" . $fp->index . ", " . self::FpToOptionsDef($fp, $ns) . self::FpToCommentDef($fp) . ")";
        else if ($fp->boolean)
            $deco = "@" . $ns . "boolean(" . $fp->index . ", " . self::FpToOptionsDef($fp, $ns) . self::FpToCommentDef($fp) . ")";
        else if ($fp->array) {
            $deco = "@" . $ns . "array(" . $fp->index . ", " . self::FpToValtypeDef($fp, $ns) . ", " . self::FpToOptionsDef($fp, $ns) . self::FpToCommentDef($fp) . ")";
        } else if ($fp->map) {
            $deco = "@" . $ns . "map(" . $fp->index . ", " . self::FpToValtypeDef($fp, $ns) . ", " . self::FpToOptionsDef($fp, $ns) . self::FpToCommentDef($fp) . ")";
        } else if ($fp->multimap) {
            $deco = "@" . $ns . "multimap(" . $fp->index . ", " . self::FpToValtypeDef($fp, $ns) . ", " . self::FpToOptionsDef($fp, $ns) . self::FpToCommentDef($fp) . ")";
        } else if ($fp->enum) {
            $deco = "@" . $ns . "enumerate(" . $fp->index . ", " . self::FpToValtypeDef($fp, $ns) . ", " . self::FpToOptionsDef($fp, $ns) . self::FpToCommentDef($fp) . ")";
        } else if ($fp->file) {
            $deco = "@" . $ns . "file(" . $fp->index . ", " . self::FpToOptionsDef($fp, $ns) . self::FpToCommentDef($fp) . ")";
        } else if ($fp->json) {
            $deco = "@" . $ns . "json(" . $fp->index . ", " . self::FpToOptionsDef($fp, $ns) . self::FpToCommentDef($fp) . ")";
        } else {
            $deco = "@" . $ns . "type(" . $fp->index . ", " . self::FpToValtypeDef($fp, $ns) . ", " . self::FpToOptionsDef($fp, $ns) . self::FpToCommentDef($fp) . ")";
        }
        return $deco;
    }
}
