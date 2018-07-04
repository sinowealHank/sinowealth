<?php
namespace app\sys\controller;

use think\Controller;
use app\index\controller\Admin;

class HrSourceList extends Admin
{
	//原始打卡数据列表
	public function index() {
		$site_id=input('site_id');
		if(!isset($site_id)){
			$site_id=1;
		}
		
		$site_flag=get_site_code($site_id);
		
		//关键字搜索
		$key=$this->request->param('key');
		if(strlen($key)>0){
			$map['exp_logic']='and';
			$map['exp'] =" (user_gh like '%".$key."%') or (email like '%".$key."%') or (nickname like '%".$key."%')";
		}
		
		$model=db('entry_dt_'.$site_flag);
		if (! empty ( $model )) {
			$page_info['list']=$model->alias('a')->join('sw_user_info_'.$site_flag.' b','a.emp_no=b.emp_no')->field(' a.recno,a.ctrl_id,a.emp_no,a.card_no,a.entry_dt,b.name ')->order("recno desc")->paginate(config('paginate')['list_rows'],false,['query' => request()->param()]);
		}
		$page_info['page']=$page_info['list']->render();
		
		$page_info['site_arr']=db('sys_site')->where('status=1')->select();
		$page_info['site_id']=$site_id;
		
		$this->assign('page_info',$page_info);
		return $this->fetch();
	}

	
}
