<?php
namespace app\user\controller;

use think\Controller;
use app\index\controller\Admin;
use think\Db;

//用户首页详情
class UserMain extends Admin
{
	/**
	 * 个人用户首页
	 *///2017/11/10 18:39 wtq  2018/2/28 wtq
	public function index() {
		
		$page_info=array();
		$return_arr=array();
		
		$page_info['show_time']=isset($_GET['time'])?$_GET['time']:'';
		
		if(!strtotime($page_info['show_time'])){
			$page_info['show_time']='';
		}

		$date=$this->request->param('month');
		
		if(strlen($date)>0){
			$date .= '-01';
		}else{
			$date = date('Y-m',time()) .'-01';
		}
		$cur_user_info=session('cur_user_info');
		
		if($page_info['show_time']){
			$date=$page_info['show_time'];
		}
		
		//获取某用户某月份的考勤数据
		$return_arr=get_user_month_hr($date,get_user_id());
		
		$page_info['site_id']=session('cur_user_info')['site_id'];
		
		$page_info['hr_json']=json_encode($return_arr);
		$page_info['user_info']=session('cur_user_info');	
		$page_info['boss_id']=CONFIG('BOSS_ID');
		$page_info['cur_date']=date('Y-m-d',time());
		
		//获取当前用户本日第一次打卡数据
		if($cur_user_info['out_site_id']>0){
			$temp_site_id=$cur_user_info['out_site_id'];
		}else{
			$temp_site_id=$cur_user_info['site_id'];
		}
		
		if($page_info['user_info']['out_site_id']>0){
			//出差人员抓卡号
			$sql="select min(entry_dt) as first_card from sw_entry_dt_".get_site_code($temp_site_id)." where entry_date='".date('Y-m-d',time())."' and card_no='".$cur_user_info['card_id']."'";
		}else{
			//非出差人员抓工号
			$sql="select min(entry_dt) as first_card from sw_entry_dt_".get_site_code($temp_site_id)." where entry_date='".date('Y-m-d',time())."' and emp_no='".$cur_user_info['user_gh']."'";
		}
		
		$first_card_arr=db()->query($sql);
		
		if(strlen($first_card_arr[0]['first_card'])>0){
			$page_info['first_card']='本日首次打卡:'.$first_card_arr[0]['first_card'];
		}else{
			$page_info['first_card']='本日还无打卡.';
		}
		
		//获取此用户管理人员ID
		$page_info['manage_user_id']=get_hr_manage_user_id($cur_user_info['id']);
		
		if(get_cache_data('user_info',get_user_id(),'is_hr_manage')==1){
			$page_info['hr_manage']=1;			
		}else{
			$page_info['hr_manage']=0;
		}
		
		
		/*获得当前用户的所有权限*/
		$pri_arr = get_session_privilege();
		/*获得当前模块和控制*/
		$module_controller = get_current_url();
		$page_info['pri_data'] = $pri_arr;
		$page_info['module_controller'] = $module_controller;
		
		$this->assign('page_info',$page_info);
		return $this->fetch();
	}
	
	/**
	 * 刷新本月考勤数据
	 */
	public function calculate_user_hr(){
		$data=date('Y-m-01',time());
		return json_encode(calculate_user_hr($data));		
	}

	/**
	 * 查看某日考勤数据
	 */
	public function get_day_hr_cal(){
		$data=$this->param;
		$page_info=array();
		
		if(isset($data['user_id'])){
			$user_id=$data['user_id'];
		}else{
			$user_id=get_user_id();
		}
		
		//判断是否获取到日期参数
		if(!isset($data['hr_date'])){
			$page_info['hr_info']='考勤日期获取失败!无法获取数据';
		}else{
			//判断日期范围
			if(strtotime($data['hr_date'])>=strtotime(date('Y-m-d'),time())){
				$page_info['hr_info']='本日&本日后考勤数据计算结果未出,无法查看!';				
			}else{
				$hr_info=db('hr_table')->where('user_id='.$user_id." and hr_date='".$data['hr_date']."'")->find();						
				$page_info['hr_info']=$hr_info['hr_status_remark'];
			}
		}
		
		$page_info['card_info']=get_user_day_card_info($data['hr_date'],$user_id);
		//pr($page_info['card_info']);
		$page_info['card_num_count']=0;
		foreach ($page_info['card_info'] as $k=>$v){
			if(($v['ctrl_id']==23 || $v['ctrl_id']==2 || $v['ctrl_id']==31) && $v['status']==1){
				$page_info['card_num_count']++;
			}
		}
		
		$this->assign('page_info',$page_info);
		return  $this->fetch();
		
	}
	
	/**
	 * 获取某用户某月份考勤数据,返回json值
	 */
	public function get_user_month_hr(){
		$data=$this->param;
		$date=$data['date'];		
		
		if(isset($data['user_id'])){
			$user_id=$data['user_id'];
		}else{
			$user_id=get_user_id();
		}
		
		//获取某用户某月份的考勤数据
		$return_arr=get_user_month_hr($date,$user_id);
		echo json_encode($return_arr['events']);
	}
	
	/**
	 * 下属员工列表
	 *///王天棋2017.9.27修改
	public function sub_user_list(){
		$user_id=get_user_id();
		$id_str=get_user_sub_user($user_id);		
		$page_info=array();
		
		$map['id']=array('in',$id_str);	
		$map['user_status']=1;
		$map['status']=1;
		$page_info['site_id']=isset($_POST['site_id'])?$_POST['site_id']:'';
		if($page_info['site_id'] && $page_info['site_id']!='all'){
			$map['site_id']=$page_info['site_id'];
		}
		$page_info['dep_id']=isset($_POST['dep_id'])?$_POST['dep_id']:'';
		if($page_info['dep_id'] && $page_info['dep_id']!='all'){
			$map['dep_id']=$page_info['dep_id'];
		}
		$page_info['user_id']=isset($_POST['user_id'])?$_POST['user_id']:'';
		if($page_info['user_id'] && $page_info['user_id']!='all'){
			$map['id']=$page_info['user_id'];
		}
		//$map['id']=array('neq',$user_id);
		$name= "sys_user";
		$model = db($name);
		
		
		//排序
		$sort_name=isset($_GET['ii'])?$_GET['ii']:'dep_id';
		$zorf=isset($_GET['a'])?$_GET['a']:1;
		$page_info['ii']=array($sort_name,$zorf);
		if($zorf==1){$zorf=false;}else{$zorf=true;}
		
		
		
		if (! empty ( $model )) {
			$page_info['list']=$this->_list($model,$map,$sort_name,$zorf);
		}
		if(isset($_GET['excel'])){
			$this->excel_out($page_info['list']);
			exit;
		}
		$page_info['page']=$page_info['list']->render();
		
		
		//搜索条件合集，站点，部门，人员
		//页面数据准备
		$page_info['site_arr']=db('sys_site')->where('status=1 and id in (1,2,3)')->select();
		if(!isset($data['site_id'])){
			$site_id=1;
		}else{
			$site_id=$data['site_id'];
		}
		$page_info['site_id']=$site_id;
		$page_info['site_id']=isset($_POST['site_id'])?$_POST['site_id']:'all';
		//准备部门数据
		$dep_db_arr=db('sys_dep')->where('status=1 and is_show=1')->field('id,pid,en_name,par_id')->order('par_id')->select();
		$page_info['dep_select']=get_dep_select($dep_db_arr,'filter','dep_id','','');
		//部门
		if(isset($data['user_id'])){
			$page_info['user_id']=$data['user_id'];
		}else{
			$page_info['user_id']='all';
		}
		$page_info['user_id']=isset($_POST['user_id'])?$_POST['user_id']:'all';
		//部门
		if(isset($data['dep_id'])){
			$page_info['dep_id']=$data['dep_id'];
		}else{
			$page_info['dep_id']='all';
		}
		$page_info['dep_id']=isset($_POST['dep_id'])?$_POST['dep_id']:'all';
		if(get_cache_data('user_info',$user_id,'all_card_flag')==0){
			//用户
			$manage_user_id=get_user_sub_user($user_id);
			$page_info['user_arr']=db('sys_user')->where('hr_status in (1,3) and id in('.$manage_user_id.') and user_status=1 and site_id in (1,2,3)')->field('id,dep_id,nickname,site_id')->select();
		}else{
			$page_info['user_arr']=db('sys_user')->where('hr_status in (1,3) and user_status=1 and site_id in (1,2,3)')->field('id,dep_id,nickname,site_id')->select();
		}
		
		$page_info['date_max']=date('Y-m-d',time());
		
		
		$this->assign('page_info',$page_info);
		return $this->fetch();
	}
	//表格导出
	public function excel_out($list){
		$data[]=array('工号','姓名','性别','部门','分机','站点','主管');
		$data_title=array('user_gh','nickname','sex','dep_id','ext_tel','site_id','hr_user_id');
		foreach ($list as $l){
			$data_body='';
			foreach ($data_title as $d){
				foreach ($l as $key=>$ll){
					if($key==$d){
						if($d=='sex'){
							$ll=$ll ? '男' : '女';
						}
						if($d=="dep_id"){
							$ll=get_cache_data('dep_info',$ll,'en_name');
						}
						if($d=="site_id"){
							$ll=get_cache_data('site_info',$ll,'site');
						}
						if($d=="hr_user_id"){
							$ll=get_cache_data('user_info',$ll,'nickname');
						}
						$data_body[$key]=$ll;
					}
				}
			}
			$data[]=$data_body;
		}
		$excel=array(
				'name'=>'员工列表',
				array(
						'data'=>$data
				)
		);
		excel_css($excel);
	}
	
	//查看某员工考勤
	public function user_hr(){
		$user_id=$this->request->param('user_id');
		$page_info=array();
		$return_arr=array();
		$user_info=db('sys_user')->where('id='.$user_id)->find();
	
		$date=date('Y-m-d',time());
		//获取某用户某月份的考勤数据
		$return_arr=get_user_month_hr($date,$user_id);
		
		$page_info['site_id']=$user_info['site_id'];
	
		$page_info['hr_json']=json_encode($return_arr);
		$page_info['user_info']=$user_info;
	
		$this->assign('page_info',$page_info);
		return $this->fetch();
	}	
	
	/*
	 * 用户申请单列表
	 */
	public function note_list(){
		
		$model = db('user_note');
		
		$map['user_id']=get_user_id();
		$map['status']=1;
		
		if (! empty ( $model )) {
			$page_info['list']=$this->_list($model,$map,'id');
		}
		$page_info['page']=$page_info['list']->render();
		
		//计算需要指定代理人时间(h)
		$page_info['age_time_val']=config('hr_holiday_manage_val')*8;
		
		$this->assign('page_info',$page_info);
		
		return  $this->fetch();		
	}
	
	/*
	 * 新增用户申请单
	 */
	public function add_note(){
		if (IS_POST) {			
			$data=$this->param;
			$insert_flag=true;
			$msg="";
			$return_tit="";				//反馈用户标题文字
			$user_id=get_user_id();
						
			$insert_arr=array();
			$insert_arr['user_id']=$user_id;	
			$insert_arr['note_type']=$data['note_type'];
			$data['begin_time']=$data['begin_date']." ".$data['begin_h'].":".$data['begin_m'].":00";
			$data['end_time']=$data['end_date'];
			
			switch ($data['note_type']){
				case 1:
					//请假申请单
					/*
					//判断是否填写开始时间,结束时间
					if(strlen($data['begin_time'])<18 || strlen($data['end_time'])<18){
						$insert_flag=false;
						$msg="开始时间 或 结束时间错误,请检查此2项数据!";
						break;
					}
					*/
					
					//避免时间刚好重叠的点,允许填写申请单
					
					//note_type=1的单据时间段不允许重复. 但有整点相等情况,如9:00~10:00  10:00~11:00
					
					/*$sql_ban="and (note_step!=4 or (hr_check_status<>0))";
					//啦啦啦
					$is_hava_note=db('user_note')->where("user_id=$user_id and (begin_time<='".$data['begin_time']."' and '".$data['begin_time']."'<end_time) or (begin_time<'".$data['end_time']."' and '".$data['end_time']."'<=end_time) or (begin_time<'".$data['begin_time']."' and end_time<'".$data['end_time']."') and status=1 $sql_ban and note_type=1")->find();
					if($is_hava_note){
						$insert_flag=false;
						$msg="hhh";
						break;
					}*/
					
					
					
					//开始时间范围判断
					$sql_ban="and (note_step!=4 or (hr_check_status<>0))";
					$is_hava_note=db('user_note')->where('user_id='.$user_id." and '".$data['begin_time']."' between date_add(begin_time,interval 1 SECOND) and date_sub(end_time,interval 1 SECOND) and status=1 $sql_ban and note_type=1")->find();
					
					if($is_hava_note){
						$insert_flag=false;
						$msg="申请单日期范围内有重复申请单,ID为".$is_hava_note['id'].",请核对申请单日期范围!";
						break;
					}
					
					//结束时间短判断
					$is_hava_note=db('user_note')->where('user_id='.$user_id." and '".$data['end_time']."' between date_add(begin_time,interval 1 SECOND) and date_sub(end_time,interval 1 SECOND) and status=1 $sql_ban and note_type=1")->find();
					if($is_hava_note){
						$insert_flag=false;
						$msg="申请单日期范围内有重复申请单,ID为".$is_hava_note['id'].",请核对申请单日期范围!";
						break;
					}
					
					//时间完全相等判断
					$is_hava_note=db('user_note')->where('user_id='.$user_id." and begin_time='".$data['begin_time']."' and end_time='".$data['end_time']."' and status=1 $sql_ban and note_type=1")->find();
					if($is_hava_note){
						$insert_flag=false;
						$msg="申请单日期范围内有重复申请单,ID为".$is_hava_note['id'].",请核对申请单日期范围!";
						break;
					}
					
					//判断结束日期是否大于等于开始日期
					if(strtotime($data['end_time'])<=strtotime($data['begin_time'])){
						$insert_flag=false;
						$msg="申请单开始日期大于结束日期,请检查数据!";
						break;
					}
					
					//判断是否小于1小时
					if($data['note_hour']<1){
						$insert_flag=false;
						$msg="申请单不能小于1小时!";
						break;
					}
					
					//判断是否填写请假时数
					if(strlen($data['note_hour'])==0){
						$insert_flag=false;
						$msg="请假时数不能为空!";
						break;
					}
					
					//判断时间差是否小于填写时间数
					$time_diff_arr=timediff($data['begin_time'], $data['end_time']);
						
					if($time_diff_arr['h']<$data['note_hour']){
						$insert_flag=false;
						$msg="请假所选日期范围小于申请时间数,请检查数据!";
						break;
					}												
					
					//判断是否填写请假标题
					if(strlen($data['note_title'])==0){
						$insert_flag=false;
						$msg="标题不能为空!";
						break;
					}
					
					$insert_arr['hr_note_id']=$data['hr_note_id'];
					$insert_arr['hr_note_name']=config('hr_note_type')[$insert_arr['hr_note_id']];
					$insert_arr['note_hour']=$data['note_hour'];
					$insert_arr['begin_time']=$data['begin_time'];
					$insert_arr['end_time']=$data['end_time'];
					$insert_arr['note_title']=$data['note_title'];
					$insert_arr['note_desc']=$data['note_desc'];
					$insert_arr['note_step']=2;					//默认考勤主管审核
					$insert_arr['cur_user_id']=session('cur_user_info')['hr_user_id'];
					
					//如果申请时间超过指定代理人时间,获取代理人参数
					$manage_time=config('hr_holiday_manage_val')*8;
					if($data['note_hour']>$manage_time){
						$insert_arr['age_user_id']=$data['age_user_id'];
						$insert_arr['cur_user_id']=$data['age_user_id'];
						//设置申请单流程状态下一步为代理人审核
						$insert_arr['note_step']=1;
					}
					
					//获取此人考勤主管ID
					$insert_arr['hr_user_id']=session('cur_user_info')['hr_user_id'];
					
					//如果超过hr_holiday_adv_manage_val设定时间,需要转上级审核
					if($data['note_hour']>config('hr_holiday_adv_manage_val')){
						$insert_arr['hr_adv_user_id']=get_hr_user_id($user_id,'hr_adv_user_id');
					}
					$return_tit="请假单(".$insert_arr['hr_note_name'].") 提交成功!";
					
					break;
				case 2:
					//晚餐预订
					//判断是否超过晚餐预订最晚时间
					$hr_supper_last_time=config('hr_supper_last_time');
					$cur_time=date('H:i',time());
					$cur_date=date('Y-m-d',time());
					if($cur_time>$hr_supper_last_time){
					//if(1==2){
						$insert_flag=false;
						$msg="已经超过本日最晚订餐时间 ".$hr_supper_last_time." ,下次记得早点填单 ^_^!";
						break;
					}else{
						
						//判断本日是否已经提交晚餐申请单
						$is_hava_note=db('user_note')->where('user_id='.$user_id." and begin_time='".$cur_date." 00:00:00"."' and status=1 and note_type=2")->find();
						if($is_hava_note){
							$insert_flag=false;
							$msg="本日(".$cur_date.")晚餐申请单已经提交,请勿重复提交!";
							break;
						}
						
						$insert_arr['note_type_2_flag']=$data['note_type_2_flag'];
						
						if($insert_arr['note_type_2_flag']==1){
							$return_tit="本日晚餐申请单(管理部代购)提交成功!";
						}else{
							$return_tit="本日晚餐申请单提交成功!";
						}
						$insert_arr['hr_note_name']='晚餐';
						$insert_arr['note_title']=$cur_date.'日晚餐';
						$insert_arr['begin_time']=$cur_date;
						$insert_arr['end_time']=$cur_date;
						$insert_arr['note_step']=4;
					}					
					break;
				case 3:
					//晚到申请单
					//判断是否填写开始时间,结束时间
					if(strlen($data['begin_time'])<10){
						$insert_flag=false;
						$msg="开始时间,请检查此项数据!";
						break;
					}
					
					//判断本日开始时间是否已经有申请单,如果有的话,当日不允许2张申请单
					$is_hava_note=db('user_note')->where('user_id='.$user_id." and '".$data['begin_time']."' between date_add(begin_time,interval 1 SECOND) and date_sub(end_time,interval 1 SECOND)  and status=1 and note_type=3")->find();
					if($is_hava_note){
						$insert_flag=false;
						$msg="申请单日期范围内有重复申请单,ID为".$is_hava_note['id'].",请核对申请单日期范围!";
						break;
					}
					
					//判断本日结束时间是否已经有申请单,如果有的话,当日不允许2张申请单
					$is_hava_note=db('user_note')->where('user_id='.$user_id." and '".$data['end_time']."' between date_add(begin_time,interval 1 SECOND) and DATE_SUB(end_time,INTERVAL 1 SECOND)  and status=1 and note_type=3")->find();
					if($is_hava_note){
						$insert_flag=false;
						$msg="申请单日期范围内有重复申请单,ID为".$is_hava_note['id'].",请核对申请单日期范围!";
						break;
					}
					
					//判断本日是否已经填写晚到申请单,不允许填写2张申请单
					$is_hava_3_note=db('user_note')->where('user_id='.$user_id." and note_type=3 and date_format(begin_time,'%Y-%m-%d')='".$data['begin_date']."'  and status=1")->find();
					if($is_hava_3_note){
						$insert_flag=false;
						$msg="本日内已经有晚到申请单,ID为".$is_hava_3_note['id'].",请核对!";
						break;
					}
					
					//判断是否填写请假标题
					if(strlen($data['note_title'])==0){
						$insert_flag=false;
						$msg="标题不能为空!";
						break;
					}					
					
					$insert_arr['hr_note_name']='晚到';
					//$insert_arr['note_hour']=$data['note_hour'];
					$insert_arr['begin_time']=date('Y-m-d',strtotime($data['begin_time']));
					$insert_arr['end_time']=date('Y-m-d',strtotime($data['begin_time']))." 23:59:59";
					$insert_arr['note_title']=$data['note_title'];
					$insert_arr['note_desc']=$data['note_desc'];
					$insert_arr['note_step']=2;
					
					//获取此人考勤主管ID
					$insert_arr['hr_user_id']=session('cur_user_info')['hr_user_id'];					
					$insert_arr['cur_user_id']=session('cur_user_info')['hr_user_id'];
					
					$return_tit="晚到申请单提交成功!";
					
					break;
				case 4:
					//外出申请单
					//判断是否填写开始时间,结束时间
					if(strlen($data['begin_time'])<18 || strlen($data['end_time']<18)){
						$insert_flag=false;
						$msg="开始时间 或 结束时间错误,请检查此2项数据!";
						break;
					}
					
					//判断本日开始时间是否已经有申请单,如果有的话,当日不允许2张申请单
					$is_hava_note=db('user_note')->where('user_id='.$user_id." and '".$data['begin_time']."' between begin_time and end_time  and status=1")->find();
					if($is_hava_note){
						$insert_flag=false;
						$msg="申请单日期范围内有重复申请单,ID为".$is_hava_note['id'].",请核对申请单日期范围!";
						break;
					}
					
					//判断本日结束时间是否已经有申请单,如果有的话,当日不允许2张申请单
					$is_hava_note=db('user_note')->where('user_id='.$user_id." and '".$data['end_time']."' between begin_time and end_time  and status=1")->find();
					if($is_hava_note){
						$insert_flag=false;
						$msg="申请单日期范围内有重复申请单,ID为".$is_hava_note['id'].",请核对申请单日期范围!";
						break;
					}

					//判断结束日期是否大于等于开始日期
					if(strtotime($data['end_time'])<=strtotime($data['begin_time'])){
						$insert_flag=false;
						$msg="申请单开始日期大于结束日期,请检查数据!";
						break;
					}
					
					//判断是否填写请假时数
					if(strlen($data['note_hour'])==0){
						$insert_flag=false;
						$msg="请假时数不能为空!";
						break;
					}
					
					//判断时间差是否小于填写时间数
					$time_diff_arr=timediff($data['begin_time'], $data['end_time']);
						
					if($time_diff_arr['h']<$data['note_hour']){
						$insert_flag=false;
						$msg="请假所选日期范围小于申请时间数,请检查数据!";
						break;
					}
					
					//判断是否填写请假标题
					if(strlen($data['note_title'])==0){
						$insert_flag=false;
						$msg="标题不能为空!";
						break;
					}
					
					$insert_arr['hr_note_name']='外出';
					$insert_arr['note_hour']=$data['note_hour'];
					$insert_arr['begin_time']=$data['begin_time'];
					$insert_arr['end_time']=$data['end_time'];
					$insert_arr['note_title']=$data['note_title'];
					$insert_arr['note_desc']=$data['note_desc'];
					$insert_arr['note_step']=0;
										
					//获取此人考勤主管ID
					$insert_arr['hr_user_id']=session('cur_user_info')['hr_user_id'];
					
					$return_tit="外出申请单单提交成功!";
					break;
			}
			
			if(!$insert_flag){
				echo setServerBackJson(0,$msg);
				exit;
			}else{
				$insert_arr['c_time']=date('Y-m-d H:i:s',time());
				
				//写入申请单日志
				$insert_arr['note_log']="[".date('Y-m-d H:i:s',time())."] 用户".get_user_nickname().'创建申请单!<br>';

				//插入申请单主表
				$id=db('user_note')->insertGetId($insert_arr);
				$note_step=$insert_arr['note_step'];
				
				//非请假单,不审核,直接写入申请单附表,请假单需主管审核后放正式计算
				if($insert_arr['note_type'] == 2 || $insert_arr['note_type'] == 4){
					$insert_arr['note_id']=$id;
					unset($insert_arr['note_title']);
					unset($insert_arr['note_desc']);
					unset($insert_arr['c_time']);
					unset($insert_arr['note_log']);										
					unset($insert_arr['hr_user_id']);
					unset($insert_arr['hr_note_name']);
					unset($insert_arr['note_step']);
					
					db('user_note_item')->insert($insert_arr);
				}
				
				if($id){					
					//如果为代理审核申请单,则通知代理人
					if($note_step==1){
						//获取代理人邮件
						$age_user_email=get_cache_data('user_info', $insert_arr['age_user_id'],'email');
						$msg= session('cur_user_info')['nickname'].' 申请 '.$insert_arr['begin_time'].'~'.$insert_arr['end_time']." 休假,请你予以代理!<br /><br />请及时登录系统: <a href='".config('SYS_URL')."'>".config('SYS_URL')."</a> 予以审核!";
						$is_send=send_email($age_user_email, '内管代理审核通知', $msg);
						if($is_send){
							$return_tit .= "邮件通知代理人 ".get_cache_data('user_info', $insert_arr['age_user_id'],'nickname').'成功!';
						}else{
							$return_tit .= "邮件通知代理人 ".get_cache_data('user_info', $insert_arr['age_user_id'],'nickname').'失败!'.$is_send;
						}
						
					}
					echo setServerBackJson(1,$return_tit,$data['from'],'closeDialog');
					exit;
				}else{
					echo setServerBackJson(0,'申请单提交失败,请截屏后联系管理员!');
					exit;
				}
			}
		}else{
			
			$page_info=array();
			$user_id=get_user_id();
			$page_info['cur_user_info']=session('cur_user_info');
			$page_info['note_type_info']=config('hr_note_type');
			$page_info['hr_role_str']=config('hr_role_info')[$page_info['cur_user_info']['hr_role_id']]['begin_time']."~".config('hr_role_info')[$page_info['cur_user_info']['hr_role_id']]['end_time'];
			$page_info['year_holiday_day']=get_user_year_holiday_day($user_id);
			$page_info['user_hr_holiday']=get_user_hr_holiday($user_id);
			$page_info['from']=input('from');
			$page_info['hr_date']=input('hr_date');
			$page_info['cur_date']=date('Y-m-d',time());
			$page_info['hr_date_info']=array();
			$page_info['begin_work_time']=config('hr_role_info')[$page_info['cur_user_info']['hr_role_id']]['begin_time'];
						
			//获取用户当前班次的时间节点
			$begin_time_arr=explode(':', $page_info['begin_work_time']);
			if(strlen($begin_time_arr[0])==1){
				$begin_time_arr[0]='0'.$begin_time_arr[0];
			}
			
			$page_info['begin_h']=$begin_time_arr[0];
			$page_info['begin_m']=$begin_time_arr[1];
			
			//判断此人考勤主管是否设定,如果未设定,则给定错误信息
			if($user_id<>config('BOSS_ID') && $page_info['cur_user_info']['hr_user_id']==0){
				echo '考勤主管未设置,无法填写申请单!';
				exit();
			}
			
			if($user_id==config('BOSS_ID')){
				echo '老板...不填申请单!';
				exit();
			}
			
			//计算需要指定代理人时间(h)
			$page_info['age_time_val']=config('hr_holiday_manage_val')*8;
			
			//获取当前用户的代理人
			$page_info['age_info']=get_user_age_info($user_id);
			
			//如果是从日历异常班次传递过来的申请单
			if(strlen($page_info['hr_date'])>0){
				$page_info['auto_sqd']=1;
				//当前日期开始工作时间
				$cur_date_begin_time=$page_info['hr_date'].' '.config('hr_role_info')[$page_info['cur_user_info']['hr_role_id']]['begin_time'];
				$page_info['hr_date_info']=db('hr_table')->where('user_id='.$user_id." and hr_date='".$page_info['hr_date']."'")->find();

				if($page_info['hr_date_info']['z_work_need_time']=='9.0'){
					$page_info['hr_date_info']['z_work_need_time']='8';
					$page_info['hr_date_info']['end_date']=$page_info['hr_date'].' '.config('hr_role_info')[$page_info['cur_user_info']['hr_role_id']]['end_time'];
				}else{
					if($page_info['hr_date_info']['abs_time']>=4){
						$temp_h=$page_info['hr_date_info']['abs_time']+1;						
					}else{
						$temp_h=$page_info['hr_date_info']['abs_time'];
					}
					$page_info['hr_date_info']['end_date']=date('Y-m-d H:i:s',strtotime($cur_date_begin_time)+$temp_h*60*60);
				}
				$page_info['sqd_json']=json_encode($page_info['hr_date_info']);				
			}else{
				$page_info['auto_sqd']=0;
				$page_info['sqd_json']=json_encode($page_info['hr_date_info']);	
			}
			
			$page_info['card_info']=get_user_day_card_info($page_info['hr_date'],$user_id);
			$page_info['card_num_count']=0;
			foreach ($page_info['card_info'] as $k=>$v){
				if(($v['ctrl_id']==23 || $v['ctrl_id']==2 || $v['ctrl_id']==31) && $v['status']==1){
					$page_info['card_num_count']++;
				}
			}
			
			$this->assign('page_info',$page_info);
			return  $this->fetch();
		}
	}
	
	//反馈页面 
	public function ifation(){
		$this->assign('data_time',isset($_GET['data_time'])?$_GET['data_time']:'');
		return  $this->fetch();
	}
	//发送反馈
	public function ifation_go(){
		$q_val=isset($_POST['q_val'])?$_POST['q_val']:'';
		if($q_val==''){echo json_encode(array('0','请描述'));}
		$mail_flag=isset($_POST['mail_flag'])?$_POST['mail_flag']:'';
		$data_time=isset($_POST['data_time'])?$_POST['data_time']:'';
		$page_info=array(
				'q_type'=>2,
				'q_val'=>$data_time.'考勤问题描述：'.$q_val,
				'user_id'=>get_user_id(),
				'create_time'=>date("Y-m-d H:i:s"),
				'mail_flag'=>$mail_flag,
				'q_time'=>$data_time
		);
		db('q_a')->insert($page_info);
		echo json_encode(array('1','提交完成'));
	}
	

	/*
	 * 编辑用户申请单
	 */
	public function edit_note(){
		if (IS_POST) {
			$data=$this->param;
			$insert_flag=true;
			$msg="";
			$return_tit="";				//反馈用户标题文字
			$user_id=get_user_id();
			$note_id=$data['note_id'];
			$note_info=db('user_note')->where('id='.$note_id)->find();
			
			$insert_arr=array();
			$insert_arr['id']=$data['note_id'];
			$insert_arr['user_id']=$user_id;
			$insert_arr['note_type']=$data['note_type'];
			$data['begin_time']=$data['begin_date']." ".$data['begin_h'].":".$data['begin_m'].":00";
			$data['end_time']=$data['end_date'];
				
			switch ($data['note_type']){
				case 1:
					//判断是否填写开始时间,结束时间
					if(strlen($data['begin_time'])<18 || strlen($data['end_time']<18)){
						$insert_flag=false;
						$msg="开始时间 或 结束时间错误,请检查此2项数据!";
						break;
					}
						
					//判断本日开始时间是否已经有申请单,如果有的话,当日不允许2张申请单
					$is_hava_note=db('user_note')->where('user_id='.$user_id." and '".$data['begin_time']."' between date_add(begin_time,interval 1 SECOND) and date_sub(end_time,interval 1 SECOND) and status=1 and id<>".$note_id)->find();
					if($is_hava_note){
						$insert_flag=false;
						$msg="申请单日期范围内有重复申请单,ID为".$is_hava_note['id'].",请核对申请单日期范围!";
						break;
					}
						
					//判断本日结束时间是否已经有申请单,如果有的话,当日不允许2张申请单
					$is_hava_note=db('user_note')->where('user_id='.$user_id." and '".$data['end_time']."' between date_add(begin_time,interval 1 SECOND) and date_sub(end_time,interval 1 SECOND) and status=1 and id<>".$note_id)->find();
					if($is_hava_note){
						$insert_flag=false;
						$msg="申请单日期范围内有重复申请单,ID为".$is_hava_note['id'].",请核对申请单日期范围!";
						break;
					}
						
					//判断结束日期是否大于等于开始日期
					if(strtotime($data['end_time'])<=strtotime($data['begin_time'])){
						$insert_flag=false;
						$msg="申请单开始日期大于结束日期,请检查数据!";
						break;
					}
						
					//判断是否填写请假时数
					if(strlen($data['note_hour'])==0){
						$insert_flag=false;
						$msg="请假时数不能为空!";
						break;
					}
						
					//判断时间差是否小于填写时间数
					$time_diff_arr=timediff($data['begin_time'], $data['end_time']);
	
					if($time_diff_arr['h']<$data['note_hour']){
						$insert_flag=false;
						$msg="请假所选日期范围小于申请时间数,请检查数据!";
						break;
					}
						
					//判断是否填写请假标题
					if(strlen($data['note_title'])==0){
						$insert_flag=false;
						$msg="标题不能为空!";
						break;
					}
						
					$insert_arr['hr_note_id']=$data['hr_note_id'];
					$insert_arr['hr_note_name']=config('hr_note_type')[$insert_arr['hr_note_id']];
					$insert_arr['note_hour']=$data['note_hour'];
					$insert_arr['begin_time']=$data['begin_time'];
					$insert_arr['end_time']=$data['end_time'];
					$insert_arr['note_title']=$data['note_title'];
					$insert_arr['note_desc']=$data['note_desc'];
					$insert_arr['note_step']=2;					//默认考勤主管审核
					$insert_arr['cur_user_id']=session('cur_user_info')['hr_user_id'];
						
					//如果申请时间超过指定代理人时间,获取代理人参数
					$manage_time=config('hr_holiday_manage_val')*8;
					if($data['note_hour']>$manage_time || $data['note_hour']==8.5){
						$insert_arr['age_user_id']=$data['age_user_id'];
						$insert_arr['cur_user_id']=$data['age_user_id'];
						//设置申请单流程状态下一步为代理人审核
						$insert_arr['note_step']=1;
					}
						
					//获取此人考勤主管ID
					$insert_arr['hr_user_id']=session('cur_user_info')['hr_user_id'];
						
					//如果超过hr_holiday_adv_manage_val设定时间,需要转上级审核
					if($data['note_hour']>config('hr_holiday_adv_manage_val')){
						$insert_arr['hr_adv_user_id']=config('user_info')[$insert_arr['hr_user_id']]['hr_user_id'];
					}
					$return_tit="请假单(".$insert_arr['hr_note_name'].") 修改成功!";
						
					break;
				case 2:
					//判断是否超过晚餐预订最晚时间
					$hr_supper_last_time=config('hr_supper_last_time');
					$cur_time=date('H:i',time());
					$cur_date=date('Y-m-d',time());
					if($cur_time>$hr_supper_last_time){
						//if(1==2){
						$insert_flag=false;
						$msg="已经超过本日最晚订餐时间 ".$hr_supper_last_time." ,下次记得早点填单 ^_^!";
						break;
					}else{
	
						//判断本日是否已经提交晚餐申请单
						$is_hava_note=db('user_note')->where('user_id='.$user_id." and begin_time='".$cur_date." 00:00:00"."' and status=1 and note_type=2")->find();
						if($is_hava_note){
							$insert_flag=false;
							$msg="本日(".$cur_date.")晚餐申请单已经提交,请勿重复提交!";
							break;
						}
	
						$insert_arr['note_type_2_flag']=$data['note_type_2_flag'];
	
						if($insert_arr['note_type_2_flag']==1){
							$return_tit="本日晚餐申请单(管理部代购)提交成功!";
						}else{
							$return_tit="本日晚餐申请单提交成功!";
						}
						$insert_arr['note_title']=$cur_date.'日晚餐';
						$insert_arr['begin_time']=$cur_date;
					}
					break;
				case 3:
					//判断是否填写开始时间,结束时间
					if(strlen($data['begin_time'])<18 || strlen($data['end_time']<18)){
						$insert_flag=false;
						$msg="开始时间 或 结束时间错误,请检查此2项数据!";
						break;
					}
						
					//判断本日开始时间是否已经有申请单,如果有的话,当日不允许2张申请单
					$is_hava_note=db('user_note')->where('user_id='.$user_id." and '".$data['begin_time']."' between date_add(begin_time,interval 1 SECOND) and date_sub(end_time,interval 1 SECOND)  and status=1")->find();
					if($is_hava_note){
						$insert_flag=false;
						$msg="申请单日期范围内有重复申请单,ID为".$is_hava_note['id'].",请核对申请单日期范围!";
						break;
					}
						
					//判断本日结束时间是否已经有申请单,如果有的话,当日不允许2张申请单
					$is_hava_note=db('user_note')->where('user_id='.$user_id." and '".$data['end_time']."' between date_add(begin_time,interval 1 SECOND) and date_sub(end_time,interval 1 SECOND)  and status=1")->find();
					if($is_hava_note){
						$insert_flag=false;
						$msg="申请单日期范围内有重复申请单,ID为".$is_hava_note['id'].",请核对申请单日期范围!";
						break;
					}
						
					//判断本日是否已经填写晚到申请单,不允许填写2张申请单
					$is_hava_3_note=db('user_note')->where('user_id='.$user_id." and note_type=3 and date_format(begin_time,'%Y-%m-%d')='".$data['begin_date']."'  and status=1")->find();
					if($is_hava_3_note){
						$insert_flag=false;
						$msg="本日内已经有晚到申请单,ID为".$is_hava_3_note['id'].",请核对!";
						break;
					}
	
					//判断结束日期是否大于等于开始日期
					if(strtotime($data['end_time'])<=strtotime($data['begin_time'])){
						$insert_flag=false;
						$msg="申请单开始日期大于结束日期,请检查数据!";
						break;
					}
						
					//判断是否填写请假时数
					if(strlen($data['note_hour'])==0){
						$insert_flag=false;
						$msg="请假时数不能为空!";
						break;
					}
						
					//判断时间差是否小于填写时间数
					$time_diff_arr=timediff($data['begin_time'], $data['end_time']);
	
					if($time_diff_arr['h']<$data['note_hour']){
						$insert_flag=false;
						$msg="请假所选日期范围小于申请时间数,请检查数据!";
						break;
					}
						
					//判断是否填写请假标题
					if(strlen($data['note_title'])==0){
						$insert_flag=false;
						$msg="标题不能为空!";
						break;
					}
						
					$insert_arr['note_hour']=$data['note_hour'];
					$insert_arr['begin_time']=$data['begin_time'];
					$insert_arr['end_time']=$data['end_time'];
					$insert_arr['note_title']=$data['note_title'];
					$insert_arr['note_desc']=$data['note_desc'];
						
					/*
						//如果申请时间超过指定代理人时间,获取代理人参数
						$manage_time=config('hr_holiday_manage_val')*8;
						if($data['note_hour']>$manage_time || $data['note_hour']==8.5){
						$insert_arr['age_user_id']=$data['age_user_id'];
						}
					*/
						
					//获取此人考勤主管ID
					$insert_arr['hr_user_id']=session('cur_user_info')['hr_user_id'];
						
					/*
						//如果超过hr_holiday_adv_manage_val设定时间,需要转上级审核
						if($data['note_hour']>config('hr_holiday_adv_manage_val')){
						$insert_arr['hr_adv_user_id']=config('user_info')[$insert_arr['hr_user_id']]['hr_user_id'];
						}
					*/
						
					$return_tit="晚到申请单单提交成功!";
						
					break;
				case 4:
					//判断是否填写开始时间,结束时间
					if(strlen($data['begin_time'])<18 || strlen($data['end_time']<18)){
						$insert_flag=false;
						$msg="开始时间 或 结束时间错误,请检查此2项数据!";
						break;
					}
						
					//判断本日开始时间是否已经有申请单,如果有的话,当日不允许2张申请单
					$is_hava_note=db('user_note')->where('user_id='.$user_id." and '".$data['begin_time']."' between begin_time and end_time  and status=1")->find();
					if($is_hava_note){
						$insert_flag=false;
						$msg="申请单日期范围内有重复申请单,ID为".$is_hava_note['id'].",请核对申请单日期范围!";
						break;
					}
						
					//判断本日结束时间是否已经有申请单,如果有的话,当日不允许2张申请单
					$is_hava_note=db('user_note')->where('user_id='.$user_id." and '".$data['end_time']."' between begin_time and end_time  and status=1")->find();
					if($is_hava_note){
						$insert_flag=false;
						$msg="申请单日期范围内有重复申请单,ID为".$is_hava_note['id'].",请核对申请单日期范围!";
						break;
					}
	
					//判断结束日期是否大于等于开始日期
					if(strtotime($data['end_time'])<=strtotime($data['begin_time'])){
						$insert_flag=false;
						$msg="申请单开始日期大于结束日期,请检查数据!";
						break;
					}
						
					//判断是否填写请假时数
					if(strlen($data['note_hour'])==0){
						$insert_flag=false;
						$msg="请假时数不能为空!";
						break;
					}
						
					//判断时间差是否小于填写时间数
					$time_diff_arr=timediff($data['begin_time'], $data['end_time']);
	
					if($time_diff_arr['h']<$data['note_hour']){
						$insert_flag=false;
						$msg="请假所选日期范围小于申请时间数,请检查数据!";
						break;
					}
						
					//判断是否填写请假标题
					if(strlen($data['note_title'])==0){
						$insert_flag=false;
						$msg="标题不能为空!";
						break;
					}
						
					$insert_arr['note_hour']=$data['note_hour'];
					$insert_arr['begin_time']=$data['begin_time'];
					$insert_arr['end_time']=$data['end_time'];
					$insert_arr['note_title']=$data['note_title'];
					$insert_arr['note_desc']=$data['note_desc'];
						
					/*
						//如果申请时间超过指定代理人时间,获取代理人参数
						$manage_time=config('hr_holiday_manage_val')*8;
						if($data['note_hour']>$manage_time || $data['note_hour']==8.5){
						$insert_arr['age_user_id']=$data['age_user_id'];
						}
					*/
						
					//获取此人考勤主管ID
					$insert_arr['hr_user_id']=session('cur_user_info')['hr_user_id'];
						
					/*
						//如果超过hr_holiday_adv_manage_val设定时间,需要转上级审核
						if($data['note_hour']>config('hr_holiday_adv_manage_val')){
						$insert_arr['hr_adv_user_id']=config('user_info')[$insert_arr['hr_user_id']]['hr_user_id'];
						}
					*/
						
					$return_tit="外出申请单单提交成功!";
					break;
			}
			if(!$insert_flag){
				echo setServerBackJson(0,$msg);
				exit;
			}else{
				$insert_arr['c_time']=date('Y-m-d H:i:s',time());
	
				//写入申请单日志
				$insert_arr['note_log']= $note_info['note_log']."<br>[".date('Y-m-d H:i:s',time())."] 用户".get_user_nickname().'修改申请单!<br>';
				//插入申请单主表
				$id=db('user_note')->update($insert_arr);
	
				//非请假单,不审核,直接写入申请单附表,请假单需主管审核后放正式计算
				if($insert_arr['note_type'] != 1){
					$insert_arr['note_id']=$id;
					db('user_note_item')->insert($insert_arr);
				}
	
				if($id){
					echo setServerBackJson(1,$return_tit,'note_list');
					exit;
				}else{
					echo setServerBackJson(0,'申请单修改失败,请截屏后联系管理员!');
					exit;
				}
			}
		}else{
			$page_info=array();
			$user_id=get_user_id();
			$note_id=input('id');
			$page_info['cur_user_info']=session('cur_user_info');			
			$page_info['note_type_info']=config('hr_note_type');
			$page_info['hr_role_str']=config('hr_role_info')[$page_info['cur_user_info']['role_id']]['begin_time']."~".config('hr_role_info')[$page_info['cur_user_info']['role_id']]['end_time'];
			$page_info['year_holiday_day']=get_user_year_holiday_day($user_id);
			$page_info['user_hr_holiday']=get_user_hr_holiday($user_id);
			$page_info['hr_date']=input('hr_date');
			$page_info['cur_date']=date('Y-m-d',time());
			$page_info['hr_date_info']=array();
			$page_info['note_info']=db('user_note')->where('id='.$note_id)->find();
			$page_info['begin_work_time']=config('hr_role_info')[$page_info['cur_user_info']['role_id']]['begin_time'];
			
			//获取用户当前班次的时间节点
			$begin_time_arr=explode(':', $page_info['begin_work_time']);
			if(strlen($begin_time_arr[0])==1){
				$begin_time_arr[0]='0'.$begin_time_arr[0];
			}
				
			$page_info['begin_h']=$begin_time_arr[0];
			$page_info['begin_m']=$begin_time_arr[1];
				
			//判断此人考勤主管是否设定,如果未设定,则给定错误信息
			if($user_id<>config('BOSS_ID') && $page_info['cur_user_info']['hr_user_id']==0){
				echo '考勤主管未设置,无法填写申请单!';
				exit();
			}
				
			if($user_id==config('BOSS_ID')){
				echo '老板...不填申请单!';
				exit();
			}
			
			//计算需要指定代理人时间(h)
			$page_info['age_time_val']=config('hr_holiday_manage_val')*8;
			
			//获取当前用户的代理人
			$page_info['age_info']=get_user_age_info($user_id);				
			
			$page_info['auto_sqd']=0;
			$page_info['sqd_json']=json_encode($page_info['hr_date_info']);
			
			$this->assign('page_info',$page_info);
			return  $this->fetch();
		}
	}	

	//删除申请单
	public function note_delete(){
		$id=input('id');
		db('user_note')->where('id='.$id)->setField('status',0);
		echo '申请单ID为'.$id."删除成功!";			
	}
	
	
	/*
	 * 查看申请单界面显示
	 */
	public function check_note(){
	
		$data=$this->param;
		$page_info=array();
		$page_info['note_info']=db('user_note')->where('id='.$data['id'])->find();
	
		$page_info['user_id']=get_user_id();
		$page_info['flag']=$data['flag'];
	
		if($page_info['note_info']['note_hour']>get_adv_manage_h()){
			if($page_info['note_info']['hr_adv_user_id']==$page_info['user_id']){
				$page_info['adv_manage_flag']=0;
			}else{
				$page_info['adv_manage_flag']=1;
			}
				
		}else{
			$page_info['adv_manage_flag']=0;
		}
	
		//判断用户是否还允许对此单据操作
		if($page_info['note_info']['cur_user_id'] != $page_info['user_id']){
			$page_info['flag']='note_view';
		}
	
		$this->assign('page_info',$page_info);
		return $this->fetch();
	}
	
	
	//个人基础数据查看
	function user_info(){
		$id=get_user_id();
		//员工数据准备
		$page_info['user_info']=db('sys_user')->where('id='.$id)->find();
	
		//准备部门数据
		$dep_db_arr=cache('dep_cache_arr');
		$page_info['dep_select']=get_dep_select($dep_db_arr,'edit','dep_id',$page_info['user_info']['dep_id'],'form-control');
			
		//准备站点数据
		$page_info['site_arr']=cache('site_cache_arr');
			
		//学历数据
		$page_info['hr_edu_arr']=config('hr_edu');
		
		//职位名称
		$page_info['hr_technical_arr']=config('hr_technical');
			
		//职等数据
		$page_info['hr_work_level_arr']=config('hr_work_level');
			
		//岗位数据
		$page_info['hr_job_type_arr']=config('hr_job_type');
		
		//在职状态
		$page_info['hr_status_arr']=config('hr_status');
		
		//考勤主管数据
		$page_info['hr_user_info']=db('sys_user')->field('id,dep_id,nickname')->where('is_hr_manage=1 and status=1 and user_status=1 ')->select();
		
		//考勤规则
		$page_info['hr_role_info']=db('sys_hr_role')->field('id,role_name')->where('status=1')->select();
		
		//当前日期
		$page_info['cur_date']=date('Y-m-d',time());
			
		//年资数据获取		
		$page_info['user_seniority']=user_seniority($page_info['user_info']['entry_date'],$page_info['user_info']['out_seniority']);
		
		//家庭成员关系
		$page_info['family_type']=config('hr_family_type');
		
		//家庭成员获取
		$page_info['family_info']=db('sys_user_family')->where('status=1 and user_id='.$id)->select();
		
		//休假数据
		$page_info['year_holiday_day']=get_user_year_holiday_day($id);
		$page_info['user_hr_holiday']=get_user_hr_holiday($id);
		//权限组别数据
		$page_info['role_arr']=get_role_arr();
			
		$this->assign('page_info',$page_info);
		return  $this->fetch();
	}

	//用户修改密码
	public function change_user_pwd(){
		if (IS_POST) {
			$id=get_user_id();
			$data=$this->param;
			
			if($data['new_pwd1'] != $data['new_pwd2']){
				echo setServerBackJson(0,'确认密码不符!');
				exit;				
			}else{
				if(strlen($data['new_pwd1'])<6){
					echo setServerBackJson(0,'密码不能小于6位!');
				}else{
					db('sys_user')->where('id='.$id)->setField('password',md5($data['new_pwd1']));
					echo setServerBackJson(1,'密码修改成功!','','closeDialog');
				}
			}			
		}else{
		return	$this->fetch();
		}
	}

	//提交用户资料修改申请
	public function change_user_info(){
		if (IS_POST) {
			$id=get_user_id();
			$data=$this->param;
			$temp_arr=array();

				if(strlen($data['q_val'])<5){
					echo setServerBackJson(0,'提交问题不能为空!');
				}else{
					$temp_arr['q_type']=1;
					$temp_arr['q_val']=$data['q_val'];
					$temp_arr['user_id']=$id;
					$temp_arr['create_time']=date('Y-m-d H:i:s',time());
					$temp_arr['mail_flag']=$data['mail_flag'];
					$insert_id=db('q_a')->insertGetId($temp_arr);
					
					if($insert_id){
						echo setServerBackJson(1,'问题提交成功! ID为: '.$insert_id,'','closeDialog');
					}else{
						echo setServerBackJson(0,'问题提交失败! 请联系管理员!');
					}
				}
		}else{
			return	$this->fetch();
		}
	}
	
	
	//自动计算假单结束时间
	public function auto_end_time(){
		$data=$this->param;
		$return_end_time="";
		
		//获取该用户的班次
		$user_info=session('cur_user_info');
		$role_info=config('hr_role_info')[$user_info['hr_role_id']];
		
		//用户实际填写的开始时间
		$begin_time_f=$data['begin_date'].' '.$data['begin_h'].':'.$data['begin_m'].':00';
		$begin_time=strtotime($begin_time_f);
		
		//如果用户填写的开始时间为假日,则寻找下一个工作日作为开始日期计算
		$is_holiday=db('sys_holiday')->where('site_id='.$user_info['site_id']." and holiday_date='".$data['begin_date']."'")->find();
		//检查最近11天范围,直到找到工作日
		if($is_holiday){
			for($i=1;$i<11;$i++){
				$begin_time=strtotime($data['begin_date']."+".$i." day");
				$begin_time_f=date('Y-m-d',$begin_time)." ".$role_info['begin_time'];
				$is_holiday=db('sys_holiday')->where('site_id='.$user_info['site_id']." and holiday_date='".date('Y-m-d',$begin_time)."'")->find();
				if(!$is_holiday){
					break;
				}
			}
		}
		
		//获取用户上班的点
		$user_work_begin_f=date('Y-m-d',$begin_time).' '.$role_info['begin_time'];
		$user_work_begin=strtotime($user_work_begin_f);
		
		//获取用户下班的点
		$user_work_end_f=date('Y-m-d',$begin_time).' '.$role_info['end_time'];
		$user_work_end=strtotime($user_work_end_f);
		
		//获取本日午休时间
		$noon_array['begin']=strtotime($data['begin_date'].' '.config('hr_noon_begin'));
		$noon_array['end']=strtotime($data['begin_date'].' '.config('hr_noon_end'));		
		
		//如果用户填写的申请单开始日期的班次时间小于用户实际上班班次时间,则拿用户实际上班班次时间计算
		if((date('H:i:s',$begin_time)<date('H:i:s',$user_work_begin)) ){			
			$begin_time_f=$user_work_begin_f;
			$begin_time=$user_work_begin;
		}
		//判断申请时间是否有跨午休,如果有跨午休,则多加一个小时
		
		//申请单开始时间+申请时间
		$end_time_temp=$begin_time+$data['note_hour']*60*60;
		//echo date('Y-m-d H:i:s',$begin_time).'--'.date('Y-m-d H:i:s',$user_work_begin);exit;
		
		/*
		$time_arr=array();
		$time_arr['note']['begin_time']=$begin_time;
		$time_arr['note']['begin_time_f']=date('Y-m-d H:i:s',$begin_time);
		$time_arr['note']['end_time']=$end_time_temp;
		$time_arr['note']['end_time_f']=date('Y-m-d H:i:s',$end_time_temp);
		$time_arr['noon']['begin_time']=$noon_array['begin'];
		$time_arr['noon']['begin_time_f']=date('Y-m-d H:i:s',$noon_array['begin']);
		$time_arr['noon']['end_time']=$noon_array['end'];
		$time_arr['noon']['end_time_f']=date('Y-m-d H:i:s',$noon_array['end']);
				
		print_r($time_arr);		
		*/
		
		//开始时间在午休时间范围内,结束时间在午休时间范围内
		if(($begin_time>=$noon_array['begin'] && $begin_time<$noon_array['end']) || ($begin_time>$noon_array['begin'] && $begin_time<$noon_array['end'])){
			if($data['begin_h']==12 && $data['begin_m']==30){
				$note_hour=$data['note_hour']+0.5;
			}else{
				$note_hour=$data['note_hour']+1;
			}
			
		}else{
			//开始时间小于午休开始时间,结束时间大于午休结束时间
			
			if($begin_time<$noon_array['begin'] && $end_time_temp>=$noon_array['end']){
				//如果申请单开始时间=工作开始时间,则申请单不做+1动作
				//if($begin_time==)
				$note_hour=$data['note_hour']+1;
			}else{
				$note_hour=$data['note_hour'];
			}
		}
		/*
		//如果为本日开始到结束请假时间,则不做+1计算
		if($begin_time==$user_work_begin && $data['note_hour']==8){
			$note_hour=$data['note_hour'];
		}
		*/
		
		//计算结束时间是否超过本日下班时间
		if($end_time_temp<=$user_work_end){
			$return_end_time=date('Y-m-d H:i:s',$begin_time+$note_hour*60*60);
			echo $return_end_time;
			exit;
		}else{
			//判断当前日期是否假日
			$is_holiday=db('sys_holiday')->where('site_id='.$user_info['site_id']." and holiday_date='".$data['begin_date']."'")->find();
			
			if($is_holiday){
				//本日为假日,拿填写申请时间,本日计算
				$note_new_hour=$note_hour;
				$note_begin_date=strtotime($data['begin_date']);
			}else{
				//非假日,拿填写时间-本日需要扣除时间,结余时间开始第二日计算
				
				//小于8小时,则不进行跨日运算
				if($note_hour<=8){
					$return_end_time=date('Y-m-d H:i:s',$begin_time+$note_hour*60*60);
					echo $return_end_time;
					exit;
				}
				
				//计算请假开始日期离本班次下班时间差多少时间
				$time_firstD_diff=timediff($begin_time, $user_work_end);				
				$note_new_hour=$note_hour-$time_firstD_diff['h'];
				/*
				if($note_new_hour>4){
					$note_new_hour=$note_new_hour+1;
				}
				*/
				$note_begin_date=strtotime($data['begin_date']."+1 day");
			}			
		}

		//计算剩余假期时间数
		for($i=0;$i<200;$i++){
			//echo date('Y-m-d',$note_begin_date).'['.$note_new_hour.']---';
			
			//判断本日是否假日,假日则不累减,工作日减8小时
			if($note_new_hour<8){
				if(is_int($note_begin_date)){
					$for_date=date('Y-m-d',$note_begin_date);
				}else{
					$for_date=$note_begin_date;
				}
				
				$is_holiday=db('sys_holiday')->where('site_id='.$user_info['site_id']." and holiday_date='".$for_date."'")->find();
				if(!$is_holiday){
					if($note_new_hour>4){
						$note_new_hour=$note_new_hour+1;
					}
					$note_begin_date= strtotime(date('Y-m-d',$note_begin_date).' '.$role_info['begin_time'])+($note_new_hour*60*60);
					$return_end_time= date('Y-m-d H:i:s',$note_begin_date);
					break;
				}else{
					for ($x=0;$x<10;$x++){
						$note_begin_date=$note_begin_date + 24*60*60;
						if(is_int($note_begin_date)){
							$for_date=date('Y-m-d',$note_begin_date);
						}else{
							$for_date=$note_begin_date;
						}
						$is_holiday=db('sys_holiday')->where('site_id='.$user_info['site_id']." and holiday_date='".$for_date."'")->find();
						if(!$is_holiday){
							$note_begin_date= strtotime(date('Y-m-d',$note_begin_date).' '.$role_info['begin_time'])+($note_new_hour*60*60);
							$return_end_time= date('Y-m-d H:i:s',$note_begin_date);
							break;
						}
					}
				}
				
			}else{				
				if(is_int($note_begin_date)){
					$for_date=date('Y-m-d',$note_begin_date);
				}else{
					$for_date=$note_begin_date;
				}
				//判断是否假日
				$is_holiday=db('sys_holiday')->where('site_id='.$user_info['site_id']." and holiday_date='".$for_date."'")->find();
				if(!$is_holiday){
					if($note_new_hour==8){
						$return_end_time=date('Y-m-d',$note_begin_date).' '.$role_info['end_time'];
						break;
					}
					$note_new_hour=$note_new_hour-8;
					$note_begin_date= $note_begin_date + 24*60*60;
				}else{
					$note_begin_date= $note_begin_date + 24*60*60;
				}
			}
		}
		
		echo  $return_end_time;			
		
	}
	

	//导出考勤报表//王天棋2017.9.27修改
	public function ext_hr_repo(){		
		
		$data['file_key']='hr_list';
		$data['file_name']='hr_list';
		$manage_user_id_str=get_user_sub_user(get_user_id());
		
		//条件
		$sql_where='';
		$_POST['site_id']=isset($_POST['site_id'])?$_POST['site_id']:'';
		$_POST['dep_id']=isset($_POST['dep_id'])?$_POST['dep_id']:'';
		$_POST['user_id']=isset($_POST['user_id'])?$_POST['user_id']:'';
		$sql_site='';
		if($_POST['site_id']!=='all' && $_POST['site_id']){$sql_site=',sw_sys_site s';$sql_where=$sql_where."s.id='".$_POST['site_id']."' and su.site_id='".$_POST['site_id']."' and ";}
		if($_POST['dep_id']!=='all' && $_POST['dep_id']){$sql_site=$sql_site.",sw_sys_dep d";$sql_where=$sql_where."d.id='".$_POST['dep_id']."' and ";}
		if($_POST['user_id']!=='all' && $_POST['user_id']){$sql_where=$sql_where."su.id='".$_POST['user_id']."' and ";}
		
		$_POST['begin_date']=isset($_POST['begin_date'])?$_POST['begin_date']:'0000-00-00';
		$_POST['end_date']=isset($_POST['end_date'])?$_POST['end_date']:date('Y-m-d',time());
		if($_POST['begin_date']==''){$_POST['begin_date']="0000-00-00";}
		if($_POST['end_date']==''){$_POST['end_date']=date('Y-m-d',time());}
		if($_POST['begin_date'] || $_POST['end_date']){
			$sql_where=$sql_where."ht.hr_date between '".$_POST['begin_date']."' and '".$_POST['end_date']."' and ";
		}
		
		$sql="select
					ht.id,su.user_gh,ht.nickname,ht.hr_date,ht.title,ht.hr_card_first,ht.hr_card_end,ht.z_work_time,ht.note_time,ht.act_time,
				    (select concat(case when n.note_type=1 then '请假单' when n.note_type=3 then '晚到' when n.note_type=4 then '外出' end,'-',n.hr_note_name) from sw_user_note n where n.id in (ht.note_id_str)) as note_type_str,
					0 as year_holiday_time
				from
					sw_hr_table ht,sw_sys_user su".$sql_site."				
				where
					ht.user_id=su.id and ".$sql_where." su.user_status=1 and su.status=1 and su.id in (".$manage_user_id_str.")";
		
		$data['tb_head']=array('id','nickname','user_gh','hr_date','title','hr_card_first','hr_card_end','z_work_time','note_time','act_time','note_type_str','year_holiday_time');
		
		$temp_arr=db()->query($sql);
		
		$data['tb_body']=$temp_arr;
		
		ext_excel('normal',$data);
	}
	
	
	//导出申请单报表//王天棋2017.9.27修改
	public function ext_note_repo(){
		$data['file_key']='note_list_user';
		$data['file_name']='note_list';
		$manage_user_id_str=get_user_sub_user(get_user_id());
		
		//条件
		$sql_where='';
		$_POST['site_id']=isset($_POST['site_id'])?$_POST['site_id']:'';
		$_POST['dep_id']=isset($_POST['dep_id'])?$_POST['dep_id']:'';
		$_POST['user_id']=isset($_POST['user_id'])?$_POST['user_id']:'';
		$sql_site='';
		if($_POST['site_id']!=='all' && $_POST['site_id']){$sql_site=',sw_sys_site s';$sql_where=$sql_where."s.id='".$_POST['site_id']."' and u.site_id='".$_POST['site_id']."' and ";}
		if($_POST['dep_id']!=='all' && $_POST['dep_id']){$sql_site=$sql_site.",sw_sys_dep d";$sql_where=$sql_where."d.id='".$_POST['dep_id']."' and ";}
		if($_POST['user_id']!=='all' && $_POST['user_id']){$sql_where=$sql_where."u.id='".$_POST['user_id']."' and ";}
		
		$_POST['begin_date']=isset($_POST['begin_date'])?$_POST['begin_date']:'0000-00-00';
		if($_POST['begin_date']){date('Y-m-d',strtotime($_POST['begin_date']." -1 day"));}
		$_POST['end_date']=isset($_POST['end_date'])?date('Y-m-d',strtotime($_POST['end_date']." +1 day")):date('Y-m-d',strtotime('+1 day'));
		if($_POST['begin_date']==''){$_POST['begin_date']="0000-00-00";}
		if($_POST['end_date']==''){$_POST['end_date']=date('Y-m-d',strtotime('+1 day'));}
		if($_POST['begin_date'] || $_POST['end_date']){
			$sql_where=$sql_where."n.c_time between '".$_POST['begin_date']."' and '".$_POST['end_date']."' and ";
		}
		//本月薪资实发数据获取
		$sql="select
					n.id,u.nickname,u.user_gh,n.c_time,
				    (case when note_type=1 then '请假单' when note_type=2 then '晚餐预订' when note_type=3 then '晚到' when note_type=4 then '外出' end) as note_type,
				    n.hr_note_id,n.note_hour,n.begin_time,n.end_time,
				    (select nickname from sw_sys_user su where su.id=n.age_user_id) as age_user,
				    (select nickname from sw_sys_user su2 where su2.id=n.hr_adv_user_id) as check_user,
				    n.hr_adv_check_status
				from
					sw_user_note n,sw_sys_user u".$sql_site."
				where
					n.user_id=u.id and ".$sql_where." u.user_status=1 and u.status=1 and u.id in (".$manage_user_id_str.")";
		
		$data['tb_head']=array('id','nickname','user_gh','c_time','note_type','hr_note_id','note_hour','begin_time','end_time','age_user','check_user','hr_adv_check_status');
		
		$temp_arr=db()->query($sql);
		
		//用户数据字段填充
		foreach ($temp_arr as $key=>$val){
			if(strlen($val['hr_note_id'])>0){
				$temp_arr[$key]['hr_note_id']=config('hr_note_type')[$val['hr_note_id']];
			}
		}
		
		$data['tb_body']=$temp_arr;
		
		ext_excel('normal',$data);
	}
	
	public function user_msg_no(){
		session('wtqrbq','0');
		echo 1;
	}
	
	
	//图片上传页面
	public function image(){
		if(session('cur_user_info.user_img_head_url')){
			$img_head=session('cur_user_info.user_img_head_url');
			$img_head_s=explode('.',$img_head);
			//获取以前的后缀
			$file_type_arr=session('cur_user_info.user_img_head_full');
			//转为数组
			$file_type_arr=json_decode($file_type_arr,true);
			//生成新名称
			$file_type_arr_key=array_keys($file_type_arr);
			$img_head_s_end=end($file_type_arr_key);
			
			$this->assign('img_head_s_end', $img_head_s_end);
			$this->assign('img_head_name', $img_head_s[0]);
			$this->assign('img_head_full', session('cur_user_info.user_img_head_full'));
		}
		
		return $this->fetch();
	}
	//图像上传
	public function image_head_up(){
		if(isset($_POST['old_num'])){
			$imgname=$_POST['old_num'];
			//保存至数据库
			db('sys_user')
			->where('id', get_user_id())
			->update(['user_img_head_url'=>"$imgname"]);
			//修改缓存数据
			session('cur_user_info.user_img_head_url',"$imgname");
			exit(json_encode(array(1,'更新完成')));
		}
		//判断用户有没有乱搞
		$error=array('上传失败','上传成功','格式不符','上传失败','上传失败');
		$img_info = getimagesize($_FILES['file_head']['tmp_name']);
		$img_h_w=explode('"',$img_info[3]);
		if($img_h_w[1]!=$img_h_w[3]){
			exit(json_encode(array(0,$error[0])));
		}
		
		//获取原图后缀
		$file_name=$_FILES['file']['name'];
		$file_type=explode('.',$file_name);
		$file_type=end($file_type);
		//判断是不是第一次上传头像
		if(session('cur_user_info.user_img_head_url')){
			//获取名称
			$img_head=session('cur_user_info.user_img_head_url');
			$img_head_s=explode('.',$img_head);
			
			//获取以前的后缀
			$file_type_arr=session('cur_user_info.user_img_head_full');
			//获取字符串长度
			$len=strlen($file_type_arr);
			//$file_type_arr=unserialize($file_type_arr);
			//转为数组
			$file_type_arr=json_decode($file_type_arr,true);
			//生成新名称
			$file_type_arr_key=array_keys($file_type_arr);
			$img_head_s_end=end($file_type_arr_key);//max(array_flip($file_type_arr));
			$img_head_s_now=$img_head_s_end+1;
			$imgname=$img_head_s[0].'.'.$img_head_s_now;
			//判断超过一定大小之后删除开早的那个
			if($len>=240){
				unset($file_type_arr[array_keys($file_type_arr)[0]]);
			}
			$file_type_arr[]=$file_type;
		}else{
			$imgname=rand_string(21,'','{[(~!@$^&-)]}').time().'.0';
			$file_type_arr=array($file_type);
		}
		//数组加密上传
		//$file_type_arr = serialize($file_type_arr);
		$file_type_arr = json_encode($file_type_arr);
		//上传原图
		$this_file_pd=$this->file_up(config('user_img_url').get_user_id().'/', $imgname,'file',config('user_img_type'));
		//判断
		if($this_file_pd!=1){
			exit(json_encode(array(0,$error[$this_file_pd])));
		}
		//命名
		$_FILES['file_head']['name']=$_POST['fileName'];
		//上传头像
		$this_file_pd=$this->file_up(config('user_img_url').get_user_id().'/', $imgname.'head','file_head',config('user_img_type'));
		//判断
		if($this_file_pd!=1){
			exit(json_encode(array(0,$error[$this_file_pd])));
		}

		//保存至数据库
		db('sys_user')
			->where('id', get_user_id())
			->update(['user_img_head_url'=>"$imgname",'user_img_head_full'=>$file_type_arr]);
		//修改缓存数据
		session('cur_user_info.user_img_head_url',"$imgname");
		session('cur_user_info.user_img_head_full',"$file_type_arr");
		exit(json_encode(array(1,$error[$this_file_pd],$imgname,json_decode(session('cur_user_info.user_img_head_full')))));
	}
	/**
	 * 
	 * @param unknown $path 上传文件路径
	 * @param unknown $file_new_name 上传文件重命名
	 * @param unknown $name 上传文件名称//默认file
	 * @param unknown $type 类型限制判断//数组//例:array('jpg','mp4','mp7')
	 * @param unknown $dir 文件夹不存在报错或新建，默认新建,需要报错随便传个值就行
	 * @return string
	 * 返回值2格式错误，3非HTTP POST上传,4文件夹不存在，0上传失败，1上传成功
	 */
	public function file_up($path,$file_new_name='',$name='file',$type=array(),$dir=''){
		//判断文件大小
		$file_size=$_FILES[$name]['size'];
		if($file_size>200*1024*1024) {
			exit("文件过大，不能上传大于200M的文件");
		}
		//判断文件类型
		$file_name=$_FILES[$name]['name'];
		$file_type=explode('.',$file_name);
		$file_type=end($file_type);
		$type_i=0;
		foreach ($type as $t){
			if($file_type==$t) {
				$type_i=1;
			}
		}
		if($type_i==0){
			return 2;
		}
		//判断文件夹是否存在，不存在报错或新建,默认新建
		if(!is_dir($path)){
			if($dir){
				return 4;
			}else{
				mkdir($path,777,true);
			}
		}
		//判断文件是否重命名
		if($file_new_name==''){
			$file_new_name=rand_string(10);
		}
		//确保恶意的用户无法欺骗脚本去访问本不能访问的文件
		$tmp=$_FILES[$name]['tmp_name'];
		if(is_uploaded_file($tmp)) {
			//ignore的意思是忽略转换时的错误，如果没有ignore参数，所有该字符后面的字符串都无法被保存
			//iconv("utf-8","gb2312//IGNORE",$filepath.$imgname.".jpg")
			if(move_uploaded_file($tmp,$path.$file_new_name.'.'.$file_type)){
				return 1;
			}else{
				return 0;
			}
		}else{
			return 3;
		}
		
	}

    // 周仁杰 2017-11-10修改 （用户修改个人资料功能）
	public function user_edit(){
		 $user_info_data = $_POST;
		 //数据过滤
		 $update_data = array();
		 foreach($user_info_data as $k => $v){
			 $update_data[$k] = trim($v);
		 }
		//实例化对象，调用校验数据方法
		$user_main = new \app\user\model\UserMain();
		$user_main->check_Senility_field($update_data);
		$id = $update_data['id'];
		unset($update_data['id']);
		//获取当前时间
		$update_data['final_edit_time'] = date("Y-m-d H:i:s",time());
		Db::name('sys_user')->where('id',$id)->update($update_data);
		 echo setServerBackJson(1,"修改成功");
	}
	
	
	
	public function user_education(){
		$sql="select user_gh,name,education,degree,schooling,major,in_school,out_school,school from sw_user_education where user_gh='".get_cache_data('user_info',get_user_id(),'user_gh')."'";
		$user_educa=db()->query($sql);
		$div='<table class="wtq_o" style="margin: 0 auto;"><tr>
				<td><div>工号</div></td>
				<td><div>名字</div></td>
				<td><div>学历</div></td>
				<td><div>学位</div></td>
				<td><div>学制</div></td>
				<td><div>专业</div></td>
				<td><div>入校时间</div></td>
				<td><div>毕业时间</div></td>
				<td><div>毕业学校</div></td>
				</tr>';
		foreach ($user_educa as $user){
			$div=$div.'<tr>';
			foreach ($user as $u){
				$div=$div.'<td><div>'.$u.'</div></td>';
			}
			$div=$div.'</tr>';
		}
		$div=$div.'</table>';
		echo json_encode($div);
	}
}











