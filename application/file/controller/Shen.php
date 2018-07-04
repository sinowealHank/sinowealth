<?php
namespace app\file\controller;
use think\Cache;
use think\Db;
use app\index\controller\Admin;

class Shen extends Up {
	public function index(){
		return $this->fetch('shen/index');
	}
	public function index_ajax(){
		return $this->index_n('shen');
	}
	//下载
	public function file_xz(){
		$this->xz('shen');
	}
	//单个审核
	public function file_shen(){
		$id=isset($_POST['id'])?$_POST['id']:'';
		if($id==''){echo json_encode(array(0,'缺少数据 '));exit;}
		$sh=$this->sql('shen');
		//判断是否有访问权限
		$this_sta=db()->query("SELECT * FROM sw_file_list where ".$sh." and id='".$id."' and file_dep_id=".$_SESSION['u_i']['dep_id']);
		if(!$this_sta){echo json_encode(array(0,'没有该项权限'));exit;}
		$this_sta=$this_sta[0];
		$page_info['ok']=isset($_POST['ok'])?$_POST['ok']:'';
		if($page_info['ok']=='0'){
			$page_info['sta']=isset($_POST['sta'])?$_POST['sta']:'';$no='不';
		}else{$page_info['sta']='';$no='';}
		if($this_sta['ok']==0 && $page_info['ok']!=='0'){echo json_encode(array(0,'不能再失败后再通过'));exit;}
		if($this_sta['ok']!==2 && $page_info['ok']=='2'){echo json_encode(array(0,'审核后不能在待审核'));exit;}
		$ok_sta=array('0','1','2');
		if(!in_array($page_info['ok'],$ok_sta)){echo json_encode(array(0,'未知状态'));exit;}
		if($this_sta['ok']==$page_info['ok'] && $page_info['ok']!=='0'){echo json_encode(array(0,'未检测到修改数据'));exit;}
		if($this_sta['sta']==$page_info['sta'] && $page_info['ok']=='0' && $this_sta['ok']==0){echo json_encode(array(0,'未检测到修改数据'));exit;}
		$page_info['log']=array('exp','CONCAT("'.'审核'.$no.'通过<br>'.'",log)');
		db('file_list')
		->where('id', $id)
		->update($page_info);
		 
		echo json_encode(array(1,'审核完成'));
		exit;
	}
	//批量审核
	public function ok_all() {
		$ok=isset($_GET['ok'])?$_GET['ok']:'';
		$all_id=isset($_POST['all_ok_id'])?$_POST['all_ok_id']:'';
		$all_id=trim($all_id,',');
		$text=isset($_POST['text'])?$_POST['text']:'';
		if($ok!=='2'){
			$all_new=explode(',',$all_id);
		}
		if($ok=='2'){
			/*$all_new=$all_id;
				$al='';
				foreach ($all_new as $a){
				$al=$al.','.$a;
				}
				$all_id=$al;*/
			echo json_encode(array(0,'不能改为待审核'));exit;
		}
		//$all_old=db()->query("select * from sw_file_list where id in (".$all_id.") and die_time>='".date("Y-m-d H:i:s")."'");
		$all_old=db()->query("select * from sw_file_list where id in (".$all_id.")");
		if($ok==0){}
		if($ok==2){}
		$name='';
		if($ok==1){
			foreach ($all_old as $keyy=>$o){
				foreach ($all_new as $key=>$n){
					if($o['id']==$n || $o['file_dep_id']!=$_SESSION['u_i']['dep_id']){
						if($o['ok']=='0' || $o['file_dep_id']!=$_SESSION['u_i']['dep_id']){
							unset($all_new[$key]);
							unset($all_old[$keyy]);
							$name=$name.','.$o['file_name'];
						}
					}
					if($o['file_dep_id']!=$_SESSION['u_i']['dep_id']){$hd='1';}
				}
			}
			$all_id='';
			foreach ($all_new as $a){
				$all_id=$all_id.','.$a;
			}
			$all_id=trim($all_id,',');
		}
		if($ok==1){
			$okk='1';$no='';
		}else{
			$okk='0';$no='不';
		}
		$sta='';
		if($ok==2){
			$sta=",sta='".$text."'";
		}
		db()->query("update sw_file_list set ok='".$okk."'".$sta." , log = concat('".'审核'.$no.'通过<br>'."',log) where id in (".$all_id.")");
		//db('files')->where('id', '('.$all_id.')')->update(array('ok'=>$okk));
		if(isset($hd)){$hd='<span style="color:red">检测到权限外内容</span>';}else{$hd='';}
		echo json_encode(array(1,'完成'.$hd));
	}
	//文件详情
	public function file_xx(){
		return $this->file_xx_all('shen');
	}
	//发表留言
	public function file_xx_up(){
		return $this->file_xx_up_all('shen');
	}
	
	public function add(){
		$this->add1('','shen');
	}
	//预览
	public function open(){
		$this->open_all('shen');
	}
}