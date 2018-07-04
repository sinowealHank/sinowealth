<?php
//user模块共用函数

/**
 * 获取某用户某日打卡信息
 */
function get_user_day_card_info($date="",$user_id=''){
	
	if(strlen($date)==0){
		$date=date('Y-m-d',time());
	}
	if(strlen($user_id)==0){
		$user_id=get_user_id();
	}
	if($user_id==get_user_id()){
		$user_info=$_SESSION['u_i'];
	}else{
		$user_info=db('sys_user')->where('id='.$user_id)->find();
	}
	
	if($user_info['out_site_id']<>0 && strlen($user_info['out_card_id'])>0){
		$user_info['site_id']=$user_info['out_site_id'];
		$user_info['card_id']=$user_info['out_card_id'];
	}
	
	$site_code=get_site_code($user_info['site_id']);
	
	//获取指定日期打卡情况
	if($user_info['site_id']==1){
		$sql="
					select
						card_no,emp_no,ctrl_id,status,
					    (case when ctrl_id=2 then '车库门' else
							(case when ctrl_id=23 then ' 前台' else '食堂' end)
					    end) as card_site,
					    entry_dt,
					    (case when status=1 and ctrl_id in (2,23) then '有效考勤打卡' else 
				
						(case when status=0 and ctrl_id in (2,23) then '无效考勤打卡' else '用餐打卡' end) 
				
						end) as card_status,
						(case when ctrl_id=24 then 1 else 0 end ) as card_order
					from
						sw_entry_dt_sh e
					where
						entry_dt between '".$date.' '.config('hr_begin_time')."' and '".$date." 23:59:59' and emp_no='".$user_info['user_gh']."' and ctrl_id in (23,2,24)
					order by 
						card_order,entry_dt,ctrl_id
					";
	}else{
		$sql="select
						card_no,emp_no,ctrl_id,status,
					    '考勤点' as  card_site,
					    entry_dt,
					    '有效考勤打卡' as card_status
					from
						sw_entry_dt_".$site_code." e
					where
						entry_dt between '".$date.' '.config('hr_begin_time')."' and '".$date." 23:59:59' and emp_no='".$user_info['user_gh']."';
					";
	}
	return  db()->query($sql);
}
