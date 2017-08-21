<?php	
/*主页模块*/
include_once('nfdw.mod.php');//引入南方电网这个类
/*
 * @chastlove 2016.10.12
 * @     NFDW类   处理南方电网项目的所有逻辑
 * */
class ADMIN extends NFDW
{
	function __construct($do="index",$paras="")
	{
		parent::__construct();
		if(!method_exists(get_class($this),$do))
			die("request error!");
		if(!defined("METHOD"))
			define("METHOD",$do);
		/*2016.10.11 start
		Author: chastlove
		 $do一般为product  或者系统自定义方法，这类要特殊处理
		$paras[0] 一般为表名
		paras[1]  一般为方法名
		$paras[2]一般我参数  后面依次类推，
		通过这个控制权限
		*/
	if(!empty($_SESSION['admin_info'])){
		if($do=="product") {//如果都是通过product这个方法走的自定义方法要控制权限
			if ($_SESSION["admin_info"]["privs"] !== "privs_all") {
				$privs_all = array();
				$privs_all = $_SESSION["admin_info"]["privs"];//session获得的所有权限
				foreach ($privs_all as $key => $value) {
					if(!empty($value['model_key'])) {
						$mod_key[] = $value['model_key'];//先取出表名
					}
					if(!empty($value['act_key'])) {
						$act_key[] = $value['act_key'];//方法名
					}
				}
			//	var_dump($paras);
			//	var_dump($mod_key);
				//var_dump($act_key);

				//var_dump($privs_all);
				if(!in_array($paras[0],$mod_key)) {
					//选判断是否存在模型
					if (isAjax()) {
						$a = array("isOk" => 0, "msg" => "你没权限访问");
						die(json_encode($a));
					} else {
						$msg = "你没权限访问！";
						tpl("/admin/system/msg", get_defined_vars());
						return;
					}
				}elseif($paras[1]) {
					if(!empty($paras[2])){
						$paras[1]="update";//如果带了参数，此方法就设置为修改这个方法
					}
					if (!in_array($paras[1], $act_key)) {//再判断有没有方法
						if (isAjax()) {
							$a = array("isOk" => 0, "msg" => "你没权限访问");
							die(json_encode($a));
						} else {
							$msg = "你没权限访问！";
							tpl("/admin/system/msg", get_defined_vars());
							return;
						}
					}
				}

			}
		}
	}
		/*--end--*/
		call_user_func_array(array(get_class($this),$do),$paras);
	}

	//首页
	function index()
	{
		//echo "index<hr>";


		tpl("admin/index",get_defined_vars());
	}
	
	//退出登录
	function logOut()
	{
		unset($_SESSION["admin_info"]);
		//var_print($_SERVER);
		go("admin-login.html?url=".urlencode($_SERVER["HTTP_REFERER"]));
	}
	
	//消息输出
	function msg()
	{
		$msg=in_get("msg");
		//die('asdfasdf');
		tpl("admin/system/msg",get_defined_vars());
	}
	
	//登录
	function login()
	{
		//var_print($_SERVER);
		if(isPost())
		{	
			$code=$_POST['validate'];
			if($_SESSION["code_num"]!==$code){
				error("验证码输入错误！");
			}
			$a=new model("admin");
			$login_error=new model("admin_login_error");
			$re=$a->getOne(" admin_name='".in_post("admin_name")."' AND passwd='".md5(in_post("passwd"))."' ");
			$result = $login_error->getOne(" admin_name='" . in_post("admin_name") . "'");//得到错误登录日志
			$rest_time=$result['lock_time']-time();
			if($rest_time<0){
				$rest_time=null;
			}
			$rest_time2=floor($rest_time/60);
			if($rest_time) {
				//判断用户名是否被锁定
				if (!$rest_time == 0) {
					error("你输入的次数过多，用户名已经被系统自动锁定，还剩" . $rest_time2 . "分钟才能登录!");
				}
				//如果过了时间，释放限制
				if ($rest_time == 0 || $rest_time > 0) {
					$sql = "delete *from admin_login_error where id=" . $result['id'];
					$this->db->query($sql);
				}
			}
			//如果登录错误
			if(!$re) {
				if(!$result){//如果没有记录先写记录
					$arr['admin_name']=in_post("admin_name");
					$arr['error_count']=1;
					$login_error->add($arr);
					error("用户名或密码错误,你还剩2次输入机会!");
				}
				$result['error_count']++;//增加错误记录
				//更新次数
				if($result['error_count']<3){
					unset($arr);;
					$arr['error_count']=$result['error_count'];
					$arr['error_count']=2;
					$login_error->update(array("error_count"=>$arr['error_count'])," admin_name='".$result["admin_name"]."' ");
					$count=3-$result['error_count'];
					error("用户名或密码错误,你还剩".$count."次输入机会!");
				}
				//更新锁定时间
				if($result['error_count']==3){
					unset($arr);
					$arr['lock_time']=time()+60*60;
					$login_error->update(array("lock_time"=>time()+60*60)," admin_name='".$result["admin_name"]."' ");
					error("你输入的错误次数已经达3次，系统将锁定一个小时不能登录!");
				}
				//判断错误记录
				if ($result['error_count'] >3) {
					error("你输入的错误次数已经达3次，系统将锁定一个小时不能登录!");
				}
					error("用户名或密码错误!");

			}
			$_SESSION["admin_info"]=$re;
			//获得权限表
			/*FIND_IN_SET
			 * chastelove   2016.10.11
			 */
			if($re["role_id"]!="privs_all")
			{
				$p=new model("admin_role");
				//error(" rule_id='".$re["rule"]."' ");
				//echo $re["role_id"];
				$privs=$p->getOne(" admin_role_id='".$re["role_id"]."' ");
				$privs_array=$privs['privs_id_string'];
				$array=explode(',',$privs_array);//获得权限集

				//error(http_build_query($privs));
				unset($p);
				$p=new model("admin_role_privs");
				unset($result);

				foreach($array as $k=>$v)
				{
					$result[]=$p->getOne("admin_role_privs_id=".$v);
					//print_r($result);//取出此用户拥有的所有权限
				}
				$_SESSION["admin_info"]["model"]=$privs['model'];
				$_SESSION["admin_info"]["privs"]=$result;
			}else{
				$_SESSION["admin_info"]["privs"]="privs_all";
			}
			
			$re=$a->update(array("last_login_time"=>time())," admin_id='".$re["admin_id"]."' ");
			
			
			//error('afdasdf123413');
			$url=in_get("url")?in_get("url"):"admin.html";
			//error(in_get("url"));

			$this->add_log("登录","","名称:".in_post("admin_name"),$re);
			ok("<p style='color:green'>登录成功<p>",$url);

		}
		//引入验证码类

		tpl("admin/system/login",get_defined_vars());
	}
	
	//设置分类
	function model($do="",$id="")
	{
		$product_id=in_get("product_id");
		$database="";
		if($product_id)
		{
			$mod=new model("products");
			$products=$mod->getOne(" products_id='".$product_id."' ");
			$database=$products["products_key"];
			if(!$products)
				error("产品不存在！");
		}
		if($id)
		{
			$mod=new model("model");
			$info=$mod->getOne(" mod_id='".$id."' ");
			if(!$info)
				error("模型不存在1！");
		}
		else
			$info=array();
		

		
		/*************************************************************属性设置开始********************************/
		
		//属性设置
		if($do=="attrs_list")
		{
			$mod=new model("model_attrs");
			$attrs=$mod->query(" mod_id='".$id."' ");
			$model_id=$id;
			tpl("admin/system/model_attrs_list",get_defined_vars());
		}
		
		//属性设置
		if($do=="attrs_add")
		{
			if($attr_id=in_request("attr_id"))
			{
				$mod=new model("model_attrs");
				$attr=$mod->getOne(" attr_id='".$attr_id."' ");
				if(!$info)
					error("属性不存在！");
			}
			else
				$attr=array();
			//error($attr_id);
			if(isPost())
			{

				$inserts["name"]			=		in_post("name");
				$inserts["type"]			=		in_post("type");
				$inserts["options"]			=		in_post("options");
				$inserts["mod_key"]			=		$info["mod_key"];
				$inserts["reg"]				=		in_post("reg");
				$inserts["def"]				=		in_post("def");
				$inserts["placeholder"]		=		in_post("placeholder");
				$inserts["length"]			=		in_post("length");
				$inserts["is_edit"]			=		in_post("is_edit","string","0");
				$inserts["require"]			=		in_post("require")?in_post("require"):0;
				$inserts["show_list"]		=		in_post("show_list")?in_post("show_list"):0;
				$inserts["add_time"]		=		time();
				$inserts["key"]				=		in_post("key");
				$inserts["mod_id"]				=		$id;
				
				$mod=new model("model_attrs");
				
				//是否属性已存在
				$re=$mod->getOne(" `key`='".$inserts["key"]."' AND mod_id='".$inserts["mod_id"]."' AND attr_id!='".$attr_id."' ");
				if($re)
					error("相同key属性已经存在！");
				
				//是否名称已存在
				$re=$mod->getOne(" `name`='".$inserts["name"]."' AND mod_id='".$inserts["mod_id"]."' AND attr_id!='".$attr_id."' ");
				if($re)
					error("相同名称属性已经存在！");
					
				
				if(($inserts["type"]=="memo")&&($inserts["length"]>255)||($inserts["type"]=="editor"))
				{
					$data_type="text";
				}
				else
					$data_type="varchar";
				
				if($attr_id)
				{
					//error(" attr_id='".$attr_id."' ");
					$re=$mod->update($inserts," attr_id='".$attr_id."' ");
					if($re&&($attr["key"]!=$inserts["key"]))	//编辑成功
						$re=$mod->Alter("CHANGE",$info["mod_key"],array("old"=>$attr["key"],"column_name"=>$inserts["key"],"datatype"=>$data_type,"lenght"=>$inserts["length"],"comment"=>$inserts["name"],"def"=>$inserts["def"]),$database);
					
				}
				else
				{
					
					$re=$mod->add($inserts);
					if($re)
					{	
						//添加字段
						$re=$mod->Alter("ADD",$info["mod_key"],array("column_name"=>$inserts["key"],"datatype"=>$data_type,"lenght"=>$inserts["length"],"comment"=>$inserts["name"]),$database);
					}
				}
				
				$this->add_log("属性/字段",(isset($attr_id)?"编辑":"添加"),"名称:".$inserts["name"],$re);
				opMsg($re);
			}
				
			$model_id=$id;
			tpl("admin/system/model_attrs_add",get_defined_vars());
		}
		
		//属性关联
		if($do=="attrs_gl")
		{
			$mod=new model("model");
			$models=array();
			$models=$mod->query();
			tpl("admin/system/model_attrs_gl",get_defined_vars());
		}
		
		//属性设置
		if($do=="attrs_gl_get")
		{
			$mod=new model("model_attrs");
			$attrs=array();
			$attrs=$mod->query(" mod_id='".$id."' ");
			$model_id=$id;
			tpl("admin/system/model_attrs_gl_get",get_defined_vars());
		}
		
		///删除属性
		if($do=="attrs_del")
		{
			
			if($attr_id=in_request("attr_id"))
			{
				$mod=new model("model_attrs");
				$attr=$mod->getOne(" attr_id='".$attr_id."' ");
				if(!$attr)
					error("属性不存在！");

				//是否还有属性
				/*$product=new model($info["mod_key"]);
				$re=$product->query(" mod_id='".$id."' ");
				if($re)
					error("模型还有数据，先删除数据，再删除模型！");
				*/
				//error($attr["key"]);
				$re=$mod->del(" attr_id='".$attr_id."' ");
				if($re)
				{
					//删除表结构
					$re=$mod->Alter("DROP",$info["mod_key"],array("column_name"=>$attr["key"]));
				}
				
				$this->add_log("属性","删除","模型:".$info["mod_name"].",属性:".$attr["name"],$re);
				
				opMsg($re);
				
			}
			else
				error("参数错误！");	
			
		}
		
		/*****************************************************属性设置结束**********************************************/
		
		//添加
		if($do=="add")
		{
			if(isPost())
			{
				
				$mod=new model("model");
				$i["mod_name"]		=		in_post("mod_name");
				$i["mod_key"]		=		in_post("mod_key");
				$i["product_id"]	=		in_request("product_id");
				
				$database="";
				if($i["product_id"])
				{
					$p_mod=new model("products");
					$product_one=$p_mod->getOne(" products_id='".$i["product_id"]."' ");
					$database="products_".$product_one["products_key"];
					//error($product_one["products_key"]);
				}
				
				//标识不能重复
				if($mod->getOne(" mod_key='".$i["mod_key"]."' AND mod_id!='".$id."'"))
				{
					error("相同模型标识已经存在！");
				}
					
				//名称已经存在
				if($mod->getOne(" mod_name='".$i["mod_name"]."' AND mod_id!='".$id."'"))
				{
					error("相同模型名称已经存在！");
				}
				//error('asdfas');
				
				if($id)
				{
					$re=$mod->renameTable($i["mod_key"],$info["mod_key"],$database);
					if($re&&($info["mod_key"]!=$i["mod_key"]))	//编辑成功
						$re=$mod->update($i," mod_id='".$id."' ");
				}
				else
				{
					if($mod->tableExists($i["mod_key"],$database))
						error("数据库表已经存在！");
						
					$re=$mod->createTable($i["mod_key"],$i["mod_key"],$database);
					if($re)	//添加数据库表
						$re=$mod->add($i);
				}
				//error('sdfasf');
				$this->add_log("模型",($id?"编辑":"添加"),"名称:".$i["mod_name"].",标识:".($id?$i["mod_key"]:""),$re);
				opMsg($re);
			}
			
			$product_id=in_request("product_id");
			tpl("admin/system/model_new",get_defined_vars());
			
		}
		
		//删除模型
		if($do=="del")
		{
			//是否还有属性
			$attrs=new model($info["mod_key"]);
			$re=$attrs->query(" mod_id='".$id."' ");
			if($re)
				error("模型还有数据，先删除数据，再删除模型！");
				
			//是否还有属性
			$attrs=new model("model_attrs");
			$re=$attrs->query(" mod_id='".$id."' ");
			if($re)
				error("模型还有属性，先删除属性，再删除模型！");
			
			$mod=new model("model");
			$re=$mod->del(" mod_id='".$id."' ");
			if($re)
			{
				$re=$mod->dropTable($info["mod_key"]);
				//error($re);
			}
			$this->add_log("模型","删除","名称:".$info["mod_name"].",标识:".$info["mod_key"],$re);
			opMsg($re);
		}
		
		//列表
		$mod=new model("model");
		
		$w="";
		if($product_id=in_get("product_id"))
			$w=" product_id='".$product_id."' ";
		$re=$mod->query($w,"*","mod_id","asc",50,1);
		$mod_key="model";
		tpl("admin/system/model",get_defined_vars());
	}

	//管理员密码设置
	private function _admin_passwd($id,$pid)
	{
		if(isPost())
		{
			
			$m=new model("admin");
			
			$admin=$m->getOne(" admin_id='".$id."' ");
			if(!$admin)
				error("管理员不存在！");
				
			$re=$m->update(array("passwd"=>md5(in_post("passwd")))," admin_id='".$id."' ");
			$this->add_log("管理员","修改密码","名称:".$admin["admin_name"],$re);
			opMsg($re);
		}
		
		tpl("admin/system/admin_passwd",get_defined_vars());
	}

	//产品
	function product($mod_key,$act="list",$id=0,$pid=0)
	{
		$query_str=$_SERVER["QUERY_STRING"];

		if(method_exists(get_class($this),"_".$mod_key."_".$act))
		{
			//die("_".$mod_key."_".$act);
			call_user_func_array(array(get_class($this),"_".$mod_key."_".$act),array($id,$pid));
		}
		
		$mod=new model("model");
		$info=$mod->getOne(" mod_key='".$mod_key."' ");
		if(!$info)
			error("模型不存在！");
		
		if($act=="set_sort")
		{
			$p=new model($mod_key);
			$product=$p->getOne($mod_key."_id='".$id."' ");
			if(!$product)
				error("操作对象不存在！");
			
			$re=$p->update(array("sort_by"=>in_get("sort"))," ".$mod_key."_id='".$id."' ");
			
			//$this->add_log($info["mod_name"],"排序".$info["mod_name"],"名称:".$product[$mod_key."_name"],$re);
			opMsg($re);
		}
		
		if($act=="del")
		{

			if($id)
			{

				$p=new model($mod_key);
				$product=$p->getOne($mod_key."_id='".$id."' ");

				if(!$product)
					error("操作对象不存在！");
				//error(" ".$mod_key."_id='".$id."' AND mod_key='".$mod_key."' ");
				$re=$p->del(" ".$mod_key."_id='".$id."' AND mod_key='".$mod_key."' ");

				//$mod=new model("product");
				//$re=$mod->del(" product_id='".$product["parent_id"]."' AND mod_key='".$mod_key."' ");
				
				$this->add_log($info["mod_name"],"删除".$info["mod_name"],"名称:".$product[$mod_key."_name"],$re);
				opMsg($re);
			}
		}
		
		//列出产品
		if($act=="list")
		{
			$filters=in_request("filters");
			$mod_attrs=new model("model_attrs");
			$attrs=$mod_attrs->query(" mod_id='".$info["mod_id"]."' AND show_list='1' ",array("name","key"));
			$cols=array($mod_key."_id");
			$titles1=array("ID");
			foreach($attrs as $k=>$v)
			{
				$titles1[]=$v["name"];
				$cols[]=$v["key"];
			}
			$cols=array_merge($cols,array("sort_by","add_time","last_update_time"));
			$titles=array_merge($titles1,array("排序","增加时间","更新时间"));
			//由于这里普通模型出现BUG所有重新写标题，管理员仍然保持不变
			$titles_new=array_merge($titles1,array("增加时间","更新时间","排序"));
			$mod=new model($mod_key);
			$fields=$mod->field_init();//列出所有字段
			//var_print($fields);
			$w="";
			//搜索
				$sKey=in_request("keyword");
				if($sKey)
					$w=" AND ".$mod_key."_name LIKE '%".$sKey."%' ";
			
			//列出分类
				if($id&&$mod_key)
				{
					$w.=" AND class_id='".$id."' ";
				}
			$products=$mod->query(" 1 ".$w,"*",$sort?$sort:"add_time","ASC",50,1);//列出所有记录
			//print_r($cols);
			//如果存在关联处理关联数据
			//$sql="select * from model_attrs where mod_id=".$info['mod_id']." and type='gl'";
			//$gl_data=$mod_attrs->db->query($sql);
			//var_dump($gl_data[0][key]);//关联的字段名
			if(in_array("pid",$fields))
			{
				$products=sort_pid_data($products,0,$mod_key);
			}
			
			//print_r($products);
			
			$tpl="";
			if(file_exists(ROOT_PATH."/tpl/admin/product/admin_product_".$mod_key."_list.htm"))
				$tpl="/admin/product/admin_product_".$mod_key."_list";
			else
				$tpl="/admin/product/admin_product_list";
			//die($tpl);
			tpl($tpl,get_defined_vars());
		}
		
		if($act=="add")
		{
			//error('asfdasf');
			if($id)
			{
				$p=new model($mod_key);
				$product=$p->getOne($mod_key."_id='".$id."' ");
				if(!$product)
					error("操作对象不存在！");
			}
			
			
			if(isPost())
			{
				foreach($_POST["attr"][$mod_key] as $k=>$v)
				{
					if($v)
						$attr_values[$k]=$v;
				}
				$attr_values["mod_id"]=$info["mod_id"];
				$attr_values["mod_key"]=$mod_key;
				$attr_values["flag"]=1;

				$p=new model($mod_key);
				if($id)
				{
					$attr_values["last_update_time"]=time();
					$attr_values["last_update_name"]=$_SESSION["admin_info"]["admin_name"];
					$re=$p->update($attr_values," ".$mod_key."_id='".$id."' ");
				}
				else
				{
					$attr_values["add_time"]=time();

					$re=$p->add($attr_values);
				}
				
				$name=isset($attr_values)&&$attr_values[$mod_key."_name"]?$attr_values[$mod_key."_name"]:$product[$mod_key."_name"];
				$this->add_log($info["mod_name"],($id?"编辑":"添加"),"名称:".$name,$re);
				opMsg($re);
			}

			$options=$this->get_options($mod_key,$id);

			if(file_exists(ROOT_PATH."/tpl/admin/product/admin_product_".$mod_key."_add.htm"))
				$tpl="/admin/product/admin_product_".$mod_key."_add";
			else
				$tpl="/admin/product/admin_product_add";
			tpl($tpl,get_defined_vars());
		}
	}
	
	//选项-添加模版区块

	
	//选项
	function options($key,$mod,$attr,$type,$pid="0")
	{
		//如果有自定义处理函数
		if(method_exists(get_class($this),"__options_".$mod))
		{
			//die("_".$mod_key."_".$act);
			call_user_func_array(array(get_class($this),"__options_".$mod),array($key,$mod,$attr,$type));
		}
		$p=new model($mod);
		$from=in_request("from");
		$fields=$p->field_init();
		//var_print($fields);
		$w="";
		if(in_array("pid",$fields))
		{
			if(!$pid)
				$w=" pid='0' ";
			else
				$w=" pid='".$pid."' ";
		}
		$options=$p->query($w);
		$have_pid=in_array("pid",$fields)?true:false;
		$tpl="tpl/admin/system/options_".$mod."_".$from.".htm";
		$tpl_mod="tpl/admin/system/options_".$mod.".htm";
		if(file_exists(ROOT_PATH.$tpl))
			$tpl="tpl/admin/system/options_".$mod."_".$from.".htm";
		elseif(file_exists(ROOT_PATH.$tpl_mod))
			$tpl=$tpl_mod;
		else
			$tpl="tpl/admin/system/options.htm";
		//die($tpl);
		$tpl=str_replace(array(".htm","tpl/"),array("",""),$tpl);
		tpl($tpl,get_defined_vars());
	}
	

	
	//选项-是否有下级
	function options_next($key,$mod,$attr,$type,$pid="0")
	{
		$p=new model($mod);
		$fields=$p->field_init();
		//var_print($fields);
		$w="";
		if(in_array("pid",$fields))
		{
			if($pid=="0")
				$w=" pid IS NULL ";
			else
				$w=" pid='".$pid."' ";
		}
		else
			error('fail');

		
		$options=$p->query($w);
		
		if(!$options)
			error("fail");
		else
			ok("true");
	}
	function get_img_size()
	{
		$url=in_request("url");
		$url=ROOT_PATH.$url;
		$image = getimagesize($url);
		$r["width"]=$image[0];
		$r["height"]=$image[1];
		die(json_encode($r));
	}
	//test为mod名字。act为方法。ID为参数，此个是路由测试方法
	/*function test($act,$id=null){
		if($act=="about"){

			tpl("admin/product/admin_product_list",get_defined_vars());
		}
	}
	//test前一个是表名，如果没用表就为空，test是方法名,路由前面要加product
	function test2(){
		echo"ok!!!!!!!!";
		echo ROOT_PATH;
		echo "</br>";
		echo __ROOT__;

		tpl("admin/product/admin_product_list",get_defined_vars());
	}*/
/*
	function _admin_role_list(){

		tpl("admin/nfdw/admin_role_list");
	}
*/


}