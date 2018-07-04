<?php
namespace app\sys\controller;

use think\Controller;
use app\index\controller\Admin;

class ExtRepo extends Admin
{
	/**
	 * 导出报表页
	 */
	public function index() {
		if(IS_POST){
			$data = $this->request->post();
			
			switch ($data['repo_type']){
				case "hr":
					//action("UserMain/ext_hr_repo/",$data);
					//\user\UserMain::ext_hr_repo($data);
					$this->ext_hr_repo($data);
					break;
				case "note":
					//action("UserMain/ext_hr_repo/",$data);
					//\user\UserMain::ext_hr_repo($data);
					$this->ext_note_repo($data);
					break;
			}
			
			
		}else{
			$page_info=array();
		
			//准备部门数据
			$dep_db_arr=db('sys_dep')->where('status=1')->field('id,pid,en_name,par_id')->order('par_id')->select();
			$page_info['dep_select']=get_dep_select($dep_db_arr,'add','dep_id','','form-control');
				
			//准备站点数据
			$page_info['site_arr']=cache('site_cache_arr');
	
			//职等数据
			$page_info['hr_work_level_arr']=config('hr_work_level');
			
			$this->assign('page_info',$page_info);
			return $this->fetch();
		}		
	}
	

	//导出考勤报表
	function ext_hr_repo($data=array()){
		$where_str="";
		
		//部门过滤条件
		if($data['dep_id']>0){
			$where_str .= " and su.dep_id=".$data['dep_id'];
		}
		
		//站点过滤
		if($data['site_id']<>'all'){
			$where_str .= " and su.site_id=".$data['site_id'];
		}
		
		//职等过滤
		if($data['hr_work_level_id']<>'all'){
			$where_str .= " and su.hr_work_level_id=".$data['hr_work_level_id'];			
		}
		
		//时间范围过滤
		if(strlen($data['begin_date'])>0 && strlen($data['end_date'])>0){
			$where_str .= " and ht.hr_date between '".$data['begin_date']." 00:00:00' and '".$data['end_date']." 23:59:59' ";
		}
		
		$tit_field=array('ID','姓名','工号','站点','日期','班/假','首次刷卡','最后一次刷卡','出勤时间','休假数','实扣假数','休假类型','累积年休数');
		$body_field=array('id','nickname','user_gh','site','hr_date','title','hr_card_first','hr_card_end','z_work_time','note_time','act_time','note_type_str','year_holiday_time');
		$file_name="user_hr-".date('Y-m-d',time()).'.xls';
	
		$str_head="";
		$str_body="";
	
		$sql="select
					ht.id,su.user_gh,s.site,ht.nickname,ht.hr_date,ht.title,ht.hr_card_first,ht.hr_card_end,ht.z_work_time,ht.note_time,ht.act_time,
				    (select concat(case when n.note_type=1 then '请假单' when n.note_type=3 then '晚到' when n.note_type=4 then '外出' end,'-',n.hr_note_name) from sw_user_note n where n.id in (ht.note_id_str)) as note_type_str,
					0 as year_holiday_time
				from
					sw_hr_table ht,sw_sys_user su,sw_sys_site s
				where
					su.site_id=s.id and su.status=1 and su.user_status=1 and ht.user_id=su.id ".$where_str;
			
		$user_info_arr=db()->query($sql);
	
		header('Content-Type: application/vnd.ms-execl; charset=utf-8');
		header('Content-Disposition: attachment; filename='.$file_name);
		header('Pragma: no-cache');
		header('Expires: 0');
	
		foreach ($tit_field as $key=>$val){
			$str_head .= $val.chr(9);
		}
		$str_head .= chr(13);
	
		foreach ($user_info_arr as $key=>$val){
			foreach($body_field as $k=>$v){
				$str_body .= $val[$v].chr(9);
			}
			$str_body.=chr(13);
		}
	
		echo auto_charset($str_head.$str_body,'utf-8','gbk');
	}
	
	//导出申请单报表
	function ext_note_repo($data=array()){
		$where_str="";
		
		//部门过滤条件
		if($data['dep_id']>0){
			$where_str .= " and u.dep_id=".$data['dep_id'];
		}
		
		//站点过滤
		if($data['site_id']<>'all'){
			$where_str .= " and u.site_id=".$data['site_id'];
		}
		
		//职等过滤
		if($data['hr_work_level_id']<>'all'){
			$where_str .= " and u.hr_work_level_id=".$data['hr_work_level_id'];
		}
		
		//时间范围过滤
		if(strlen($data['begin_date'])>0 && strlen($data['end_date'])>0){
			$where_str .= " and ((n.begin_time between '".$data['begin_date']." 00:00:00' and '".$data['end_date']." 23:59:59') or n.end_time between '".$data['begin_date']." 00:00:00' and '".$data['end_date']." 23:59:59') ";
		}
		
		
		$tit_field=array('ID','姓名','工号','站点','创建日期','申请类型','假期类型','申请时数','开始日期','结束日期','代理人','审批人','审批状态');
		$body_field=array('id','nickname','user_gh','site','c_time','note_type','hr_note_id','note_hour','begin_time','end_time','age_user','check_user','hr_adv_check_status');
		$file_name="hr_note-".date('Y-m-d',time()).'.xls';
	
		$str_head="";
		$str_body="";
	
		$sql="select
					n.id,u.nickname,u.user_gh,s.site,n.c_time,
				    (case when note_type=1 then '请假单' when note_type=2 then '晚餐预订' when note_type=3 then '晚到' when note_type=4 then '外出' end) as note_type,
				    n.hr_note_id,n.note_hour,n.begin_time,n.end_time,
				    (select nickname from sw_sys_user su where su.id=n.age_user_id) as age_user,
				    (select nickname from sw_sys_user su2 where su2.id=n.hr_adv_user_id) as check_user,
				    n.hr_adv_check_status
				from
					sw_user_note n,sw_sys_user u,sw_sys_site s
				where
					u.site_id=s.id and u.status=1 and u.user_status=1 and n.user_id=u.id and n.status=1 ".$where_str;
			
		$user_info_arr=db()->query($sql);
	
		//用户数据字段填充
		foreach ($user_info_arr as $key=>$val){
			if(strlen($val['hr_note_id'])>0){
				$user_info_arr[$key]['hr_note_id']=config('hr_note_type')[$val['hr_note_id']];
			}
		}
	
		header('Content-Type: application/vnd.ms-execl; charset=utf-8');
		header('Content-Disposition: attachment; filename='.$file_name);
		header('Pragma: no-cache');
		header('Expires: 0');
	
		foreach ($tit_field as $key=>$val){
			$str_head .= $val.chr(9);
		}
		$str_head .= chr(13);
	
		foreach ($user_info_arr as $key=>$val){
			foreach($body_field as $k=>$v){
				$str_body .= $val[$v].chr(9);
			}
			$str_body.=chr(13);
		}
	
		echo auto_charset($str_head.$str_body,'utf-8','gbk');
	}
	


}











