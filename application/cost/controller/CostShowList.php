<?php
namespace app\cost\controller;

use think\Controller;
use app\index\controller\Admin;

class CostShowList extends Admin
{
	public function index(){
		$page_info = cost_get_field();
		$key = cost_string_to_array(cost_config('6')); //模糊查询字段
	
		foreach($key as $k=>$v){
			$page_info['key'][$k]['name'] = $v;
			$page_info['key'][$k]['show'] = db('cost_field')->where('field',$v)->column('field_show');
			$page_info['key'][$k]['show'] = $page_info['key'][$k]['show']['0'];
		}
		//去掉rodno和type
		unset($page_info['key']['0']);
		unset($page_info['key']['1']);
		//產品線下拉框字段
		$user = session('cur_user_info');
		$lines = db('cost_user_type')->where('user_id',$user['id'])->column('show_line');
		$page_info['line'] = cost_string_to_array($lines['0']);
		$this->assign('page_info',$page_info);
		return  $this->fetch();
	}
	//获取cost数据
	public function costDate(){	
		$sort = isset($_POST['sort']) ? $_POST['sort'] : 'id';
		$order = isset($_POST['order']) ? $_POST['order'] : 'desc';
		$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
		$rows = isset($_POST['rows']) ? intval($_POST['rows']) : 10;
		$search = $_POST;
	//如果有查詢，則根據查詢条件筛选数据
		if(isset($search['key_field'])){
			if($search['key_field'] == '1'){
				if(!empty($search['key'])){
					$like_search = '%'.$search['key'].'%';
					$map['fab|CP_factory|Assy_ab|PIN|F_T_Out|tester|F_T_Tester']  = ['like',$like_search];
				}
			}else{
				if(!empty($search['key'])){
					$like_search = '%'.$search['key'].'%';
					$map[$search['key_field']]  = ['like',$like_search];
				}
			}
		}
		if(!empty($search['prdno'])){
			$map['prdno'] = ['like','%'.$search['prdno'].'%'];
		}
		if(!empty($search['type'])){
			
			$map['type'] = ['like','%'.$search['type'].'%'];
		}
		//能查看的产品线
		$user = session('cur_user_info');
		$lines = db('cost_user_type')->where('user_id',$user['id'])->column('show_line');
		if(!empty($search['line']) && $search['line'] != '1'){
			$map['line'] = $search['line'];
		}else{
			$map['line'] = ['in',$lines['0']];
		}
		$offset = ($page-1)*$rows;
		$map['show_type'] = '1';
		$map['flow'] = '4';
		
		
		$page_info['line'] = cost_string_to_array($lines['0']);
	
// 		$qq =db('cost_cost')->where($config_show)->where($search)->where($map)->limit($offset,$rows)->order($sort,$order)->select();
// 		$qty= db('cost_cost')->where($config_show)->where($search)->where($map)->count();
		$qq =db('cost_cost')->where($map)->limit($offset,$rows)->order($sort,$order)->select();
		$qty= db('cost_cost')->where($map)->count();
		$json='{"total":'.$qty.',"rows":'.json_encode($qq).'}';
		echo $json;
	}
	//操作页面框
	function action(){
		//$page_info = cost_get_field();
		$page_info['id'] = $_GET['id'];
		$page_info['indexRow'] = $_GET['indexRow'];
		
		$this->assign('page_info',$page_info);
		return  $this->fetch();
	}
	//单行修改页面
	function showDetail(){
		$cost_id = $_GET['id'];
		//获取字段信息
		$field = db('cost_field')->order('id','asc')->select();
		//获取此条数据
		$res =db('cost_cost')->where('id',$cost_id)->find();
	
		//整合数据
		
		$page_info['info'] = array();
		foreach($field as $k=>$v){
			if($v['section'] == '4'){
				continue;
			}
			$page_info['info'][$k]['field'] = $v['field'];
			$page_info['info'][$k]['field_show'] = $v['field_show'];
			$page_info['info'][$k]['value'] = $res[$v['field']];
			$page_info['info'][$k]['section'] = $v['section'];
		}
	
		
		$page_info['id'] = $cost_id;
		$page_info['indexRow'] = $_GET['indexRow'];
		$page_info['field_category_1'] = cost_config(1);
		$page_info['field_category_4'] = cost_config(4);//需要下拉框字段
		$page_info['flow'] = $res['flow'];
		$this->assign('page_info',$page_info);
		return  $this->fetch();
	}

	
}

