<?php
namespace app\sys\controller;

use think\Controller;
use app\index\controller\Admin;
use think\Db;


class NoteList extends Admin
{
	/**
	 * 考勤申请单查询统计
	 */
	public function index() {
		$page_info=array();
		$data = $this->param;

		
		//cal_tb_param('hr_note',$data);
		
		//准备站点数据
		$page_info['site_info']=config('site_info');
		
		//准备部门数据
		$page_info['dep_info']=config('dep_info');

		//准备用户数据
		$page_info['user_info']=config('user_info');
		
		//准备申请单类型数据
		$page_info['note_type_info'] = config('hr_note_type');
		
		//获取上月时间范围
		$page_info['month_range'] = get_begin_last_date(get_last_month());
		
		$this->assign('page_info',$page_info);
		return $this->fetch();
	}

	//输出申请单表格数据
	public function get_note_data(){
//		$param = $this->param;
		//获取登录用户的站点
		$id = session('login_id');
		$site_id = get_cache_data('user_info',$id,'site_id');

		$param = $_POST;
		$where_str = "";
		//分页
		$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
		$rows = isset($_POST['rows']) ? intval($_POST['rows']) : 30;
		$limit = 0+($page-1)*30;



		$last_month_range = get_begin_last_date(get_last_month());

		if(isset($param['data'])){
			$site_ID = $param['data'][0]['value'];
			$dep_Id = $param['data'][1]['value'];
			$note_TYPE = $param['data'][2]['value'];
			$hr_note_ID = $param['data'][3]['value'];
			$begin_Date = $param['data'][4]['value'];
			$end_Date = $param['data'][5]['value'];
			$note_Step = $param['data'][6]['value'];
			$note_step_status = $param['data'][7]['value'];
			$KEY = trim($param['data'][8]['value']);
			//查询站点
			$site_flag = get_site_code($site_id);


			//申请单是否通过
			if($note_step_status == "0" || $note_step_status == "1"){
				$where_str .= " and n.note_check_status=".$note_step_status;
			}

			if(!empty($site_ID)){
				$where_str .= " and u.site_id=".$site_ID;
			}
			if(!empty($dep_Id)){
				$where_str .= " and d.id=".$dep_Id;
			}
			if(!empty($note_TYPE)){
				$where_str .= " and n.note_type=".$note_TYPE;;
			}
			if(!empty($hr_note_ID)){
				$where_str .= " and n.hr_note_id=".$hr_note_ID;
			}
			if(strlen($begin_Date)>0 && strlen($end_Date)>0){
				if(is_date($begin_Date) && is_date($end_Date)){
					$where_str .=" and ((n.begin_time between '".$begin_Date." 00:00:00' and '".$end_Date." 23:59:59') or (n.end_time between '".$begin_Date." 00:00:00' and  '".$end_Date." 23:59:59')) ";

				}else{
					$where_str .=" and ((n.begin_time between '".$last_month_range[0]." 00:00:00' and '".$last_month_range[1]."  23:59:59') or (n.end_time between '".$last_month_range[0]." 00:00:00' and '".$last_month_range[1]." 23:59:59') ";
				}

			}else{
				$where_str .=" and ((n.begin_time between '".$last_month_range[0]." 00:00:00' and '".$last_month_range[1]." 23:59:59') or (n.end_time between '".$last_month_range[0]." 00:00:00' and  '".$last_month_range[1]." 23:59:59')) ";
			}
			if(!empty($note_Step)){

			}
			if($note_Step !=0 && $note_Step == 1){
				if($note_Step ==1){
					$where_str .= " and n.note_step=4 ";
				}else{
					$where_str .= " and n.note_step <>4 and note_type in (1,3) ";
				}
			}

			if($note_Step == 2){
				$where_str .= " and n.note_step <>4 and note_type in (1,3) ";
			}
			if(strlen($KEY)>0){
				$where_str .= " and ( u.user_gh like '%".$KEY."%' or n.id='".$KEY."' or u.email like '%".$KEY."%' or u.nickname like '%".$KEY."%' ) ";
			}

		}else{
			$where_str .=" and ((n.begin_time between '".$last_month_range[0]." 00:00:00' and '".$last_month_range[1]." 23:59:59') or (n.end_time between '".$last_month_range[0]." 00:00:00' and  '".$last_month_range[1]." 23:59:59')) ";
		}

		//站点设置
		$site_flag = get_site_code($site_id);



				
		if(isset($data['sort'])){
			$order_str=$data['sort'];
		}else{
			$order_str=" user_gh ";
		}
		
//		$site_id=$_SESSION['hr_note']['site_id'];
//		$dep_id=$_SESSION['hr_note']['dep_id'];
//		$note_type=$_SESSION['hr_note']['note_type'];
//		$hr_note_id=$_SESSION['hr_note']['hr_note_id'];
//		$begin_date=$_SESSION['hr_note']['begin_date'];
//		$end_date=$_SESSION['hr_note']['end_date'];
//		$note_step=$_SESSION['hr_note']['note_step'];
//		$key=$_SESSION['hr_note']['key'];
//		$last_month_range=get_begin_last_date(get_last_month());
//
//		//判断是否有传入时间段,默认查询上月日期范围申请单
//		if(strlen($begin_date)>0 && strlen($end_date)>0){
//
//			if(is_date($begin_date) && is_date($end_date)){
//				$where_str=" and ((n.begin_time between '".$begin_date." 00:00:00' and '".$end_date." 23:59:59') or (n.end_time between '".$begin_date." 00:00:00' and  '".$end_date." 23:59:59')) ";
//			}else{
//				$where_str=" and ((n.begin_time between '".$last_month_range[0]." 00:00:00' and '".$last_month_range[1]."  23:59:59') or (n.end_time between '".$last_month_range[0]." 00:00:00' and '".$last_month_range[1]." 23:59:59') ";
//			}
//		}else{
//			$where_str=" and ((n.begin_time between '".$last_month_range[0]." 00:00:00' and '".$last_month_range[1]." 23:59:59') or (n.end_time between '".$last_month_range[0]." 00:00:00' and  '".$last_month_range[1]." 23:59:59')) ";
//		}
//
//
//
//
//		//部门过滤
//		if($dep_id !=0){
//			$where_str .= " and d.id=".$param['dep_id'];
//		}
//
//		//假单类型过滤
//		if($note_type !=0){
//			$where_str .= " and n.note_type=".$param['note_type'];
//		}
//
//		//请假单类型过滤
//		if($hr_note_id !=0){
//			$where_str .= " and n.hr_note_id=".$param['hr_note_id'];
//		}
//
//		//状态过滤
//		if($note_step !=0 && $note_type==1){
//			if($note_step==1){
//				$where_str .= " and n.note_step=4 ";
//			}else{
//				$where_str .= " and n.note_step <>4 and note_type in (1,4) ";
//			}
//		}
//
//		if($note_step==2){
//			$where_str .= " and n.note_step <>4 and note_type in (1,4) ";
//		}
//
//		//关键字过滤
//		if(strlen($key)>0){
//			$where_str .= " and ( u.user_gh like '%".$param['key']."%' or n.id='".$param['key']."' or u.email like '%".$param['key']."%' or u.nickname like '%".$param['key']."%' ) ";
//		}

		//id、公号、姓名、开始时间排序
		if(isset($_POST['sort'])){
			$sort = trim($_POST['sort']);
			$order = $_POST['order'];
			if($sort == "id"){
				$where_str .= " order by n.id ".$order."";
			}else if($sort == "user_gh"){
				$where_str .= " order by u.user_gh ".$order;
			}else if($sort == "nickname"){
				$where_str .= " order by u.nickname ".$order;
			}else if($sort == "begin_time"){
				$where_str .= " order by n.begin_time ".$order;
			}
		}else{
			$where_str .= " order by n.id desc";
		}


		$sql="
			   select
					n.id,n.c_time,n.note_log,u.id as user_id,d.en_name,u.user_gh,u.nickname,
				    n.note_type,n.note_type_2_flag,n.hr_note_id,n.hr_note_name,n.note_hour,
					n.age_user_id,n.begin_time,n.end_time,n.note_title,n.note_desc,n.cur_user_id,n.note_step,n.note_check_status,
					(CASE WHEN n.note_type=3 THEN
                    (SELECT entry_dt FROM sw_entry_dt_".$site_flag." WHERE emp_no=u.user_gh AND entry_date=DATE_FORMAT(n.begin_time,'%Y-%m-%d') ORDER BY entry_dt LIMIT 1)
                     ELSE
                    ''
                    END
              ) AS fist_entry
				from 
					sw_user_note n,sw_sys_user u,sw_sys_dep d
				where
					n.user_id=u.id and n.note_type in (1,3) and u.status=1 and u.user_status=1 and n.status=1 and u.site_id in (".$_SESSION['u_i']['cal_site_id_str'].") and u.dep_id=d.id  ".$where_str." limit ".$limit.",".$rows."";

		$sql1 ="
			   select
					n.id,n.c_time,n.note_log,u.id as user_id,d.en_name,u.user_gh,u.nickname,
				    n.note_type,n.note_type_2_flag,n.hr_note_id,n.hr_note_name,n.note_hour,
					n.age_user_id,n.begin_time,n.end_time,n.note_title,n.note_desc,n.cur_user_id,n.note_step,n.note_check_status
            	from
					sw_user_note n,sw_sys_user u,sw_sys_dep d
				where
					n.user_id=u.id and n.note_type in (1,3) and u.status=1 and u.user_status=1 and n.status=1 and u.site_id in (".$_SESSION['u_i']['cal_site_id_str'].") and u.dep_id=d.id  ".$where_str;

		$note_arr = db()->query($sql);
	 

		//构建表格数据
		$tb_arr=array();
		
		//考勤计算数据获取
//		$tb_arr['total'] = count($note_arr);
//		$tb_arr['total'] = Db::name('user_note')->count();

		$tb_arr['total'] = count(Db::query($sql1));

		$tb_arr['rows']=array();
		$count_note_hour=0;
		
		foreach($note_arr as $key=>$val){
			$temp_arr=array();
			$temp_arr['id']=$val['id'];
			$temp_arr['user_gh']=$val['user_gh'];
			$temp_arr['dep_name']=$val['en_name'];
			$temp_arr['nickname']=$val['nickname'];
			$temp_arr['c_time'] = $val['c_time'];
			$temp_arr['fist_entry'] = $val['fist_entry'];

			//查询最后一次审核时间


			$temp_time = count($this->getDates($val['note_log'])[0]);

			if($temp_time != 1)
			{
				$temp_arr['u_time'] = '20'.$this->getDates($val['note_log'])[0][$temp_time-1];
			}else
			{
				$temp_arr['u_time'] = '';
			}




//			$temp_arr['u_time'] = $temp_time;

			//申请单状态判断逻辑 2017-11-10 周仁杰修改
			if($val['note_step'] == 4 && $val['note_check_status'] == 1){
				$temp_arr['note_check_status'] = '通过';
			}else if($val['note_step'] == 4 && $val['note_check_status'] == 0){
				$temp_arr['note_check_status'] = '<span style="color: red;font-size: 12px;">'.'不通过'.'</span>';
			}else{
				$temp_arr['note_check_status'] = '审核中';
			}



			
			
			if($val['note_type']==1){
				$temp_arr['note_type']=get_note_type_name($val['note_type']).' - '.config('hr_note_type')[$val['hr_note_id']];
			}else{
				$temp_arr['note_type']=get_note_type_name($val['note_type']);
			}
			
			$temp_arr['age_name']=get_cache_data('user_info', $val['age_user_id'],'nickname');
			$temp_arr['note_hour']=c_z($val['note_hour']);
			$temp_arr['note_title']=$val['note_title'];			
			$temp_arr['note_desc']=s_str($val['note_desc'],50);	
			$temp_arr['cur_user']=get_cache_data('user_info', $val['cur_user_id'],'nickname');;
			$temp_arr['note_step']=get_note_step($val['note_step']);
			//请假单显示开始结束时间
			if($val['note_type']==1){
				$temp_arr['begin_time']=$val['begin_time'];
				$temp_arr['end_time']=$val['end_time'];
			}else{
				$temp_arr['begin_time']='';
				$temp_arr['end_time']='';
			}
			
			$count_note_hour += $val['note_hour'];
			array_push($tb_arr['rows'],$temp_arr);
		}

		//统计行数据获取
		$count_arr['id']='';
		$count_arr['user_id']='';
		$count_arr['user_gh']='';
		$count_arr['dep_name']='';
		$count_arr['nickname']='申请单数量';
			
		$count_arr['note_type']=$tb_arr['total'];
		$count_arr['age_name']='总时数:';
		$count_arr['note_hour']=c_z($count_note_hour);
		$count_arr['note_title']='';
		$count_arr['note_desc']='';
		$count_arr['note_step']='';		
		$count_arr['begin_time']='';
		$count_arr['end_time']='';
		/*
		$tb_arr['footer']=array();
		//pr($tb_arr);
		array_push($tb_arr['footer'],$count_arr);
		//pr(json_encode($tb_arr));
		 
		 */
		echo json_encode($tb_arr);
		
	}

	/** 2018 2/27 周仁杰修改
	 * @param null $string
	 * @return mixed
	 */
	public function getDates($string = null){
		$pattern="/\\d{1,2}((-|\/)\d{1,2}){2}(\s{0,5}\\d{1,2}(\:\d{1,2}){1,2}){0,1}/";
		preg_match_all($pattern,$string,$match);
		return $match;
	}

	
	//删除申请单
	function delete_note(){
//		$data=$this->param;
		$data['id'] = $_POST['id'];
		$date = Db::name('user_note')->field('begin_time,end_time,user_id')->where('id',$data['id'])->find();
		$user_id = $date['user_id'];
		//取出当前的站点
		$site_id = get_cache_data('user_info',$user_id,'site_id');


		//获得当前数据站点
	    $begin_date = $date['begin_time'];
		$end_date = $date['end_time'];
		$tmp_arr1 = explode('-',$begin_date);
		$tmp_arr2 = explode('-',$end_date);

		//取出年份
		$begin_date_year = abs($tmp_arr1[0]);
		$end_date_year = abs($tmp_arr2[1]);

		//取出月份
		$begin_date_month = abs($tmp_arr1[1]);
		$end_date_month = abs($tmp_arr2[1]);

		$year_date[0] = $begin_date_year;
		$year_date[1] = $end_date_year;

		$month_date[0] = $begin_date_month;
		$month_date[1] = $end_date_month;

		$check_month = Db::name('sys_event')->where('year','in',$year_date)->where('month','in',$month_date)->where('site_id',$site_id)->where('type_flag',2)->value('month');

		if($check_month){
			echo setServerBackJson(0,"申请单时间范围内考勤数据已结转,不允许删除");exit;
		}

        //申请单逻辑判断
//		if($check_month == $begin_date_month && $check_month == $end_date_month){
//			echo setServerBackJson(0,"当前申请单不能删除");exit;
//		}else if($check_month >= $begin_date_month && $check_month >= $end_date_month){
//			echo setServerBackJson(0,"当前申请单不能删除");exit;
//		}else if($check_month >= $begin_date_month  && $end_date_month > $check_month){
//			echo setServerBackJson(0,"当前申请单不能删除");exit;
//		}

		//判断申请单时间范围内考勤数据是否已经发送,如果已经发送,则申请单不允许进行任何操作 need_todo		
		db('user_note_item')->where('note_id='.$data['id'])->delete();
		
		db('user_note')->where('id='.$data['id'])->delete();
		
		echo setServerBackJson(1,'申请单ID'.$data['id'].'删除成功!');
	}

	
	
	
	
	
	
	
	
	
}
