<?php
namespace app\user\controller;

use think\Controller;
use app\index\controller\Admin;
//王天棋2017.9.27修改
class HrCount extends Admin
{
	/**
	 * 查看下属考勤统计表,条件过滤
	 */
	public function index() {

		$data=$this->param;
		$page_info=array();	
		$date_field_arr=array();
		$err_flag=false;
		$card_str=false;

		//获取当前用户管理用户user_id
		$user_id=get_user_id();//感觉这里少了这个...
		$manage_user_id_str=get_user_sub_user($user_id);
		if($_SESSION['u_i']['is_hr_manage']==1){
			//$manage_user_id_arr=db()->query("select group_concat(id) as id_str from sw_sys_user where  hr_status in (1,3) and status=1 and user_status=1");
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
	
		}
		//条件
		$sql_where='';
		$_POST['site_id']=isset($_POST['site_id'])?$_POST['site_id']:'';
		$_POST['dep_id']=isset($_POST['dep_id'])?$_POST['dep_id']:'';
		$_POST['user_id']=isset($_POST['user_id'])?$_POST['user_id']:'';
		$sql_site='';
		if($_POST['site_id']!=='all' && $_POST['site_id']){$sql_site=',sw_sys_site s';$sql_where=$sql_where."s.id='".$_POST['site_id']."' and u.site_id='".$_POST['site_id']."' and ";}
		if($_POST['dep_id']!=='all' && $_POST['dep_id']){$sql_where=$sql_where."d.id='".$_POST['dep_id']."' and ";}
		if($_POST['user_id']!=='all' && $_POST['user_id']){$sql_where=$sql_where."u.id='".$_POST['user_id']."' and ";}
		//排序
		$sort_name=isset($_GET['ii'])?$_GET['ii']:'dep_name';
		$zorf=isset($_GET['a'])?$_GET['a']:'';
		if($sort_name){$sql_paixu=' order by '.$sort_name;}
		if($zorf){$sql_paixu=$sql_paixu.' desc';}
		$page_info['ii']=array($sort_name,$zorf);
		$sql="
				select 
					d.en_name as dep_name,u.nickname,h.user_id,sum(z_out_work_time) as out_work_time,
				    sum(case when note_type=1 and hr_note_id=2 then act_time else 0 end) as vacation_hour,
				    sum(case when note_type=1 and hr_note_id=1 then act_time else 0 end) as work_vacation_hour,
				    sum(case when note_type=1 and hr_note_id=3 then act_time else 0 end) as sick_leave,
				    sum(case when note_type=1 and hr_note_id=4 then act_time else 0 end) as marry_leave,
				    sum(case when note_type=1 and hr_note_id=5 then act_time else 0 end) as baby_leave,
				    sum(case when note_type=1 and hr_note_id=6 then act_time else 0 end) as over_leave,
				    sum(case when note_type=1 and hr_note_id=7 then act_time else 0 end) as work_err_leave,
				    sum(case when note_type=1 and hr_note_id=8 then act_time else 0 end) as f_baby_leave
				from 
					sw_hr_table h,sw_sys_user u,sw_sys_dep d".$sql_site."
				where 
					h.user_id=u.id and u.dep_id=d.id and u.hr_status in (1,3)  and u.status=1 and user_status=1 and
					".$sql_where."
					hr_date between '".$month_arr[0]."' and '".$month_arr[1]."' and u.id in (".$manage_user_id_str.")
				group by user_id
							
				union all
							
			#sw_hr_table中未计算到考勤的人员拼接
         	select 
				d.en_name as dep_name,u.nickname,u.id as user_id,0 as out_work_time,0 as vacation_hour,0 as work_vacation_hour,0 as sick_leave,0 as marry_leave,0 as baby_leave,0 as over_leave, 0 as work_err_leave,0 as f_baby_leave
         from sw_sys_user u,sw_sys_dep d".$sql_site." where u.dep_id=d.id and u.hr_status in (1,3) and ".$sql_where."  u.id in(".$manage_user_id_str.") and u.id not in (
			select 
					u.id from
					sw_hr_table h,sw_sys_user u,sw_sys_dep d
				where 
					h.user_id=u.id and u.dep_id=d.id  and u.status=1 and user_status=1 and 
					
					hr_date between '".$month_arr[0]."' and '".$month_arr[1]."' and u.id in (".$manage_user_id_str.")
)   
    			
				".$sql_paixu;
		
		$date_list_arr=db()->query($sql);
		if(isset($_GET['excel'])){
			$this->excel_out($date_list_arr);
			exit;
		}
		$page_info['date_list']=$date_list_arr;
		
		$page_info['list']=db()->query($sql);
		
		//限制日期控件最大最小值
		$page_info['date_min']='2017-01-01';
		$page_info['date_max']=date('Y-m-d',time());

		$page_info['begin_date']=$month_arr[0];
		$page_info['end_date']=$month_arr[1];
		
		
		//搜索条件合集，站点，部门，人员
		//页面数据准备
		$page_info['site_arr']=db('sys_site')->where('status=1 and id in (1,2,3)')->select();
		$page_info['site_id']=$_POST['site_id'];
		//准备部门数据
		$dep_db_arr=db('sys_dep')->where('status=1 and is_show=1')->field('id,pid,en_name,par_id')->order('par_id')->select();
		$page_info['dep_select']=get_dep_select($dep_db_arr,'filter','dep_id','','');
		//部门
		if(isset($data['user_id'])){
			$page_info['user_id']=$data['user_id'];
		}else{
			$page_info['user_id']='all';
		}
		//部门
		if(isset($data['dep_id'])){
			$page_info['dep_id']=$data['dep_id'];
		}else{
			$page_info['dep_id']='all';
		}
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
	public function excel_out($date_list_arr){
		foreach ($date_list_arr as $key=>$d){
			unset($date_list_arr[$key]['user_id']);
		}
		Array_unshift($date_list_arr,array('员工编号','员工姓名','高标','休假时数','公假时数','病假时数','婚假时数','产假时数','丧假时数','工伤假时数','陪产假时数'));
		//$k=array('dep_name','nickname','out_work_time','vacation_hour','work_vacation_hour','sick_leave','marry_leave','baby_leave','over_leave','work_err_leave','f_baby_leave');
		$excel=array(
				'name'=>'考勤统计表',
				array(
					'data'=>$date_list_arr
				)
		);
		excel_css($excel);
	}
	
	

}




















