<?php
/*主页模块*/
class INDEX extends COMMON
{
	function __construct($do = "index", $paras = "")
	{
		parent::__construct();
		if (!method_exists(get_class($this), $do))
			die("request error!");
		if (!defined("METHOD"))
			define("METHOD", $do);
//        var_print();
//        var_print($_SERVER["PATH_INFO"]);
		call_user_func_array(array(get_class($this), $do), $paras);
	}

	//首页
	function index()
	{

		echo "<style type=\"text/css\">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: \"微软雅黑\"; color: #333;font-size:24px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px }</style><div style=\"padding: 24px 48px;\"> <h1>:)</h1><p>欢迎使用 ！</p><br/>[ 您现在访问的是mod模块的Index控制器index方法 ]</div>";
}
}