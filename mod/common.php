<?php
//TIANVIEW--公共类，基类都在里面
/*  version 1.0
 *  @author wuya
 */
class COMMON {
	
    var $db;
	var $tpl;
	//var $db;
	//var $db2;
    
    function __construct()
    {
		self::dis_magic();
		self::set_debug(CFG_DEBUG);
	}
	
	/**
     * @brief 取消魔法转义
     */
    public function dis_magic()
    {
		if(get_magic_quotes_gpc())
		{
			if(isset($_POST))
			{
				$_POST = $this->stripslashes($_POST);
			}

			if(isset($_GET))
			{
				$_GET = $this->stripslashes($_GET);
			}

			if(isset($_COOKIE))
			{
				$_COOKIE = $this->stripslashes($_COOKIE);
			}

			if(isset($_REQUEST))
			{
				$_REQUEST = $this->stripslashes($_REQUEST);
			}
		}
    }

    /**
     * @brief 辅助disableMagicQuotes();
     */
    private function stripslashes($arr)
    {
    	if(is_array($arr))
		{
			foreach($arr as $key => $value)
			{
				$arr[$key] = $this->stripslashes($value);
			}
			return $arr;
		}
		else
		{
			return stripslashes($arr);
		}
    }

    /**
     * @brief 设置调试模式
     * @param $flag true开启，false关闭
     */
    private function set_debug($flag)
    {
		
    	if(function_exists("ini_set"))
		{
			ini_set("display_errors",$flag ? "on" : "off");
		}
    	if(!$flag)
        {
			error_reporting(0);
		}
    }
	
	function add_log($act,$do,$remark,$r)
	{
		$m=new model("admin_log");
		$i["admin_id"]=$_SESSION["admin_info"]["admin_id"];
		$i["admin_name"]=$_SESSION["admin_info"]["admin_name"];
		$i["act"]=$act;
		$i["do_it"]=$do;
		$i["remark"]=$remark;
		$i["result"]=$r?"成功":"失败";
		$i["add_time"]=time();
		$i["ip"]=real_ip();
		
		$re=$m->add($i);
		
		$a=new model("admin");
		//error(" admin_id='".$i["admin_id"]."' ");
		$re=$a->update(array("last_op_do"=>$act.":".$do)," admin_id='".$i["admin_id"]."' ");
		return $re;
	}
	
	function getApiAccessToken() //AccessToeken获取
	{
		
		$apiUrl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.CFG_WX_APPID.'&secret='.CFG_WX_SECRET;
		$res = file_get_contents($apiUrl);
		if(!empty($res))
		{
			$info = json_decode($res,1);
			
			if(isset($info['errcode'])) return false;

			return $info['access_token'];
		}
		return false;
	}
	
	function getApiUserInfo()
	{
	
		if($token=$this->getApiAccessToken())
		{	
			$apiUrl = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$token.'&openid='.OPENID.'&lang=zh_CN';
			//die($apiUrl);
			$info = json_decode(file_get_contents($apiUrl),1);
			
			
		}
		
		return $info;
	}

}

class COMMON_MEMBER extends COMMON{
    
    function __construct()
    {
        parent::__construct();
        $this->init();
    }
    
    function init()
    {
		//checkLogin($this);
    }
}

class COMMON_ADMIN extends COMMON{
    
	//var $menus=array();
	
    function __construct()
    {
        parent::__construct();
		//$this->menus=require_once(ROOT_PATH."/sites/".SITE."/inc/admin_menu.inc.php");
		//var_print($_SESSION);
		if(!isset($_SESSION["admin_info"])&&!strstr($_SERVER["REQUEST_URI"],"admin-login.html"))
			go("admin-login.html?url=".$_SERVER["REQUEST_URI"]);
		//$_SESSION["admin_info"]["admin_id"]				=			"1";
		//$_SESSION["admin_info"]["admin_name"]			=			"snlkpgy";
    }
	
	public static function menus()
	{
		$menu=require(ROOT_PATH."/inc/admin_menu.inc.php");
		//var_print($menu);
		return $menu;
	}
	
	//获得表单选项
	public static function get_options($mod_key,$id=0,$default=array())
	{
		//var_print($default);
		$model_mod=new model("model");
		$info=$model_mod->getOne(" mod_key='".$mod_key."' ");
		if(!$info)
			error("模型不存在！");
		
		$attr_mod=new model("model_attrs");
		$attrs=$attr_mod->query(" mod_id='".$info["mod_id"]."' AND is_edit='1'","*","sort","asc");
		if(!$attrs)
			error("模型属性不存在！");
			
		if($id)
		{
			$p=new model($mod_key);
			$product=$p->getOne(" ".$mod_key."_id='".$id."' ");
			if(!$product)
				error("编辑对象不存在！");
		}
		//var_print($attrs);
		$r_str="<table border='0' cellspacing='5' cellpadding='3' width='650' class='product_add'>";
		foreach($attrs as $k=>$v)
		{
			$name=$v["name"];
			$key=$v["key"];
			$value=$v["def"]?$v["def"]:"";
			$md5=uniqid();
			if($default[$key])
				$value=$default[$key];
			elseif($product[$key])
				$value=$product[$key];
			//echo $key."=".$value.'<br>';
			switch($v["type"])
			{
				case "text":
					$r_str.="<tr id='option_tr_".$key."'><td class='attr_left' valign='top' width='90'>".$name."</td><td class='attr_right' valign='top'><input type='text' reg='".$v["reg"]."' tip='".$v["placeholder"]."' placeholder='"."请输入".$name."' name='attr[".$mod_key."][".$key."]' id='attr_".$mod_key."_".$key."' value='".$value."'></td></tr>";
					break;
				case "radio":
					parse_str(str_replace("&amp;","&",$v["options"]),$options);
					$r_str.="<tr id='option_tr_".$key."'><td class='attr_left' valign='top' width='90'>".$name."</td><td class='attr_right' valign='top'>";
					foreach($options as $k2=>$v2)
					{
						$checked=trim($k2)==trim($value)?" checked ":"";
								//echo $checked;
						$r_str.="&nbsp; <input type='radio' ".$checked." reg='".$v["reg"]."' placeholder='".($v["placeholder"]?$v["placeholder"]:"请输入".$name)."' name='attr[".$mod_key."][".$key."]' id='attr_".$mod_key."_".$key."' value='".$k2."' />".$v2;
					}
					//if($default)
					$r_str.="</td></tr>";
					break;
			/*
			* start
			*2016.10.9 Chastelove
			*/
			/*
			case "checkbox":
					parse_str(str_replace("&amp;","&",$v["options"]),$options);
					$r_str.="<tr id='option_tr_".$key."'><td class='attr_left' valign='top' width='90'>".$name."</td><td class='attr_right' valign='top'>";
					foreach($options as $k2=>$v2)
					{
				
						$checked=trim($k2)==trim($value)?" checked ":"";
								//echo $checked;
						$r_str.="&nbsp; <input type='checkbox' ".$checked." reg='".$v["reg"]."' placeholder='".($v["placeholder"]?$v["placeholder"]:"请输入".$name)."' name='checkbox[]' id='attr_".$mod_key."_".$key."' value='".$k2."' />".$v2;
					}
					//if($default)
					$r_str.="</td></tr>";
					break;*/
			
			/*--END--*/
				case "memo":
					$r_str.="<tr id='option_tr_".$key."'><td class='attr_left' valign='top' width='90'>".$name."</td><td class='attr_right' valign='top'><textarea reg='".$v["reg"]."' style='width:90%; border:1px solid #ddd; padding:5px; height:80px;' placeholder='".($v["placeholder"]?$v["placeholder"]:"请输入".$name)."' name='attr[".$mod_key."][".$key."]' id='".$key."'>".$value."</textarea></td></tr>";
					break;
				case "file":
					$r_str.="<tr id='option_tr_".$key."'><td class='attr_left' valign='top' width='90'>".$name."</td><td class='attr_right' valign='top'>";
					$file=$value?$value:"/tpl/admin/images/add_icon.png";
					$r_str.="<input type='hidden' name='attr[".$mod_key."][".$key."]' id='attr_".$key."' value='".$value."' /><img src='".$file."' class='ajaxDialogUpload' style='border:1px solid #ddd; width:65px; height:65px;' href='upload_img.php?id=attr_".$key."' id='attr_".$key."_"."pic' />"; 
					break;
				case "gl":
					//die($v["options"]);
					parse_str(str_replace("&amp;","&",$v["options"]),$op);
					//var_print($op);
					$r_str.="<tr id='option_tr_".$key."_".$md5."' label='option_tr_".$key."'><td class='attr_left' valign='top' width='90'>".$name."</td><td class='attr_right' valign='top'>";
					$r_str.="<input type='hidden' name='attr[".$mod_key."][".$key."]' id='attr_".$key."_".$md5."' value='".$value."' /><input type='hidden' name='attr[".$mod_key."][".$key."_name]' id='attr_".$key."_name_".$md5."' value='".($product[$key."_name"]?$product[$key."_name"]:($default[$key."_name"]?$default[$key."_name"]:""))."' /><a href='admin-options-".$key."-".$op["mod"]."-".$op["attr"]."-".$op["type"].".html?from=".$mod_key."' id='attr_option_".$key."_".$md5."' class='ajaxDialog'>选择</a>&nbsp; <label id='attr_".$key."_label_".$md5."'>".($product[$key."_name"]?$product[$key."_name"]:($default[$key."_name"]?$default[$key."_name"]:""))."</label></td><script>$('#attr_option_".$key."_".$md5."').ajaxDialog();</script></tr>"; 
					break;
				case "imgs":
								$r_str.="<tr id='option_tr_".$key."'><td class='attr_left'>".$must."&nbsp;".$v["name"]."</td><td class='attr_right'>";
								//echo $v["key"];
								//echo 'index-upload_img.html?tid='.in_get_post("tid").'&cid='.in_get_post("cid").'&name='.$v["key"];
								$r_str.='<br /><input type="button"  name="img_upload_2" href="index-upload_img.html?tid='.in_get_post("tid").'&cid='.in_get_post("cid").'&name='.$v["key"].'" class="button_hui ajaxDialogUpload" href="admin-upload-img.html?id='.$key.'" imgs_name="'.$v["key"].'" value="插入图片" /><br /><span class="help">上传时请注意，第一张会做为封面图片显示！</span><div id="upload_img_show">';
								if(is_array($av))
								{
									//echo $av;
									foreach($av as $i)
										$r_str.='<li><img src="'.$i.'" /><input type="hidden" name="attrs['.$v["key"].'][]" class="src" value="'.$i.'" /></li>';
								}
								$r_str.='<script charset="utf-8" src="/tpl/js/insertImg.js?v='.gTime().'"></script>';
								$r_str.='</div><div class="clear"></div></td></tr>';
								break;
				case "editor":
						$default=$v["options"];
								if(strstr($default,"\n"))
								{
									$default=explode("\n",$default);
								}
								$r_str.="<tr id='option_tr_".$key."'><td class='attr_left'>&nbsp;".$v["name"]."</td><td class='attr_right'>";
								//$r_str.='';
								$r_str.='<div style=" margin:10px; border:1px solid #ddd; width:90%; padding:5px; background:#eee; border-radius:15px;"><textarea name="attr['.$mod_key.']['.$key.']" tabindex="1" reg="'.$v["reg"].'" id="attr_'.$key.'" class="isEditor" style="border:1px solid #ddd; width:100%; height:350px;">'.$value.'</textarea><script>var editor=KindEditor.create("textarea[id=\"attr_'.$key.'\"]",{afterBlur: function () { this.sync(); },allowFileManager:false,width:"100%",height:"'.$default.'",allowImageRemote:true,pasteType:1,items:["fontsize","forecolor", "hilitecolor", "bold", "italic", "underline","removeformat", "|", "justifyleft", "justifycenter", "justifyright", "insertorderedlist","insertunorderedlist","link","source","|", "image", "media", "insertfile"]}); </script></div>';
								$r_str.="</td></tr>";
								break;
			}
		}
		$r_str.="</table>";
		//file_put_contents(ROOT_PATH."/options.txt",$r_str);
		return $r_str;
	}
}

class COMMON_YIYUN extends COMMON{
    
	//var $menus=array();
	
    function __construct()
    {
        parent::__construct();
		//$this->menus=require_once(ROOT_PATH."/sites/".SITE."/inc/admin_menu.inc.php");
		//var_print($_SESSION);
		if(!isset($_SESSION["yiyun_info"])&&!strstr($_SERVER["REQUEST_URI"],"shanghu-login.html"))
			go("shanghu-login.html?url=".$_SERVER["REQUEST_URI"]);
		//$_SESSION["admin_info"]["admin_id"]				=			"1";
		//$_SESSION["admin_info"]["admin_name"]			=			"snlkpgy";
    }
	
	public static function menus()
	{
		$menu=require(ROOT_PATH."/inc/yiyun_menu.inc.php");
		//var_print($menu);
		return $menu;
	}
	
	//获得表单选项
	public static function get_options($mod_key,$id=0,$default=array())
	{
		//var_print($default);
		$model_mod=new model("model");
		$info=$model_mod->getOne(" mod_key='".$mod_key."' ");
		if(!$info)
			error("模型不存在！");
		
		$attr_mod=new model("model_attrs");
		$attrs=$attr_mod->query(" mod_id='".$info["mod_id"]."' AND is_edit='1'","*","sort","asc");
		if(!$attrs)
			error("模型属性不存在！");
			
		if($id)
		{
			$p=new model($mod_key);
			$product=$p->getOne(" ".$mod_key."_id='".$id."' ");
			if(!$product)
				error("编辑对象不存在！");
		}
		//var_print($attrs);
		$r_str="<table border='0' cellspacing='5' cellpadding='3' width='650' class='product_add'>";
		foreach($attrs as $k=>$v)
		{
			$name=$v["name"];
			$key=$v["key"];
			$value=$v["def"]?$v["def"]:"";
			$md5=uniqid();
			if($default[$key])
				$value=$default[$key];
			elseif($product[$key])
				$value=$product[$key];
			//echo $key."=".$value.'<br>';
			switch($v["type"])
			{
				case "text":
					$r_str.="<tr id='option_tr_".$key."'><td class='attr_left' valign='top' width='90'>".$name."</td><td class='attr_right' valign='top'><input type='text' reg='".$v["reg"]."' tip='".$v["placeholder"]."' placeholder='"."请输入".$name."' name='attr[".$mod_key."][".$key."]' id='attr_".$mod_key."_".$key."' value='".$value."'></td></tr>";
					break;
				case "radio":
					parse_str(str_replace("&amp;","&",$v["options"]),$options);
					$r_str.="<tr id='option_tr_".$key."'><td class='attr_left' valign='top' width='90'>".$name."</td><td class='attr_right' valign='top'>";
					foreach($options as $k2=>$v2)
					{
						$checked=trim($k2)==trim($value)?" checked ":"";
								//echo $checked;
						$r_str.="&nbsp; <input type='radio' ".$checked." reg='".$v["reg"]."' placeholder='".($v["placeholder"]?$v["placeholder"]:"请输入".$name)."' name='attr[".$mod_key."][".$key."]' id='attr_".$mod_key."_".$key."' value='".$k2."' />".$v2;
					}
					//if($default)
					$r_str.="</td></tr>";
					break;
				case "memo":
					$r_str.="<tr id='option_tr_".$key."'><td class='attr_left' valign='top' width='90'>".$name."</td><td class='attr_right' valign='top'><textarea reg='".$v["reg"]."' style='width:90%; border:1px solid #ddd; padding:5px; height:80px;' placeholder='".($v["placeholder"]?$v["placeholder"]:"请输入".$name)."' name='attr[".$mod_key."][".$key."]' id='".$key."'>".$value."</textarea></td></tr>";
					break;
				case "file":
					$r_str.="<tr id='option_tr_".$key."'><td class='attr_left' valign='top' width='90'>".$name."</td><td class='attr_right' valign='top'>";
					$file=$value?$value:"/tpl/admin/images/add_icon.png";
					$r_str.="<input type='hidden' name='attr[".$mod_key."][".$key."]' id='attr_".$key."' value='".$value."' /><img src='".$file."' class='ajaxDialogUpload' style='border:1px solid #ddd; width:65px; height:65px;' href='upload_img.php?id=attr_".$key."' id='attr_".$key."_"."pic' />"; 
					break;
				case "gl":
					//die($v["options"]);
					parse_str(str_replace("&amp;","&",$v["options"]),$op);
					//var_print($op);
					$r_str.="<tr id='option_tr_".$key."_".$md5."' label='option_tr_".$key."'><td class='attr_left' valign='top' width='90'>".$name."</td><td class='attr_right' valign='top'>";
					$r_str.="<input type='hidden' name='attr[".$mod_key."][".$key."]' id='attr_".$key."_".$md5."' value='".$value."' /><input type='hidden' name='attr[".$mod_key."][".$key."_name]' id='attr_".$key."_name_".$md5."' value='".($product[$key."_name"]?$product[$key."_name"]:($default[$key."_name"]?$default[$key."_name"]:""))."' /><a href='admin-options-".$key."-".$op["mod"]."-".$op["attr"]."-".$op["type"].".html?from=".$mod_key."' id='attr_option_".$key."_".$md5."' class='ajaxDialog'>选择</a>&nbsp; <label id='attr_".$key."_label_".$md5."'>".($product[$key."_name"]?$product[$key."_name"]:($default[$key."_name"]?$default[$key."_name"]:""))."</label></td><script>$('#attr_option_".$key."_".$md5."').ajaxDialog();</script></tr>"; 
					break;
				case "imgs":
								$r_str.="<tr id='option_tr_".$key."'><td class='attr_left'>".$must."&nbsp;".$v["name"]."</td><td class='attr_right'>";
								//echo $v["key"];
								//echo 'index-upload_img.html?tid='.in_get_post("tid").'&cid='.in_get_post("cid").'&name='.$v["key"];
								$r_str.='<br /><input type="button"  name="img_upload_2" href="index-upload_img.html?tid='.in_get_post("tid").'&cid='.in_get_post("cid").'&name='.$v["key"].'" class="button_hui ajaxDialogUpload" href="admin-upload-img.html?id='.$key.'" imgs_name="'.$v["key"].'" value="插入图片" /><br /><span class="help">上传时请注意，第一张会做为封面图片显示！</span><div id="upload_img_show">';
								if(is_array($av))
								{
									//echo $av;
									foreach($av as $i)
										$r_str.='<li><img src="'.$i.'" /><input type="hidden" name="attrs['.$v["key"].'][]" class="src" value="'.$i.'" /></li>';
								}
								$r_str.='<script charset="utf-8" src="/tpl/js/insertImg.js?v='.gTime().'"></script>';
								$r_str.='</div><div class="clear"></div></td></tr>';
								break;
				case "editor":
						$default=$v["options"];
								if(strstr($default,"\n"))
								{
									$default=explode("\n",$default);
								}
								$r_str.="<tr id='option_tr_".$key."'><td class='attr_left'>&nbsp;".$v["name"]."</td><td class='attr_right'>";
								//$r_str.='';
								$r_str.='<div style=" margin:10px; border:1px solid #ddd; width:90%; padding:5px; background:#eee; border-radius:15px;"><textarea name="attr['.$mod_key.']['.$key.']" tabindex="1" reg="'.$v["reg"].'" id="attr_'.$key.'" class="isEditor" style="border:1px solid #ddd; width:100%; height:350px;">'.$value.'</textarea><script>var editor=KindEditor.create("textarea[id=\"attr_'.$key.'\"]",{afterBlur: function () { this.sync(); },allowFileManager:false,width:"100%",height:"'.$default.'",allowImageRemote:true,pasteType:1,items:["fontsize","forecolor", "hilitecolor", "bold", "italic", "underline","removeformat", "|", "justifyleft", "justifycenter", "justifyright", "insertorderedlist","insertunorderedlist","link","source","|", "image", "media"]}); </script></div>';
								$r_str.="</td></tr>";
								break;
			}
		}
		$r_str.="</table>";
		//file_put_contents(ROOT_PATH."/options.txt",$r_str);
		return $r_str;
	}
	
}