<?php
class Model
{
	//数据库操作对象
	public $db = NULL;

	//数据表名称
	private $tableName = '';
	
	public $sql="";

	//要更新的表数据,key:对应表字段; value:数据;
	private $tableData = array();
	
	//var $pages=0;
	public $count=0;

	/**
	 * @brief 构造函数,创建数据库对象
	 * @param string $tableName 表名称(当多表操作时以逗号分隔,如：user,goods);
	 */
	public function __construct($tableName)
	{
		$this->db = db::set();
		
		$tablePre = CFG_DB_PREFIX;

		//多表处理
		if(stripos($tableName,','))
		{
			$tables = explode(',',$tableName);
			foreach($tables as $val)
			{
				if($this->tableName != '')
					$this->tableName .= ',';

				$this->tableName .= $tablePre.trim($val);
			}
		}

		//单表处理
		else
		{
			$this->tableName = $tablePre.$tableName;
		}
	}

	/**
	 * @brief 设置要更新的表数据
	 * @param array $data key:字段名; value:字段值;
	 */
	public function setData($data)
	{
		if(is_array($data))
			$this->tableData = $data;
		else
			return false;
	}
	
	function renameTable($table,$old,$database=""){
		
		if($database)
		{
			$db_link = new mysqli(CFG_DB_HOST,CFG_DB_USER,CFG_DB_PASS,$database);
		}
		
        $sql = "RENAME TABLE `".CFG_DB_PREFIX.$old."` TO `".CFG_DB_PREFIX.$table."`" ;
		
        $re = $database?$db_link->query($sql):$this->db->query($sql);
		
        return $re;
    }
	
	//获得表的字段
	function getCols($table,$db="",$colname=''){
        
        $sql = "SELECT column_name,data_type,character_maximum_length  AS length,is_nullable AS is_null,";
        $sql .= "column_default  AS default_str,column_comment  AS  comment FROM Information_schema.columns WHERE table_Name='".$table."'";
        if($colname) $sql .= " and column_name='$colname'";
		if($db) $sql .= " and TABLE_SCHEMA='$db'";
        //die($sql);
        $result = $this->db->query($sql);

        return $result;
		
    }
	
	//字段处理
	function Alter($type,$table,$fields=array(),$database=""){
		
		if($database)
		{
			$db_link = new mysqli(CFG_DB_HOST,CFG_DB_USER,CFG_DB_PASS,$database);
		}
		
		$type=strtoupper($type);
		
		//删除字段
        if($type=='DROP'){
            $sql = "ALTER TABLE ".CFG_DB_PREFIX.$table." DROP `".$fields["column_name"].'`';
        }
		
		//添加字段
        if($type=='ADD'){
            $sql = "ALTER TABLE ".CFG_DB_PREFIX.$table." ADD `".$fields["column_name"]."` $fields[datatype]( $fields[lenght] )  DEFAULT '$fields[def]' COMMENT '$fields[comment]'";
        }
		
		//修改字段
        if($type=='CHANGE'){
            $sql = "ALTER TABLE ".CFG_DB_PREFIX.$table." CHANGE `".$fields["old"]."` `".$fields["column_name"]."` $fields[datatype]( $fields[lenght] ) DEFAULT '$fields[def]' COMMENT '$fields[comment]'";
        }
       	
		$re = $database?$db_link->query($sql):$this->db->query($sql);
		
        return $re;
    }
	
	//删除表
	function dropTable($table,$database=""){
		if($database)
		{
			$db_link = new mysqli(CFG_DB_HOST,CFG_DB_USER,CFG_DB_PASS,$database);
		}
        $sql = "DROP TABLE ".CFG_DB_PREFIX.$table;
        $re = $database?$db_link->query($sql):$this->db->query($sql);
		return $re;
    }
	
	//创建表
	function createTable($table,$table_key,$database=""){
		
		if($database)
		{
			$db_link = new mysqli(CFG_DB_HOST,CFG_DB_USER,CFG_DB_PASS,$database);
		}
		
       $sql = "CREATE TABLE ".CFG_DB_PREFIX.$table;
        $sql .= "(`".$table_key."_id` INT NOT NULL AUTO_INCREMENT,`mod_key` VARCHAR(65),`mod_id` INT(10),`flag` TinyInt(1),`last_update_name` VARCHAR(60),`last_update_time` INT(10),`add_time` INT(10),`sort_by` INT(255), PRIMARY KEY (`".$table_key."_id`) )";
        $sql .= "ENGINE=InnoDB DEFAULT CHARACTER SET = utf8 COLLATE = utf8_general_ci";
		//error($database);
        $re = $database?$db_link->query($sql):$this->db->query($sql);
		return $re;
    }
	
	//数据库是否存在
	function dataExists($database){
		
        $sql = "SELECT TABLE_NAME as name FROM Information_schema.SCHEMATA WHERE SCHEMA_NAME='".$database."' ";
		//error($sql);
        $re = $this->db->query($sql);
		//error($db_link->query($sql));
		return $re;
    }
	
	/*复制表
	*/
	function copyTable($f,$t,$f_d,$t_d)
	{
		$from=$f_d?$f_d.".".$f:$f;
		$to=$t_d?$t_d.".".$t:$t;
		$sql="CREATE TABLE ".$to." LIKE ".$from;
		return $this->db->query($sql);
	}
	
	//表是否存在
	function tableExists($table,$database=""){
		
        $sql = "SELECT TABLE_NAME as name FROM Information_schema.tables WHERE TABLE_NAME='".$table."' AND TABLE_SCHEMA='".$database."' ";
        $re = $this->db->query($sql);
		return $re;
		
    }
	
	function createDatabase($name)
	{
		$db_link = new mysqli(CFG_DB_HOST,CFG_DB_USER,CFG_DB_PASS);
		$sql="CREATE DATABASE IF NOT EXISTS ".$name." default charset utf8 COLLATE utf8_general_ci";
		//error($sql);
		return $db_link->query($sql);
	}

	/**
	 * @brief 更新
	 * @param  string $where 更新条件
	 * @param  array  $except 非普通数据形式(key值)
	 * @return int or bool int:影响的条数; bool:false错误
	 */
	public function update($data=array(),$where)
	{
		//$except = is_array($except) ? $except : array($except);

		//获取更新数据
		//$tableObj  = $this->tableData;
		$updateStr = '';
		$where     = (strtolower($where) == 'all') ? '' : ' WHERE '.$where;
		
		$tableFields=self::field_init();
		foreach($data as $key => $val)
		{
			if(in_array($key,$tableFields))
			{
				if($updateStr != '') $updateStr.=' , ';
				//if(!in_array($key,$except))
					$updateStr.= '`'.$key.'` = \''.$val.'\'';
				//else
					//$updateStr.= '`'.$key.'` = '.$val;
			}
		}
		$sql = 'UPDATE '.$this->tableName.' SET '.$updateStr.$where;
		return $this->db->query($sql);
	}

	/**
	 * @brief 添加
	 * @return int or bool int:插入的自动增长值 bool:false错误
	 */
	public function add($data)
	{
		//获取插入的数据
		$tableObj = $data?$data:$this->tableData;

		$insertCol = array();
		$insertVal = array();
		$tableFields=self::field_init();
		foreach($tableObj as $key => $val)
		{
			if(in_array($key,$tableFields))
			{
				$insertCol[] = '`'.$key.'`';
				$insertVal[] = '\''.$val.'\'';
			}
		}
		$this->sql=$sql = 'INSERT INTO '.$this->tableName.' ( '.join(',',$insertCol).' ) VALUES ( '.join(',',$insertVal).' ) ';
//			
		return $this->db->query($sql);
	}

	/**
	 * @brief 删除
	 * @param string $where 删除条件
	 * @return int or bool int:删除的记录数量 bool:false错误
	 */
	public function del($where)
	{
		$where = (strtolower($where) == 'all') ? '' : ' WHERE '.$where;
		$sql   = 'DELETE FROM '.$this->tableName.$where;
		return $this->db->query($sql);
	}

	/**
	 * @brief 获取单条数据
	 * @param string $where 查询条件
	 * @param array or string $cols 查询字段,支持数组格式,如array('cols1','cols2')
	 * @return array 查询结果
	 */
	public function getOne($where = false,$cols = '*',$sort,$by)
	{
		$result = $this->query($where,$cols,$sort,$by,1);
		if(empty($result))
		{
			return array();
		}
		else
		{
			return $result[0];
		}
	}

	/**
	 * @brief 获取多条数据
	 * @param string $where 查询条件
	 * @param array or string $cols 查询字段,支持数组格式,如array('cols1','cols2')
	 * @param array or string $orderBy 排序字段
	 * @param array or string $desc 排列顺序 值: DESC:倒序; ASC:正序;
	 * @param array or int $limit 显示数据条数 默认(5000)
	 * @return array 查询结果
	 */
	public function query($where=false,$cols='*',$orderBy=false,$desc='DESC',$limit=50,$is_page=0,$debug=0)
	{
		//字段拼接
		if(is_array($cols))
		{
			$cols1=array();
			foreach($cols as $k=>$v)
			{
				$cols1[$k]="`".$v."`";
			}
			$colStr = join(',',$cols1);
		}
		else
		{
			$colStr = ($cols=='*' || !$cols) ? '*' : $cols;
		}
		$sql = 'SELECT '.$colStr.' FROM '.$this->tableName;
		//echo $sql."<br />";
		//条件拼接
		if($where != false) $sql.=' WHERE '.$where;
		if($is_page)
		{
			$sql_count = 'SELECT count(*) as total FROM '.$this->tableName;
			if($where != false) $sql_count.=' WHERE '.$where;
			$re=$this->db->query($sql_count);
			define(PAGE_COUNT,$re[0]["total"]);
			define(PAGE_SIZE,$limit);
        }
		

		//排序拼接
		if($orderBy != false)
		{
			if(!is_array($orderBy))
			{
				$sql.= ' ORDER BY '.$orderBy;
				$sql.= (strtoupper($desc) == 'DESC') ? ' DESC ':' ASC ';
			}
			else
			{
				$sql.=" ORDER BY ";
				foreach($orderBy as $k=>$v)
				{
					$t[]=$v." ".$desc[$k];
				}
				$sql.=" ".implode(",",$t)." ";
			}
		}
		
		//条数拼接
		if($limit != 'all')
		{
			$limit = intval($limit);
			$page=in_request("page")&&$is_page?in_get("page"):1;
			$start=($page-1)*$limit;
			//$limit = $limit ? $limit : 5000;
			$sql.=' limit '.$start.',' . $limit;
		}
		return $this->db->query($sql);
	}

	/**
	 * @brief 写操作回滚
	 */
	public function rollback()
	{
		$this->db->switchLink("w");
		return $this->db->rollback();
	}
	
	public function field_init(){
        //$sql = " DESCRIBE ".$this->prefix.$table;
        
        $sql = "SELECT column_name FROM Information_schema.columns WHERE table_Name='".$this->tableName."'";
		//error($sql);
        $rs = $this->db->query($sql);
		$r=array();
		foreach($rs as $k=>$v)
		{
			$r[]=$v["column_name"];
		}
		//error(http_build_query($r));
        return $r;
    }
}