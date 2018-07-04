<?php
namespace app\sys\controller;

use think\Controller;
use app\index\controller\Admin;

class Dep extends Admin
{
    public function index()
    {
    	//获取部门数组
    	$dep_db_arr=db('sys_dep')->where('status=1')->field("id,pid,zh_name as text")->order('order_id')->select();
    	
    	//树节点添加a标签
    	foreach ($dep_db_arr as $key=>$val){
    		$dep_db_arr[$key]['text']="<span  onclick=edit_dep(this) dep_id=".$val['id'].">".$val['text']."</span>";
    	}
    	
    	$dep_arr=formatTree($dep_db_arr);
    	$dep_json=json_encode($dep_arr);
    	$page_info['dep_json']=$dep_json;
    	
    	$this->assign('page_info',$page_info);
        return  $this->fetch();
    }
    
    public function add(){
    	
    	if (IS_POST) {
    		$data = $this->request->post();
    		
    		//数据校验
    		$result=$this->validate($data,'Dep');    		
    		
    		if($result !== true){
    			echo setServerBackJson(0, $result);
    			exit;
    		}
    		
    		//同部门下属部门名称重复检测
    		$id=db('sys_dep')->where(" en_name='".$data['en_name']."' or zh_name='".$data['zh_name']."'")->value('id');
   			if($id>0){
   				echo setServerBackJson(0,'同级别部门中文名&英文名重复!');
   				exit;
   			}
   			
   			//同部门下属部门名称重复检测
   			$id=db('sys_dep')->where(" dep_code='".$data['dep_code']."'")->value('id');
   			if($id>0){
   				echo setServerBackJson(0,'部门编号重复!');
   				exit;
   			}
    		
    		$id=db('sys_dep')->insertGetId($data);	    			
    		
    		if (false !== $id) {
    			
    			cache('dep_cache_arr',null);
    			if($data['pid']==0){
    				db('sys_dep')->where('id='.$id)->setField('par_id',$id);
    			}else{
    				$par_id=db('sys_dep')->where('id='.$data['pid'])->value('par_id');	    					
    				db('sys_dep')->where('id='.$id)->setField('par_id',$par_id.','.floatval($id));
    			}
    			echo setServerBackJson(1, '部门'.$data['en_name'].'添加成功！','index','closeDialog');
    		} else {
    			echo setServerBackJson(0, '数据添加失败!请联系管理员');
    			exit;
    		}
    	}else{
    		$page_info=array();
    		//准备部门数据
    		$dep_db_arr=db('sys_dep')->where('status=1')->field('id,pid,en_name,par_id')->order('par_id')->select();
    		$page_info['dep_select']=get_dep_select($dep_db_arr,'add','pid');
    		
    		$this->assign('page_info',$page_info);
    		return $this->fetch();
    	}  
    }

    public function edit($id=null){
    	if (IS_POST) {
    		$data = $this->request->post();
    		$result=$this->validate($data,'Dep');
    		if(true !== $result){
    			echo setServerBackJson(0, $result);
    			exit; 			
    		}else{
    			
    			//获取上级部门par_id
    			$par_id=db('sys_dep')->where('id='.$data['pid'])->value('par_id');
    			$data['par_id']=$par_id.','.$data['id'];
    			
    			db('sys_dep')->update($data);
    			cache('dep_cache_arr',null);
    			echo setServerBackJson(1, $data['zh_name'].'部门更新成功','index','closeDialog');
    		}    		
    				
    	}else{
    		$page_info=array();    		
    		$page_info['dep_info']=db('sys_dep')->find($id);
    		//准备部门数据
    		$dep_db_arr=db('sys_dep')->where('status=1')->field('id,pid,en_name,par_id')->order('par_id')->select();
    		$page_info['dep_select']=get_dep_select($dep_db_arr,'add','pid',$page_info['dep_info']['pid']);
    		
    		$this->assign('page_info',$page_info);
    		return $this->fetch();
    	}
    }

}
