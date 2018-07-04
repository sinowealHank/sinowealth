<?php
namespace app\demo\controller;

use think\Controller;
use app\index\controller\Admin;

class Index extends Admin
{
	protected $beforeActionList = [
			'first',
	];
	
	function first(){
		//config('paginate.list_rows',3);
		//dump(config('paginate')['list_rows']);
	}
	
	public function index(){
		
		$array=array();
	
	$array[0]['user_gh']='111';
	$array[0]['sex']=1;
	$array[0]['address']="aaa";
	
	$array[1]['user_gh']='111';
	$array[1]['sex']=1;
	$array[1]['address']="aaa";
	
	$array[2]['user_gh']='111';
	$array[2]['sex']=1;
	$array[2]['address']="aaa";
	
	/*
	$array[0]['id']=1;
	$array[0]['username']='111';
	$array[0]['sex']=1;
	$array[0]['address']="aaa";
	
	$array[1]['id']=2;
	$array[1]['username']='111';
	$array[1]['sex']=1;
	$array[1]['address']="aaa";
	
	$array[2]['id']=3;
	$array[2]['username']='111';
	$array[2]['sex']=1;
	$array[2]['address']="aaa";
		*/
		
		$page_info['site_arr']=cache('site_cache_arr');
		$this->assign('page_info',$page_info);
		return  $this->fetch();
	}
	
	function test(){
		return $this->fetch();
	}
	
	function form(){
		return $this->fetch();
	}
}
