<?php
namespace app\user\controller;

use think\Controller;
use app\index\controller\Admin;

class UserMsg extends Admin{
	public function index(){
		$msg=db()->query("select * from sw_sys_msg where user_id='".get_user_id()."' order by id desc");
		$this->assign('user_msg', json_encode($msg));
		$msg_f=db()->query("select type_flag from sw_sys_msg where user_id='".get_user_id()."' group by type_flag ");
		$select='';
		foreach ($msg_f as $m){
			$select=$select.'<option value="'.$m['type_flag'].'">'.Config('wtq_user_msg')[$m['type_flag']].'</option>';
		}
		$this->assign('type_flagg', $select);
		
		$this->assign('type_flag', Config('wtq_user_msg'));
		return $this->fetch();
	} 
	public function index_ajax(){
		$ii=isset($_GET['ii'])?$_GET['ii']:'id';
		$a=isset($_GET['a'])?$_GET['a']:'1';
		if($ii){$sql_paixu=' order by '.$ii;}
		if($a){$sql_paixu=$sql_paixu.' desc';}
		
		$type_flag=isset($_POST['type_flag'])?$_POST['type_flag']:'';
		//echo $type_flag;
		$view_flag=isset($_POST['view_flag'])?$_POST['view_flag']:'';
		//echo $view_flag;
		$sql='';
		if($type_flag){$sql=$sql." and type_flag like '%$type_flag%'";}
		if($view_flag==='0' || $view_flag=='1'){$sql=$sql." and view_flag='$view_flag'";}
		$msg=db()->query("select * from sw_sys_msg where user_id='".get_user_id()."' ".$sql.$sql_paixu);
		echo json_encode($msg);
	}
	//标记为已读
	public function msg_ok(){
		$id=isset($_POST['id'])?$_POST['id']:'';
		
		$msg=db()->query("select user_id from sw_sys_msg where id='$id'");
		
		if($msg[0]['user_id']!=get_user_id()){echo json_encode(array('0','该信息不属于你'));exit;}
		db('sys_msg')
			->where('id', $id)
			->update(['view_flag'=>1]);
		echo json_encode(array('1','完成'));
	}
	//信息详情拉取
	public function user_msg_detail(){
		$id=isset($_GET['id'])?$_GET['id']:'';
		$msg=db()->query("select * from sw_sys_msg where id='$id'");
		if($msg==''){
			exit('没有数据');
		}
		$this->assign('msg', $msg[0]);
		$this->assign('type_flag', Config('wtq_user_msg'));
		return $this->fetch();
	}
	
}