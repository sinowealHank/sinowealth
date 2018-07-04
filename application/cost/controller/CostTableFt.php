<?php
namespace app\cost\controller;

use think\Controller;
use app\index\controller\Admin;

class CostTableFt extends Admin
{
	
	//costTable页面
	public function index(){		
		$page_info['costToId'] = input('costToId');

		session('costToId',$page_info['costToId']);
		
		$F_T_Out = db('cost_config')->where('name','F_T_Out')->column('content');
		$page_info['F_T_Out'] = cost_string_to_array($F_T_Out['0']);
		$this->assign('page_info',$page_info);
		return  $this->fetch('index');
	}
	//获取cost数据
	public function ftDate(){
		
		$sort = isset($_POST['sort']) ? $_POST['sort'] : 'id';
		$order = isset($_POST['order']) ? $_POST['order'] : 'desc';
		$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
		$rows = isset($_POST['rows']) ? intval($_POST['rows']) : 10;
		$search = $_POST;
		if(session('costToId')){
			$search['cost_id'] = session('costToId');
			session('costToId',null);
		}
	
		if(!empty($search['cost_id'])){
			$map['cost_id'] = trim($search['cost_id']);
		}
		if(!empty($search['test_time_notice'])){
			$map['test_time_notice'] = ['like','%'.trim($search['test_time_notice']).'%'];
		}
		if(!empty($search['prdno'])){
			$map['prdno'] = ['like','%'.trim($search['prdno']).'%'];
		}
		if(!empty($search['F_T_Out'])){
			if($search['F_T_Out'] != '1'){
				$map['F_T_Out'] = trim($search['F_T_Out']);
			}
		}
		$map['status'] = 1;
		$offset = ($page-1)*$rows;
		$qq =db('cost_ft')->where($map)->limit($offset,$rows)->order($sort,$order)->select();
		$qty= db('cost_ft')->where($map)->count();
		$json='{"total":'.$qty.',"rows":'.json_encode($qq).'}';
		echo $json;
	} 
	//检测新增ID
	function addTest(){
		$cost_id = $_POST['cost_id'];
		if(empty($cost_id)){
			echo setServerBackJson(0,'请输入关联ID!');exit;
		}
		$costRes = db('cost_cost')->where('id',$cost_id)->count();
		if($costRes == 0){
			echo setServerBackJson(0,'cost表中没有此ID!');exit;
		}
		echo 1;
	}
	//新增记录页面显示
	function add(){
		$page_info = array();
		$cost_id = $_GET['cost_id'];
		$cpRes = db('cost_ft')->where('cost_id',$cost_id)->order('id desc')->find();
		if($cpRes){
			$page_info['list'] = $cpRes;
		}else{
			$cpRes = db('cost_ft')->order('id desc')->find();
			foreach($cpRes as $k=>$v){
				$cpRes[$k] = '';
			}
			$cost = db('cost_cost')->where('id',$cost_id)->find();
			$cpRes['prdno'] = $cost['prdno'];
			$cpRes['F_T_Out'] = $cost['F_T_Out'];
			
			$page_info['list'] = $cpRes;
		}
		//下拉框ft场
		$F_T_Out = db('cost_config')->where('name','F_T_Out')->column('content');
		$page_info['F_T_Out'] = cost_string_to_array($F_T_Out['0']);
		//下拉计算公式
		$res = db('cost_config')->where('name','test_time')->column('content');
		$contents = explode(';',$res['0']);
		$formulas = array();
		foreach($contents as $v){
			$formula = explode('=',$v);
			$formulas[] = $formula['0'];
		}
		$page_info['formula'] =  array_filter($formulas);
		$page_info['cost_id'] = $cost_id;
		$this->assign('page_info',$page_info);
		return  $this->fetch();
	}
	//添加一条数据
	function addRow(){
		$data = $_POST;
		
		$data['status'] = 1;
		$data['is_new'] = 2;
		if(isset($_POST['yes'])){
			db('cost_ft')->where('cost_id',$_POST['cost_id'])->setField('is_new',0);
			$data['is_new'] = 1;
			unset($data['yes']);
			//改变cost表数据
			$update = array();
			$update['by25'] = $data['by25'];
			$update['F_T_Out'] = $data['F_T_Out'];
			$update['F_T_Tester'] = $data['F_T_Tester'];
			db('cost_cost')->where('id',$_POST['cost_id'])->update($update);
			//改变sqlserver表数据
			
		}
		db('cost_ft')->insertGetId($data);
		echo setServerBackJson(1,'新增成功!',1);exit;
	}
	//修改页面显示
	function edit(){
		$cpRes = db('cost_ft')->where('id',$_GET['id'])->find();
		$page_info['list'] = $cpRes;
		$F_T_Out = db('cost_config')->where('name','F_T_Out')->column('content');
		$page_info['F_T_Out'] = cost_string_to_array($F_T_Out['0']);
		//下拉计算公式
		$this->assign('page_info',$page_info);
		return  $this->fetch();
	}
	//修改数据
	function editRow(){
		$data = $_POST;
		$data['status'] = 1;
		$data['is_new'] = 2;
		
		unset($data['id']);
		if(isset($_POST['yes'])){
			db('cost_ft')->where('cost_id',$_POST['cost_id'])->setField('is_new',0);
			$data['is_new'] = 1;
			unset($data['yes']);
		}
	
		$res = db('cost_ft')->where('id',$_POST['id'])->update($data);
		if($res){
			if(isset($_POST['yes'])){
				$update = array();
				$update['by25'] = $data['by25'];
				$update['F_T_Out'] = $data['F_T_Out'];
				$update['F_T_Tester'] = $data['F_T_Tester'];
				db('cost_cost')->where('id',$_POST['cost_id'])->update($update);			
			}
			
			echo setServerBackJson(1,'修改成功!',1);exit;
		}else{
			echo setServerBackJson(0,'数据没有变化!');exit;
		}
		
	}
	
	//批量修改
	function editBySearch(){
		$search = $_GET;
		if(!empty($search['cost_id'])){
			$map['cost_id'] = trim($search['cost_id']);
		}
		if(!empty($search['test_time_notice'])){
			$map['test_time_notice'] = ['like','%'.trim($search['test_time_notice']).'%'];
		}
		if(!empty($search['prdno'])){
			$map['prdno'] = ['like','%'.trim($search['prdno']).'%'];
		}
		if(!empty($search['F_T_Out'])){
			if($search['F_T_Out'] != '1'){
				$map['F_T_Out'] = trim($search['F_T_Out']);
			}
		}
		$map['is_new'] = 1;
		$qty= db('cost_ft')->where($map)->count();
		$page_info['count'] = $qty;
		$this->assign('page_info',$page_info);
		return  $this->fetch();
	}
	//执行批量修改    只可以修改h_r字段，如果修改其他字段需修改此段代码
	function doEdits(){
		$search = $_POST;
		if(!empty($search['cost_id'])){
			$map['cost_id'] = trim($search['cost_id']);
		}
		if(!empty($search['test_time_notice'])){
			$map['test_time_notice'] = ['like','%'.trim($search['test_time_notice']).'%'];
		}
		if(!empty($search['prdno'])){
			$map['prdno'] = ['like','%'.trim($search['prdno']).'%'];
		}
		if(!empty($search['F_T_Out'])){
			if($search['F_T_Out'] != '1'){
				$map['F_T_Out'] = trim($search['F_T_Out']);
			}
		}
		$map['is_new'] = 1;
		$res= db('cost_ft')->where($map)->select();
		//ft_price　= h_r/3600*F_T_TWEO
		$update = array();
		$update['h_r'] = $_POST['content']; 
		foreach($res as $v){			
			$update['ft_price'] = floatval($v['F_T_TWEO'])*floatval($_POST['content'])/3600;
			db('cost_ft')->where('id',$v['id'])->setField($update);
		}
		echo setServerBackJson(1,'修改成功!',1);exit;
	}
}