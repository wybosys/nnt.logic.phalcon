<?php
// 请不要修改该自动生成的文件

namespace Framework\Phalcon;

include_once __DIR__ . "/model-impl.php";

class ApiModel extends \Model {
    public $domain = "framework/phalcon";
}




  const DEF_TEST = "ABC";



  class MCall extends ApiModel {
  
      /** @Api(1, [string], [input], "服务名")
	* @var string
      */
      public $name;
  
      /** @Api(2, [type, Object], [input], "参数集")
      */
      public $args;
  
      /** @Api(3, [type, Object], [output], "获得")
      */
      public $output;
  
  }

  class MCidrTest extends ApiModel {
  
      /** @Api(1, [string], [input], "规则 172.0.0.0/24")
	* @var string
      */
      public $rule;
  
      /** @Api(2, [string], [input], "IP")
	* @var string
      */
      public $ip;
  
      /** @Api(3, [boolean], [output], "结果")
	* @var boolean
      */
      public $result;
  
  }

  class MEchoo extends ApiModel {
  
      /** @Api(1, [string], [input], "输入")
	* @var string
      */
      public $input;
  
      /** @Api(2, [string], [output], "输出")
	* @var string
      */
      public $output;
  
      /** @Api(3, [enum, Code], [output], "状态")
      */
      public $status;
  
  }

  class MHostInfo extends ApiModel {
  
      /** @Api(1, [string], [input])
	* @var string
      */
      public $name;
  
      /** @Api(2, [string], [output])
	* @var string
      */
      public $info;
  
  }

  class MKv extends ApiModel {
  
      /** @Api(1, [string], [input, output])
	* @var string
      */
      public $key;
  
      /** @Api(2, [string], [input, output, optional])
	* @var string
      */
      public $value;
  
  }

  class MLog extends ApiModel {
  
      /** @Api(1, [string], [input, output])
	* @var string
      */
      public $msg;
  
      /** @Api(2, [integer], [input, output, optional])
	* @var int
      */
      public $type;
  
  }

  class MOutput extends ApiModel {
  
      /** @Api(1, [string], [output])
	* @var string
      */
      public $output;
  
  }

  class MUploadImage extends ApiModel {
  
      /** @Api(1, [file], [input])
      */
      public $file;
  
      /** @Api(2, [string], [output])
	* @var string
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