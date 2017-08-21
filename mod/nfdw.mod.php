<?php
/*
 * chastelove
*@南方电网模型类
*/
class NFDW extends COMMON_ADMIN{
	function __construct($do="index",$paras="")
	{
		parent::__construct();
		if(!method_exists(get_class($this),$do))
			die("request error!");
		if(!defined("METHOD"))
			define("METHOD",$do);
		call_user_func_array(array(get_class($this),$do),$paras);
	}
	//角色增加和修改
	function _admin_role_add($id){
		//取出所有权限准备分配

		$m=new model("admin_role_privs");
		$sql="select admin_role_privs_id,privs_name,`group`,privs_group_name
 from admin_role_privs LEFT  join privs_group on admin_role_privs.group=privs_group.privs_group_id";
		$result= $m->db->query($sql);

		for($i=0;$i<count($result);$i++){

			$arr[$result[$i]['privs_group_name']][$i]['admin_role_privs_id']=$result[$i]['admin_role_privs_id'];
			$arr[$result[$i]['privs_group_name']][$i]['privs_name']=$result[$i]['privs_name'];

		}
		//var_dump($arr);

		/*for($i=0;$i<count($re);$i++){
			//r_dump($re[$i][privs_group_id]);
			$sql="select *from admin_role_privs where `group`= $re[$i][privs_group_id]";
           result=$m->db->query($sql);
		}*/
					if(isPost()){
						$data['admin_role_name']=$_POST['role_name'];
						$checkbox=$_POST['checkbox'];
						$model=$_POST['model'];
						$role_id=$_POST['id'];
						$role_id=trim($role_id,"/");
						$checkbox=array_filter($checkbox);
						$data['privs_id_string']=implode(',',$checkbox);
						$model=array_filter($model);
						$data['model']=implode(',',$model);
						$data['mod_key']="admin_role";//此处要加入表的的标识
						$data['mod_id']="2";
						$data['add_time']=time();
						$data['last_update_time']=time();
						$admin_role=new model('admin_role');

						//判断有没有重复的名字
						$re=$admin_role->getOne("admin_role_name='".$data['admin_role_name']."'");
						if(empty($data['admin_role_name'])){
							msg(0,"用户名不能为空");
							return;
						}
						//role_id默认会为0。0则是增加操作
						if (!empty($re)){
							if($role_id==0){
								msg(0, "已经存在此角色名");
								return;
								}
							}
						if($role_id==0) {
							$re = $admin_role->add($data);


						}
						if($role_id!==0){
							$re=$admin_role->update($data,"admin_role_id=".$role_id);
							//var_dump($data);


						}
						if($re){
							opMsg($re);
						}
		}
		if($id!==null) {
			$admin_role=new model('admin_role');
			$re_updata=$admin_role->getOne("admin_role_id=".$id,"admin_role_name,privs_id_string");

			$privs_id_string=explode(",",$re_updata['privs_id_string']);
			$privs_id_string=json_encode($privs_id_string);
			//var_dump($privs_id_string);
		}
		tpl("admin/product/admin_product_admin_role_add",get_defined_vars());
	}
	//管理员列表显示
	/*
	function  _admin_list(){
		/*$m=new model('admin');
		$sql="select admin_name,role_id,last_login_time,description,admin_role_name
    from admin LEFT join admin_role on admin.role_id=admin_role.admin_role_id";
		$re=$m->db->query($sql);
		//$re=$m->query(false,"admin_name,role_id,last_login_time,description");
		tpl("admin/product/admin_product_admin_list",get_defined_vars());

	}*/

}