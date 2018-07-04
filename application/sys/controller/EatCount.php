<?php
namespace app\sys\controller;

use think\Controller;
use app\index\controller\Admin;
use think\console\command\make\Model;

class EatCount extends Admin
{
	/**
	 * 用餐数据列表
	 */
	public function index() {
		$page_info=array();
		
		$data=$this->param;
		if(!isset($data['begin_date'])){
			$data['begin_date']=date('Y-m-d',time());
		}
		if(!isset($data['end_date'])){
			$data['end_date']=date('Y-m-d',time());
		}
		
		$page_info['begin_date']=$data['begin_date'];
		$page_info['end_date']=$data['end_date'];
		
		//判断本月用餐数据是否已经锁定,如果未锁定,获取最新用餐数据
		$cur_month_is_lock=db('sys_eat_list')->where('year='.C_YEAR.' and month='.C_MONTH.'')->find();
		if(!$cur_month_is_lock || $cur_month_is_lock['is_lock']==0){
			//抓取本月最新用餐数据
			$date_rang=get_begin_last_date();
			
			$sql="
					select
			            sum(case when e.entry_dt between concat(e.entry_date,' ".config('hr_breakfast_begein')."') and concat(e.entry_date,' ".config('hr_breakfast_end')."') then 1 else 0 end) as bf_num,
			            sum(case when e.entry_dt between concat(e.entry_date,' ".config('hr_lunch_begin')."') and concat(e.entry_date,' ".config('hr_lunch_end')."') then 1 else 0 end) as lunch_num,
			            (select count(*) from sw_user_note_item i where i.begin_time=concat(e.entry_date,' 00:00:00') and i.note_type=2 and i.user_id=u.id) as supper_num
					from
						sw_entry_dt_sh e,sw_sys_user u
			        where
						e.emp_no=u.user_gh and e.ctrl_id=24 and e.entry_date between '".$date_rang[0]." 00:00:00' and '".$date_rang[1]." 23:59:59' and u.status=1 and u.user_status=1
					order by entry_dt
					";
			$eat_arr_temp=db()->query($sql);
			$eat_arr=$eat_arr_temp[0];
			$eat_arr['year']=C_YEAR;
			$eat_arr['month']=C_MONTH;
			$eat_arr['bf_money']=$eat_arr['bf_num']*config('bf_price');
			$eat_arr['lunch_money']=$eat_arr['lunch_num']*config('lunch_price');
			$eat_arr['supper_money']=$eat_arr['supper_num']*config('lunch_price');
			$eat_arr['bf_num_s']=$eat_arr['bf_num'];
			$eat_arr['bf_money_s']=$eat_arr['bf_money'];
			$eat_arr['lunch_num_s']=$eat_arr['lunch_num'];
			$eat_arr['lunch_money_s']=$eat_arr['lunch_money'];
			$eat_arr['supper_num_s']=$eat_arr['supper_num'];
			$eat_arr['supper_money_s']=$eat_arr['supper_money'];
			$eat_arr['last_update_time']=date('Y-m-d H:i:s',time());
			
			//如果没有建记录,则插入,如果有建,则更新
			if(!$cur_month_is_lock){
				$eat_arr['log']=date('Y-m-d H:i:s',time()).' 由用户'.get_user_nickname().' 创建数据';
				db('sys_eat_list')->insert($eat_arr);
			}else{
				$eat_arr['id']=$cur_month_is_lock['id'];
				db('sys_eat_list')->update($eat_arr);
			}
			
		}
		
		$model=db('sys_eat_list');
		if (! empty ( $model )) {
			$page_info['list']=$this->_list($model,'','id',false);
		}
		$page_info['page']=$page_info['list']->render();
		
		//判断本日晚餐是否已经通知
		$is_note_supper=db('sys_log')->where(" log_type=1 and date_format(create_time,'%Y-%m-%d')='".date('Y-m-d',time())."'")->find();
		if($is_note_supper){
			$page_info['supper_flag']=1;
		}else{
			$page_info['supper_flag']=0;
		}
		
		if($page_info['list']->total()>0){
			$page_info['empty']=0;
		}else{
			$page_info['empty']=1;
		}
		
		$this->assign('page_info',$page_info);
		return $this->fetch();
	}
	
	/**
	 * 查看某月份详细用餐列表
	 */
	function day_list(){
		$data=$this->param;
		$year=$data['year'];
		$month=$data['month'];
		$date_rang=get_begin_last_date($year.'-'.$month.'-01');
		$sql="
				select
						e.entry_date,
			            sum(case when e.entry_dt between concat(e.entry_date,' ".config('hr_breakfast_begein')."') and concat(e.entry_date,' ".config('hr_breakfast_end')."') then 1 else 0 end) as bf_num,
			            sum(case when e.entry_dt between concat(e.entry_date,' ".config('hr_lunch_begin')."') and concat(e.entry_date,' ".config('hr_lunch_end')."') then 1 else 0 end) as lunch_num,
			            (select count(*) from sw_user_note_item i where i.begin_time=concat(e.entry_date,' 00:00:00') and i.note_type=2 and i.user_id=u.id) as supper_num
					from
						sw_entry_dt_sh e,sw_sys_user u
			        where
						e.emp_no=u.user_gh and e.ctrl_id=24 and e.entry_date between '".$date_rang[0]." 00:00:00' and '".$date_rang[1]." 23:59:59' and u.status=1 and u.user_status=1
					group by 
						e.entry_date
					order by entry_dt
				";
		$eat_day_list=db()->query($sql);
		$page_info['bf_count']=0;
		$page_info['lunch_count']=0;
		$page_info['supper_count']=0;
		foreach ($eat_day_list as $k=>$v){
			$eat_day_list[$k]['bf_money']=$v['bf_num']*config('bf_price');
			$eat_day_list[$k]['lunch_money']=$v['lunch_num']*config('lunch_price');
			$eat_day_list[$k]['supper_money']=$v['supper_num']*config('lunch_price');
			$page_info['bf_count'] += $v['bf_num'];
			$page_info['lunch_count'] += $v['lunch_num'];
			$page_info['supper_count'] += $v['supper_num'];
		}
		
		$page_info['year']=$year;
		$page_info['month']=$month;
		$page_info['list']=$eat_day_list;
		
		if(count($eat_day_list)==0){
			$page_info['empty']=1;
		}else{
			$page_info['empty']=0;
		}
		
		$this->assign('page_info',$page_info);
		return $this->fetch();
	}
}











