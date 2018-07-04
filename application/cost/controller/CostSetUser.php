<?php
namespace app\cost\controller;

use think\Controller;
use app\index\controller\Admin;
use think\Db;
class CostSetUser extends Admin{
	
	public function index(){
		$data=$this->param;
		$page_info=array();
		//列表过滤器，生成查询Map对象
		$map = $this->_search ('cost_user_type');		
		
		if (method_exists ( $this, '_filter' )) {
			$this->_filter ( $map );
		}
		$page_info['job_nb'] = '';
		$page_info['user_name'] = '';
		if(isset($_POST['job_nb'])){
			$mop['job_nb'] = $_POST['job_nb'];
			$page_info['job_nb'] = $_POST['job_nb'];
		}
		if(isset($_POST['user_name'])){
			$mop['user_name'] = $_POST['user_name'];
			$page_info['user_name'] = $_POST['user_name'];
		}
		$name= "cost_user_type";
		$model = db($name);
		if (! empty ( $model )) {
			$page_info['list']=$this->_list($model,$map,'id',false);
		}
		
		$page_info['page']=$page_info['list']->render();
		
		
		$page_info['row_total']=$page_info['list']->total();

		$this->assign('page_info',$page_info);
		return $this->fetch();
		
	}
	//添加页面弹出
	function add(){
		return $this->fetch();
	}
	//添加页面弹出
	function manager(){
		$lines = db('cost_config')->where('name','line')->column('content');
		$page_info['line'] = cost_string_to_array($lines['0']);
		$this->assign('page_info',$page_info);
		return $this->fetch();
	}
	//用户添加
	function userAdd(){
		$data = $_POST;
		$job_nb = db('cost_user_type')->where('job_nb',$data['job_nb'])->find();
		if($job_nb){
			echo setServerBackJson(0,'此用户已经存在!');exit;
		}
		$res = db('sys_user')->where('user_gh',$data['job_nb'])->find();
		if($res == false){
			echo setServerBackJson(0,'系统中无此工号!');exit;
		}
		$list['job_nb'] = $data['job_nb'];
		if($data['power_2'] == '1'){
			$list['power'] = $data['power_1'];
		}else{
			$list['power'] = $data['power_1']+1;
		}
		if($data['power_1'] == '9'){
			$list['power'] = '9';
		}
		$list['user_id'] = $res['id'];
		$list['user_name'] = $res['nickname'];
		
		
		$res = db('cost_user_type')->insert($list);
		echo setServerBackJson(1,'添加成功!',1);
	}
	//用户添加
	function managerAdd(){
		$data = $_POST;
		$job_nb = db('cost_user_type')->where('job_nb',$data['job_nb'])->find();
		if($job_nb){
			echo setServerBackJson(0,'此用户已经存在!');exit;
		}
		$res = db('sys_user')->where('user_gh',$data['job_nb'])->find();
		if($res == false){
			echo setServerBackJson(0,'系统中无此工号!');exit;
		}
		$list['job_nb'] = $data['job_nb'];
		$list['show_line'] = cost_array_to_string($data['line']);		
		$list['user_id'] = $res['id'];
		$list['user_name'] = $res['nickname'];
		$list['power'] = '11';
		$res = db('cost_user_type')->insert($list);
		echo setServerBackJson(1,'添加成功!',1);
	}
	//修改页面弹出
	function edit(){
		$id = $_GET['id'];
		
		$page_info['list'] = db('cost_user_type')->where('id',$id)->find();
		
		$this->assign('page_info',$page_info);
		return $this->fetch();
	}
	//修改用户
	function userEdit(){
		$data = $_POST;	
		if($data['power_2'] == '1'){
			$list['power'] = $data['power_1'];
		}else{
			$list['power'] = $data['power_1']+1;
		}
		if($data['power_1'] == '9'){
			$list['power'] = '9';
		}
		$res = db('cost_user_type')->where('id',$data['id'])->update($list);
		if($res){
			echo setServerBackJson(1,'修改成功!',1);exit;
		}else{
			echo setServerBackJson(0,'数据没有修改!');exit;
		}
		
	}
	//删除用户
	function delete(){
		$id = $_GET['id'];
		$page_info['list'] = db('cost_user_type')->where('id',$id)->delete();
		echo setServerBackJson(1,'删除成功!',1);exit;
	}

	//显示修改数据页面
	public function setcost(){
		return $this->fetch();
	}

	public function mssql(){
		$id = $_POST['id'];

		$id = trim($id);
		if(empty($id)){
			echo setServerBackJson(0,"cost_id不能为空");exit;
		}

	    $cost_data_id = Db::name('cost_cost')->where('id',$id)->value('id');

		cost_insert_sqlServer($cost_data_id);
		$sql = "select max(id) as id from dbo.pp_cost";
		$sql=auto_charset($sql);
		$max_id = get_mssql_info('mms',$sql);
		$result = Db::name('cost_cost')->where('id',$cost_data_id)->setField('o_id',$max_id['0']['id']);
		if($result){
			echo setServerBackJson(1,"修改成功");exit;
		}else{
			echo setServerBackJson(1,"修改失败");exit;
		}

	}
	 
}

