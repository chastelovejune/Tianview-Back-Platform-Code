<?php
	
	//跳转网址
	function go($url,$time=0)
	{
		//echo "fdad";
		//die('<meta http-equiv="refresh" content="'.$time.';url='.$url.'"');
		die("<script type='text/javascript'>location.href='".$url."';</script>");
	}
	
	function thumb($fileName, $width = 200, $height = 200 ,$newFileName="")
	{
		
		$GD = new gd($fileName);
		//die($fileName." ".$newFileName);
		if($GD)
		{
			$GD->resize($width,$height);
			$GD->pad($width,$height);
			
			//存储缩略图
			//if($saveDir && IFile::mkdir($saveDir))
			//{
				//生成缩略图文件名
				//$thumbBaseName = $extName.basename($fileName);
				//$thumbFileName = $saveDir.basename($newName);
				$newFileName=$newFileName?$newFileName:$fileName;
				$GD->save($newFileName);
				return $thumbFileName;
			//}
				//直接输出浏览器
			//else
			//{
				//return $GD->show();
			//}
		}
		return null;
		
	}
	
	//是否是微信
	function isWechat()
	{
		if( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false )
		{
			return true;
		}
		return false;
	}
	
	//将配置数组，定义为常量
	function define_cfg($s)
	{
		foreach($s as $k=>$v)
		{
			define("CFG_".$k,$v);
		}
	}
	//时间差
	function time2string($time1){
		$second=strtotime($time1)-strtotime(date('Y-m-d H:i:s',time()));
		$hour = floor($second/3600);
		$second = $second%3600;//除去整小时之后剩余的时间
		$minute = floor($second/60);
		//返回字符串
		return $hour.'小时'.$minute.'分';
	}
	//获得订单状态
	function get_order_status($v,$type)
	{
		$pay_status=array("0"=>"<span style='color:#ccc;'>未付款</span>","1"=>"<span style='color:#f30;'>已付款,未确认</span>","2"=>"<span style='color:#060;'>已付款</span>");
		$shipping_status=array("0"=>"<span style='color:#ccc;'>未发货</span>","1"=>"<span style='color:#333;'>备货中</span>","2"=>"<span style='color:#060;'>已发货</span>","3"=>"<span style='color:#000;'>已收货</span>");
		$comment_status=array("0"=>"<span style='color:#ccc;'>未评价</span>","1"=>"<span style='color:#060;'>已评价</span>");
		
		$name=$type."_status";
		$info=$$name;
		return $info[$v];
	}
	
	//发送短信
	function send_sms($mobile,$type=1,$msg="")
	{
		//header("Content-Type: text/html; charset=UTF-8");
		
		$types=array("1"=>"【云南赛鸽网】您注册的短信验证码为：@请勿将验证码提供给他人。","2"=>"您有新的“易云店”已支付订单：@请及时发货处理！","3"=>"您本次密码找回的验证码为:@请勿将验证码提供给他人。","4"=>"您的微信绑定验证码为：@请勿泄露。","5"=>"新的“易云店”已支付订单需要您配合发货：@请及时发货处理！");
		$m=new model("sms_send_logs");
		if(($type==1)||($type==4))
		{
			$re=$m->getOne(" mobile='".$mobile."' and sms_type='".$type."' and flag='1'","*","add_time","desc");
			if($re)
			{
				if((time()-$re["add_time"])<60)
				{
					$t=60-(time()-$re["add_time"]);
					error("请".$t."秒后再发送！");
				}
			}
			$msg = mt_rand(12345, 99999);//获取随机验证码
		}
			
		$flag = 0; 
		$params='';//要post的数据
		
		//以下信息自己填以下
		//$mobile='';//手机号
		$argv = array( 
			'name'=>"13099977413 ",     //必填参数。用户账号
			'pwd'=>"F7ED2AB5A91A2C59CF8E5C5E32F8 ",     //必填参数。（web平台：基本资料中的接口密码）
			'content'=>str_replace("@",$msg,$types[$type]),  //必填参数。发送内容（1-500 个汉字）UTF-8编码
			'mobile'=>$mobile,   //必填参数。手机号码。多个以英文逗号隔开
			'stime'=>'',   //可选参数。发送时间，填写时已填写的时间发送，不填时为当前时间发送
			'sign'=>"",    //必填参数。用户签名。
			'type'=>'pt',  //必填参数。固定值 pt
			'extno'=>''    //可选参数，扩展码，用户定义扩展码，只能为数字
		);
		//print_r($argv);exit;
		//构造要post的字符串 
		//echo $argv['content'];
		foreach ($argv as $key=>$value) { 
			if ($flag!=0) { 
				$params .= "&"; 
				$flag = 1; 
			} 
			$params.= $key."="; $params.= urlencode($value);// urlencode($value); 
			$flag = 1; 
		} 
		$url = "http://web.cr6868.com/asmx/smsservice.aspx?".$params; //提交的url地址
		//加入数据库
		$con= substr(file_get_contents($url), 0, 1 );  //获取信息发送后的状态
		//error($con);
		$i["mobile"]=$mobile;
		$i["sms_code"]=$msg;
		$i["sms_type"]=$type;
		$i["flag"]=0;
		$i["add_time"]=time();
		$i["mod_key"]="sms_send_logs";
		$i["mod_id"]="13";
		//error(http_build_query($i));
		$re=$m->add($i);
		go($url);
		if($re)
		{
			if($con == '0'){
				$m->update(array("flag"=>1)," sms_send_logs_id='".$re."' ");
				echo "1";
			}else{
				echo "0";
			}
		}

		
	}
	
	//设置session
	function set_session($key,$value='')
	{
		if($value)
			$_SESSION[$key]=$value;
		else
			unset($_SESSION[$key]);
	}
	
	//获取session
	function get_session($key)
	{
		return $_SESSION[$key];
	}
	
	//输出
	function out($o)
	{
		if(isset($o))
			return $o;
	}
	
	//模版处理
	function tpl($file,$datas=array(),$is_die=true)
	{
//		echo $file."<hr>";
		extract($GLOBALS);
		extract($datas);
//		echo $file;//打印当前模板
		if(isAjax())
			define("AJAX",true);
		
		if(strstr($file,"admin/")&&!isAjax())
		{
			 include_once("tpl/admin/top.htm");
		}
		
		if(strstr($file,"shanghu")&&!isAjax())
		{
			 include_once("tpl/shanghu/top.htm");
		}
		
		include_once("tpl/".$file.".htm");
		
		if(strstr($file,"admin/")&&!isAjax())
		{
			 include_once("tpl/admin/foot.htm");
		}
		
		if(strstr($file,"shanghu")&&!isAjax())
		{
			 include_once("tpl/shanghu/foot.htm");
		}
		
		if($is_die)
			exit();
	}
	
	//输出调式信息
	function var_print($array,$is_die=1)
	{
		print("<pre>".print_r($array,true)."</pre>");
		$is_die?exit():"";
	}
	
	//是否是post请求
	function isPost()
	{	
		//error('asfasf');
		return (($_SERVER['REQUEST_METHOD'] == 'POST') && (empty($_SERVER['HTTP_REFERER']) || preg_replace("/https?:\/\/([^\:\/]+).*/i", "\\1", $_SERVER['HTTP_REFERER']) == preg_replace("/([^\:]+).*/", "\\1", $_SERVER['HTTP_HOST'])));
	}
	
	//是否是ajax请求
	function isAjax()
	{
		if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) ){if('xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])){return true;}}
		return false;
	}
	
	//做json的返回
	function msg($isok=0,$msg="",$url="")
	{
		//echo isAjax();
		if(isAjax())
		{
			$a=array("isOk"=>$isok,"msg"=>$msg,"url"=>$url);
			die(json_encode($a));
		}
		else
		{
			//var_print($_SERVER);
			//die($msg);
			$path=explode("-",$_SERVER["PATH_INFO"],2);
			//die("Location: ".$path[0]."-msg.html?msg=".urlencode($msg)."&url=".urlencode($url));
			if($path[0]!="www")
				$path[0]="";
			else
				$path[0].="-";
			go($path[0]."msg.html?msg=".urlencode($msg)."&url=".urlencode($url));
			//header("Location: /admin/index.php/".ACT."/".DOING);
		}
	}
	
	/*输出错误信息*/
	/*如果is_log=1,则将错误写入日志！*/
	function error($msg,$url="")
	{
		//die($msg);
		msg(0,$msg,$url);
	}
	
	function ok($msg,$url="")
	{
		msg(1,$msg,$url);
	}
	
	//创建json信息
	function jsonMsgCreate($id)
	{
		return $id?$msg."成功":$msg."失败！";
	}
	
	function gtime()
	{
		return time();
	}
// 说明：获取完整URL

function curPageURL()
{
    $pageURL = 'http';

    if ($_SERVER["HTTPS"] == "on")
    {
        $pageURL .= "s";
    }
    $pageURL .= "://";

    if ($_SERVER["SERVER_PORT"] != "80")
    {
        $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
    }
    else
    {
        $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
    }
    return $pageURL;
}
/**
 * 当前url 导航高亮
 */
function is_active($act){
    $url=$_SERVER["PATH_INFO"];
//    var_dump($url);
    if ($act=='/'){
        if (empty($url)||$url=='/index'){
            return 1;
        }
    }elseif(strstr($url, $act)){
        return 1;
    }
}

/**
 * 中文字符串截取
 * @param string $string 需要截取的字符串
 * @param string $length 截取长度
 * @param string $etc    字符超过的替换符号
 * @return mixed
 */
function truncate_cn($string,$length,$etc = ' ...'){
    $result = '';
    $string = html_entity_decode(trim(strip_tags($string)), ENT_QUOTES, 'utf-8');
    for($i = 0, $j = 0; $i < strlen($string); $i++){
        if($j >= $length){
            for($x = 0, $y = 0; $x < strlen($etc); $x++){
                if($number = strpos(str_pad(decbin(ord(substr($string, $i, 1))), 8, '0', STR_PAD_LEFT), '0')){
                    $x += $number - 1;
                    $y++;
                }else{
                    $y += 0.5;
                }
            }
            $length -= $y;
            break;
        }
        if($number = strpos(str_pad(decbin(ord(substr($string, $i, 1))), 8, '0', STR_PAD_LEFT), '0')){
            $i += $number - 1;
            $j++;
        }else{
            $j += 0.5;
        }
    }
    for($i = 0; (($i < strlen($string)) && ($length > 0)); $i++){
        if($number = strpos(str_pad(decbin(ord(substr($string, $i, 1))), 8, '0', STR_PAD_LEFT), '0')){
            if($length < 1.0) break;
            $result .= substr($string, $i, $number);
            $length -= 1.0;
            $i += $number - 1;
        }else{
            $result .= substr($string, $i, 1);
            $length -= 0.5;
        }
    }
    $result = htmlentities($result, ENT_QUOTES, 'utf-8');
    if($i < strlen($string)) $result .= $etc;
    $result = str_replace('&nbsp;','',$result);
    $result = str_replace('　','',$result);
    return $result;
}
	
	function sub_str($str,$len,$start=0,$dot=1)
	{
		//return mb_substr($str,$start,$len)."...";
		if(mb_strlen($str,CFG_CHARSET)>$len)
		{
			$str=mb_substr($str,$start,$len,CFG_CHARSET).($dot?"...":"");
		}
		else
			$str;
		return $str;
	}
	
	/**
	 * 获得用户的真实IP地址
	 *
	 * @access  public
	 * @return  string
	 */
	function real_ip()
	{
		static $realip = NULL;
	
		if ($realip !== NULL)
		{
			return $realip;
		}
	
		if (isset($_SERVER))
		{
			if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
			{
				$arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
	
				/* 取X-Forwarded-For中第一个非unknown的有效IP字符串 */
				foreach ($arr AS $ip)
				{
					$ip = trim($ip);
	
					if ($ip != 'unknown')
					{
						$realip = $ip;
	
						break;
					}
				}
			}
			elseif (isset($_SERVER['HTTP_CLIENT_IP']))
			{
				$realip = $_SERVER['HTTP_CLIENT_IP'];
			}
			else
			{
				if (isset($_SERVER['REMOTE_ADDR']))
				{
					$realip = $_SERVER['REMOTE_ADDR'];
				}
				else
				{
					$realip = '0.0.0.0';
				}
			}
		}
		else
		{
			if (getenv('HTTP_X_FORWARDED_FOR'))
			{
				$realip = getenv('HTTP_X_FORWARDED_FOR');
			}
			elseif (getenv('HTTP_CLIENT_IP'))
			{
				$realip = getenv('HTTP_CLIENT_IP');
			}
			else
			{
				$realip = getenv('REMOTE_ADDR');
			}
		}
	
		preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
		$realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';
	
		return $realip;
	}
	
	//获取时间截
	function getMicTime(){
        $mictime=microtime();//获取时间戳和微秒数
        list($usec,$sec)=explode(" ",$mictime);//把微秒数分割成数组并转换成变量处理
        return (float)$usec+(float)$sec;//把转换后的数据强制用浮点点来处理
    }
	
	//获得,post请求的数据，如果为空，返回default默认值
	function in_post($key,$type="string",$default="")
	{
		$r=isset($_POST[$key])?$_POST[$key]:null;
		
		if(($r==null)&&($default!=""))
			return $default;
			
		return filter($r,$type);
	}
	
	//获得get请求的数据，如果为空，返回default默认值
	function in_get($key,$type="string",$default="")
	{
		$r=isset($_GET[$key])?$_GET[$key]:null;
		
		if(($r==null)&&($default!=""))
			return $default;
			
		return filter($r,$type);
	}
	
	//获得提交的参数
	function in_request($in,$type="string",$default="")
	{
		$r=in_get($in,$type,$default)?in_get($in,$type,$default):in_post($in,$type,$default);
		return $r;
	}
	
	//对输入的字符串进行过滤
	function filter($str,$type="string")
	{
		if(is_array($str))
		{
			$resultStr = array();
			foreach($str as $key => $val)
			{
				$key = filter($key, $type);
				$val = filter($val, $type);
				$resultStr[$key] = $val;
			}
			return $resultStr;
		}
		else
		{
			switch($type)
			{
				case "int":
					return intval($str);
					break;

				case "float":
					return floatval($str);
					break;

				case "text":
					return filter_text($str,$len);
					break;

				case "bool":
					return (bool)$str;
					break;

				case "url":
					return filter_clearUrl($str);
					break;

				case "filename":
					return filter_fileName($str);
					break;

				default:
					return filter_str($str);
					break;
			}
		}
	}
	
	//过漏网址
	function filter_clearUrl($url)
	{
		return str_replace(array('\'','"','&#',"\\","<",">"),'',$url);
	}
	
	//过漏文件地址
	function filter_fileName($string)
	{
		return str_replace(array('./','../','..'),'',$string);
	}
	
	//处理字符串
	function filter_str($str)
	{
		$str = trim($str);
		$str = htmlspecialchars($str,ENT_NOQUOTES);
		$str = str_replace(array("/*","*/"),"",$str);
		//error($str);
		return add_slashes($str);
	}
	
	//处理text输入
	function filter_text($str)
	{
		require_once(ROOT_PATH."/inc/htmlpurifier/HTMLPurifier.standalone.php");
		$cache_dir=IWeb::$app->getRuntimePath()."htmlpurifier/";

		if(!file_exists($cache_dir))
		{
			IFile::mkdir($cache_dir);
		}
		$config = HTMLPurifier_Config::createDefault();

		//配置 允许flash
		$config->set('HTML.SafeEmbed',true);
		$config->set('HTML.SafeObject',true);
		$config->set('HTML.SafeIframe',true);
		$config->set('Output.FlashCompat',true);
		$config->set('HTML.SafeEmbed',true);

		//配置 缓存目录
		//$config->set('Cache.SerializerPath',$cache_dir); //设置cache目录

		//允许<a>的target属性
		$def = $config->getHTMLDefinition(true);
		$def->addAttribute('a', 'target', 'Enum#_blank,_self,_target,_top');

		//过略掉所有<script>，<i?frame>标签的on事件,css的js-expression、import等js行为，a的js-href
		$purifier = new HTMLPurifier($config);
		return addslashes($purifier->purify($str));
	}
	
	//添加魔法转义斜杠
	function add_slashes($str)
	{
		if($str&&is_array($str))
		{
			$resultStr = array();
			foreach($str as $key => $val)
			{
				$resultStr[$key] = add_slashes($val);
			}
			return $resultStr;
		}
		else
		{
			return addslashes($str);
		}
	}
	
	//
	function getMicroTime()
	{
		$time=explode(" ",microtime(true));
		return ((float)$time[0]+(float)$time[1]);
	}
	
	//删除某个地址项
	function url_del_arg($url,$name)
	{
		if(!strstr($url,"?"))
			return $url;
		$t=explode("?",$url);
		$urls=$t;
		$url=$t[1];
		$ps=explode("&",$url);
		$r=array();
		foreach($ps as $k=>$v)
		{
			$t=explode("=",$v);
			if($t[0]==$name)
				continue;
			$r[$t[0]]=$t[1];
		}
		return $urls[0]."?".http_build_query($r);
	}
	
	//获得翻页的网址
	function get_page_url()
	{
		$url=$_SERVER['REQUEST_URI'];
		return url_del_arg($url,"page");
	}
	
	
	//获得验证码
	function getVerifyCode($len)
	{
		//error(strtoupper(substr(md5(uniqid(mt_rand())),0,$len)));
		return strtoupper(substr(md5(uniqid(mt_rand())),0,$len));
	}
	
	//操作消息返回
	function opMsg($isok,$url="")
	{
		msg($isok,$isok?"操作成功！":"操作失败！",$url);
	}
	
	//做远程post提交
	function curlPost($url,$data,&$r)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$r = curl_exec($ch);
		//$r="[ ".$r."] ";
		$r=json_decode($r,true);
		//error($r["errcode"]);
		//error($r['type'].'asdf');
		if (curl_errno($ch)) {
			$isOk=false;
			$msg="发送请求失败！";
		}
		else
		{
			$isOk=true;
			$msg="创建菜单成功！";
		}
		curl_close($ch);
		return $r["errcode"]==0?true:false;
	}
	
	//分页
	function pages()
{
    $page=in_get("page");
    if (empty($page))
    {
        $page = 1;
    }

    $url=$url?$url:get_page_url();
    //die($url);
    //echo $count;
    if(PAGE_COUNT)
        $pages=ceil(PAGE_COUNT/PAGE_SIZE);
    else
        return;

    $count=PAGE_COUNT;
    $number=PAGE_SIZE;

    if ($pages>1)
    {
        $str 		= $count."，每页".$number."条，分页：";

        $min = min($pages, $page + 5);
        $min=$min<1?1:$min;
        for ($i = $page-3; $i <= $min ; $i++)
        {
            if ($i < 1)
            {
                continue;
            }

            $str .= "<a href='".$url.(strstr($url,"?")?"&":"?")."page=".$i."'";
            $str .= $page == $i ? " style='color:#f30; font-size:16px; font-weight:bold; border:0px; ' " : '';
            $str .= ">$i</a>";
        }
    }
    else
    {
        $str = '共有：'.$count."条记录";
    }

    return $str;
}
function index_pages()
{
    $page=in_get("page");
    if (empty($page))
    {
        $page = 1;
    }

    $url=$url?$url:get_page_url();
    //die($url);
    //echo $count;
    if(PAGE_COUNT)
        $pages=ceil(PAGE_COUNT/PAGE_SIZE);
    else
        return;

    $count=PAGE_COUNT;
    $number=PAGE_SIZE;

    if ($pages>1)
    {
        $str 		= '<div class="mb-pageBk clearfix"><div class="mb-pageBkRt">';


        $min = min($pages, $page + 5);
        $min=$min<1?1:$min;
        if ($page>2||$page==2){
            $str .="<span class='prev'><a href='".$url.(strstr($url,"?")?"&":"?")."page=".($page-1)."'></a></span>";
            $str .='<ul>';
        }
        for ($i = $page-3; $i <= $min ; $i++)
        {
            if ($i < 1)
            {
                continue;
            }
            $str .='<li class="lia ';
            $str .= $page == $i ? ' current"' : '"';
            $str .= "><a href='".$url.(strstr($url,"?")?"&":"?")."page=".$i."'";
            $str .= ">$i</a></li>";
        }
        $str .='</ul>';
        if ($page<$pages){
            $str .="<span class='next'><a href='".$url.(strstr($url,"?")?"&":"?")."page=".($page+1)."'></a></span>";
        }
        $str .='</div></div>';
    }
    else
    {

    }

    return $str;
}
	
	//解析sersilize
	function stripslashes_deep($value)
	{
		$value = is_array($value) ?
					array_map('stripslashes_deep', $value) :
				stripslashes($value);
	
		return $value;
	}
	
	//做反解析
	function isUnserialize($memo)
	{
		return stripslashes_deep(unserialize($memo));
	}
	
	
	//计算两个经纬度坐标之间的距离
	function geo_distance($s, $e) {
	 //earth's mean radius in KM
	 $r = 6378.137;
	 $s[0] = deg2rad($s[0]);
	 $s[1] = deg2rad($s[1]);
	 $e[0] = deg2rad($e[0]);
	 $e[1] = deg2rad($e[1]);
	 $d0 = abs($s[0] - $e[0]);
	 $d1 = abs($s[1] - $e[1]);
	 $p = pow(sin($d0/2), 2) + cos($s[0]) * cos($e[0]) * pow(sin($d1/2), 2);
	 $ds = $r * 2 * asin(sqrt($p));
	 return $ds;
	}
	
	function cmp($a, $b)
	{
		if ($a["jl"] == $b["jl"]) {
			return 0;
		}
		return ($a["jl"] < $b["jl"]) ? -1 : 1;
	}
	
	//权限判断
	function is_priv($mod,$act="")
	{
		if(isset($_SESSION["admin_info"])&&($_SESSION["admin_info"]["privs"]=="privs_all"))
			return true;
		if($act!="")
			return isset($_SESSION["admin_info"]["privs"][$mod][$act]);
		else
			return isset($_SESSION["admin_info"]["privs"][$mod]);
		//return $act?isset($_SESSION["admin_info"]["privs"][$mod][$act]):$_SESSION["admin_info"]["privs"][$mod];
	}
	
	//加logo
	function addLogo($qrurl,$logo)
    {
		//die(ROOT_PATH.$logo);
		$QR = imagecreatefrompng($qrurl);
    	if($logo !== FALSE)
		{
			//die($logo);
			$logo = imagecreatefromstring(file_get_contents(ROOT_PATH.$logo));
			 
			$QR_width = imagesx($QR);
			$QR_height = imagesy($QR);
			 
			$logo_width = imagesx($logo);
			$logo_height = imagesy($logo);
			 
			$logo_qr_width = $QR_width / 5;
			$scale = $logo_width / $logo_qr_width;
			$logo_qr_height = $logo_height / $scale;
			$from_width = ($QR_width - $logo_qr_width) / 2;
			 
			imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
		}
		//header('Content-type: image/png');
		imagepng($QR,$qrurl);
		imagedestroy($QR);
    	return false;
    }
	
	/**
	 * @param
	 * @return array
	 * @brief 无限极分类递归函数
	 */
	function sort_pid_data($catArray, $id = 0 , $prefix = '')
	{
		static $formatCat = array();
		static $floor     = 0;

		foreach($catArray as $key => $val)
		{
			if($val['pid'] == $id)
			{
				//$str         = self::nstr($prefix,$floor);
				$val[$prefix.'_name'] = $val[$prefix.'_name'];

				$val['floor'] = $floor;
				$val['pid']	  =	$val['pid'];
				$formatCat[]  = $val;

				unset($catArray[$key]);

				$floor++;
				sort_pid_data($catArray, $val[$prefix.'_id'] ,$prefix);
				$floor--;
			}
		}
		return $formatCat;
	}
	
?>
