<?php
namespace app\cost\controller;

use think\Controller;
use think\Db;
use app\index\controller\Admin;

class CostTableCp extends Admin
{
	
	//costTable页面
	public function index(){
		$page_info['costToId'] = input('id');
		session('costToId',$page_info['costToId']);
		
		$CP_factory = db('cost_config')->where('name','CP_factory')->column('content');
		$page_info['CP_factory'] = cost_string_to_array($CP_factory['0']);



		$this->assign('page_info',$page_info);
		return  $this->fetch();
	}
	//获取cost数据
	public function cpDate(){	
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
			$map['cost_id'] = $search['cost_id'];
		}
		if(!empty($search['test_time_notice'])){
			$map['test_time_notice'] = ['like','%'.trim($search['test_time_notice']).'%'];
		}
		if(!empty($search['type'])){
			$map['type'] = ['like','%'.trim($search['type']).'%'];
		}
		if(!empty($search['CP_factory'])){
			if($search['CP_factory'] != '1'){
				$map['CP_factory'] = trim($search['CP_factory']);
			}
		}
		$map['status'] = 1;
		$offset = ($page-1)*$rows;
		$qq =db('cost_cp')->where($map)->limit($offset,$rows)->order($sort,$order)->select();
		$qty= db('cost_cp')->where($map)->count();
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
		$cpRes = db('cost_cp')->where('cost_id',$cost_id)->order('id desc')->find();
		if($cpRes){
			$page_info['list'] = $cpRes;
		}else{
			$cpRes = db('cost_cp')->order('id desc')->find();
			foreach($cpRes as $k=>$v){
				$cpRes[$k] = '';
			}
			$cost = db('cost_cost')->where('id',$cost_id)->find();
			$cpRes['type'] = $cost['type'];
			$cpRes['CP_factory'] = $cost['CP_factory'];
			
			$page_info['list'] = $cpRes;
		}
		//下拉框cp场
		$CP_factory = db('cost_config')->where('name','CP_factory')->column('content');
		$page_info['CP_factory'] = cost_string_to_array($CP_factory['0']);
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
		if($data['formula'] == ''){
			echo setServerBackJson(0,'请选择计算公式!');exit;
		}else{
			unset($data['formula']);
		}
		$data['status'] = 1;
		$data['is_new'] = 2;
		if(isset($_POST['yes'])){
			db('cost_cp')->where('cost_id',$_POST['cost_id'])->setField('is_new',0);
			$data['is_new'] = 1;
			unset($data['yes']);
			//改变cost表数据
			$update = array();
			$update['Tester'] = $data['Tester'];
			$update['by61'] = $data['by61'];
			$update['by59'] = $data['by59'];
			db('cost_cost')->where('id',$_POST['cost_id'])->update($update);
			//改变sqlserver表数据
			
		}
		db('cost_cp')->insertGetId($data);
		echo setServerBackJson(1,'新增成功!',1);exit;
	}
	//修改页面显示
	function edit(){
		$cpRes = db('cost_cp')->where('id',$_GET['id'])->find();
		$page_info['list'] = $cpRes;
		$CP_factory = db('cost_config')->where('name','CP_factory')->column('content');
		$page_info['CP_factory'] = cost_string_to_array($CP_factory['0']);
		//下拉计算公式
		$res = db('cost_config')->where('name','test_time')->column('content');
		$contents = explode(';',$res['0']);
		$formulas = array();
		foreach($contents as $v){
			$formula = explode('=',$v);
			$formulas[] = $formula['0'];
		}
		$page_info['formula'] =  array_filter($formulas);
		$this->assign('page_info',$page_info);
		return  $this->fetch();
	}
	//修改数据
	function editRow(){
		$data = $_POST;

		$data['status'] = 1;
		$data['is_new'] = 2;
		if($data['formula'] == ''){
			echo setServerBackJson(0,'请选择计算公式!');exit;
		}
//		else{
//			unset($data['formula']);
//		}
		unset($data['id']);
		if(isset($_POST['yes'])){
			db('cost_cp')->where('cost_id',$_POST['cost_id'])->setField('is_new',0);
			$data['is_new'] = 1;
			unset($data['yes']);
		}
	
		$res = db('cost_cp')->where('id',$_POST['id'])->update($data);
		if($res){
			if(isset($_POST['yes'])){
				unset($data['formula']);
				$update = array();
				$update['Tester'] = $data['Tester'];
				$update['by61'] = $data['by61'];
				$update['by59'] = $data['by59'];
				db('cost_cost')->where('id',$_POST['cost_id'])->update($update);			
			}
			
			echo setServerBackJson(1,'修改成功!',1);exit;
		}else{
			echo setServerBackJson(0,'数据没有变化!');exit;
		}
		
	}
	//计算公式配置页面显示
	function formula(){
		$res = db('cost_config')->where('name','test_time')->column('content');
		$page_info['content'] = str_replace(";",";&#10;",$res['0']);
		
		$this->assign('page_info',$page_info);
		return  $this->fetch();
	}
	//公式录入数据库
	function testTime(){
		$content = $_POST['formula'];
		$content = str_replace(PHP_EOL, '', $content);
		$content = str_replace(array('；',';'),';',$content);
		$content = str_replace('）',')',$content);
		$res = db('cost_config')->where('name','test_time')->setField('content',$content);
		echo setServerBackJson(1,'修改成功!');exit;
	}
	//根据公式计算testtime
	function ajaxFormula(){
		$res= db('cost_config')->where('name','test_time')->column('content');
		$contents = explode(';',$res['0']);
		$str = '';
		foreach($contents as $v){
			$formula = explode('=',$v);
			if(trim($formula['0']) == trim($_POST['formula'])){
			    $str = 	$formula['1'];
			}
		}
		echo $str;
	}
	//批量修改
	function editBySearch(){
		$search = $_GET;
		if(!empty($search['cost_id'])){
			$map['cost_id'] = $search['cost_id'];
		}
		if(!empty($search['test_time_notice'])){
			$map['test_time_notice'] = ['like','%'.$search['test_time_notice'].'%'];
		}
		if(!empty($search['type'])){
			$map['type'] = ['like','%'.$search['type'].'%'];
		}
		if(!empty($search['CP_factory'])){
			if($search['CP_factory'] != '1'){
				$map['CP_factory'] = $search['CP_factory'];
			}
		}
		$map['is_new'] = 1;
		$qty= db('cost_cp')->where($map)->count();
		$page_info['count'] = $qty;
		$this->assign('page_info',$page_info);
		return  $this->fetch();
	}
	//执行批量修改    只可以修改h_r字段，如果修改其他字段需修改此段代码
	function doEdits(){
		$search = $_POST;
		if(!empty($search['cost_id'])){
			$map['cost_id'] = $search['cost_id'];
		}
		if(!empty($search['test_time_notice'])){
			$map['test_time_notice'] = ['like','%'.$search['test_time_notice'].'%'];
		}
		if(!empty($search['type'])){
			$map['type'] = ['like','%'.$search['type'].'%'];
		}
		if(!empty($search['CP_factory'])){
			if($search['CP_factory'] != '1'){
				$map['CP_factory'] = $search['CP_factory'];
			}
		}
		$map['is_new'] = 1;
		$res= db('cost_cp')->where($map)->select();
		//cp_cost=tt_pcs*h_r+back+uv+trim
		$update = array();
		$update['h_r'] = $_POST['content']; 
		foreach($res as $v){			
			$update['cp_cost'] = floatval($v['tt_pcs'])*floatval($_POST['content'])+floatval($v['back'])+floatval($v['uv'])+floatval($v['trim']);
			db('cost_cp')->where('id',$v['id'])->setField($update);
		}
		echo setServerBackJson(1,'修改成功!',1);exit;
	}
}