<?php
class Mysql extends db_face
{
	//当前数据库连接资源,读或写
	public $linkRes = false;
	
	/**
	 * @brief 数据库连接
	 * @param array $dbinfo 数据库的连接配制信息 [0]ip地址 [1]用户名 [2]密码 [3]数据库
	 * @return bool or resource 值: false:链接失败; resource类型:链接的资源句柄;
	 */
	public function connect($host,$user,$passwd,$name,$port)
	{
		$port  = isset($port) ? $port : ini_get("mysqli.default_port");

	  	$this->linkRes = new mysqli($host,$user,$passwd,$name,$port);
	  	if($this->linkRes->connect_error)
	  	{
	  		//throw new IException($this->linkRes->connect_error,1000);
			die("数据库连接错误！");
	  		return false;
	  	}
	  	else
	  	{
		  	$DBCharset = CFG_DB_CHARSET;
		  	$this->linkRes->set_charset($DBCharset);
		  	$this->linkRes->query("SET SESSION sql_mode = '' ");
		  	$this->linkRes->query("set global tx_isolation='read-uncommitted' ");
		  	return $this->linkRes;
	  	}
	}

	/**
	* @brief MYSQL的SQL执行的系统入口
	* @param string $sql 要执行的SQL语句
	* @return mixed
	*/
	public function doSql($sql)
	{
		//读操作
		$readyConf = array('select','show','describe');
		if(in_array(self::$sqlType,$readyConf))
		{
			return $this->read($sql,MYSQLI_ASSOC);
		}
		//写操作
		else
		{
			return $this->write($sql);
		}
	}

	/**
	* @brief 获取数据库内容
	* @param $sql SQL语句
	* @param $type 返回数据的键类型
	* @return array 查询结果集
	*/
	private function read($sql,$type = MYSQLI_ASSOC)
	{
		$result   = array();
		$resource = $this->linkRes->query($sql);

		if($resource)
		{
			while($data = $resource->fetch_assoc())
			{
				$result[] = $data;
			}
			$resource->free();
			return $result;
		}
		else
		{
			return false;
		}
	}

	/**
	* @brief 写入操作
	* @param string $sql SQL语句
	* @return int or bool 失败:false; 成功:影响的结果数量;
	*/
	private function write($sql)
	{
		$result = $this->linkRes->query($sql);

		if($result==true)
		{
			switch(self::$sqlType)
			{
				case "insert":
				{
					//echo $sql."<hr>";
					return $this->linkRes->insert_id;
				}
				break;

				default:
				{
					//return $this->linkRes->affected_rows;
					return true;
				}
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * @brief 开启事务处理
	 * @param boolean
	 */
	public function autoCommit()
	{
		return $this->linkRes->autocommit(false);
	}

	/**
	 * @brief 提交事务
	 * @param boolean
	 */
	public function commit()
	{
		return $this->linkRes->commit();
	}

	/**
	 * @brief 回滚事务
	 * @param boolean
	 */
	public function rollback()
	{
		return $this->linkRes->rollback();
	}
}