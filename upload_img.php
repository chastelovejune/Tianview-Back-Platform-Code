<?php
header('Content-Type: text/html; charset=UTF-8');
define("ROOT_PATH",dirname(__FILE__));
$act=isset($_REQUEST["act"])?$_REQUEST["act"]:"";
$id=isset($_REQUEST["id"])?$_REQUEST["id"]:"";
if($_POST)
{
	set_time_limit(10000);
	$ext=strtolower(strrchr($_FILES["file"]["name"],"."));	//获得推展名
	$max=$_POST["max_size"]?$_POST["max_size"]:2048;
	
	//判断文件大小
	$file_size=round($_FILES["file"]["size"]/1024,2);
	if($file_size>$max)
		die("<script>parent.upload_error('文件不能大于".$max."KB！');</script>");
						
	//扩展名是否合法
	$limit_ext=$_POST["limit_ext"]?$_POST["limit_ext"]:".jpg,.png,.gif";
	$limit_ext_array=explode(",",$limit_ext);
	if(!in_array($ext,$limit_ext_array))
		die("<script>alert('扩展名错误，只能上传扩展名为：".$limit_ext."的文件！');</script>");
						
	$file="data/imgs/".uniqid(mt_rand());	//上传临时保存路径
	$file.=$ext;
	if(move_uploaded_file($_FILES["file"]["tmp_name"],$file))
	{
		$file1="/".$file;
		$curr_upload_file=$file1;
		echo '<script src="/tpl/admin/js/jquery-1.10.1.js"></script>';
		echo "<script>$('#".$id."',parent.document).val('".$curr_upload_file."');";
		echo "$('#".$id."_pic',parent.document).attr('src','".$curr_upload_file."').show(); parent.CURR_DIALOG.close();";
		die("</script>");
	}
	else
	{
		die("<script>alert('文件上传失败！');</script>");
	}
}

?>
<iframe name="upload_iframe" id="upload_iframe" src="" width="0" height="0" scrolling="no"></iframe>
<img id="upload_pic" src="" style="cursor:move; max-width:650px; display:none;"/>
<form style="padding:5px; color:#999;" action="upload_img.php?id=<?=$id;?>" method="post" target="upload_iframe" class="upload_form" id="upload_form" enctype="multipart/form-data" name="upload_form">
<input name="file" type="file" id="file"  style="width:350px; height:25px;"/>
<input type="hidden" name="max_size" id="max_size" value="2048" />
<input type="hidden" name="limit_ext" id="limit_ext" value=".jpg,.png,.gif" />
<input type="submit" id="start_upload" name="start_upload" class="button_hong" value="上传" />
</form>
