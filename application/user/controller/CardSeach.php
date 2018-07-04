<?php
namespace app\user\controller;

use think\Controller;
use app\index\controller\Admin;

class CardSeach extends Admin
{
	//原始打卡数据列表
	public function index() {
		$data=$this->param;
		if(!isset($data['site_id'])){
			$site_id=1;
		}else{
			$site_id=$data['site_id'];
		}	
		
		$site_flag=get_site_code($site_id);
		
		$where_str=' 1=1  ';
		
		$user_id=get_user_id();
		
		//部门过滤
		if(isset($data['dep_id']) && $data['dep_id'] <>'all'){
			$where_str .= ' and b.dep_id='.$data['dep_id'];
		}
		
		//开始日期
		if(isset($data['b_date']) && strlen($data['b_date'])>0){
			$b_date=$data['b_date'];
		}else{
			$b_date=date('Y-m-01 00:00:00',time());
		}
		
		//结束日期
		if(isset($data['e_date']) && strlen($data['e_date'])>0){
			$e_date=$data['e_date'];
		}else{
			$e_date=date('Y-m-d 23:59:59',time());
		}
		
		//用户
		if(isset($data['user_id'])){
			if($data['user_id']<>'all'){
				$where_str .= " and b.id=".$data['user_id'];
			}
		}		
		
		if(get_cache_data('user_info',$user_id,'all_card_flag')==0){
			//判断当前用户可以查看用户ID
			$manage_user_id=get_user_sub_user($user_id);
			$where_str .= " and b.id in (".$manage_user_id.") ";
		}
		
		$where_str .= " and  a.entry_dt between '".$b_date."' and '".$e_date."' ";
		
		//关键字搜索		
		if(isset($data['key'])){
			$where_str .= " and b.id in (select id from sw_sys_user where user_gh='".$data['key']."' or email like '%".$data['key']."%' or nickname like '%".$data['key']."%' or card_id='".$data['key']."')";
		}
		
		//过滤食堂打卡
		$where_str .= " and ctrl_id in (23,2) ";
		
		//准备部门数据
		$dep_db_arr=db('sys_dep')->where('status=1 and is_show=1')->field('id,pid,en_name,par_id')->order('par_id')->select();
		$page_info['dep_select']=get_dep_select($dep_db_arr,'filter','dep_id','','');
			
		$model=db('entry_dt_'.$site_flag);
		if (! empty ( $model )) {
			//paginate(config('paginate')['list_rows'],false,['query' => request()->param()])
			$page_info['list']=$model->alias('a')->join('sw_sys_user b','a.emp_no=b.user_gh')->field(' a.recno,a.ctrl_id,a.emp_no,a.status,a.card_no,a.entry_dt,b.nickname ')->where($where_str)->group('a.entry_dt')->order("a.entry_dt")->paginate(config('paginate')['list_rows'],false,['query' => request()->param()]);
			//$page_info['list']=$model->alias('a')->join('sw_sys_user b','a.emp_no=b.user_gh')->field(' a.recno,a.ctrl_id,a.emp_no,a.card_no,a.entry_dt,b.nickname ')->where($where_str)->group('a.entry_dt')->order("a.entry_dt")->buildSql();
			//pr($page_info['list']);
		}
		$page_info['page']=$page_info['list']->render();
		
		//页面数据准备
		$page_info['site_arr']=db('sys_site')->where('status=1 and id in (1,2,3)')->select();
		$page_info['site_id']=$site_id;
		
		//部门
		if(isset($data['dep_id'])){
			$page_info['dep_id']=$data['dep_id'];
		}else{
			$page_info['dep_id']='all';
		}
		
		//部门
		if(isset($data['user_id'])){
			$page_info['user_id']=$data['user_id'];
		}else{
			$page_info['user_id']='all';
		}
		
		if(get_cache_data('user_info',$user_id,'all_card_flag')==0){
			//用户
			$page_info['user_arr']=db('sys_user')->where('hr_status in (1,3) and id in('.$manage_user_id.') and user_status=1 and site_id in (1,2,3)')->field('id,dep_id,nickname,site_id')->select();
		}else{
			$page_info['user_arr']=db('sys_user')->where('hr_status in (1,3) and user_status=1 and site_id in (1,2,3)')->field('id,dep_id,nickname,site_id')->select();
		}		
		
		//日期范围
		$page_info['b_date']=$b_date;
		$page_info['e_date']=$e_date;
		
		//关键字
		if(isset($data['key'])){
			$page_info['key']=$data['key'];
		}else{
			$page_info['key']='';
		}
		
		$this->assign('page_info',$page_info);
		return $this->fetch();
	}

	
}
