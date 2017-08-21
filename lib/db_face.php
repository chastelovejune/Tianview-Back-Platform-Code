<?php
abstract class DB_FACE
{
	//数据库写操作连接资源
	private static $wTarget = NULL;
	
	public static $sql	=	NULL;

	//数据库读操作连接资源
	private static $rTarget = NULL;

	//SQL类型
	protected static $sqlType = NULL;

	/**
	* @brief 获取SQL语句的类型,类型：select,update,insert,delete
	* @param string $sql 执行的SQL语句
	* @return string SQL类型
	*/
	private function getSqlType($sql)
	{
		$strArray = explode(' ',trim($sql),2);
		return strtolower($strArray[0]);
	}

	/**
	 * @brief 设置数据库读写分离并且执行SQL语句
	 * @param string $sql 要执行的SQL语句
	 * @return int or bool SQL语句执行的结果
	 */
    public function query($sql)
    {
		//取得SQL类型
        self::$sqlType = $this->getSqlType($sql);

		//读方式
        if(self::$sqlType=='select' || self::$sqlType=='show')
        {
            if(self::$rTarget == NULL)
            {
				//多数据库支持并且读写分离
                if(CFG_DB_R_W==2)
                {
					//获取ip地址
					$ip = IClient::getIP();
                    self::$rTarget = $this->connect(IHash::hash(IWeb::$app->config['DB']['read'],$ip));
                }
                else
                {
                	self::$rTarget = $this->connect(CFG_DB_HOST,CFG_DB_USER,CFG_DB_PASS,CFG_DB_NAME,CFG_DB_PORT);
                }
            }
            $this->switchLink("r");
			//echo $sql."<hr>";
            $result = $this->doSql($sql);
            if($result === false)
            {
				//throw new IException("{$sql}\n -- ".$this->linkRes->error,1000);
				if(CFG_DEBUG)
					error($sql);
				return false;
            }
            return $result;
        }
        //写方式
        else
        {
            if(self::$wTarget == NULL)
            {
				//多数据库支持并且读写分离
                if(CFG_DB_R_W==2)
                {
                	self::$wTarget = $this->connect(IWeb::$app->config['DB']['write']);
                }
                else
                {
                	self::$wTarget = $this->connect(CFG_DB_HOST,CFG_DB_USER,CFG_DB_PASS,CFG_DB_NAME,CFG_DB_PORT);
                }

                //写链接启用事务
                $this->switchLink("w");
                $this->autoCommit();
            }
            $this->switchLink("w");
            $result = $this->doSql($sql);
            if($result === false)
            {
            	$errorMsg = $this->linkRes->error;
            	$this->rollback();
				$debug=in_request("debug");
				if($debug)
				{
					file_put_contents(ROOT_PATH."sql.txt",$sql);
					error($sql);
				}
				//throw new IException("{$sql}\n -- ".$errorMsg,1000);
				return false;
            }
            return $result;
        }
    }

	//析构函数
    public function __destruct()
    {
    	if(self::$wTarget)
    	{
    		$this->switchLink("w");
    		$this->commit();
    	}
    }

    //切换读写链接
    public function switchLink($type)
    {
    	return $this->linkRes = ($type == 'r') ? self::$rTarget : self::$wTarget;
    }

	//数据库连接
    abstract public function connect($host,$user,$passwd,$name,$port);

	//执行sql通用接口
    abstract public function doSql($sql);
}