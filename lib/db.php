<?php
class DB
{
	//数据库对象
	public static $instance   = NULL;

	//默认的数据库连接方式
	private static $defaultDB = 'mysql';

	/**
	 * @brief 创建对象
	 * @return object 数据库对象
	 */
	public static function set()
	{
		//单例模式
		if((self::$instance != NULL) && is_object(self::$instance))
		{
			return self::$instance;
		}
		
		//获取数据库配置信息
		if(!CFG_DB_HOST)
		{
			die('can not find DB info');
		}
		$dbinfo = CFG_DB_HOST;
		
		//数据库类型
		$dbType = CFG_DB_TYPE? CFG_DB_TYPE : self::$defaultDB;
		switch($dbType)
		{
			case "mysql":
			{
				return self::$instance = new mysql();
			}
			break;
		}
	}
    private function __construct(){}
    private function __clone(){}
}