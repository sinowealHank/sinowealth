<?php
namespace app\sys\controller;

use think\Controller;
use app\index\controller\Admin;
use think\Db;

//用户首页详情
class Moreemail extends Admin{
	public function index() {
		$user=db()->query("select user_gh,nickname,email,ext_tel from sw_sys_user");
		$this->assign('user', $user);
		return $this->fetch();
	}
	public function send() {
		$email=isset($_POST['email'])?$_POST['email']:'';
		$title=isset($_POST['title'])?$_POST['title']:'';
		$html=isset($_POST['html'])?$_POST['html']:'';
		$name=isset($_POST['name'])?$_POST['name']:'';
		foreach ($email as $e){
			send_email($e, $title, $html);
		}
		send_email(get_cache_data('user_info',get_user_id(),'email'), $title, $name."<br>".$html);
		echo json_encode(array(1,'成功'));
	}
}