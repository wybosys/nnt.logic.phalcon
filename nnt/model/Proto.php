<?php

namespace Nnt\Model;

use Nnt\Controller\Application;
use Nnt\Core\ArrayT;
use Nnt\Core\Code;
use Nnt\Core\Config;
use Nnt\Core\Kernel;
use Nnt\Core\MapT;
use Nnt\Store\Filter;
use Phalcon\Annotations\Adapter\Apcu;
use Phalcon\Http\Request\File;

class ModelDeclaration
{
    /**
     * @var boolean
     */
    public $hidden;

    /**
     * @var boolean
     */
    public $enum;

    /**
     * @var boolean
     */
    public $const;

    /**
     * @var string
     */
    public $super;

    /**
     * @var boolean
     */
    public $noauth;

    /**
     * @var MemberDeclaration[]
     * Map<string, MemberDeclaration>
     */
    public $members = [];

    /**
     * @var PropDeclaration[]
     * Map<string, PropDeclaration>
     */
    public $props = [];
}

class MemberDeclaration
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $model;

    /**
     * @var boolean
     */
    public $noauth;

    /**
     * @var boolean
     */
    public $noexport;

    /**
     * @var boolean
     */
    public $expose;

    /**
     * @var boolean
     */
    public $signature;

    /**
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
     * @var boolean
     */
    public $filter;

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
     * @var boolean
     */
    public $type;

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
     * 检查输入参数是否满足模型定义
     */
    static function Check($params, $model)
    {
        // 获取模型描述
        $decl = self::DeclarationOf($model);

        // 填充，如果遇到不符合的，返回错误
        if ($decl->props) {
            foreach ($decl->props as $name => $prop) {
                if (!$prop->input) {
                    continue;
                }
                if (!isset($params[$name])) {
                    if ($prop->optional) {
                        continue;
                    }
                    return Code::PARAMETER_NOT_MATCH;
                }

                $model->{$name} = self::GetValue($params[$name], $prop);

                if (!$prop->optional && $model->{$name} === null) {
                    return Code::PARAMETER_NOT_MATCH;
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
        $decl = self::DeclarationOf($model);

        if ($decl->props) {
            if (is_array($params)) {
                foreach ($decl->props as $name => $prop) {
                    if (!isset($params[$name])) {
                        continue;
                    }
                    // 根据设置，提取输入数据
                    $model->{$name} = self::GetValue($params[$name], $prop);
                }
            } else {
                foreach ($decl->props as $name => $prop) {
                    if (!isset($params->{$name})) {
                        continue;
                    }
                    // 根据设置，提取输入数据
                    $model->{$name} = self::GetValue($params->{$name}, $prop);
                }
            }
        }

        return $model;
    }

    /**
     * 输出模型的数据到基本对象
     */
    static function Output($model): array
    {
        $ret = [];
        if ($model == null)
            return $ret;

        $decl = self::DeclarationOf($model);

        if ($decl->props) {
            foreach ($decl->props as $name => $prop) {
                if (!$prop->output) {
                    continue;
                }
                $ret[$name] = self::OutputValue($model->{$name}, $prop);
            }
        }

        return $ret;
    }

    /**
     * 获取模型的输入参数
     */
    static function Input($model): array
    {
        $ret = [];
        if ($model == null)
            return $ret;

        $decl = self::DeclarationOf($model);

        if ($decl->props) {
            foreach ($decl->props as $name => $prop) {
                if (!$prop->input) {
                    continue;
                }
                $ret[$name] = self::OutputValue($model->{$name}, $prop);
            }
        }

        return $ret;
    }

    protected static function GetValue($val, PropDeclaration $prop)
    {
        if ($prop->string) {
            return (string)$val;
        }

        if ($prop->json) {
            return Kernel::toJson($val);
        }

        if ($prop->integer || $prop->enum) {
            return (int)$val;
        }

        if ($prop->double) {
            return (double)$val;
        }

        if ($prop->boolean) {
            return $val !== "false";
        }

        if ($prop->file) {
            return $val instanceof File ? $val : null;
        }

        if ($prop->object) {
            return json_decode($val, true);
        }

        if ($prop->enum) {
            return (int)$val;
        }

        if ($prop->array) {
            if (!is_array($val)) {
                $val = explode(',', $val);
            }

            switch ($prop->valtyp) {
                case 'string':
                    {
                        $ret = ArrayT::Convert($val, function ($e) {
                            return Kernel::ToString($e);
                        });
                    }
                    break;
                case 'integer':
                case 'enum':
                    {
                        $ret = ArrayT::Convert($val, function ($e) {
                            return Kernel::ToInt($e);
                        });
                    }
                    break;
                case 'double':
                    {
                        $ret = ArrayT::Convert($val, function ($e) {
                            return Kernel::ToDouble($e);
                        });
                    }
                    break;
                case 'boolean':
                    {
                        $ret = ArrayT::Convert($val, function ($e) {
                            return Kernel::ToBoolean($e);
                        });
                    }
                    break;
                default:
                    {
                        $val = Kernel::toJsonObj($val);
                        $ret = ArrayT::Convert($val, function ($e) use ($prop) {
                            $t = new $prop->valtyp();
                            self::Decode($t, $e);
                            return $t;
                        });
                    }
                    break;
            }
            return $ret;
        }

        if ($prop->map) {
            switch ($prop->valtyp) {
                case 'string':
                    {
                        $ret = MapT::Convert($val, function ($e) {
                            return Kernel::ToString($e);
                        });
                    }
                    break;
                case 'integer':
                case 'enum':
                    {
                        $ret = MapT::Convert($val, function ($e) {
                            return Kernel::ToInt($e);
                        });
                    }
                    break;
                case 'double':
                    {
                        $ret = MapT::Convert($val, function ($e) {
                            return Kernel::ToDouble($e);
                        });
                    }
                    break;
                case 'boolean':
                    {
                        $ret = MapT::Convert($val, function ($e) {
                            return Kernel::ToBoolean($e);
                        });
                    }
                    break;
                default:
                    {
                        $ret = MapT::Convert($val, function ($e) use ($prop) {
                            $t = new $prop->valtyp();
                            self::Decode($t, $e);
                            return $t;
                        });
                    }
                    break;
            }

            return $ret;
        }

        if ($prop->filter) {
            return Filter::ParseString($val);
        }

        $tgt = new $prop->valtyp;
        $obj = json_decode($val, true);
        self::Decode($tgt, $obj);
        return $tgt;
    }

    protected static function OutputValue($val, PropDeclaration $prop)
    {
        if ($prop->string) {
            return (string)$val;
        }

        if ($prop->json) {
            return Kernel::toJson($val);
        }

        if ($prop->integer || $prop->enum) {
            return (int)$val;
        }

        if ($prop->double) {
            return (double)$val;
        }

        if ($prop->boolean) {
            return $val ? true : false;
        }

        if ($prop->array) {
            $arr = [];
            if ($val) {
                switch ($prop->valtyp) {
                    case 'string':
                        {
                            $arr = ArrayT::Convert($val, function ($e) {
                                return Kernel::ToString($e);
                            });
                        }
                        break;
                    case 'integer':
                    case 'enum':
                        {
                            $arr = ArrayT::Convert($val, function ($e) {
                                return Kernel::ToInt($e);
                            });
                        }
                        break;
                    case 'double':
                        {
                            $arr = ArrayT::Convert($val, function ($e) {
                                return Kernel::ToDouble($e);
                            });
                        }
                        break;
                    case 'boolean':
                        {
                            $arr = ArrayT::Convert($val, function ($e) {
                                return Kernel::ToBoolean($e);
                            });
                        }
                        break;
                    default:
                        {
                            $arr = ArrayT::Convert($val, function ($e) {
                                return self::Output($e);
                            });
                        }
                        break;
                }
            }
            return $arr;
        }

        if ($prop->map) {
            $arr = [];
            if ($val) {
                switch ($prop->valtyp) {
                    case 'string':
                        {
                            $arr = MapT::Convert($val, function ($e) {
                                return Kernel::ToString($e);
                            });
                        }
                        break;
                    case 'integer':
                    case 'enum':
                        {
                            $arr = MapT::Convert($val, function ($e) {
                                return Kernel::ToInt($e);
                            });
                        }
                        break;
                    case 'double':
                        {
                            $arr = MapT::Convert($val, function ($e) {
                                return Kernel::ToDouble($e);
                            });
                        }
                        break;
                    case 'boolean':
                        {
                            $arr = MapT::Convert($val, function ($e) {
                                return Kernel::ToBoolean($e);
                            });
                        }
                        break;
                    default:
                        {
                            $arr = MapT::Convert($val, function ($e) {
                                return self::Output($e);
                            });
                        }
                        break;
                }
            }
            return $arr;
        }

        if ($prop->object) {
            return $val;
        }

        if ($prop->filter) {
            return (string)$val;
        }

        if ($prop->type) {
            if (!($val instanceof $prop->valtyp)) {
                if (is_object($val)) {
                    // 如果设置的是对象，则代表业务代码中赋值了一个错误的数据类型
                    throw new \Exception("$prop->name 不是 $prop->valtyp 类型", Code::FAILED);
                } else {
                    // 常见的，model设置为数据库对象，但数据库findfirst返回的是false，所以可以直接返回null代表没有查找到对象
                    return null;
                }
            }
            return self::Output($val);
        }

        return self::Output($val);
    }

    static function CollectParameters(\Phalcon\Http\Request $request)
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

        // 如果是json
        $json = $request->getJsonRawBody();
        if ($json) {
            foreach ($json as $k => $v) {
                $ret[$k] = $v;
            }
        }

        // 如果是xml
        $ct = $request->getHeader('content-type');
        if (strpos($ct, 'xml') !== false) {
            $xml = Kernel::toXmlObj($request->getRawBody());
            foreach ($xml as $k => $v) {
                $ret[$k] = $v;
            }
        }

        // 去除phalcon默认的_url参数
        unset($ret['_url']);

        return $ret;
    }

    static function LoadClassDeclarationOf(\Phalcon\Annotations\Reflection $reflect, ModelDeclaration $decl)
    {
        $annClass = $reflect->getClassAnnotations();
        if (!$annClass)
            return;
        if (!$annClass->has('Model'))
            return;
        $mdl = $annClass->get('Model');
        $ops = $mdl->getArgument(0);
        if (is_array($ops)) {
            $decl->noauth = in_array('noauth', $ops);
            $decl->hidden = in_array('hidden', $ops);
            $decl->enum = in_array('enum', $ops) || in_array('enumm', $ops);
            $decl->const = in_array('const', $ops) || in_array('constant', $ops);
            $decl->super = $mdl->getArgument(1);
        } else {
            $decl->super = $ops;
        }
    }

    static function LoadMembersDeclarationOf(\Phalcon\Annotations\Reflection $reflect, ModelDeclaration $decl)
    {
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

            $mem = new MemberDeclaration();
            $mem->name = $name;
            $mem->model = $model;

            $ops = $action->getArgument(1);
            if (is_string($ops)) {
                $mem->comment = $ops;
            } else if (is_array($ops)) {
                $mem->optional = in_array('optional', $ops);
                $mem->noauth = in_array('noauth', $ops);
                $mem->noexport = in_array('noexport', $ops);
                $mem->expose = in_array('expose', $ops);
                $mem->signature = in_array('signature', $ops);
                $mem->comment = $action->getArgument(2);
            }

            $decl->members[$mem->name] = $mem;
        }
    }

    static function LoadPropsDeclarationOf(\Phalcon\Annotations\Reflection $reflect, ModelDeclaration $decl)
    {
        $annProps = $reflect->getPropertiesAnnotations();
        if (!$annProps)
            return;

        $super = $decl->super ? self::DeclarationOf($decl->super) : null;

        foreach ($annProps as $name => $prop) {
            if (!$prop->has('Api'))
                continue;

            $api = $prop->get('Api');
            $idx = $api->getArgument(0);
            $typs = $api->getArgument(1);

            $ops = $api->getArgument(2);
            if (!is_array($ops)) {
                continue;
            }

            $mem = new PropDeclaration();
            $mem->name = $name;
            $mem->input = in_array('input', $ops);
            $mem->output = in_array('output', $ops);
            $mem->optional = in_array('optional', $ops);
            $mem->comment = $api->getArgument(3) ? $api->getArgument(3) : "";
            $mem->index = (int)$idx;

            if ($super) {
                // 判断是否当前属性位于父类中
                if (isset($super->props[$name])) {
                    $mem->index *= Config::MODEL_FIELDS_MAX;
                }
            }

            switch ($typs[0]) {
                case 'string':
                    $mem->string = true;
                    break;
                case 'json':
                    $mem->json = true;
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
                case 'filter':
                    $mem->filter = true;
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
                case 'enum':
                case 'enumerate':
                    $mem->enum = true;
                    $mem->valtyp = $typs[1];
                    break;
                case 'object':
                    $mem->object = true;
                    break;
                case 'type':
                    $mem->type = true;
                    $mem->valtyp = $typs[0];
                    self::DeclarationOf($mem->valtyp);
                    break;
                default:
                    $mem->valtyp = $typs[0];
                    try {
                        $mem->type = self::DeclarationOf($mem->valtyp) != null;
                    } catch (\Throwable $ex) {
                        // pass
                    };
                    break;
            }

            $decl->props[$mem->name] = $mem;
        }
    }

    /**
     * 获得model的参数描述
     */
    static function DeclarationOf($obj): ModelDeclaration
    {
        $clazz = $obj;
        if (is_object($obj)) {
            $clazz = get_class($obj);
        }

        // 判断之前有没有解析
        $ver = Application::$shared->config('version', '0.0.0');
        $ck = "::nnt::model::proto::$ver::$clazz";
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

        $ret = new ModelDeclaration();

        // 读取描述详细信息
        self::LoadClassDeclarationOf($ann, $ret);
        self::LoadMembersDeclarationOf($ann, $ret);
        self::LoadPropsDeclarationOf($ann, $ret);

        apcu_store($ck, $ret, $ttl);
        return $ret;
    }

    static function GetClassName($clazz): string
    {
        $cmps = explode('\\', $clazz);
        return $cmps[count($cmps) - 1];
    }

    static function FpToTypeDef(PropDeclaration $fp): string
    {
        if ($fp->string) {
            $typ = "string";
        } else if ($fp->json) {
            $typ = "IndexedObject";
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
                    $vt = self::GetClassName($fp->valtyp);
                    break;
            }
            $typ .= $vt;
            $typ .= ">";
        } else if ($fp->map) {
            $typ = "Map<" . self::ValtypeDefToDef($fp->keytyp) . ", " . self::ValtypeDefToDef($fp->valtyp) . ">";
        } else if ($fp->multimap) {
            $typ = "Multimap<" . self::ValtypeDefToDef($fp->keytyp) . ", " . self::ValtypeDefToDef($fp->valtyp) . ">";
        } else if ($fp->enum) {
            $typ = self::GetClassName($fp->valtyp);
        } else if ($fp->file) {
            if ($fp->input)
                $typ = "any";
            else
                $typ = "string";
        } else if ($fp->filter) {
            $typ = 'string';
        } else if ($fp->json || $fp->object) {
            $typ = "Object";
        } else {
            $typ = self::GetClassName($fp->valtyp);
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

        return self::GetClassName($def);
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

        return self::GetClassName($def);
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
        else if ($fp->json)
            $deco = "@" . $ns . "json(" . $fp->index . ", " . self::FpToOptionsDef($fp, $ns) . self::FpToCommentDef($fp) . ")";
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
        } else if ($fp->filter) {
            $deco = "@" . $ns . "filter(" . $fp->index . ", " . self::FpToOptionsDef($fp, $ns) . self::FpToCommentDef($fp) . ")";
        } else if ($fp->json) {
            $deco = "@" . $ns . "json(" . $fp->index . ", " . self::FpToOptionsDef($fp, $ns) . self::FpToCommentDef($fp) . ")";
        } else {
            $deco = "@" . $ns . "type(" . $fp->index . ", " . self::FpToValtypeDef($fp, $ns) . ", " . self::FpToOptionsDef($fp, $ns) . self::FpToCommentDef($fp) . ")";
        }
        return $deco;
    }

    static function FpToDecoDefPHP(PropDeclaration $fp): string
    {
        $deco = null;
        if ($fp->string) {
            $deco = "@Api(" . $fp->index . ", [string], " . self::FpToOptionsDef($fp) . self::FpToCommentDef($fp) . ")";
            $deco .= "\n\t* @var string";
        } else if ($fp->integer) {
            $deco = "@Api(" . $fp->index . ", [integer], " . self::FpToOptionsDef($fp) . self::FpToCommentDef($fp) . ")";
            $deco .= "\n\t* @var int";
        } else if ($fp->double) {
            $deco = "@Api(" . $fp->index . ", [double], " . self::FpToOptionsDef($fp) . self::FpToCommentDef($fp) . ")";
            $deco .= "\n\t* @var double";
        } else if ($fp->boolean) {
            $deco = "@Api(" . $fp->index . ", [boolean], " . self::FpToOptionsDef($fp) . self::FpToCommentDef($fp) . ")";
            $deco .= "\n\t* @var boolean";
        } else if ($fp->array) {
            $deco = "@Api(" . $fp->index . ", [array, " . self::FpToValtypeDef($fp) . "], " . self::FpToOptionsDef($fp) . self::FpToCommentDef($fp) . ")";
        } else if ($fp->map) {
            $deco = "@Api(" . $fp->index . ", [map, " . self::FpToValtypeDef($fp) . "], " . self::FpToOptionsDef($fp) . self::FpToCommentDef($fp) . ")";
        } else if ($fp->multimap) {
            $deco = "@Api(" . $fp->index . ", [multimap, " . self::FpToValtypeDef($fp) . "], " . self::FpToOptionsDef($fp) . self::FpToCommentDef($fp) . ")";
        } else if ($fp->enum) {
            $deco = "@Api(" . $fp->index . ", [enum, " . self::FpToValtypeDef($fp) . "], " . self::FpToOptionsDef($fp) . self::FpToCommentDef($fp) . ")";
        } else if ($fp->file) {
            $deco = "@Api(" . $fp->index . ", [file], " . self::FpToOptionsDef($fp) . self::FpToCommentDef($fp) . ")";
        } else if ($fp->filter) {
            $deco = "@Api(" . $fp->index . ", [filter], " . self::FpToOptionsDef($fp) . self::FpToCommentDef($fp) . ")";
        } else if ($fp->json) {
            $deco = "@Api(" . $fp->index . ", [json], " . self::FpToOptionsDef($fp) . self::FpToCommentDef($fp) . ")";
        } else {
            $deco = "@Api(" . $fp->index . ", [type, " . self::FpToValtypeDef($fp) . "], " . self::FpToOptionsDef($fp) . self::FpToCommentDef($fp) . ")";
        }
        return $deco;
    }

    /**
     * @return int[]
     * Map<string, int>
     */
    static function ConstsOfClass($clazz)
    {
        $ret = [];
        $reflect = new \ReflectionClass($clazz);
        foreach ($reflect->getConstants() as $name => $val) {
            $ret[$name] = $val;
        }
        return $ret;
    }
}

// 内嵌自定义的类型查找
spl_autoload_register(function ($classname) {
    // 文件、路径均为小写
    $path = str_replace('\\', '/', strtolower($classname));
    $target = APP_DIR . "/$path.php";
    if (!is_file($target)) {
        // 再尝试一次使用类名加载
        $ps = explode('\\', $classname);
        $target = APP_DIR;
        for ($i = 0, $l = count($ps); $i < $l - 1; ++$i) {
            $target .= '/' . strtolower($ps[$i]);
        }
        $target .= '/' . $ps[$l - 1] . ".php";
        if (!is_file($target)) {
            echo "没有找到类文件 $target";
            return false;
        }
    }
    include_once $target;
    return true;
});
