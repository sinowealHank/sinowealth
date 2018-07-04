<?php
namespace app\sys\controller;

use think\Controller;
use app\index\controller\Admin;

class HrRole extends Admin
{
	//规则列表
	public function index() {
		
		$data=db('sys_hr_role')->where('status=1')->select();		
		$this->assign('data',$data);
		return $this->fetch();
	}

	//新增规则
	Public function add(){
		if (IS_POST) {
			$temp_arr=$this->param;
			
			if(strlen($temp_arr['role_name'])==0){
				echo setServerBackJson(0,'规则名称不能为空!');
				exit;
			}
				
			if(strlen($temp_arr['order_id'])==0){
				$temp_arr['order_id']=1;
			}
			
			//判断规则名称是否唯一
			$is_have=db('sys_hr_role')->where("role_name='".$temp_arr['role_name']."'")->find();
			if($is_have){
				echo setServerBackJson(0,'规则名称'.$temp_arr['role_name'].'重复!');
				exit;
			}else{
				$temp_arr['create_user']=get_user_id();
				$temp_arr['create_date']=date('Y-m-d H:i:s',time());
				db('sys_hr_role')->insert($temp_arr);
				echo setServerBackJson(1,$temp_arr['role_name'].'规则添加成功!','index','closeCurrent');
			}
			
		}else{
			$page_info=array();
			$page_info['site_info']=CONFIG('site_info');
			
			$this->assign('page_info',$page_info);
			return $this->fetch();
		}		
		
	}

	//编辑规则
	Public function edit(){
		if (IS_POST) {
			$temp_arr=$this->param;
				
			if(strlen($temp_arr['role_name'])==0){
				echo setServerBackJson(0,'规则名称不能为空!');
				exit;
			}
			
			if(strlen($temp_arr['order_id'])==0){
				$temp_arr['order_id']=1;
			}	

			//判断规则名称是否唯一
			$is_have=db('sys_hr_role')->where("role_name='".$temp_arr['role_name']."' and id<>".$temp_arr['id'])->find();
			if($is_have){
				echo setServerBackJson(0,'规则名称'.$temp_arr['role_name'].'重复!');
				exit;
			}else{
				$temp_arr['update_user']=get_user_id();
				$temp_arr['update_date']=date('Y-m-d H:i:s',time());
				db('sys_hr_role')->update($temp_arr);
				echo setServerBackJson(1,$temp_arr['role_name'].'规则编辑成功!','index','closeCurrent');
			}
				
		}else{
			$page_info=array();
			$page_info['site_info']=CONFIG('site_info');
			//$page_info['id']=$this->param('id');
			$page_info['id']=$this->request->param('id');
			
			$page_info['role_info']=db('sys_hr_role')->where('id='.$page_info['id'])->find();
				
			$this->assign('page_info',$page_info);
			return $this->fetch();
		}
	
	}

	//删除考勤规则
	function del(){
		db('sys_hr_role')->where('id='.$this->request->param('id'))->setField('status',0);
		echo '考勤规则删除成功!';
	}
	
}
