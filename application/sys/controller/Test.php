<?php
namespace app\sys\controller;

set_time_limit(0);
ini_set("memory_limit","-1");
use think\Controller;
use app\index\controller\Admin;

class Test extends Admin
{
	
	public function index(){
		
		$role_arr=db('role')->select();
		
		$tree_1=$this->_reSort($role_arr);
		//pr($tree_1);
		
		$tree_2=$this->make_tree($tree_1);
		
		$page_info['tree']=json_encode($tree_2);
		
		$this->assign('page_info',$page_info);
		return $this->fetch();
	}

	function test_aa(){
		echo 'test_aa';
	}

	
	function _reSort($data,$parent_id=0, $level=0, $isClear=TRUE){
		static $ret = array();
		if($isClear){
			$ret = array();
		}
		foreach ($data as $k => $v)
		{
			if($v['parent_id'] == $parent_id)
			{
				$v['level'] = $level;
				$ret[] = $v;
				$this->_reSort($data, $v['id'], $level+1, FALSE);
			}
		}
		return $ret;
	}
	
	
	function  make_tree($list,$pk='id',$pid='parent_id',$child='children',$root=0){
		$tree=array();
		$packData=array();
		foreach ($list as $data){
			$packData[$data[$pk]] = $data;
		}
		foreach ($packData as $key =>$val){
			if($val[$pid] == $root){//代表跟节点
				$tree[]= & $packData[$key];
			}else{
				//找到其父类
				$packData[$val[$pid]][$child][]=& $packData[$key];
			}
		}
		return $tree;
	}
	
	
	
}
