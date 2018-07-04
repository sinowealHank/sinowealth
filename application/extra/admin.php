<?php
use think\Db;
/**
 +----------------------------------------------------------
 * 递归部门数组,生成树结构部门数组
 +----------------------------------------------------------
 * @param array $dep_db_arr
 +----------------------------------------------------------
 * @return array
 +----------------------------------------------------------
 */
function formatTree($array, $pid = 0){
	$arr = array();
	$tem = array();
	foreach ($array as $v) {
		if ($v['pid'] == $pid) {
			$tem = formatTree($array, $v['id']);
			//判断是否存在子数组
			$tem && $v['nodes'] = $tem;
			$arr[] = $v;
		}
	}
	return $arr;
}

/**
 +----------------------------------------------------------
 * 生成部门select
 +----------------------------------------------------------
 * @param array $dep_arr 		部门数据数组
 * @param string $type 			添加(一级部门),编辑(只显示对应部门),过滤条件(all)
 * @param string $name 			select名称
 * @param string $select_val 	默认选择项
 * @param string $class 		样式
 +----------------------------------------------------------
 * @return array
 +----------------------------------------------------------
 */
function get_dep_select($dep_arr,$type,$name,$select_val=0,$class=""){
	if(strlen($class)>0){
		$class_str=" class='.$class.'";
	}else{
		$class_str="";
	}

	//设置部门select 第一行
	switch ($type){
		case 'add':
			$temp_str="<select name='".$name."' $class_str><option value='0'>一级部门</option>";
			break;
		case 'edit':
			$temp_str="<select name='".$name."' $class_str>";
			break;
		case 'filter':
			$temp_str="<select name='".$name."' $class_str><option value='all'>选择部门</option>";
			break;
		default:
			$temp_str="<select name='".$name."' $class_str>";
			break;
	}
	
	$split_fh="";
	$select_str="";
	foreach($dep_arr as $array){

		$lay_id=substr_count($array['par_id'],',');
		if($lay_id<>0){
			for($i=0;$i<$lay_id;$i++){
				$split_fh .= " |";
			}
			$split_fh .= "-";
		}
		if($array['id']==$select_val){
			$select_str="selected='selected'";
		}else{
			$select_str='';
		}			
			
			$temp_str .= "<option value='$array[id]' ".$select_str.">".$split_fh.$array['en_name']."</option>";
			$split_fh="";
			
	}
	
		$temp_str .="</select>";
	return $temp_str;
}

/**
 +----------------------------------------------------------
 * 根据密码和盐值生成新密码
 +----------------------------------------------------------
 * @param string $pwd 			用户密码
 * @param string $salt 			生成的盐值
 * @param string $type 			是get还是set
 +----------------------------------------------------------
 * @return array
 +----------------------------------------------------------
 */
function user_pwd($pwd,$salt,$type){
	$array=array();
	
	$array[0]['username']='111';
	$array[0]['sex']=1;
	$array[0]['address']="aaa";
	
	$array[1]['username']='111';
	$array[1]['sex']=1;
	$array[1]['address']="aaa";
	
	$array[2]['username']='111';
	$array[2]['sex']=1;
	$array[2]['address']="aaa";
	
}

/**
 +----------------------------------------------------------
 * 根据用户ID 返回该用户考勤管理人员ID字符串
 +----------------------------------------------------------
 * @param string $id 			用户ID
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function get_hr_manage_user_id($id){
	//判断hr_user_id是否为0
	$user_info=db('sys_user')->where('id='.$id)->find();

	if($user_info['id'] != config('boss_id')){
		//判断是否考勤主管
		if($user_info['is_hr_manage']==1){
			//获取本ID管理员工
			$sql="select group_concat(id) as id_str from sw_sys_user where status=1 and hr_status in (1,3) and user_status=1 and hr_user_id=".$id;
			$temp_arr=db()->query($sql);
			$manage_id_str=$temp_arr[0]['id_str'];
			
			//获取本ID管理员工下属员工
			if(strpos($manage_id_str,',')>0){
				$user_arr=explode(',',$manage_id_str);
				foreach ($user_arr as $key=>$val){
					if(strlen(get_hr_manage_user_id($val))>1){
						$manage_id_str .= ','. get_hr_manage_user_id($val);
					}					
				}
			}
		}else{
			$manage_id_str=get_user_id();
		}
	}else{
		$sql="select group_concat(id) as id_str from sw_sys_user where status=1 and id <> 1 and id <> ".$id;
		$temp_arr=db()->query($sql);
		$manage_id_str=$temp_arr[0]['id_str'];
	}
	if(strlen($manage_id_str)==0){
		return get_user_id();
	}else{
		$manage_id_str = check_douhao($manage_id_str, 0);
		return $manage_id_str.','.get_user_id();
	}	
}



/*
 *抓取各地门禁&考勤基础信息 
 */
function get_card_hr_base($flag){
	switch ($flag){
		case 'sh':
			//获取所有卡&人员&工号对应信息
			$sql="select a.emp_no,b.card_no,a.name from HR_Staff a,tblCardPub b where a.emp_no=b.emp_no;";
			$user_arr=get_mssql_info('sh', $sql);
			
			//清空员工门禁卡数据&对比员工门禁卡
			$sql="truncate table sw_user_info_sh";
			db()->query($sql);
			db('user_info_sh')->insertAll($user_arr);
			
			//人事系统中卡数据插入到sw_sh_user_info
			$sql="update sw_user_info_sh s set hr_card_no=(select card_id from sw_sys_user hr where hr.user_gh=s.emp_no);";
			db()->query($sql);
		break;
		case 'sz':
			//获取所有卡&人员&工号对应信息
			$sql="select a.emp_no,b.card_no,a.name from HR_Staff a,tblCardPub b where a.emp_no=b.emp_no;";
			$user_arr=get_mssql_info('sz', $sql);
				
			//清空员工门禁卡数据&对比员工门禁卡
			$sql="truncate table sw_user_info_sz";
			db()->query($sql);
			db('sh_user_info')->insertAll($user_arr);
				
			//人事系统中卡数据插入到sw_sh_user_info
			$sql="update sw_user_info_sz s set hr_card_no=(select card_id from sw_sys_user hr where hr.user_gh=s.emp_no);";
			db()->query($sql);
		break;
		case 'xa':
			//获取所有卡&人员&工号对应信息
			$sql="select a.emp_no,b.card_no,a.name from HR_Staff a,tblCardPub b where a.emp_no=b.emp_no;";
			$user_arr=get_mssql_info('xa', $sql);
				
			//清空员工门禁卡数据&对比员工门禁卡
			$sql="truncate table sw_user_info_xa";
			db()->query($sql);
			db('user_info_xa')->insertAll($user_arr);
				
			//人事系统中卡数据插入到sw_sh_user_info
			$sql="update sw_user_info_xa s set hr_card_no=(select card_id from sw_sys_user hr where hr.user_gh=s.emp_no);";
			db()->query($sql);
		break;
	}	
}

/*
 * 初始考勤数据库,抓取设定时间之前的所有考勤数据
 */
function reset_card_hr_rec($date="",$flag){
	switch ($flag){
		case 'sh':
			if(strlen($date)==0){
				//当月第一天
				$date=date('Y-m',time());
			}else{
				$date=date('Y-m',strtotime($date));
			}
			
			for($i=1;$i<31;$i++){
				if($i>date('d',time())){
					break;
				}
				$cur_date=$date.'-'.$i;
				$sql="select recno,ctrl_id,emp_no,card_no,CONVERT(varchar(100), entry_dt, 20) as entry_dt,convert(varchar(100),entry_dt,23) as entry_date from Agms_entryrec where entry_dt>'".($cur_date." 00:00:00")."' and entry_dt<'".($cur_date.' 23:59:59')."' and  len(emp_no) >0 and ctrl_id in (".get_card_site_str($flag).") order by recno;";
				$first_arr=get_mssql_info('sh',$sql);
				
				$sql=db('entry_dt_sh')->insertAll($first_arr);
			}
			return '上海'.$date.'月原始打卡数据抓取成功!';
		break;
		case 'xa':
			if(strlen($date)==0){
				//当月第一天
				$date=date('Y-m',time());
			}else{
				$date=date('Y-m',strtotime($date));
			}
				
			for($i=1;$i<31;$i++){
				if($i>date('d',time())){
					break;
				}
				$cur_date=$date.'-'.$i;
				$sql="select recno,ctrl_id,emp_no,card_no,CONVERT(varchar(100), entry_dt, 20) as entry_dt,convert(varchar(100),entry_dt,23) as entry_date from Agms_entryrec where entry_dt>'".($cur_date." 00:00:00")."' and entry_dt<'".($cur_date.' 23:59:59')."' and  len(emp_no) >0 and ctrl_id in (".get_card_site_str($flag).") order by recno;";
				$first_arr=get_mssql_info('xa',$sql);
		
				$sql=db('entry_dt_xa')->insertAll($first_arr);
			}
			return '西安'.$date.'月原始打卡数据抓取成功!';
			break;
		case 'sz':
			if(strlen($date)==0){
				//当月第一天
				$date=date('Y-m',time());
			}else{
				$date=date('Y-m',strtotime($date));
			}
		
			for($i=1;$i<=31;$i++){
				if(strtotime($date)>time()){
					break;
				}
				$cur_date=$date.'-'.$i;
				//$sql="select EventID as recno,31 as ctrl_id,employeecode as emp_no,card_no,CONVERT(varchar(100), entry_dt, 20) as entry_dt,convert(varchar(100),entry_dt,23) as entry_date from Agms_entryrec where entry_dt>'".($cur_date." 00:00:00")."' and entry_dt<'".($cur_date.' 23:59:59')."' and  len(emp_no) >0 and ctrl_id in (".get_card_site_str($flag).") order by recno;";
				$sql="select v.EventID as recno,31 as ctrl_id,t.employeecode as emp_no,v.CardNo as card_no,CONVERT(varchar(100), v.EventTime, 20) as entry_dt,convert(varchar(100),v.EventTime,23) as entry_date from TEvent v,temployee t where v.CardNo=t.CardNo and v.EventTime>'".($cur_date." 00:00:00")."' and v.EventTime<'".($cur_date.' 23:59:59')."' and  len(v.EmployeeID) >0  order by v.EventID;";
				$first_arr=get_mssql_info('sz',$sql);
		
				$sql=db('entry_dt_sz')->insertAll($first_arr);
			}
			return '深圳'.$date.'月原始打卡数据抓取成功!';
			break;
	}
}

/**
 * 获取打卡地点字符串
 */
function get_card_site_str($site_flag){
	$site_arr=config('hr_card_site_'.$site_flag);
	
	$temp_str="";
	foreach ($site_arr as $key=>$val){
		$temp_str .= $val['id'].',';
	}
	return check_douhao($temp_str, 0);	
}

/**
 * 获取考勤标记in字段
 */
function get_hr_site_in($flag="hr_flag",$site_id){
	$in_str="";
	$site_code=db('sys_site')->where('id='.$site_id)->value('site_code');
	$config_name='hr_card_site_'.strtolower($site_code);
	
	if($flag=='hr_flag'){
		foreach (config($config_name) as $key=>$val){
			if($val['hr_flag']==1){
				$in_str .= $val['id'].",";
			}
		}
	}else{
		foreach (config($config_name) as $key=>$val){
			if($val['food_flag']==1){
				$in_str .= $val['id'].",";
			}
		}
	}
	return check_douhao($in_str, 0);
}

/**
 * 返回打卡点名称
 */
function get_card_site_name($site_id,$ctrl_id){
	$hr_card_site_arr=config('hr_card_site_'.get_site_code($site_id));
	foreach ($hr_card_site_arr as $key=>$val){
		if($val['id']==$ctrl_id){
			return $val['name'];
		}
	}
}

/*
 * 获取某站点某月份假日数据
 * $date  年-月
 * $site_id 那个站点
 * $init_flag 是初始日历组件还是只是返回某月份日历参数
 * 返回zui - calendar 需要数组格式
 */
function get_site_holiday($date,$site_id,$init_flag=false){
	
	$month_arr=get_begin_last_date($date.'-01');
	
	$holiday_arr=db('sys_holiday')->where('site_id='.$site_id." and holiday_date between '".$month_arr[0]."' and '".$month_arr[1]."' ")->select();
		$temp_arr=array();
		
		if($init_flag){		
			$return_arr=array();
			$return_arr['calendars'][0]['name']='danger';
			$return_arr['calendars'][0]['color']='red';
			$return_arr['calendars'][1]['name']='success';
			$return_arr['calendars'][1]['color']='green';
			$return_arr['calendars'][2]['name']='warning';
			$return_arr['calendars'][2]['color']='yellow';
			$return_arr['calendars'][3]['name']='info';
			$return_arr['calendars'][3]['color']='blue';
			$return_arr['calendars'][4]['name']='important';
			$return_arr['calendars'][4]['color']='brown';
			$return_arr['calendars'][5]['name']='special';
			$return_arr['calendars'][5]['color']='purple';
			$return_arr['calendars'][6]['name']='primary';
			$return_arr['calendars'][6]['color']='primary';
			
			$return_arr['events']=array();
			
			if($holiday_arr){
				foreach ($holiday_arr as $key=>$val){
					if($val['holiday_type']==1){
						$color='danger';
					}else{
						$color='primary';
					}
					
					$return_arr['events'][$key]['title']=$val['holiday_name'];
					$return_arr['events'][$key]['desc']='';
					$return_arr['events'][$key]['calendar']=$color;
					$return_arr['events'][$key]['allDay']='true';
					$return_arr['events'][$key]['start']=$val['holiday_date'];
					$return_arr['events'][$key]['end']=$val['holiday_date'];
				}
			}
		}else{
			foreach ($holiday_arr as $key=>$val){
				
				if($val['holiday_type']==1){
					$color='danger';
				}else{
					$color='primary';
				}
				
				$return_arr[$key]['title']=$val['holiday_name'];
				$return_arr[$key]['desc']='';
				$return_arr[$key]['calendar']=$color;
				$return_arr[$key]['allDay']='true';
				$return_arr[$key]['start']=$val['holiday_date'];
				$return_arr[$key]['end']=$val['holiday_date'];
			}
		}
		return $return_arr;
}

/*获取站点标识符*/
function get_site_code($site_id){
	return strtolower(get_cache_data('site_info',$site_id,'site_code'));
}

/**
 * 获取某用户上级主管
 */
function get_hr_user_id($user_id,$flag='hr_user_id'){

	//判断该用户上级主管是否设置
	if(!isset(config('user_info')[$user_id]['hr_user_id'])){
		echo setServerBackJson(0,'用户考勤主管获取失败!');
		exit;
	}else{
		$hr_user_id=config('user_info')[$user_id]['hr_user_id'];
		
		if($flag=='hr_adv_user_id'){
			//判断该用户转上级主管是否设置
			if(!isset(config('user_info')[$hr_user_id]['hr_user_id'])){
				echo setServerBackJson(0,'用户上上级考勤主管获取失败!');
				exit;
			}else{
				$hr_adv_user_id=config('user_info')[$hr_user_id]['hr_user_id'];
				return $hr_adv_user_id;
			}
		}else{
			return $hr_user_id;
		}
	}
}


/**
 * 递归获取某用户下属员工
 * $user_id 要获取的某用户ID
 * $id_str 递归时传递过来的拼接字符
 */
function get_user_sub_user($user_id,$id_str=""){
	if(strlen($id_str)>0){				
		$sql="select group_concat(id) as id_str from sw_sys_user where user_status=1 and  status=1 and hr_user_id in (".$id_str.")";
		$sub_user_str_arr=db()->query($sql);
		$return_str=$sub_user_str_arr[0]['id_str'];
		
		//判断下属员工中是否有考勤主管
		if(strlen($return_str)>0){
			$sql="select group_concat(id) as id_str from sw_sys_user where id in (".$return_str.") and is_hr_manage=1 and user_status=1 and status=1";
			$sub_user_str_arr=db()->query($sql);
			if(strlen($sub_user_str_arr[0]['id_str'])>0){
				$return_str .=','.get_user_sub_user('',$sub_user_str_arr[0]['id_str']);
			}
		}
	}else{
		$sql="select group_concat(id) as id_str from sw_sys_user where user_status=1 and status=1 and hr_user_id=".$user_id;	
		$sub_user_str_arr=db()->query($sql);
		$return_str=$sub_user_str_arr[0]['id_str'];
		
		//判断下属员工中是否有考勤主管
		if(strlen($return_str)>0){
			$sql="select group_concat(id) as id_str from sw_sys_user where id in (".$return_str.") and status=1 and user_status=1 and is_hr_manage=1";
			$sub_user_str_arr=db()->query($sql);
			if(strlen($sub_user_str_arr[0]['id_str'])>0){
			 	$return_str .=','.get_user_sub_user('',$sub_user_str_arr[0]['id_str']);
			}
		}		
	}
	if(strlen($return_str)==0){
		$return_str=get_user_id();
	}else{
		$return_str.=','.get_user_id();
	}
	return $return_str;
}

//根据部门层次结构字符或者部门ID返回字符标头 如 MCU/FAE
//$type id或者par_id,$level 层次结构第几层开始
function get_dep_tit_str($val,$type='par_id',$level=2){
	$dep_arr_temp=db('sys_dep')->select();
	$dep_arr=array();
	$return_str="";
		
	//部门数组处理
	foreach ($dep_arr_temp as $k=>$v){
		$dep_arr[$v['id']]=$v;
	}
	
	switch ($type){
		case 'par_id':
			$par_id_str=$val;
			break;
		case 'dep_id':
			$par_id_str=$dep_arr[$val]['par_id'];
			break;
		default:
			$par_id_str=$val;
			break;			
	}	
	
	if($type=='par_id'){
		$temp_arr=explode(',',$par_id_str);
		$i=$level;
		for($i;$i<count($temp_arr);$i++){
			if($i<count($temp_arr)-1){
				$return_str .= $dep_arr[$temp_arr[$i]]['en_name'].'/';
			}else{
				$return_str .= $dep_arr[$temp_arr[$i]]['en_name'];
			}
			
		}
	}
	
	return $return_str;
}


//检查客户端ip地址
function check_client_ip()
{
	$ip = get_client_ip();
	//取出IP段
	$ip_str = explode('.',$ip);
	$ip_segment = $ip_str[0].".".$ip_str[1].".".$ip_str[2];

	//在数据库中查找是否次IP段
	$segment = Db::name('SysIp')->where('ip',$ip_segment)->where('ip_type',1)->find();
	if($segment)
	{
		return 1;
	};
	//数据中查找是否有此IP
	$ip_result =  Db::name('SysIp')->where('ip',$ip)->where('ip_type',0)->find();
	if($ip_result)
	{
		return 1;
	};
	return 0;
}





















































































