<?php
namespace app\file\controller;
use think\Cache;
use think\Db;
use app\index\controller\Admin;

class VipFileUp extends VipFile {
	public function index(){
		return $this->fetch('vip_file_up/index');
	}
	public function vip_file_up_ajax(){
		$this->vip_file_ajax('xiao');
	}
	//异步拉取vip文件配置
	public function vip_change_ajax(){
		$id=isset($_POST['id'])?$_POST['id']:'';
		if($id==''){echo json_encode(array('0','缺少东西'));exit;}
		$vip_files=db()->query("SELECT * FROM sw_file_vip_files left join sw_file_vip_con on sw_file_vip_files.id=sw_file_vip_con.vip_id where sw_file_vip_files.id='".$id."' and sw_file_vip_files.use_ok='1' and  sw_file_vip_files.user_id like '%(".get_user_id().")%' and  sw_file_vip_files.dep_id='".$_SESSION['u_i']['dep_id']."'");
		if(!$vip_files){echo json_encode(array('0','无权限'));exit;}
		$_POST['id2']=$_POST['id'];
		$_POST['id']='';
		if($vip_files[0]['fenlei']==''){
			$_GET['id']='0';
		}else{
			$_GET['id']=$vip_files[0]['fenlei'];
		}
		$vip_files[0]['fenlei']=$vip_files[0]['qx'];
		$vip_files[0]['qx']=$vip_files[0]['user_id'];
		$div=$this->change_ajax_div('','<input wtq_is="more_id" name="id" style="display:none" value="'.$id.'" >',$vip_files);
		echo json_encode($div);
	}
	//修改文件配置
	public function vip_change_ok(){
		$qx=isset($_GET['qx_id'])?$_GET['qx_id']:'';
		$id=isset($_POST['id'])?$_POST['id']:'0';
	
		if($id==''){echo json_encode(array('0','缺少数据'));exit;}
		$vip_files=db()->query("SELECT * FROM sw_file_vip_files left join sw_file_vip_con on sw_file_vip_files.id=sw_file_vip_con.vip_id where sw_file_vip_files.id='".$id."' and sw_file_vip_files.use_ok='1' and  sw_file_vip_files.user_id like '%(".get_user_id().")%' and  sw_file_vip_files.dep_id='".$_SESSION['u_i']['dep_id']."'");
		if(!$vip_files){echo json_encode(array('0','没有权限'));exit;}
	
		$down=isset($_POST['xz'])?$_POST['xz']:'0';
		$fenlei=isset($_POST['ml'])?$_POST['ml']:'';
		if($fenlei=='' && $vip_files[0]['fenlei']==''){echo json_encode(array('0','请选择目录'));exit;}
		$import_text=isset($_POST['gj'])?$_POST['gj']:'';
		$bb=isset($_POST['bb'])?$_POST['bb']:'';
		$file_sta=isset($_POST['sta'])?$_POST['sta']:'';
		$die_time=isset($_POST['old_time'])?$_POST['old_time']:'';
		if($die_time==''){$die_time='9999-05-19 16:00:58';}
		$page_info=array('qx'=>$qx,'down'=>$down,'fenlei'=>$fenlei,'import_text'=>$import_text,'bb'=>$bb,'file_sta'=>$file_sta,'die_time'=>$die_time);
	
		$vip_con=db()->query("SELECT * FROM sw_file_vip_con where cid='".$vip_files[0]['cid']."' and vip_id='".$id."'");
		if($vip_con){
			db('file_vip_con')
			->where('cid', $vip_files[0]['cid'])
			->update($page_info);
		}else{
			$page_info['vip_id']=$id;
			db('file_vip_con')->insert($page_info);
		}
		echo json_encode(array('1','完成'));
	}
	//文件上传
	public function vip_files_up(){
		if(!$_FILES['file']['name']){$this->errorUpload('缺少文件');}
		$vip_files=db()->query("SELECT * FROM sw_file_vip_files left join sw_file_vip_con on sw_file_vip_files.id=sw_file_vip_con.vip_id where sw_file_vip_files.name='".$_FILES['file']['name']."' and sw_file_vip_files.use_ok='1' and  sw_file_vip_files.user_id like '%(".get_user_id().")%' and  sw_file_vip_files.dep_id='".$_SESSION['u_i']['dep_id']."'");
		if(!$vip_files){$this->errorUpload('无权限');}
		$page_info=array(
				'qx'=>$vip_files[0]['qx'],
				'down'=>$vip_files[0]['down'],
				'fenlei'=>$vip_files[0]['fenlei'],
				'import_text'=>$vip_files[0]['import_text'],
				'bb'=>$vip_files[0]['bb'],
				'file_sta'=>$vip_files[0]['file_sta'],
				'die_time'=>$vip_files[0]['die_time'],
		);
		if($vip_files[0]['fenlei']==''){$this->errorUpload('未设置');}
		if($vip_files[0]['vip_file_new_id']==''){
			$page_info['bb_id']='0';
		}else{
			$files=db()->query("SELECT * FROM sw_file_list where id='".$vip_files[0]['vip_file_new_id']."'");
			if($files[0]['bb_id']=='0'){
				$page_info['bb_id']=$files[0]['id'];
			}else{
				$page_info['bb_id']=$files[0]['bb_id'];
			}
			db('file_list')
			->where('id', $files[0]['id'])
			->update(['bb_new'=>0]);
		}
		$page_info['more_id']='0';
		$page_info['ok']='1';
		$user_path=\think\Config::get('url').'/'.$page_info['fenlei'];
		$page_info=$this->file_x($page_info,$user_path,'vip');
		$page_info['url']=\think\Config::get('url').'/'.$page_info['fenlei'].'/';
		$page_info['log']=get_user_nickname().'_'.date("Y-m-d H:i:s").'_上传了'.$_FILES['file']['name'];
		$user_id=db('file_list')->insertGetId($page_info);
	
		db('file_vip_con')
		->where('cid', $vip_files[0]['cid'])
		->setInc('up_cs');
		db('file_vip_con')
		->where('cid', $vip_files[0]['cid'])
		->update(['vip_file_new_id'=>$user_id]);
	
		$data=array('vip_id'=>$vip_files[0]['id'],'file_id'=>$user_id,'time'=>date("Y-m-d H:i:s"),'vip_use'=>get_user_id());
		db('file_vip_and_files')->insert($data);
	}
}