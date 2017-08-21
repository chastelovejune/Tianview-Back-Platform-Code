<?php
//RUNCACMS--框架引擎
/*  version 1.0
 *  @author 
 */

class FRAME {
    
    var $mode;
    var $view;
	var $ERROR	=	array();

    function __construct() 
    {
		$this->Uri();

    }
    
    function Uri($default='index')	//处理框架路径
    {
		//var_print($_SERVER);
		
		if(isset($_SERVER["PATH_INFO"]))
		{
			$info = substr($_SERVER["PATH_INFO"],1);	//获取当前路径
			//die($info);
			if(strstr($info,"-"))
        		$uri = explode('-', $info);
			else
				$uri = explode('/', $info);
				
			if(count($uri)==1)
				$uri[1]=$default;
		}
		else
		{
			$uri[0]=$default;
			$uri[1]=$default;
		}
		/*
		 * @判断路径是否有权限
		 * @chastelove  2016.10.11
		 * @登录才有权限判断  start
		 */
/*
		if(!empty($_SESSION["admin_info"]["privs"])) {
			$privs=array();
			$privs_all=array();
			$privs_all = $_SESSION["admin_info"]["privs"];//session获得的所有权限

				foreach ($privs_all as $key => $value) {
					$privs[] = $value['privs_url'];
				}

			$url_path=trim($_SERVER["PATH_INFO"],'/');;
			var_dump($privs);
				foreach($privs as $k=>$v){
				if($url_path!==$privs[$k]){
					echo "<script>alert('你没有权限访问此方法')<script>";
					return;

				}
			}
		}
*/
		//var_dump($_SERVER["PATH_INFO"]);
		/*--end--***/
        $this->Load_mode($uri);	//装入相应的模块
        if(!$this->mode["func"]) $this->mode["func"] = $default;
        $array=array();
        for($i = ($this->mode["key"]+2);$i < count($uri);$i++){
            $array[] = $uri[$i];
        }
		//var_print($this->mode);
        $run = new $this->mode["name"]($this->mode["func"],$array);
    }
    
    function Load_mode($uri)	//加载模块
    {
        $filePath = '';
        //$uri[0] = 'mod';	//数组0终为空，可赋值
        $this->mode = $this->Find_file($uri);	//查找模块
        if(count($this->mode)>1){
			//die($this->mode['path']);
            require_once $this->mode['path'];
        }else{
            die("do not find mod file!");
            exit();
        }        
    }
	
	/*function admin_class_method($mod,$meth,$paras)
	{
		require_once(ROOT_PATH.ADMIN_PATH."/mod/".$mod.".php");
		$mod=explode("/".$mod);
		$mod=strtoupper(array_pop($mod));
		$temp=new $mod;
		return $temp->$meth(*/
    
    function Find_file($uri=array())	//查找文件
    {
        $filePath = 'mod';
        $mode = array();
        foreach($uri as $k => $f){
			$filePath .= '/';
            $filePath .= $f;
			
            if(!file_exists($filePath)){
                if(file_exists($filePath.'.mod.php')){	//先找到，先返回，找到就返回
                    $mode = array(
                        'path' => $filePath.'.mod.php',
                        'name' => $f,
                        'key'  => $k,
                        'func' => $uri[$k+1],
                    );
					//print_r($mode);
                    return $mode;
                }
            }
        }
		//echo $filePath;
        return $filePath;
    }
     
	//报错 
    function msg($str,$url='',$time=3)	//错误信息提示;
    {
		$url=$url?'location.replace("'.$url.'");':"history.go(-1);";
		die("<script>alert('".addslashes($str)."');".$url."</script>");
    }
	
	function error($str)
	{
		$this->ERROR[]=$str;
	}
	
	function errorPrint()
	{
		foreach($this->ERROR as $er)
		{
			$errors.="<p>";
		}
		msg($errors);
		$this->ERROR=array();
	}
	
	function Error_msg($info)
	{
		//echo "fdasf";
		header("Location: /index-msg-".urlencode($info).".html");
	}
	
	/**
	 * @brief 实现系统类的自动加载
	 * @param String $className 类名称
	 * @return bool true
	 */
	public static function autoload($className)
	{
		if(!preg_match('|^\w+$|',$className))
		{
			die('the class name is inaccurate');
		}

		//内核定义类
		if(isset(self::$_classes[$className]))
		{
			include(ROOT_PATH.self::$_classes[$className]);
			return true;
		}

		//应用扩展类
		/*if(isset(self::$_classes))
		{
            if(isset(self::$_classes[$className]) && self::$_classes[$className])
            {
            	$filePath = self::parseAlias(self::$_classes[$className]).strtolower( $className ) .'.php';
            	if(is_file($filePath))
            	{
	                include($filePath);
	                return true;
            	}
            }
            else
            {
                foreach(self::$_classes as $classPath)
                {
                    $filePath = self::parseAlias($classPath).strtolower( $className ) .'.php';
                    if(!is_file($filePath))
                    {
                    	$filePath = self::parseAlias($classPath).$className.'.php';
                    }

                    if(is_file($filePath))
                	{
	                    include($filePath);
	                    return true;
                	}
                }
            }
		}*/
		return false;
	}
	
	//系统内核所有类文件注册信息
	public static $_classes = array(
        'model'			=>	'lib/model.php',
		'db'			=>	'lib/db.php',
		'db_face'		=>	'lib/db_face.php',
		'mysql'			=>	'lib/driver/mysql.php',
		'gd'			=>	'lib/plus/gd/GD.php',
		'ip'			=>	'lib/plus/ip/IP.class.php',
	);
	
}
spl_autoload_register(array('FRAME','autoload'));

