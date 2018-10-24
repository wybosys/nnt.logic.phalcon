<?php
// 请不要修改该自动生成的文件

namespace Framework\Phalcon;

include_once "./model-impl.php";

class ApiModel extends \Model {
    public $domain = "framework/phalcon";
}




  const DEF_TEST = "ABC";



  class MCall extends ApiModel {
  
      /*
      * @Model.string(1, [Model.input], "服务名")
      */
      public $name;
  
      /*
      * @Model.type(2, Object, [Model.input], "参数集")
      */
      public $args;
  
      /*
      * @Model.type(3, Object, [Model.output], "获得")
      */
      public $output;
  
  }

  class MCidrTest extends ApiModel {
  
      /*
      * @Model.string(1, [Model.input], "规则 172.0.0.0/24")
      */
      public $rule;
  
      /*
      * @Model.string(2, [Model.input], "IP")
      */
      public $ip;
  
      /*
      * @Model.boolean(3, [Model.output], "结果")
      */
      public $result;
  
  }

  class MEchoo extends ApiModel {
  
      /*
      * @Model.string(1, [Model.input], "输入")
      */
      public $input;
  
      /*
      * @Model.string(2, [Model.output], "输出")
      */
      public $output;
  
      /*
      * @Model.enumerate(3, Code, [Model.output], "状态")
      */
      public $status;
  
  }

  class MHostInfo extends ApiModel {
  
      /*
      * @Model.string(1, [Model.input])
      */
      public $name;
  
      /*
      * @Model.string(2, [Model.output])
      */
      public $info;
  
  }

  class MKv extends ApiModel {
  
      /*
      * @Model.string(1, [Model.input, Model.output])
      */
      public $key;
  
      /*
      * @Model.string(2, [Model.input, Model.output, Model.optional])
      */
      public $value;
  
  }

  class MLog extends ApiModel {
  
      /*
      * @Model.string(1, [Model.input, Model.output])
      */
      public $msg;
  
      /*
      * @Model.integer(2, [Model.input, Model.output, Model.optional])
      */
      public $type;
  
  }

  class MOutput extends ApiModel {
  
      /*
      * @Model.string(1, [Model.output])
      */
      public $output;
  
  }

  class MUploadImage extends ApiModel {
  
      /*
      * @Model.file(1, [Model.input])
      */
      public $file;
  
      /*
      * @Model.string(2, [Model.output])
      */
      public $path;
  
  }

  class MUser extends ApiModel {
  
  }

  class MNil extends ApiModel {
  
  }


class Routers {

  static $TestEchoo = ["test.echoo", "Framework\Phalcon\MEchoo", "输出"];

  static $TestEcho = ["test.echo", "Framework\Phalcon\MEchoo", "输出"];

  static $TestCallechoo = ["test.callechoo", "Framework\Phalcon\MEchoo", "输出"];

  static $TestSvccall = ["test.svccall", "Framework\Phalcon\MCall", "远程调用"];

  static $TestHostinfo = ["test.hostinfo", "Framework\Phalcon\MHostInfo", "主机信息"];

  static $TestUploadimage = ["test.uploadimage", "Framework\Phalcon\MUploadImage", "上传图片"];

  static $TestPhpinfo = ["test.phpinfo", "Framework\Phalcon\MOutput", "php信息"];

  static $TestMklog = ["test.mklog", "Framework\Phalcon\MLog", "输出日志"];

  static $TestRedis = ["test.redis", "Framework\Phalcon\MKv", "测试redis"];

  static $TestCidr = ["test.cidr", "Framework\Phalcon\MCidrTest", "CIDR测试"];

  static $TestCache = ["test.cache", "Framework\Phalcon\MEchoo", "测试缓存"];

  static $TestApcu = ["test.apcu", "Framework\Phalcon\MEchoo", "测试缓存"];

  static $TestNoargs = ["test.noargs", "Framework\Phalcon\MNil", ""];

}


  function TestEchoo():MEchoo {
  return \Model::NewRequest(Routers::$TestEchoo);
  }

  function TestEcho():MEchoo {
  return \Model::NewRequest(Routers::$TestEcho);
  }

  function TestCallechoo():MEchoo {
  return \Model::NewRequest(Routers::$TestCallechoo);
  }

  function TestSvccall():MCall {
  return \Model::NewRequest(Routers::$TestSvccall);
  }

  function TestHostinfo():MHostInfo {
  return \Model::NewRequest(Routers::$TestHostinfo);
  }

  function TestUploadimage():MUploadImage {
  return \Model::NewRequest(Routers::$TestUploadimage);
  }

  function TestPhpinfo():MOutput {
  return \Model::NewRequest(Routers::$TestPhpinfo);
  }

  function TestMklog():MLog {
  return \Model::NewRequest(Routers::$TestMklog);
  }

  function TestRedis():MKv {
  return \Model::NewRequest(Routers::$TestRedis);
  }

  function TestCidr():MCidrTest {
  return \Model::NewRequest(Routers::$TestCidr);
  }

  function TestCache():MEchoo {
  return \Model::NewRequest(Routers::$TestCache);
  }

  function TestApcu():MEchoo {
  return \Model::NewRequest(Routers::$TestApcu);
  }

  function TestNoargs():MNil {
  return \Model::NewRequest(Routers::$TestNoargs);
  }