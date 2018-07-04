<?php
namespace app\file\controller;
use think\Cache;
use think\Db;
use app\index\controller\Admin;

class Files extends Up {
	public function index(){
		$this->assign('meta_title', '文件');
		return $this->fetch('files/index');
	}
	//页面内容
	public function index_ajax(){
		$this->index_n();
	}
	//文件详情
	public function file_xx(){
		return $this->file_xx_all('file');
	}
	//文件下载
	public function file_xz(){
		$this->xz();
	}
	//文件预览
	public function open(){
		$this->open_all();
	}
	//发表留言
	public function file_xx_up(){
		return $this->file_xx_up_all('file');
	}
	//加一
	public function add(){
		$this->add1('','file');
	}
	//菜单ajax
	public function menu_ajax(){
		echo json_encode($this->menu_ajax_all('','','file'));
	}
}