<?php
namespace app\file\controller;
use think\Cache;
use think\Db;
use app\index\controller\Admin;

class VipFile extends Up {
	public function index(){
		return $this->fetch('vip_file/index');
	}
	//异步拉取文件log
	public function vip_file_log_ajax(){
		$id=isset($_POST['id'])?$_POST['id']:'';
		if($id==''){echo json_encode(array('0','缺少东西'));exit;}
		$vip_files=db()->query("SELECT id,log FROM sw_file_vip_files where dep_id='".$_SESSION['u_i']['dep_id']."' and id='".$id."'");
		if(!$vip_files){echo json_encode(array('0','无权限'));exit;}
		$vip_and_files=db()->query("SELECT * FROM sw_file_vip_and_files where vip_id='".$vip_files[0]['id']."'");
		$div='';
		foreach ($vip_and_files as $v){
			$div=$div.get_user_nickname($v['vip_use']).'_'.$v['time'].'进行了上传<br>';
		}
		echo json_encode(array($div.$vip_files[0]['log']));
	}
	//vip文件上传打开关闭
	public function vip_file_open_close(){
		$id=isset($_POST['id'])?$_POST['id']:'';
		if($id==''){echo json_encode(array('0','缺少东西'));exit;}
		$use_ok=isset($_POST['use_ok'])?$_POST['use_ok']:'';
		$vip_files=db()->query("SELECT use_ok,log FROM sw_file_vip_files where dep_id='".$_SESSION['u_i']['dep_id']."' and id='".$id."'");
		if(!$vip_files){echo json_encode(array('0','无权限'));exit;}
		if($use_ok==0){$ok="关闭";$a=1;}else if($use_ok==1){$ok="开启";$a=0;}
		if($vip_files[0]['use_ok']==$use_ok){
			echo json_encode(array('1','已'.$ok));exit;
		}
		if($vip_files[0]['log']){
			$page_info['log']=array('exp','CONCAT("'.get_user_nickname().$ok.'<br>",log)');
		}else{
			$page_info['log']=get_user_nickname().$ok.'<br>';
		}
		$page_info['use_ok']=$use_ok;
		db('file_vip_files')
		->where('id', $id)
		->update($page_info);
		echo json_encode(array('1','已'.$ok,$a));
	}
	//vip文件异步拉取(传值拉取上传页面)
	public function vip_file_ajax($a=''){
		$zt=addslashes(isset($_GET['zt'])?$_GET['zt']:'');
		$sql_jq=addslashes(isset($_GET['sql_jq'])?$_GET['sql_jq']:'');
		$name=addslashes(isset($_GET['name'])?$_GET['name']:'');
		$page=addslashes(isset($_GET['page'])?$_GET['page']:'1');
		//排序方式
		$ii=addslashes(isset($_GET['ii'])?$_GET['ii']:'id desc');
		//$arr_ii=array();
		if($ii=='id' || $ii=='id desc' || $ii=='name' || $ii=='name desc' || $ii=='creat_time' || $ii=='creat_time desc' || $ii=='use_ok' || $ii=='use_ok desc'){}else{$ii='id';}
		if(isset($_GET['a'])?$_GET['a']:''==1){$ii=$ii.' desc';}
		//查询
		$name_sql='';$zt_sql='';
		if($name){
			if($sql_jq=='yes'){
				$name_sql=" and (name = '".$name."' or id = '".$name."')";
			}else{
				$name_sql=" and (name like '%".$name."%')";
			}
		}
		if($zt!=''){$zt_sql=" and use_ok in(".$zt.")";}
	
		//获取分页部分信息
		if($a=='xiao'){
			$vip_files_s=db()->query("SELECT count(*) FROM sw_file_vip_files left join sw_file_vip_con on sw_file_vip_files.id=sw_file_vip_con.vip_id where sw_file_vip_files.use_ok='1' and  sw_file_vip_files.user_id like '%(".get_user_id().")%' and sw_file_vip_files.dep_id='".$_SESSION['u_i']['dep_id']."' ".$name_sql."");
		}else{
			$vip_files_s=db()->query("SELECT count(*) FROM sw_file_vip_files where dep_id='".$_SESSION['u_i']['dep_id']."' ".$name_sql." ".$zt_sql."");
		}
		$all_shumu=$vip_files_s[0]['count(*)'];
		$page_size=\think\Config::get('page_size');
		//获取总分页数
		$zy = $all_shumu / $page_size;
		$zy = ceil($zy);
		//判断页码正确性
		if($page>$zy){$page=$zy;}
		if($page<=0){$page=1;}
		$star=$page*$page_size-$page_size;
	
		//sql
		if($a=='xiao'){
			$vip_files=db()->query("SELECT * FROM sw_file_vip_files left join sw_file_vip_con on sw_file_vip_files.id=sw_file_vip_con.vip_id where sw_file_vip_files.use_ok='1' and  sw_file_vip_files.user_id like '%(".get_user_id().")%' and  sw_file_vip_files.dep_id='".$_SESSION['u_i']['dep_id']."' ".$name_sql."  order by sw_file_vip_files.".$ii." limit ".$star.','.$page_size);
			//select * from student left join course on student.ID=course.ID
		}else{
			$vip_files=db()->query("SELECT * FROM sw_file_vip_files where dep_id='".$_SESSION['u_i']['dep_id']."' ".$name_sql." ".$zt_sql." order by ".$ii." limit ".$star.','.$page_size);
		}
	
	
		//分页
		$all_shumu=$vip_files_s[0]['count(*)'];
		$file=ajax_page($vip_files,'&name='.$name.'&sql_jq='.$sql_jq.'&ii='.$ii,$page,$vip_files_s[0]['count(*)']);
		//数据
		if(isset($_GET['a'])?$_GET['a']:''==1){
			$file[]='icon-level-up';
		}else{$file[]='icon-level-down';}
		echo json_encode($file);
	}
	//添加新的vip文件
	public function add_vip_files(){
		$name=isset($_POST['name'])?$_POST['name']:'';
		$id=isset($_POST['id'])?$_POST['id']:'';
		if($id==''){
			if($name==''){echo json_encode(array('0','请输入名称'));exit;}
		}else{
			if($name!=''){echo json_encode(array('0','禁止修改名称'));exit;}
		}
		$user_id=isset($_POST['user_id'])?$_POST['user_id']:'';
		$vip_files=db()->query("SELECT use_ok,log FROM sw_file_vip_files where dep_id='".$_SESSION['u_i']['dep_id']."' and name='".$name."'");
		if($vip_files){echo json_encode(array('0','该名称已存在'));exit;}
	
		if($id){
			if($name){
				$data=array('dep_id'=>$_SESSION['u_i']['dep_id'],'name'=>$name,'user_id'=>$user_id,'use_ok'=>1,'creat_time'=>date("Y-m-d H:i:s"),'log'=>get_user_nickname().'添加<br>');
				db('file_vip_files')->insert($data);
			}else{
				$data=array('user_id'=>$user_id,'use_ok'=>1,'creat_time'=>date("Y-m-d H:i:s"),'log'=>array('exp','CONCAT("'.get_user_nickname().'修改<br>",log)'));
				db('file_vip_files')
				->where('id', $id)
				->update($data);
			}
		}else{
			$data=array('dep_id'=>$_SESSION['u_i']['dep_id'],'name'=>$name,'user_id'=>$user_id,'use_ok'=>1,'creat_time'=>date("Y-m-d H:i:s"),'log'=>get_user_nickname().'添加<br>');
			db('file_vip_files')->insert($data);
		}
		echo json_encode(array('1','完成'));
	}
	//权限选则
	public function vip_gr_qx(){
		$id=isset($_POST['id'])?$_POST['id']:'';
		if($id){
			$vip_files=db()->query("select * from sw_file_vip_files where dep_id='".$_SESSION['u_i']['dep_id']."' and id='".$id."'");
			$vip_files[0]['qx']=$vip_files[0]['user_id'];
		}else{
			$vip_files=array(array('qx'=>''));
		}
		$gr=$this->users($vip_files);
		echo json_encode($gr);
	}
}