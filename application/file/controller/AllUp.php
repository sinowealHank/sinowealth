<?php
namespace app\file\controller;
use think\Cache;
use think\Db;
use app\index\controller\Admin;

class AllUp extends OneUp {
	//拉取主页面
	public function index(){
		$this->assign('up_sour', 'yes');
		$vip=db()->query("select file_id from sw_file_vip_and_files where vip_use='".get_user_id()."'");
		$file_id='';
		foreach ($vip as $v){
			$file_id[]=$v['file_id'];
		}
		$this->assign('vip', json_encode($file_id));
		$this->assign('meta_title', '批量上传');
		return $this->fetch('all_up/index');
	}
	//添加新文件夹
	public function fenlei_add(){
		$this->fenlei_add_all('All_up/up');
	}
	//目录ajax
	public function menu_ajax(){
		if($_SESSION['u_i']['user_gh']=='a01'){
			echo json_encode($this->menu_ajax_all("",'All_up/up'));
		}else{
			echo json_encode($this->menu_ajax_all("where id = '".$_SESSION['u_i']['dep_id']."'",'All_up/up'));
		}
	}
	//批量上传拉取
	public function up(){
		$id=$_GET['id'];
		$this_sta=array(array('qx'=>'','down'=>'1','import_text'=>'','bb'=>'','file_sta'=>'','die_time'=>''));
		//拉取用户
		$data3[0]=$this->he($this_sta);
		//拉取目录
		$ccc=$this->up_menu();
		//拉取第一页
		$data3[1]=$this->zhuti($this_sta,'','',$ccc);
	
		$this->assign('list3',$data3[1]);
		$this->assign('list2',$data3[0]);
		
		$this->assign('only_identification_probably',md5(time().$this->randCode('20','-1')));
		
		return $this->fetch('up/all_up');
	}
}