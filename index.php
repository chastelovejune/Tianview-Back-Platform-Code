<?php
//输出头文件
header('Content-Type: text/html; charset=UTF-8');

//时区设置
@ini_set('date.timezone', 'Asia/Shanghai');
define("TIANVIEW", true);
session_start();
//define("ENV","dev");
define("ROOT_PATH", dirname(__file__) . DIRECTORY_SEPARATOR); //根目录定义，由程序自已确认

//不用环境的域名，按此域名载入不同的配置文件
//$env_array=explode(".",$_SERVER["HTTP_HOST"]);
//$env=$env_array[1];	//如果定义了ENV常量，以常量为准，常量dev、test、pro分别代表开发、测试、生产环境
//define("SITE",$env_array[1]);
//echo  ROOT_PATH;
//print_r($_SERVER);
require_once 'inc/global.function.php';
define_cfg(require_once ("config_dev.php"));
//define('__ROOT__',"www.test.com");//定义根路径
error_reporting(E_ALL);
ini_set('display_errors', '1');
//设置cookie的有效目录
//session_set_cookie_params(3600,'/',".".CFG_DOMAIN);
require_once 'lib/frame.engine.php';
require_once "mod/common.php";
$frame = new FRAME();
//$frame->Uri();