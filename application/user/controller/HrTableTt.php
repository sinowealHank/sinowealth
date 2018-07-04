<?php
namespace app\user\controller;

use think\Controller;
use app\index\controller\Admin;

class HrTableTt extends Admin
{
	/**
	 * 高标统计表
	 *///王天棋2017.9.27修改
	public function index() {

		$data=$this->param;
		$page_info=array();	
		$date_field_arr=array();
		$err_flag=false;
		$card_str=false;

		if(isset($data['user_id'])){
			if($data['user_id']=='all'){
				$where_str_user1='';
				$where_str_user2='';
			}else{
				$where_str_user1=" and id=".$data['user_id'];
				$where_str_user2=" and user_id=".$data['user_id'];
			}
		}else{
			$where_str_user1='';
			$where_str_user2='';
		}
		$user_id=get_user_id();

		//获取当前用户管理用户user_id
		$manage_user_id_str=get_user_sub_user($user_id);
		if($_SESSION['u_i']['is_hr_manage']==1){
			//$manage_user_id_arr=db()->query("select group_concat(id) as id_str from sw_sys_user where user_status=1 and status=1 and hr_status in (1,3) ".$where_str_user1);
			$manage_user_id_str=get_user_sub_user($user_id);
		}
		
		//默认查询当月1号到当前日期
		$month_arr=get_begin_last_date();
		
		//获取本月开始时间到当前时间
		if($month_arr[1]>date('Y-m-d',time())){
			$month_arr[1]=date('Y-m-d',time());
		}
		
		if(IS_POST){
			if(strlen($data['begin_date'])>0){
				$month_arr[0]=$data['begin_date'];
			}
			if(strlen($data['end_date'])>0){
				$month_arr[1]=$data['end_date'];
			}
			
			if($month_arr[1]<$month_arr[0]){
				echo setServerBackJson(0,'结束日期小于开始日期,无法查询!');
				exit;	
			}
			
			if(isset($data['card_time'])){
				$card_str=true;
			}else{
				$card_str=false;
			}
		}
		
		//获取日期范围内的天数
		$date_arr=db('sys_month_day')->where(" val between '".$month_arr[0]."' and '".$month_arr[1]."'")->select();
		
		//查询所有用户时不允许跨2个月
		if(strlen($where_str_user1)==0){
			if(count($date_arr)>63){
				$err_msg="查询时间范围不能超过2个月,系统无法计算!";
				$err_flag=true;
			}
		}		
		
		if(!$err_flag){
			
			$sql="select u.id as user_id,d.en_name as dep_name,hr.card_id,u.nickname,hr.site_id,hr.hr_role_id,holiday_id,sum(z_out_work_time)  as out_work_tt,";
			//拼接无考勤人员数据
			$sql2="select  u.id as user_id,d.en_name as dep_name,u.card_id as card_id,u.nickname,u.site_id,'' as hr_role_id,'' as holiday_id,0 as  out_work_tt,";
			
			foreach ($date_arr as $key=>$val){
				$date_list_arr[$key]['date']=$val['val'];
				$date_list_arr[$key]['field']='day_'.$key;
					
				//判断是否需要显示具体打卡时间
				if($card_str){
					$temp_str=" concat(date_format(hr.hr_card_first,'%H:%i'),'~',date_format(hr.hr_card_end,'%H:%i'),'  ',hr.z_out_work_time) ";
				}else{
					$temp_str=" hr.z_out_work_time ";
				}
					
				$sql .= "max(case when hr.hr_date='".$val['val']."' then ".$temp_str." else '' end) as day_".$key.",";
				$sql2 .= "0 as day_".$key.",";
			}
			
			$sql = check_douhao($sql, 0);
			$sql2= check_douhao($sql2, 0);
		
			$sql_where='';
			$_POST['site_id']=isset($_POST['site_id'])?$_POST['site_id']:'';
			$_POST['dep_id']=isset($_POST['dep_id'])?$_POST['dep_id']:'';
			$_POST['user_id']=isset($_POST['user_id'])?$_POST['user_id']:'';
			$sql_site='';
			if($_POST['site_id']!=='all' && $_POST['site_id']){$sql_site=',sw_sys_site s';$sql_where=$sql_where."s.id='".$_POST['site_id']."' and u.site_id='".$_POST['site_id']."' and ";}
			if($_POST['dep_id']!=='all' && $_POST['dep_id']){$sql_where=$sql_where."d.id='".$_POST['dep_id']."' and ";}
			if($_POST['user_id']!=='all' && $_POST['user_id']){$sql_where=$sql_where."u.id='".$_POST['user_id']."' and ";}
			//排序
			$sort_name=isset($_GET['ii'])?$_GET['ii']:'';
			$zorf=isset($_GET['a'])?$_GET['a']:'';
			$sql_paixu='';
			if($sort_name){$sql_paixu=' order by '.$sort_name;}
			if($zorf){$sql_paixu=$sql_paixu.' desc';}
			$page_info['ii']=array($sort_name,$zorf);
			$sql .= "
				from
					sw_sys_month_day md,sw_hr_table hr,sw_sys_user u,sw_sys_dep d".$sql_site."
				where
					hr.user_id=u.id and u.dep_id=d.id and u.hr_status in (1,3) and u.status=1 and u.user_status=1 and
					".$sql_where."
					md.val between '".$month_arr[0]."' and '".$month_arr[1]."' and hr.hr_date=md.val and hr.user_id in (".$manage_user_id_str.") ".$where_str_user2."
				group by hr.user_id
				
				#拼接无考勤人员
				union all
					".$sql2."
						from 
								sw_sys_user u,sw_sys_dep d".$sql_site."
				where
					u.dep_id=d.id and ".$sql_where." u.status=1 and u.hr_status in (1,3)  and u.user_status=1 and u.id in (".$manage_user_id_str.") 
					and u.id not in (
							select u.id
							from
								sw_sys_month_day md,sw_hr_table hr,sw_sys_user u,sw_sys_dep d".$sql_site."
							where
								hr.user_id=u.id and u.dep_id=d.id and u.status=1 and u.user_status=1 and
								".$sql_where."
								md.val between '".$month_arr[0]."' and '".$month_arr[1]."' and hr.hr_date=md.val and hr.user_id in (".$manage_user_id_str.") ".$where_str_user2."
							)
			
			".$sql_paixu;
			$page_info['err_flag']=0;
			$page_info['err_msg']='';
			
			$page_info['date_list']=$date_list_arr;
			
			$page_info['list']=db()->query($sql);
			if(isset($_GET['excel'])){
				$this->excel_out($page_info['list'],$page_info['date_list']);
				exit;
			}
		}else{
			$page_info['err_flag']=1;
			$page_info['err_msg']=$err_msg;
		}
		
		//限制日期控件最大最小值
		$page_info['date_min']='2017-01-01';
		$page_info['date_max']=date('Y-m-d',time());
		
		//当前用户可以查看的用户过滤
		$page_info['user_arr']=db('sys_user')->where("id in (".$manage_user_id_str.")")->order('site_id')->select();

		$page_info['begin_date']=$month_arr[0];
		$page_info['end_date']=$month_arr[1];
		if(isset($data['user_id'])){
			$page_info['user_id']=$data['user_id'];
		}else{
			$page_info['user_id']='all';
		}
		if(isset($data['card_time'])){
			$page_info['card_time_flag']=1;
		}else{
			$page_info['card_time_flag']=0;
		}
		
		
		//搜索条件合集，站点，部门，人员
		//页面数据准备
		$page_info['site_arr']=db('sys_site')->where('status=1 and id in (1,2,3)')->select();
		$data=$this->param;
		$page_info['site_id']=$_POST['site_id'];
		//准备部门数据
		$dep_db_arr=db('sys_dep')->where('status=1 and is_show=1')->field('id,pid,en_name,par_id')->order('par_id')->select();
		$page_info['dep_select']=get_dep_select($dep_db_arr,'filter','dep_id','','');
		$user_id=get_user_id();
		//部门
		if(isset($data['dep_id'])){
			$page_info['dep_id']=$data['dep_id'];
		}else{
			$page_info['dep_id']='all';
		}
		//上边有重复都是感觉.......还是覆盖了吧
		if(get_cache_data('user_info',$user_id,'all_card_flag')==0){
			//用户
			$manage_user_id=get_user_sub_user($user_id);
			$page_info['user_arr']=db('sys_user')->where('hr_status in (1,3) and id in('.$manage_user_id.') and user_status=1 and site_id in (1,2,3)')->field('id,dep_id,nickname,site_id')->select();
		}else{
			$page_info['user_arr']=db('sys_user')->where('hr_status in (1,3) and user_status=1 and site_id in (1,2,3)')->field('id,dep_id,nickname,site_id')->select();
		}
		
		$this->assign('page_info',$page_info);
		return $this->fetch();
		
	}
	public function excel_out($list,$date_list){
		foreach ($list as $key=>$d){
			unset($list[$key]['user_id']);
			unset($list[$key]['card_id']);
			unset($list[$key]['site_id']);
			unset($list[$key]['hr_role_id']);
			unset($list[$key]['holiday_id']);
		}
		$data_title=array('部门','用户','TT(小时)');
		foreach ($date_list as $d_l){
			$data_title[]=m_d($d_l['date']);
		}
		Array_unshift($list,$data_title);
		$excel=array(
				'name'=>'高标统计表',
				array(
						'data'=>$list
				)
		);
		excel_css($excel);
	}
}




















