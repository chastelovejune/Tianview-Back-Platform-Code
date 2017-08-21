<?php

	$menus = array(
		"管理员" => array(
			"mod" => "product",
			"methods" => array(
				"admin" => "管理员列表",
			),
		),
		"角色和权限" => array(
			"mod" => "product",
			"methods" => array(
				"admin_role" => "角色设置",
				"admin_role_privs" => "权限设置",

			),
		),

		"模型管理" => array(
			"mod" => "model",
			"methods" => array(
				"model" => "模型管理",
		),
		),
	);
if($_SESSION["admin_info"]["privs"]!=="privs_all") {

	//如果不是超级管理员拼接菜单数组
	$privs=$_SESSION["admin_info"]["privs"];
	$module=$_SESSION["admin_info"]["model"];//字段为模块名
	$result=explode(',',$module);//模块名字
	var_dump($result);

	for ($i = 0; $i < count($result); $i++) {
        //  echo $result[$i];
		if (array_key_exists($result[$i],$menus)) {
	echo $result[$i];

		}
	}
	var_dump($menus);
	//var_dump($arr);
	//var_dump($module);
}
	return $menus;

?>