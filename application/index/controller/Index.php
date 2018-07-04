<?php
namespace app\index\controller;

use think\Controller;
use app\index\controller\Admin;

class Index extends Admin
{
    public function index()
    { 
    	$page_info=array();
    	$return_arr=array();
    	
    	$date=$this->request->param('month').'-01';
    	
    	//获取某用户某月份的考勤数据
    	$return_arr=get_user_month_hr($date,get_user_id());
    	
    	$page_info['site_id']=session('cur_user_info')['site_id'];
    	
    	$page_info['hr_json']=json_encode($return_arr);
    	$page_info['user_info']=session('cur_user_info');	
    	
    	$this->assign('page_info',$page_info);
        return  $this->fetch();
    }   

    public function clear() {
    		\think\Cache::clear(); // 清空缓存数据
    		\think\Log::clear();
    		//常用数据缓存
    		cache_data('config','reset');
    		cache_data('dep','reset');
    		cache_data('site','reset');
    		cache_data('user','reset');
    		cache_data('hr_role','reset');
    		
    		return $this->success("缓存清理成功！", url('index/index'));
    		//echo setServerBackJson(1, '缓存清理成功！');
    }
}
