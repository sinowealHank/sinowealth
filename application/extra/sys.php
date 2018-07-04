<?php
//sys 模块共用函数

/*
 * 定时抓取考勤数据
 * $site_flag 抓取那个点数据
 * $flag 是间隔5分钟抓还是实时抓取
 */
function get_card_hr_rec($site_code,$flag="interval"){
	$update_flag=false;
	$record_count=0;

	if($flag=="interval"){
		//判断最后一次抓取时间到现在是否超过设定抓取时间

		//获取最后一次更新时间
		$last_update_time=db('remark_flag')->where(" name='last_get_data_".$site_code."'")->value('value');

		$interval=config('hr_interval_time');

		//相差秒
		$timediff=(time()-strtotime($last_update_time));
		if($timediff>((int)$interval*60)){
			$update_flag=true;
		}
	}elseif($flag=='get'){
		$update_flag=true;
	}	
	
	//判断与该站点网络是否正常
	$hr_db_arr=CONFIG('hr_souce_data')[$site_code];

	try{
		$fp=fsockopen($hr_db_arr['HOST'],1433,$errno,$errstr,2);
		$conn_flag=true;
	}catch(Exception $e){
		$conn_flag=false;
	}

	if($update_flag && $conn_flag){
		switch($site_code){
			case 'sh':
				//获取当前考勤表中最大记录
				$min_rec=db('entry_dt_sh')->max('recno');
				//如果数据库中无任何打卡记录,则跳出循环,此处只做数据同步,初始数据会抓所有数据)
				if($min_rec==0){
					break;
				}
					
				//获取当前门禁表中最大记录
				$sql="select MAX(recno) as recno from Agms_entryrec;";
				$temp_arr=get_mssql_info('sh', $sql);
				$max_rec=$temp_arr[0]['recno'];
					
				if($max_rec>$min_rec){
					//取数据差值
					$sql="select recno,ctrl_id,emp_no,card_no,CONVERT(varchar(100), entry_dt, 20) as entry_dt,convert(varchar(100),entry_dt,23) as entry_date from Agms_entryrec where recno>".$min_rec." and recno<=".$max_rec." and ctrl_id in (".get_card_site_str($site_code).") order by recno;";
					$temp_arr=get_mssql_info('sh', $sql);
					$record_count=count($temp_arr);
					db('entry_dt_sh')->insertAll($temp_arr);
				}

				break;
			case 'xa':
				//获取当前考勤表中最大记录
				$min_rec=db('entry_dt_xa')->max('recno');
				//如果数据库中无任何打卡记录,则跳出循环,此处只做数据同步,初始数据会抓所有数据)
				if($min_rec==0){
					break;
				}
					
				//获取当前门禁表中最大记录
				$sql="select MAX(recno) as recno from Agms_entryrec;";
				$temp_arr=get_mssql_info('xa', $sql);
				$max_rec=$temp_arr[0]['recno'];
					
				if($max_rec>$min_rec){
					//取数据差值
					$sql="select recno,ctrl_id,emp_no,card_no,CONVERT(varchar(100), entry_dt, 20) as entry_dt,convert(varchar(100),entry_dt,23) as entry_date from Agms_entryrec where recno>".$min_rec." and recno<=".$max_rec." and ctrl_id in (".get_card_site_str($site_code).") order by recno;";
					$temp_arr=get_mssql_info('xa', $sql);
					$record_count=count($temp_arr);
					foreach ($temp_arr as $k=>$v){
						db('entry_dt_xa')->insert($v);
					}
				}
					
				break;
			case 'sz':
				//获取当前考勤表中最大记录
				$min_rec=db('entry_dt_sz')->max('recno');
				//如果数据库中无任何打卡记录,则跳出循环,此处只做数据同步,初始数据会抓所有数据)
				if($min_rec==0){
					break;
				}
					
				//获取当前门禁表中最大记录
				$sql="select MAX(EventID) as recno from TEvent;";
				$temp_arr=get_mssql_info('sz', $sql);
				$max_rec=$temp_arr[0]['recno'];
					
				if($max_rec>$min_rec){
					//取数据差值
					$sql="select v.EventID as recno,31 as ctrl_id,t.employeecode as emp_no,v.CardNo as card_no,CONVERT(varchar(100), v.EventTime, 20) as entry_dt,convert(varchar(100),EventTime,23) as entry_date from TEvent v,temployee t where v.CardNo=t.CardNo and v.EventID>".$min_rec." and v.EventID<=".$max_rec." order by v.EventID;";
					$temp_arr=get_mssql_info('sz', $sql);
					$record_count=count($temp_arr);
					foreach ($temp_arr as $k=>$v){
						db('entry_dt_sz')->insert($v);
					}
				}
				break;
		}
		db('remark_flag')->where("name='last_get_data_".$site_code."'")->setField('value',date('Y-m-d H:i:s',time()));
		return $record_count;
	}

}



/**
 * 计算所有用户考勤
 */
function calculate_hr($date,$site_id=1,$cal_flag=''){
	switch ($site_id){
		case 1:
			get_card_hr_rec('sh');
			break;
		case 2:
			get_card_hr_rec('sz');
			break;
		case 3:
			get_card_hr_rec('xa');
			break;
	}

	$year=date('Y',strtotime($date));
	$month=(int)date('m',strtotime($date));
	//判断该站点是否已结转,如果已结转,则不进行运算
	$is_finish=db('sys_event')->where('site_id='.$site_id." and year=".$year.' and month='.$month.' and type_flag=2')->find();
	
	if(!$is_finish){
		//header("Content-type: text/html; charset=utf-8");
		//echo $date.'  '.get_site_code($site_id).' 考勤数据未结转,开始进行计算!<br />';

		$user_arr=db('sys_user')->where('status=1 and hr_status in (1,3) and (length(card_id)>0 or length(out_card_id)>0) and cal_hr_user=1 and user_status=1 and site_id='.$site_id)->select();
		$month_arr=get_begin_last_date($date);
		
		//清理某月份之前的重复考勤记录
		$sql="DELETE
				FROM
				    sw_entry_dt_".get_site_code($site_id)."
				WHERE
				    recno IN (
				       select recno from  (SELECT
				            recno
				        FROM
				            sw_entry_dt_".get_site_code($site_id)."
						where entry_date>'".$year.'-'.$month.'-01'."'
				        GROUP BY
				            recno
				        HAVING
				            count(recno) > 1
				         )  a 
				    ) 
				AND id NOT IN (
				    select id from (SELECT
				        min(id) as id
				    FROM
				        sw_entry_dt_".get_site_code($site_id)."
					where entry_date >='".$year.'-'.$month.'-01'."'
				    GROUP BY
				        recno
				    HAVING
				        count(recno) > 1
					)b
				)
				";
				
		db()->query($sql);
		
		if($site_id==1 || $site_id==3){
			//规避非有效打卡记录
			set_entry_status($month_arr,$site_id);
			//echo '规避非有效打开完成!<br />';
		}		
		
		$count_num=count($user_arr);
		db()->query('truncate table sw_sys_temp_log');
		//echo '共计计算用户数'.$count_num.'人考勤数据!<br />';
		foreach ($user_arr as $key=>$val){			
			$month_hr_table_arr=calculate_user_hr_table($site_id,$date,$val['id'],$cal_flag);
				
			foreach ($month_hr_table_arr as $k=>$v){
				//判断本条记录在hr_table中是否存在,如果存在,则更新,如果不存在,则插入
				$hr_table_id=db('hr_table')->where('user_id='.$v['user_id']." and hr_date='".$v['hr_date']."'")->value('id');
				if($hr_table_id){
					$v['id']=$hr_table_id;
					db('hr_table')->update($v);
				}else{
					unset($v['id']);
					db('hr_table')->insert($v);
				}
			}
		}
		return '站点'.cache('site_cache_arr')[$site_id]['site'].date('Y-m',strtotime($date)).'考勤批量计算完成!';
	}else{
		return '站点'.cache('site_cache_arr')[$site_id]['site'].date('Y-m',strtotime($date)).'考勤数据已结转,无法重新计算!';
	}
}



/**
 * 计算某用户指定月份考勤数据
 * $cal_flag 是否只计算有问题的日期
 */
function calculate_user_hr_table($site_id,$date,$user_id="",$cal_flag=''){
	
	if(strlen($user_id)==0){
		$user_id=get_user_id();
	}
	
	//获取该用户信息
	$user_info=db('sys_user')->where('id='.$user_id)->find();
	
	//该站点信息获取
	$site_info=db('sys_site')->where('id='.$site_id)->find();
	
	//设置初始考勤开始时间
	if(strlen($site_info['hr_begin_time'])==0){
		$site_info['hr_begin_time']='08:00:00';
	}

	//判断用户是否计算考勤
	$hr_flag=$user_info['is_hr_user'];
	
	//公司事件
	/*
	 * 1:本日考勤是否正常(用户打卡,申请单,手动调整数据)
	 * 2:打卡记录
	 * 3:用餐记录
	*/
	$month_begin_end_arr=get_begin_last_date($date);
	
	if(date('Y-m-d',strtotime($month_begin_end_arr[1]))>date('Y-m-d',time())){
		$month_begin_end_arr[1]=date('Y-m-d',time()).' 23:59:59';
	}
	
	/*
	 //如果时间范围为本月,则将最后一天设定为当前日期
	 if(date('m',time())==date('m',strtotime($month_day_arr[0]))){
	 $month_day_arr[1]=date('Y-m-d',time());
	 }
	 */	
	
	//判断该用户是否属于长时间外站出差
	if($user_info['out_site_id']<>0 && strlen($user_info['out_card_id'])>0){
		$user_info['site_id']=$user_info['out_site_id'];
		$user_info['card_id']=$user_info['out_card_id'];
		//将对应表中的打卡数据与卡对应关系调整
		$out_tb_flag=get_site_code($user_info['out_site_id']);
		$sql_1="update sw_entry_dt_".$out_tb_flag." set emp_no='".$user_info['user_gh']."' where card_no='".$user_info['out_card_id']."' and entry_date between '".$month_begin_end_arr[0]."' and '".$month_begin_end_arr[1]."'";
		db()->query($sql_1);
	}

	$site_code=get_site_code($user_info['site_id']);

	$log_msg="";
	$log_msg='当前计算用户['.$user_info['nickname'].']'.$date.'考勤数据!用户ID:'.$user_info['id'].' 工号:'.$user_info['user_gh'].'<br>';
	$temp_arr=array();
	$temp_arr['val']=$log_msg;
	$temp_arr['date_time']=get_date_time();
	
	db('sys_temp_log')->insert($temp_arr);	

	if($cal_flag=='err_date'){
		//只计算有问题的数据(考勤维护表中人事计算有问题的数据)
		$where_str=" and md.val in (select hr_date from sw_hr_table where user_id=".$user_id." and  hr_date between '".$month_begin_end_arr[0]." ".config('hr_begin_time')."' and '".$month_begin_end_arr[1]." ".config('hr_end_time')."' and hr_status=0)";
	}else{
		$where_str='';
	}
	
	//如果为上海站点,则只取status=1的记录参与计算
	if($user_info['site_id']==1 || $user_info['site_id']==3){
		$where_str .= " and dt.status=1";
	}

	//考虑到用户出差情况,判断某用户在时间范围内是否有打卡记录,如果没有,则不添加打卡记录中时间范围选型,否则会计算出空值,导致考勤数据计算错误,还要加上是不是打的考勤卡...
	$sql="select count(*) as count_num from sw_entry_dt_".$site_code." where ctrl_id in (".get_hr_site_in('hr_flag',$user_info['site_id']).") and card_no='".$user_info['card_id']."' and entry_dt between '".$month_begin_end_arr[0]." ".config('hr_begin_time')."' and '".$month_begin_end_arr[1]." ".config('hr_end_time')."'";
	$user_month_card_count_arr=db()->query($sql);

	if($user_month_card_count_arr[0]['count_num']==0){
		$month_where_str="";
	}else{
		$month_where_str=" and dt.entry_dt between '".$month_begin_end_arr[0]." ".config('hr_begin_time')."' and '".$month_begin_end_arr[1]." ".config('hr_end_time')."' ";
	}
	
	//如果在晚上11点前,不计算当日, 如果是晚上11点后,则计算当日
	if(date('H',time())>22){
		$time_str=" and md.val<=date_format(now(),'%Y-%m-%d') ";
	}else{
		$time_str=" and md.val<date_format(now(),'%Y-%m-%d') ";
	}

	$sql="
			select
			    su.id as user_id,su.hr_status as hr_user_status,su.user_gh,
				(case when su.out_site_id<>0 and length(su.out_card_id)>0 then out_card_id else card_id end ) as card_id,
				su.nickname,su.site_id,su.hr_role_id,md.val as hr_date,
			    (select id from sw_sys_holiday sh where sh.site_id=".$user_info['site_id']." and sh.holiday_date=md.val) as holiday_id,
				(select count(*) from sw_entry_dt_".$site_code." dt1 where dt1.entry_date=md.val and dt1.ctrl_id=".get_hr_site_in('food_flag',$user_info['site_id'])." and dt1.emp_no='".$user_info['user_gh']."' and dt1.entry_dt between concat(md.val,' ".config('hr_breakfast_begein').":00') and  concat(md.val,' ".config('hr_breakfast_end').":00')) as breakfast_count,
			    (select count(*) from sw_entry_dt_".$site_code." dt1 where dt1.entry_date=md.val and dt1.ctrl_id=".get_hr_site_in('food_flag',$user_info['site_id'])." and dt1.emp_no='".$user_info['user_gh']."' and dt1.entry_dt between concat(md.val,' ".config('hr_lunch_begin').":00') and  concat(md.val,' ".config('hr_lunch_end').":00')) as lunch_count,
			    (select count(*) from sw_user_note_item un where user_id=".$user_info['site_id']." and note_type=2 and begin_time= concat(md.val,' 00:00:00')) as dinner_count,
			    (select group_concat(id) from sw_user_note_item sn where sn.user_id=su.id and note_type<>2 and sn.begin_time>=concat(md.val,' 00:00:00') and sn.end_time<=concat(md.val,' 23:59:59')) as note_id_str,
			    min(case when dt.entry_date=md.val then dt.entry_dt end) as hr_card_first,
			    max(case when dt.entry_date=md.val and dt.entry_dt>=concat(md.val,' 07:29:59') then dt.entry_dt end) as hr_card_end,
			    sum(case when dt.entry_date=md.val then 1 else 0 end) as card_count,
			    now() as last_u_time,now() as last_cal_time
			 
			 from sw_entry_dt_".$site_code." dt,sw_sys_month_day md,sw_sys_user su
			 where
				md.year=".date('Y',strtotime($date))." and md.month=".date('m',strtotime($date))." and dt.status=1  and su.user_gh=dt.emp_no  and su.id=".$user_id." and dt.ctrl_id in (".get_hr_site_in('hr_flag',$user_info['site_id']).") and hour(dt.entry_dt)>5 
			    ".$month_where_str.$time_str." and md.val>=su.entry_date
			    ".$where_str."
			group by md.val
			order by md.val
			";

	$hr_table_arr=db()->query($sql);
	//本日考勤数据是否正常,1:正常,2:异常
	
	//配置参数获取
	$hr_status=0;
	
	//考勤最早开始计算时间
	$hr_begin_time_c=config('hr_begin_time');
	//早上最晚打卡时间
	$hr_begin_end_time_c=config('hr_day_begin_end_time');
	//考勤最晚结束计算时间
	$hr_end_time=config('hr_end_time');	

	$weekarray=array("日","一","二","三","四","五","六");

	//中午计算时间范围
	$hr_noon_begin_c=config('hr_noon_begin');
	$hr_noon_end_c=config('hr_noon_end');

	$hr_work_act_time=config('hr_work_act_time');
	
	foreach ($hr_table_arr as $key=>$val){
		$begin_card_early=false;		//标记是否6:00~7:30区间打卡
		$firstr_card_rec="";			//缓存第一次打卡时间
		$early_str="";					//如果是第一次打卡时间在此区间,添加7:30开始计算考勤字符说明
		$note_id_str="";				//主申请单ID字符串
		
		//判断该用户是否属于长时间外站出差
		if($user_info['out_site_id']<>0 && strlen($user_info['out_card_id'])>0){
			$val['site_id']=$user_info['out_site_id'];
			$val['card_id']=$user_info['out_card_id'];
		}
		
		//本日后日期,考勤计算自动跳出
		if($val['hr_date']>date('Y-m-d',time())){
			break;
		}
		//如果开始时间小于7:30,则按照7:30开始计算
		if(strlen($val['hr_card_first'])>0 && strtotime($val['hr_card_first'])<strtotime($val['hr_date'].' '.$site_info['hr_begin_time'])){
			$firstr_card_rec=$val['hr_card_first'];
			$val['hr_card_first']=$val['hr_date'].' '.$site_info['hr_begin_time'];
			$begin_card_early=true;
		}
		
		//上海设置最早打卡点7:30,西安设置最早打卡点为8:00,深圳设置最早打卡点为8:30
		if($begin_card_early){
			$early_str='<br>'.$firstr_card_rec."打卡,".$site_info['hr_begin_time']."点开始计算考勤!";
		}

		$holiday_arr=array();
		$note_arr=array();
		$note_time=0;
		$cal_flag=0;
		$out_work_str="";
		
		//当前循环体内用于计算的工作时间
		if($site_code<>'tw'){
			$c_hr_work_time=get_user_work_time($val['site_id'],$val['user_gh'],$val['hr_date']);
		}else{
			//每日考勤时间
			$hr_sec_range=config('hr_sec_range');
			$c_hr_work_time=config('hr_work_time')*60*60-$hr_sec_range;
		}

		$hr_table_arr[$key]['title']="班";
		$hr_table_arr[$key]['out_work_time']=0;
		$hr_table_arr[$key]['note_time']=0;
		$hr_table_arr[$key]['act_time']=0;
		$hr_table_arr[$key]['abs_time']=0;
		$hr_table_arr[$key]['z_note_time']=0;
		$hr_table_arr[$key]['z_work_time']="";
		$hr_table_arr[$key]['z_work_time_str']="";
		$hr_table_arr[$key]['z_first_time_array']="";
		$hr_table_arr[$key]['z_late_note_flag']=0;
		$hr_table_arr[$key]['z_out_work_time']=0;
		$hr_table_arr[$key]['z_work_need_time']=0;
		$hr_table_arr[$key]['z_one_card']=0;
		$hr_table_arr[$key]['hr_status']=0;
		$hr_table_arr[$key]['hr_status_remark']="";
		$hr_table_arr[$key]['hr_remark']="";
		$hr_table_arr[$key]['z_cal_flag']=0;
		$user_work_time=0;		//当前循环体内用户工作了多少时间计数

		//判断是否奇偶次
		if($hr_table_arr[$key]['card_count']%2==0){
			$val['is_odd']=0;
			$hr_table_arr[$key]['is_odd']=0;
		}else{
			$val['is_odd']=1;
			$hr_table_arr[$key]['is_odd']=1;
		}

		//如果系统判断为实习生,则不扣餐费
		if($hr_table_arr[$key]['hr_user_status']==3){
			//$hr_table_arr[$key]['breakfast_count']=0;
			$hr_table_arr[$key]['lunch_count']=0;
		}
		
		//获取假日信息
		if(strlen($val['holiday_id'])>0){
			$holiday_arr=db('sys_holiday')->where('id='.$val['holiday_id'])->find();
			$hr_table_arr[$key]['title']=$holiday_arr['holiday_name'];
		}

		//考勤人员考勤数据计算
		if($hr_flag){
			
			//计算用户出勤时间
			if(config('hr_cal_ext_flag')==1  && $user_info['site_id']==1 && $val['card_count']>=3){
				//4次或者6次打卡,计算用户出勤时间,重新计算$user_work_time时间数
				$user_work_time=get_user_work_time_more($user_info['user_gh'],$val['hr_date'],$user_info['site_id']);
			}else{
				//2次打卡,计算1前一后时间差
				if($val['card_count']>1){
					//如果用户打卡2次都在午间,则计算出勤时间为0
					if($val['hr_card_first']>$hr_noon_begin_c && $val['hr_card_end']<$hr_noon_end_c){
						$user_work_time=0;
					}else{
						$user_work_time=timediff($val['hr_card_first'], $val['hr_card_end']);
					}					
				}else{
					//单次打卡,出勤时间为0
					$user_work_time=0;
				}				
			}
				
			//拼接本日考勤最早,最晚打卡时间
			$hr_begin_time=strtotime($val['hr_date']." ".$hr_begin_time_c);
			$hr_begin_end_time=strtotime($val['hr_date']." ".$hr_begin_end_time_c);
				
			//拼接本日中午考勤时间段
			$hr_noon_begin=strtotime($val['hr_date']." ".$hr_noon_begin_c);
			$hr_noon_end=strtotime($val['hr_date']." ".$hr_noon_end_c);
				
			//获取申请单信息
			if(strlen($val['note_id_str'])>0){
				$note_arr=db('user_note_item')->where(" id in (".$val['note_id_str'].")")->select();

				//如果只有一张申请单,则把这张申请单的主ID,申请单类型一起更新,为后续计算考勤数据提供依据
				if(count($note_arr)==1){
					$hr_table_arr[$key]['note_id']=$note_arr[0]['note_id'];
					$hr_table_arr[$key]['note_type']=$note_arr[0]['note_type'];
					$hr_table_arr[$key]['hr_note_id']=$note_arr[0]['hr_note_id'];
					$note_id_str=$note_arr[0]['note_id'];
				}else{
					//如果有2张申请单,则晚到单不做考勤计算依据,只计算请假单
					foreach ($note_arr as $k=>$v){
						if($v['note_type']==1){
							$hr_table_arr[$key]['note_id']=$v['note_id'];
							$hr_table_arr[$key]['note_type']=$v['note_type'];
							$hr_table_arr[$key]['hr_note_id']=$v['hr_note_id'];
							$note_id_str .=$v['note_id'].',';
						}else{
							//删除晚到申请单
							unset($note_arr[$k]);
						}
					}
				}
			}
			//计算请假单中类型为1的请假时数
			if(count($note_arr)>0){
				foreach ($note_arr as $k=>$v){
					//小于8小时假单
					if($v['note_type']==1 && $v['note_hour']<=8){
						$note_time += $v['note_hour'];
					}else{
						//大于8小时假单,计算到本日是否还够时间扣
							
						//判断是否在同一日
						if(date('Y-m-d',strtotime($v['begin_time']))==date('Y-m-d',strtotime($v['end_time']))){
							$note_time =$v['note_hour'];
						}
							
						//判断本日是否假日,如果假日,则$note_time=0,如果不为假日,则
							
					}
				}
			}
			$hr_table_arr[$key]['z_note_time']=$val['z_note_time']=$note_time;
			
			//考勤判断数据填充
			//出勤时间数对照
			$hr_table_arr[$key]['z_work_time']=$user_work_time['hour'].':'.$user_work_time['min'].':'.$user_work_time['sec'];
			$hr_table_arr[$key]['z_work_time_str']=$val['z_work_time']=' 本日考勤时间计算数['.round($c_hr_work_time/(60*60),2).'小时 ('.$c_hr_work_time.'秒)] 用户出勤时间数['.$user_work_time['h'].'小时 '.'('.$user_work_time['s'].')秒]';
				
			//第一次打卡时间范围对照
			$hr_table_arr[$key]['z_first_time_array']=$val['z_first_time_array']=$hr_begin_time.' - '.strtotime($val['hr_card_first']).' - '.$hr_begin_end_time;
				
			if(strlen($val['note_id_str'])>0 && strlen($val['hr_card_first'])>0 && strlen($val['hr_card_end'])>0){
				//判断是否有晚到申请单,对晚到申请单晚到几个小时不考虑,只考虑当日出勤时间
				$is_have_late_note=db('user_note_item')->where('user_id='.$val['user_id']." and note_type=3 and date_format(begin_time,'%Y-%m-%d')='".date('Y-m-d',strtotime($val['hr_card_first']))."'")->find();
					
				if($is_have_late_note){
					$hr_table_arr[$key]['z_late_note_flag']=$val['z_late_note_flag']=1;
				}else{
					$hr_table_arr[$key]['z_late_note_flag']=$val['z_late_note_flag']=0;
				}
			}else{
				$hr_table_arr[$key]['z_late_note_flag']=$val['z_late_note_flag']=0;
			}

			//判断是否单次打卡
			if((strtotime($val['hr_card_first'])==strtotime($val['hr_card_end']) && (strlen($val['hr_card_first'])>0 || strlen($val['hr_card_end'])>0)) || $val['card_count']==1){
				$hr_table_arr[$key]['z_one_card']=$val['z_one_card']=1;
			}else{
				$hr_table_arr[$key]['z_one_card']=$val['z_one_card']=0;
			}
			
			//计算本日出勤情况,如果有高标,则返回高标值  com,如果缺勤,则计算缺多少时间.0.5 起算 user
			$work_time_val=$user_work_time['s']-$c_hr_work_time;
			
			if($work_time_val>=0){
				//奇数次打卡不计算高标
				if($val['is_odd']==0){
					//高标
					$hr_table_arr[$key]['z_out_work_time']=$val['z_out_work_time']=get_out_work_time($user_work_time,$c_hr_work_time);
					$hr_table_arr[$key]['z_work_need_time']=$val['z_work_need_time']=0;
					//echo 'dd'.$val['z_out_work_time'].'--';exit;
				}else{
					$hr_table_arr[$key]['z_out_work_time']=$val['z_out_work_time']=0;
					$hr_table_arr[$key]['z_work_need_time']=$val['z_work_need_time']=0;
				}
				
			}else{
				//不够出勤时间
				//echo $c_hr_work_time-$user_work_time['s'];exit;
				$hr_table_arr[$key]['z_out_work_time']=$val['z_out_work_time']=0;
				$hr_table_arr[$key]['z_work_need_time']=$val['z_work_need_time']=int_time($c_hr_work_time-$user_work_time['s'],'add');
			}

			
//-------------------------------------------------------------------------------------------------------------------------------------	
			//$user_work_time['s']>=$c_hr_work_time || $val['z_note_time']>=$val['z_work_need_time'] || $val['z_note_time']==$hr_work_act_time
// 			echo $val['z_work_need_time'].'---<br>';
// 			echo "user_work_time['s']>=c_hr_work_time  ";echo $user_work_time['s']-$c_hr_work_time.'<br>';	
// 			echo "val['z_note_time']>=val['z_work_need_time'] "; echo $val['z_note_time']-$val['z_work_need_time'].'<br>';
// 			pr($hr_table_arr[$key]);	
// 			echo date('Y-m-d H:i:s',$hr_begin_end_time);exit;
			
			//工作日出勤正常&不正常
			if(strlen($val['holiday_id'])==0 && ($val['card_count']<=9 || $val['site_id']<>1) && ($user_work_time['s']>=$c_hr_work_time || $val['z_note_time']>=$val['z_work_need_time'] || $val['z_note_time']==$hr_work_act_time) && ((strlen($val['note_id_str'])>0 && strtotime($val['hr_card_first'])>=$hr_begin_end_time) || strtotime($val['hr_card_first'])<=$hr_begin_end_time) && $cal_flag==0){
				//echo '出勤正常循环体<br>';
				
				$remark_str="";
				$hr_table_arr[$key]['hr_status']=1;
				//高标判断
				if($val['z_out_work_time']>0){
					$hr_table_arr[$key]['out_work_time']=$val['z_out_work_time'];
					$remark_str="高标".$val['z_out_work_time']."小时.";
				}else{
					$hr_table_arr[$key]['out_work_time']=0;
					$remark_str="无高标.";
				}

				//申请单判断
				if($val['z_note_time']>0 && ($val['z_note_time']>=$val['z_work_need_time'] ||  $val['z_note_time']==$hr_work_act_time)){
					if($val['z_note_time']==8 && $val['z_work_need_time']>0){
						//计算申请时间数&实际扣除时间数
						$hr_table_arr[$key]['note_time']=$val['note_time']=$hr_work_act_time;
						$hr_table_arr[$key]['act_time']=$val['act_time']=$hr_work_act_time;
						$remark_str .= "申请单:".$hr_work_act_time."小时,实扣:".$hr_work_act_time.".";
						$hr_table_arr[$key]['z_cal_flag']=1;
						$cal_flag=1;
					}else{
						//计算申请时间数&实际扣除时间数
						$hr_table_arr[$key]['note_time']=$val['note_time']=$note_time;
						$hr_table_arr[$key]['act_time']=$val['act_time']=get_note_need_time($val['z_work_need_time'],$note_time);
						$remark_str .= "申请单:".$note_time."小时,实扣:".get_note_need_time($val['z_work_need_time'],$note_time)."小时.";
						$hr_table_arr[$key]['z_cal_flag']=2;
						$cal_flag=1;
					}
				}else{
					$hr_table_arr[$key]['note_time']=0;
					$hr_table_arr[$key]['act_time']=0;
					//是否有晚到申请单
					if($val['z_late_note_flag']==1){
						$remark_str .= "晚到,有晚到申请单.";
						$hr_table_arr[$key]['z_cal_flag']=3;
						$cal_flag=1;
					}else{
						$remark_str .= "无申请单.";
						$hr_table_arr[$key]['z_cal_flag']=4;
						$cal_flag=1;
					}
						
				}
					
				if($val['z_one_card']==1){
					$hr_table_arr[$key]['hr_status_remark']=$val['hr_date']."日(周".$weekarray[date('w',strtotime($val['hr_date']))].",班) 系统记录".date('H:i:s',strtotime($val['hr_card_end']))."打卡一次,".$remark_str."状态:正常!".$early_str;
				}else{
					//无打卡记录判断
					if(strlen($val['hr_card_first'])==0 && strlen($val['hr_card_end'])==0){
						$hr_table_arr[$key]['hr_status_remark']=$val['hr_date']."日(周".$weekarray[date('w',strtotime($val['hr_date']))].",班)无打卡,申请单(ID:".$note_id_str.")时数".$val['z_note_time'].",状态:正常!".$early_str;
					}else{
						if($val['card_count']>3 && $val['site_id']==1){
							$temp_str=",注意:本日有多次有效刷卡.";
						}else{
							$temp_str='';
						}
						$hr_table_arr[$key]['hr_status_remark']=$val['hr_date']."日(周".$weekarray[date('w',strtotime($val['hr_date']))].",班) 早打卡".date('H:i:s',strtotime($val['hr_card_first'])).",晚打卡".date('H:i:s',strtotime($val['hr_card_end'])).$temp_str.",出勤".$user_work_time['hour'].":".$user_work_time['min'].":".$user_work_time['sec'].",".$remark_str."状态:正常!".$early_str;
					}
						
				}
				if($user_info['site_id']==1){
					$hr_table_arr[$key]['hr_remark']="用餐: 早->".$val['breakfast_count'].",中->".$val['lunch_count'].",晚->".$val['dinner_count'];
				}
					
			}else{
				//echo '出勤异常循环体<br>';
				
				$remark_str="";
				
				if($val['is_odd']!=1){
					$cal_flag=0;
				}				

				if(config('hr_cal_ext_flag')==1  && $user_info['site_id']==1){
					if($begin_card_early){
						$early_str=$firstr_card_rec."打卡,".date('H:i:s',strtotime($val['hr_card_first']))."点开始计算考勤!";
					}
				
					//4次或者6次打卡,计算用户出勤时间,重新计算$user_work_time时间数
					if($val['card_count']>=3){						
						$user_work_time=get_user_work_time_more($user_info['user_gh'],$val['hr_date'],$user_info['site_id']);
						//判断用户
				
						//用户的z_work_need_time 重新计算
						if($user_work_time['s']<$c_hr_work_time){
							//$val['z_work_need_time']=$c_hr_work_time-$user_work_time['s'];
							$hr_table_arr[$key]['z_work_need_time']=$val['z_work_need_time']=int_time($c_hr_work_time-$user_work_time['s'],'add');
						}
					}
					
					//有效打卡次数超过7次,不予以计算
					if(strlen($val['holiday_id'])==0  && $val['card_count']>9 && $val['site_id']==1){
						$hr_table_arr[$key]['z_work_need_time']=8;
						$hr_table_arr[$key]['z_out_work_time']=0;
						$hr_table_arr[$key]['hr_status']=0;
						$hr_table_arr[$key]['hr_status_remark']=$val['hr_date']."日(周".$weekarray[date('w',strtotime($val['hr_date']))].") 工作日有效考勤打卡9次以上,系统无法计算!,无法计算考勤状态,状态:异常!".$early_str;
						if($user_info['site_id']==1){
							$hr_table_arr[$key]['hr_remark']="用餐: 早->".$val['breakfast_count'].",中->".$val['lunch_count'].",晚->".$val['dinner_count'];
						}
						$hr_table_arr[$key]['z_cal_flag']=18;
						$cal_flag=1;
					}
						
				
				}
				
				$hr_table_arr[$key]['hr_status']=0;
				//出勤时间不足,无申请单,标记给用户
				if(strlen($val['holiday_id'])==0 && $val['z_work_need_time']>$val['z_note_time'] && strlen('note_id_str')==0){
					$remark_str="出勤差".$val['z_work_need_time']."小时,无假单,";
					$hr_table_arr[$key]['hr_status_remark']=$val['hr_date']."日(周".$weekarray[date('w',strtotime($val['hr_date']))].",班) 早打卡".date('H:i:s',strtotime($val['hr_card_first'])).",晚打卡".date('H:i:s',strtotime($val['hr_card_end'])).",出勤".$user_work_time['hour'].":".$user_work_time['min'].":".$user_work_time['sec'].",".$remark_str."状态:异常!".$early_str;
					if($user_info['site_id']==1){
						$hr_table_arr[$key]['hr_remark']="用餐: 早->".$val['breakfast_count'].",中->".$val['lunch_count'].",晚->".$val['dinner_count'];
					}
					$hr_table_arr[$key]['z_cal_flag']=5;
					$cal_flag=1;
				}
					
				//有晚到行为,出勤时间足够,无申请单,标记给用户
				if(strlen($val['holiday_id'])==0 && strlen($val['note_id_str'])==0 && strtotime($val['hr_card_first'])>$hr_begin_end_time && $val['z_work_need_time']==0  && $cal_flag==0){
					$hr_table_arr[$key]['out_work_time']=0;
					$hr_table_arr[$key]['note_time']=0;
					$hr_table_arr[$key]['act_time']=0;
					$remark_str="有晚到,无申请单,";
					$hr_table_arr[$key]['hr_status_remark']=$val['hr_date']."日(周".$weekarray[date('w',strtotime($val['hr_date']))].",班) 早打卡".date('H:i:s',strtotime($val['hr_card_first'])).",晚打卡".date('H:i:s',strtotime($val['hr_card_end'])).",出勤".$user_work_time['hour'].":".$user_work_time['min'].":".$user_work_time['sec'].",".$remark_str."状态:异常!".$early_str;
					if($user_info['site_id']==1){
						$hr_table_arr[$key]['hr_remark']="用餐: 早->".$val['breakfast_count'].",中->".$val['lunch_count'].",晚->".$val['dinner_count'];
					}
					$hr_table_arr[$key]['z_cal_flag']=6;
					$cal_flag=1;
				}

				//出勤时间不够,未填申请单,非单次打卡用户,晚打卡早于午间开始时间,按照上午出勤多少时间,8小时减去此时间作为缺勤时间
				if(strlen($val['holiday_id'])==0 && $val['z_work_need_time']>$val['z_note_time'] && $val['z_one_card']==0 && strlen($val['note_id_str'])==0 && strlen($val['hr_card_first'])>0 && strlen($val['hr_card_end'])>0 && strtotime($val['hr_card_end'])<=$hr_noon_begin && $cal_flag==0){
					$hr_table_arr[$key]['out_work_time']=0;
					$hr_table_arr[$key]['note_time']=0;
					$hr_table_arr[$key]['act_time']=0;
					$remark_str="出勤不足,差".int_time($c_hr_work_time-$user_work_time['s'],'add')."小时,请及时填写申请单.";
					$hr_table_arr[$key]['hr_status_remark']=$val['hr_date']."日(周".$weekarray[date('w',strtotime($val['hr_date']))].",班) 早打卡".date('H:i:s',strtotime($val['hr_card_first'])).",晚打卡".date('H:i:s',strtotime($val['hr_card_end'])).",出勤".$user_work_time['hour'].":".$user_work_time['min'].":".$user_work_time['sec'].",".$remark_str."状态:异常!".$early_str;
					if($user_info['site_id']==1){
						$hr_table_arr[$key]['hr_remark']="用餐: 早->".$val['breakfast_count'].",中->".$val['lunch_count'].",晚->".$val['dinner_count'];
					}
					$hr_table_arr[$key]['z_cal_flag']=18;
					$cal_flag=1;
				}
				
				//出勤时间不够,未填申请单,非单次打卡用户,晚打卡次以上,标记给用户
				if(strlen($val['holiday_id'])==0 && $val['z_work_need_time']>$val['z_note_time'] && $val['z_one_card']==0 && strlen($val['note_id_str'])==0 && strlen($val['hr_card_first'])>0 && strlen($val['hr_card_end'])>0 && $cal_flag==0){
					$hr_table_arr[$key]['out_work_time']=0;
					$hr_table_arr[$key]['note_time']=0;
					$hr_table_arr[$key]['act_time']=0;
					$remark_str="出勤不足,差".$val['z_work_need_time']."小时,请及时填写申请单.";
					if($val['card_count']>3 && $val['site_id']==1){
						$temp_str=",注意:本日有多次有效刷卡.";
					}else{
						$temp_str='';
					}
					$hr_table_arr[$key]['hr_status_remark']=$val['hr_date']."日(周".$weekarray[date('w',strtotime($val['hr_date']))].",班) 早打卡".date('H:i:s',strtotime($val['hr_card_first'])).",晚打卡".date('H:i:s',strtotime($val['hr_card_end'])).$temp_str.",出勤".$user_work_time['hour'].":".$user_work_time['min'].":".$user_work_time['sec'].",".$remark_str."状态:异常!".$early_str;
					if($user_info['site_id']==1){
						$hr_table_arr[$key]['hr_remark']="用餐: 早->".$val['breakfast_count'].",中->".$val['lunch_count'].",晚->".$val['dinner_count'];
					}
					$hr_table_arr[$key]['z_cal_flag']=7;
					$cal_flag=1;
				}
				
				//单次打卡用户,标记给用户
				if(strlen($val['holiday_id'])==0 && $val['z_one_card']==1 && $cal_flag==0){
					//是否有假单
					$hr_table_arr[$key]['out_work_time']=0;
					$hr_table_arr[$key]['note_time']=0;
					$hr_table_arr[$key]['act_time']=0;
					if(strlen($val['note_id_str'])==0){
						$remark_str="单次打卡,无申请单,请及时上系统填写申请单.";
						$hr_table_arr[$key]['hr_status_remark']=$val['hr_date']."日(周".$weekarray[date('w',strtotime($val['hr_date']))].",班) ".date('H:i:s',strtotime($val['hr_card_first']))."打卡一次,".$remark_str."状态:异常!".$early_str;
						if($user_info['site_id']==1){
							$hr_table_arr[$key]['hr_remark']="用餐: 早->".$val['breakfast_count'].",中->".$val['lunch_count'].",晚->".$val['dinner_count'];
						}
						$hr_table_arr[$key]['z_cal_flag']=8;
						$cal_flag=1;
					}else{
						//假单是否够扣
						$hr_table_arr[$key]['note_time']=$val['note_time']=$note_time;
						$hr_table_arr[$key]['act_time']=$val['act_time']=$note_time;
						$remark_str="单次打卡,申请单(ID:".$note_id_str.")申请时数为".$val['z_note_time']."小时,还差".($hr_work_act_time-$val['z_note_time'])."小时,请及时上系统填写申请单!";
						$hr_table_arr[$key]['hr_status_remark']=$val['hr_date']."日(周".$weekarray[date('w',strtotime($val['hr_date']))].",班) 系统记录到你".date('H:i:s',strtotime($val['hr_card_end']))."打卡一次,".$remark_str."状态:异常!".$early_str;
						if($user_info['site_id']==1){
							$hr_table_arr[$key]['hr_remark']="用餐: 早->".$val['breakfast_count'].",中->".$val['lunch_count'].",晚->".$val['dinner_count'];
						}
						$hr_table_arr[$key]['z_cal_flag']=9;
						$cal_flag=1;
					}
				}
					
				//上班日无打卡,标记给用户
				if(strlen($val['holiday_id'])==0 && $val['z_work_need_time']>$val['z_note_time'] && $val['z_one_card']==0 && strlen($val['hr_card_first'])==0 && strlen($val['hr_card_end'])==0 && $cal_flag==0){
					//无申请单
					$hr_table_arr[$key]['out_work_time']=0;
					$hr_table_arr[$key]['note_time']=0;
					$hr_table_arr[$key]['act_time']=0;
					if(strlen($val['note_id_str'])==0){
						$remark_str="出勤时间不足,无申请单,请及时填写申请单.";
						$hr_table_arr[$key]['hr_status_remark']=$val['hr_date']."日(周".$weekarray[date('w',strtotime($val['hr_date']))].",班)无打卡记录,".$remark_str."状态:异常!";
						if($user_info['site_id']==1){
							$hr_table_arr[$key]['hr_remark']="用餐: 早->".$val['breakfast_count'].",中->".$val['lunch_count'].",晚->".$val['dinner_count'];
						}
						$hr_table_arr[$key]['z_cal_flag']=10;
					}else{
						$remark_str="申请单(ID:".$note_id_str.")时数".$val['z_note_time'].",请及时填写申请单.";
						$hr_table_arr[$key]['hr_status_remark']=$val['hr_date']."日(周".$weekarray[date('w',strtotime($val['hr_date']))].",班)无打卡记录,".$remark_str."状态:异常!";
						if($user_info['site_id']==1){
							$hr_table_arr[$key]['hr_remark']="用餐: 早->".$val['breakfast_count'].",中->".$val['lunch_count'].",晚->".$val['dinner_count'];
						}
						$hr_table_arr[$key]['z_cal_flag']=11;
					}
					$cal_flag=1;
				}
					
				//出勤时间不够,有填申请单,非单次打卡用户,标记给用户
				if(strlen($val['holiday_id'])==0 && $val['z_work_need_time']>$val['z_note_time'] && $val['z_one_card']==0 && strlen($val['note_id_str'])>0 && $cal_flag==0){
					$hr_table_arr[$key]['out_work_time']=0;
					$hr_table_arr[$key]['note_time']=0;
					$hr_table_arr[$key]['act_time']=0;
					$remark_str="出勤时间不足,申请单(ID:".$note_id_str.")时数".$val['z_note_time']."小时,还差".($val['z_work_need_time']-$val['z_note_time'])."小时,请及时填写申请单.";
					$hr_table_arr[$key]['hr_status_remark']=$val['hr_date']."日(周".$weekarray[date('w',strtotime($val['hr_date']))].",班) 早打卡".date('H:i:s',strtotime($val['hr_card_first'])).", 晚打卡".date('H:i:s',strtotime($val['hr_card_end'])).",出勤".$user_work_time['hour'].":".$user_work_time['min'].":".$user_work_time['sec'].",".$remark_str."状态:异常!".$early_str;
					if($user_info['site_id']==1){
						$hr_table_arr[$key]['hr_remark']="用餐: 早->".$val['breakfast_count'].",中->".$val['lunch_count'].",晚->".$val['dinner_count'];
					}
					$hr_table_arr[$key]['z_cal_flag']=12;
					$cal_flag=1;
				}
					
				//假日,有申请单则不予以计算,有拉卡计算高标出勤
				if(strlen($val['holiday_id'])>0 && $cal_flag==0){
					$hr_table_arr[$key]['hr_status']=1;
					$hr_table_arr[$key]['out_work_time']=0;
					$hr_table_arr[$key]['note_time']=0;
					$hr_table_arr[$key]['act_time']=0;
					//获取假日信息
					$holiday_info=db('sys_holiday')->where('id='.$val['holiday_id'])->find();
					if($user_work_time['s']>0){
						$hr_table_arr[$key]['z_work_need_time']=0;
						$hr_table_arr[$key]['z_out_work_time']=int_time($user_work_time['s'], 'com');
							
						if($hr_table_arr[$key]['z_out_work_time']>0){
							$remark_str="早打卡".date('H:i:s',strtotime($val['hr_card_first'])).", 晚打卡".date('H:i:s',strtotime($val['hr_card_end'])).",出勤".$user_work_time['hour'].":".$user_work_time['min'].":".$user_work_time['sec']."高标".$hr_table_arr[$key]['z_out_work_time'].'小时.'.$early_str;
						}else{
							$remark_str="";
						}
							
						$hr_table_arr[$key]['hr_status_remark']=$val['hr_date']."日(周".$weekarray[date('w',strtotime($val['hr_date']))].",".$holiday_info['holiday_name']."),".$remark_str."状态:正常!".$early_str;
						if($user_info['site_id']==1){
							$hr_table_arr[$key]['hr_remark']="假日无用餐.";
						}
						$hr_table_arr[$key]['z_cal_flag']=13;
						$cal_flag=1;
					}else{
						$hr_table_arr[$key]['hr_status_remark']=$val['hr_date']."日(周".$weekarray[date('w',strtotime($val['hr_date']))].",".$holiday_info['holiday_name']."),状态:正常!".$early_str;
						if($user_info['site_id']==1){
							$hr_table_arr[$key]['hr_remark']="假日无用餐.";
						}
						$hr_table_arr[$key]['z_cal_flag']=14;
						$cal_flag=1;
					}
				}
					
				//未计算情况
				if($cal_flag==0){
					$hr_table_arr[$key]['hr_status']=0;
					$hr_table_arr[$key]['hr_status_remark']=$val['hr_date']."日(周".$weekarray[date('w',strtotime($val['hr_date']))]."),无法计算考勤状态,状态:异常!".$early_str;
					$hr_table_arr[$key]['z_cal_flag']=15;
					$cal_flag=1;
				}
					
			}
			//pr($hr_table_arr[$key]);
			//echo '算完了.';exit;				
				
		}else{
			//echo $val['hr_date'].'--'.$val['hr_card_first'].'--'.$val['hr_card_end'].'<br>';
			//非考勤人员数据计算
			//2017-03-03日(周五,班) 早打卡08:43:18,晚打卡17:55:02,出勤9:11:44,无高标.无申请单.状态:正常!  用餐: 早->1,中->0,晚->0
			$hr_table_arr[$key]['hr_status']=1;
			if(strlen($val['holiday_id'])>0){
				//获取假日信息
				$holiday_info=db('sys_holiday')->where('id='.$val['holiday_id'])->find();
				$hr_table_arr[$key]['hr_status_remark']=$val['hr_date']."日(周".$weekarray[date('w',strtotime($val['hr_date']))].",".$holiday_info['holiday_name']."),状态:正常!";
				if($user_info['site_id']==1){
					$hr_table_arr[$key]['hr_remark']="假日无用餐.";
				}
			}else{
				if(strlen($val['hr_card_first'])>5){
					$no_hr_hr_card_first=$val['hr_card_first'];
				}else{
					$no_hr_hr_card_first='无打卡记录!';
				}

				if(strlen($val['hr_card_end'])>5){
					$no_hr_hr_card_end=$val['hr_card_end'];
				}else{
					$no_hr_hr_card_end='无打卡记录!';
				}

				$hr_table_arr[$key]['hr_status_remark']=$val['hr_date']."日(周".$weekarray[date('w',strtotime($val['hr_date']))].",班) 早打卡: ".$no_hr_hr_card_first.",晚打卡: ".$no_hr_hr_card_end.",非考勤人员,状态:正常!";
				if($user_info['site_id']==1){
					$hr_table_arr[$key]['hr_remark']="用餐: 早->".$val['breakfast_count'].",中->".$val['lunch_count'].",晚->".$val['dinner_count'];
				}
			}
				
			$hr_table_arr[$key]['z_cal_flag']=16;
			$cal_flag=1;
		}
	}

	//将 z_work_need_time 大于8的设定到8小时
	foreach ($hr_table_arr as $k=>$v){
		if($v['z_work_need_time']>8){
			$hr_table_arr[$k]['z_work_need_time']=8;
		}
		if($hr_table_arr[$k]['z_work_need_time']>$hr_table_arr[$k]['z_note_time']){
			$hr_table_arr[$k]['abs_time']=$hr_table_arr[$k]['z_work_need_time']-$hr_table_arr[$k]['z_note_time'];
		}else{
			$hr_table_arr[$k]['abs_time']=0;
		}		

		//晚到计矿工8小时
		if($hr_table_arr[$k]['z_cal_flag']==6){
			$hr_table_arr[$k]['abs_time']=8;
		}
		
		//删除user_gh字段
		unset($hr_table_arr[$k]['user_gh']);
	}

	return $hr_table_arr;
}

//计算某日超过4次&6次打开的出勤时间数
function get_user_work_time_more($user_gh,$date,$site_id=0){
	//获取某日有效打卡信息
	
	$hr_begin_str=get_cache_data('site_info',$site_id,'hr_begin_time');
	$site_code=get_site_code($site_id);
	
	//判断用户6:00~7:30之间是否有打卡,如果有打卡,则从6:00~7:30之间的只算一次打卡	
	$is_card_early=db('entry_dt_'.$site_code)->where("emp_no='".$user_gh."' and ctrl_id in (2,23,31) and status=1 and entry_dt between '".$date.' '.config('hr_begin_time')."' and '".$date." 07:29:59'")->order('entry_dt')->select();
		
	if(strlen($hr_begin_str)<3){
		$hr_begin_str="08:00:00";
	}
	
	if(strlen($site_code)==0){
		$site_code='sh';
	}
	
	if(count($is_card_early)>0){
		$card_arr_temp=db('entry_dt_'.$site_code)->where("emp_no='".$user_gh."' and ctrl_id in (2,23,31) and status=1 and entry_dt between '".$date." ".$hr_begin_str."' and '".$date." 23:59:59'")->order('entry_dt')->select();
		
		$card_arr=array();
		if(count($card_arr_temp)>0){
			$card_arr[0]=$card_arr_temp[0];
		}else{
			//6:00~7:30有打卡,7:30后无打卡
			$card_arr[0]=db('entry_dt_'.$site_code)->where("emp_no='".$user_gh."' and ctrl_id in (2,23,31) and status=1 and entry_dt between '".$date.' '.config('hr_begin_time')."' and '".$date." 07:29:59'")->order('entry_dt')->limit(1)->select();
		}
		
		$card_arr[0]['entry_dt']=$date.' '.$hr_begin_str;
		//重组数组,塞一条7:30打卡的数据到第一条
		foreach ($card_arr_temp as $k=>$v){
			array_push($card_arr, $v);
		}
	}else{
		//规避同一条数据多次抓取的记录,添加group
		$card_arr=db('entry_dt_'.$site_code)->where("emp_no='".$user_gh."' and ctrl_id in (2,23,31) and status=1 and entry_dt between '".$date.' '.config('hr_begin_time')."' and '".$date." 23:59:59'")->group('entry_dt')->order('entry_dt')->select();
	}
	
	$work_time=0;

	//循环打卡状况数组,计算累加出勤时间
	$time_in="";		//进门时间
	foreach ($card_arr as $k=>$v){
		//$user_work_time=timediff($v['hr_card_first'], $v['hr_card_end']);
		//echo $user_work_time;
		if($k==0 || ($k%2)==0){
			$time_in=$v['entry_dt'];
		}else{
			 $temp_time_arr=timediff($time_in,$v['entry_dt']);
			 //echo $time_in.'---'.$v['entry_dt'].'  '.$temp_time_arr['s'].'<br>';
			 $work_time += $temp_time_arr['s'];
		}		
	}
	return hr_time_format($work_time);
}

//判断用户本日有效打卡是否有跨午间计算时间
function get_user_work_time($site_id,$user_gh,$date){
	
	$site_code=get_site_code($site_id);
	
	//每日考勤时间
	$hr_sec_range=config('hr_sec_range');
	$hr_work_time=config('hr_work_time')*60*60-$hr_sec_range;
	
	//获取某日有效打卡信息
	switch ($site_code){
		case 'sh':			
			$card_arr=db('entry_dt_sh')->where("emp_no='".$user_gh."' and ctrl_id in (2,23) and status=1 and entry_dt between '".$date.' '.config('hr_begin_time')."' and '".$date." 23:59:59'")->order('entry_dt')->select();				
			break;
		case 'sz':
			$card_arr=db('entry_dt_sz')->where("emp_no='".$user_gh."' and ctrl_id in (31) and status=1 and entry_dt between '".$date.' '.config('hr_begin_time')."' and '".$date." 23:59:59'")->order('entry_dt')->select();
			break;
		case 'xa':
			$card_arr=db('entry_dt_xa')->where("emp_no='".$user_gh."' and ctrl_id in (31) and status=1 and entry_dt between '".$date.' '.config('hr_begin_time')."' and '".$date." 23:59:59'")->order('entry_dt')->select();
			break;
		case 'tw':
			$card_arr=array();
			break;
	}	
	
	//本日有打卡则计算,否则返回8小时
	if(count($card_arr)>0){
		//中午计算时间范围
		$hr_noon_begin_c=$date.' '.config('hr_noon_begin');
		$hr_noon_end_c=$date.' '.config('hr_noon_end');
		$return=false;
		$time_in="";		//进门时间
		foreach ($card_arr as $key=>$val){
			//打卡落在午间时间
			if($val['entry_dt']>$hr_noon_begin_c && $val['entry_dt']<$hr_noon_end_c && $site_code=='sh'){
				$return=true;
			}
		
			if(count($card_arr)>=2){
				//如果多次打卡,一进一出时间有大于午间时间
				if($key==0 || ($key%2)==0){
					$time_in=$val['entry_dt'];
					//echo $time_in.'in <br>';
				}else{
					//echo $time_in.'in --'.$val['entry_dt'].' out';
					//一进一出都不落在午间范围
					if(strtotime($time_in)<strtotime($hr_noon_begin_c) && strtotime($val['entry_dt'])>strtotime($hr_noon_end_c) ){
						//echo ' 跨午间1小时,需计算用户9小时.<br>';
						$return=false;
						break;
					}else{
						//echo ' 未跨午间1小时,需计算用户8小时.<br>';
						//午间打卡算8小时规则仅适用上海
						if($site_code=='sh'){
							$return=true;
						}else{
							$return=false;
						}						
					}
				}
			}
		}

		//第一次打卡时间大于午间结束时间
		if(strtotime($card_arr[0]['entry_dt'])>strtotime($hr_noon_end_c)){
			$return=true;
		}
		
		//echo $card_arr[0]['entry_dt'].'--'.$hr_noon_begin_c;exit;
		//第一次打卡或者最后一次打卡落在午间,不论站点算8小时
		if(($card_arr[0]['entry_dt']>$hr_noon_begin_c && $card_arr[0]['entry_dt']<$hr_noon_end_c) || ($card_arr[count($card_arr)-1]['entry_dt']>$hr_noon_begin_c && $card_arr[count($card_arr)-1]['entry_dt']<$hr_noon_end_c)) {
			$return=true;
		}
	}else{
		$return = true;
	}	
	
	if($return){
		//echo '<br>算8小时'; 		
		return $hr_work_time-60*60;
	}else{
		//echo '<br>算9小时'; 
		return $hr_work_time;
	}
}

//重新计算当前用户操作站点表格参数 ,hr_trim,pay_trim,hr_note
function cal_tb_param($param_type,$param){
	//当传递过来的参数大于0时,进行session数据更新
	if(count($param)>0){
		$_SESSION[$param_type]=array();

		$date_temp=getlastMonthDays();
		$last_month=strtotime($date_temp[0]);
		$user_info=db('sys_user')->where('id='.get_user_id())->find();

		//站点参数
		if(!isset($param['site_id'])){
			$site_id=$user_info['site_id'];
		}else{
			if(strlen($param['site_id'])>0){
				$site_id=$param['site_id'];
			}else{
				$site_id=$user_info['site_id'];
			}
		}

		//部门参数
		if(!isset($param['dep_id'])){
			$dep_id=0;
		}else{
			if(strlen($param['dep_id'])>0){
				$dep_id=$param['dep_id'];
			}else{
				$dep_id=0;
			}
		}

		//日期参数
		if(!isset($param['year_month'])){
			if($param_type=='hr_trim'){
				$year_month=date('Y',$last_month).'-'.(int)date('m',$last_month);
			}else{
				$year_month=date('Y',time()).'-'.(int)date('m',time());
			}

		}else{
			if(strlen($param['year_month'])>0){
				$year_month=$param['year_month'];
			}else{
				if($param_type=='hr_trim'){
					$year_month=date('Y',$last_month).'-'.(int)date('m',$last_month);
				}else{
					$year_month=date('Y',time()).'-'.(int)date('m',time());
				}
			}
		}

		//关键字
		if(!isset($param['key'])){
			$key="";
		}else{
			if(strlen($param['year_month'])>0){
				if($param_type=='hr_trim'){
					$key=auto_charset($param['key']);
				}else{
					$key=$param['key'];
				}

			}else{
				$key="";
			}
		}

		$_SESSION[$param_type]['site_id']=$site_id;
		$_SESSION[$param_type]['dep_id']=$dep_id;
		$_SESSION[$param_type]['year_month']=$year_month;
		$_SESSION[$param_type]['key']=$key;
		$_SESSION[$param_type]['site_pay_flag']=get_cache_data('site_info',$site_id,'site_pay_flag');
		if($param_type=='hr_note'){
			$_SESSION[$param_type]['note_type']=$param['note_type'];
			$_SESSION[$param_type]['hr_note_id']=$param['hr_note_id'];
			$_SESSION[$param_type]['begin_date']=$param['begin_date'];
			$_SESSION[$param_type]['end_date']=$param['end_date'];
			$_SESSION[$param_type]['note_step']=$param['note_step'];
		}
		
	}

	/*
	 $data['site_id']=$site_id;
	 $data['site_name']=get_cache_data('site_info', $site_id,'site');
	 $data['dep_id']=$dep_id;
	 $data['year_month']=$year_month;
	 $data['year']=(int)date('Y',strtotime($year_month));
	 $data['month']=(int)date('m',strtotime($year_month));
	 $data['key']=$key;

	 return $data;
	 */
}


//生成指定月份考勤数据
function cal_hr($site_id='',$year_month=''){

	if(strlen($site_id)==0){
		$site_id=session('cur_user_info')['site_id'];
	}
	
	if(strlen($year_month)==0){
		$temp_arr=getlastMonthDays();
		$year_month=$temp_arr[0];
	}else{
		$year_month=$year_month.'-01';
	}
	
	$last_month_range=getMonthBEArr($year_month);
	$last_month_f=$year_month;
	$last_month_i=strtotime($year_month);
	
	//上上月日期范围
	$llast_month_f=date('Y-m',strtotime('-1 month',$last_month_i));
	$llast_month_i=strtotime(date('Y-m',strtotime('-1 month',$last_month_i)));	

	//获取当前在职员工信息
	$user_arr=db('sys_user')->where("hr_status in (1,3) and site_id=".$site_id." and status=1 and user_status=1")->select();

	$year=date('Y',$last_month_i);
	$month=date('n',$last_month_i);

	//获取上月份数据
	foreach ($user_arr as $key=>$val){
		$hr_cal_arr=array();
		$hr_cal_arr['user_id']=$val['id'];
		$hr_cal_arr['year']=$year;
		$hr_cal_arr['month']=$month;
		$hr_cal_arr['casual_leave']=0;

		//判断该用户上上月结算年休(last_year_remain_leave),上月结算补休(last_month_remain_leave)是否有结余数据,如果没有,则采用人事表中的基础数据作为上月结余
		$llast_month_info=db('hr_cal')->where(' year='.date('Y',$llast_month_i)." and month=".date('n',$llast_month_i)." and user_id=".$val['id'])->find();
		if(!$llast_month_info){
			$hr_cal_arr['last_annual_num']=$val['annual_num'];
			$hr_cal_arr['last_repair_num']=$val['repair_num'];
		}else{
			$hr_cal_arr['last_annual_num']=$llast_month_info['local_annual_num'];
			$hr_cal_arr['last_repair_num']=$llast_month_info['local_repair_num'];
		}

		//节假日结加班费时数
		//$hr_cal_arr['holiday_hour']=0;

		//本月某用户实际申请单休假时间数统计,本月申请休假,所有类型的申请单时间数,是算所有申请单时间还是只算需要扣除休假类型的时间?
		/*
		$local_note_hour_count=db('hr_table')->where(" user_id=".$val['id']." and isnull(note_id_str)=false and hr_note_id in (".config('hr_note_id_level').")  and hr_date between '".$last_month_range[0]." 00:00:00' and '".$last_month_range[1]." 23:59:59'")->sum('z_note_time');

		//判断是否考勤人员,如果为非考勤人员,则按照已审批申请单计算申请时数
		if($val['is_hr_user']==0){
			//计算非考勤用户填写的申请单时间
			$hr_cal_arr['local_note_hour']=db('user_note_item')->where("user_id=".$val['id']." and hr_note_id in (".config("hr_note_id_level").")  and ((begin_time between '".$last_month_range[0]." 00:00:00' and '".$last_month_range[1]." 23:59:59') or (end_time between '".$last_month_range[0]." 00:00:00' and '".$last_month_range[1]." 23:59:59'))")->sum('note_hour');
		}else{
			$hr_cal_arr['local_note_hour']=$local_note_hour_count;
		}
		*/
		
		//计算当前用户申请的休假时间数
		$local_note_hour_count=$hr_cal_arr['local_note_hour']=db('user_note_item')->where("user_id=".$val['id']." and hr_note_id in (2)  and ((begin_time between '".$last_month_range[0]." 00:00:00' and '".$last_month_range[1]." 23:59:59') or (end_time between '".$last_month_range[0]." 00:00:00' and '".$last_month_range[1]." 23:59:59'))")->sum('note_hour');
		
		//local_note_hour做非0处理
		if(strlen($hr_cal_arr['local_note_hour'])==0){
			$hr_cal_arr['local_note_hour']=0;
		}
		
		//$local_note_hour_count做非0处理
		if(strlen($local_note_hour_count)==0){
			$local_note_hour_count=0;
		}
		
		//写入本行的申请单时间数,不计算年休补休时数,由行计算函数统一计算
		$hr_cal_arr['local_note_hour']=$local_note_hour_count;
		
// 		//本次结算年休时数,local_annual_num
// 		//判断本月申请单时间数够不够年休扣
// 		if(($hr_cal_arr['last_annual_num']-$local_note_hour_count)>=0){
// 			//够年休扣
// 			$hr_cal_arr['local_annual_num']=$hr_cal_arr['last_annual_num']-$local_note_hour_count;
// 			$hr_cal_arr['local_repair_num']=$hr_cal_arr['last_repair_num'];
			
// 			//本次年休+补休,local_num
// 			$hr_cal_arr['local_num']=$hr_cal_arr['local_annual_num']+$hr_cal_arr['local_repair_num'];
// 		}else{
// 			//如果不够年休扣,加上补休数看是否够时间扣
// 			if((($hr_cal_arr['last_annual_num']+$hr_cal_arr['last_repair_num'])-$local_note_hour_count)>=0){
// 				//年休全部扣完
// 				$hr_cal_arr['local_annual_num']=$hr_cal_arr['last_annual_num'];
// 				//扣补休
// 				$hr_cal_arr['local_repair_num']=$hr_cal_arr['last_repair_num']-($local_note_hour_count-$hr_cal_arr['last_annual_num']);
				
// 				//本次年休+补休,local_num
// 				$hr_cal_arr['local_num']=$hr_cal_arr['local_annual_num']+$hr_cal_arr['local_repair_num'];
// 			}else{
// 				//如果补休也不够扣,则看扣完补休还有多少个小时
// 				$hr_cal_arr['local_annual_num']=0;
// 				$hr_cal_arr['local_repair_num']=0;
// 				$leave_note_hour=$hr_cal_arr['last_annual_num']-$local_note_hour_count;
				
// 				//扣完有的假小于16个小时,则放到结算负数中
// 				if($leave_note_hour>=-16){
// 					$hr_cal_arr['local_num']=$leave_note_hour;
// 				}else{
// 					//休假全部扣完-16还不够扣,转事假
// 					$hr_cal_arr['local_num']=-16;
// 					$leave_note_hour=$leave_note_hour+16;
// 					$hr_cal_arr['casual_leave']=abs($leave_note_hour);
// 				}
// 			}
// 		}
		
		//事假  casual_leave,请假类型为事假
		$hr_cal_arr['casual_leave'] += db('hr_table')->where(" user_id=".$val['id']." and isnull(note_id_str)=false and hr_note_id=".config('casual_id')." and hr_date between '".$last_month_range[0]."' and '".$last_month_range[1]."'")->sum('note_time');

		//病假  sick_leave,请假类型为病假
		$hr_cal_arr['sick_leave'] = db('hr_table')->where(" user_id=".$val['id']." and isnull(note_id_str)=false and hr_note_id=".config('sick_id')." and hr_date between '".$last_month_range[0]."' and '".$last_month_range[1]."'")->sum('note_time');

		//病假  sick_leave做非0处理
		if(strlen($hr_cal_arr['sick_leave'])==0){
			$hr_cal_arr['sick_leave']=0;
		}
		
		//旷职 abs_hour,无出勤,无申请单
		$hr_cal_arr['abs_hour'] = db('hr_table')->where(" user_id=".$val['id']." and isnull(holiday_id)=true and hr_date between '".$last_month_range[0]."' and '".$last_month_range[1]."' and hr_status=0")->sum('abs_time');

		//旷职 abs_hour做非0处理
		if(strlen($hr_cal_arr['abs_hour'])==0){
			$hr_cal_arr['abs_hour']=0;
		}
		
		//计算早餐,午餐数量
		$hr_cal_arr['bf_num'] = db('hr_table')->where(" user_id=".$val['id']." and isnull(holiday_id)=true and hr_date between '".$last_month_range[0]."' and '".$last_month_range[1]."'")->sum('breakfast_count');
		$hr_cal_arr['lunch_num'] = db('hr_table')->where(" user_id=".$val['id']." and isnull(holiday_id)=true and hr_date between '".$last_month_range[0]."' and '".$last_month_range[1]."'")->sum('lunch_count');

		//早餐做非0处理
		if(strlen($hr_cal_arr['bf_num'])==0){
			$hr_cal_arr['bf_num']=0;
		}
		//午餐做非0处理
		if(strlen($hr_cal_arr['lunch_num'])==0){
			$hr_cal_arr['lunch_num']=0;
		}
		
		//备注,如果有转正,或者年度调薪之类的需要在此提醒

		//实习生数据调整
		if($val['hr_status']==3){
			$hr_cal_arr['last_annual_num']=0;
			$hr_cal_arr['last_repair_num']=0;
			$hr_cal_arr['local_note_hour']=0;
			$hr_cal_arr['local_annual_num']=0;
			$hr_cal_arr['local_repair_num']=0;
			$hr_cal_arr['local_num']=0;
			$hr_cal_arr['casual_leave']=0;
			$hr_cal_arr['sick_leave']=0;
			$hr_cal_arr['abs_hour']=0;
				
			//计算实习生本月打卡次数
			$sql="select distinct entry_date from sw_entry_dt_".get_site_code($val['site_id'])." where card_no='".$val['card_id']."' and entry_date between '".$last_month_range[0]."' and '".$last_month_range['1']."'";
			$date_count=count(db()->query($sql));
			if($date_count>0){
				$hr_cal_arr['intern_day']=$date_count;
				$hr_cal_arr['remark']='实习生,'.$month.'月考勤'.$date_count.'天!';
			}else{
				$hr_cal_arr['intern_day']=0;
				$hr_cal_arr['remark']='实习生,'.$month.'月无考勤,无薪资!';
			}
		}

		//最后更新人员,last_user_id
		$hr_cal_arr['last_user_id']=get_user_id();

		//创建时间
		$hr_cal_arr['create_time']=get_date_time();

		//更新时间
		$hr_cal_arr['update_time']=get_date_time();
		
		//pr($hr_cal_arr); 
		
		//判断该用户本月这条记录是否已存在
		$is_have=db('hr_cal')->where('user_id='.$val['id'].' and year='.$year.' and month='.$month)->find();
		if(!$is_have){
			$row_id=db('hr_cal')->insertGetId($hr_cal_arr);
		}else{
			$hr_cal_arr['id']=$is_have['id'];
			db('hr_cal')->update($hr_cal_arr);
			$row_id=$is_have['id'];
		}
		hr_row_cal($row_id);		
	}

	//判断sw_sys_event中是否已经有上月考勤写入记录,如果有,则LOG标识
	$is_have_event=db('sys_event')->where(' year='.$year.' and month='.$month.' and type_flag=1 and site_id='.$site_id)->find();

	if(!$is_have_event){
		//写入sw_sys_event表,确定上月考勤数据已经生成
		$temp_arr=array();
		$temp_arr['title']=$year.'-'.$month.' 考勤数据生成';
		$temp_arr['year']=$year;
		$temp_arr['month']=$month;
		$temp_arr['type_flag']=1;
		$temp_arr['site_id']=$site_id;
		$temp_arr['log']=get_date_time().' '.get_cache_data('site_info',$site_id,'site').'考勤数据 由'.get_user_nickname().' 生成!';
		$temp_arr['last_user_id']=get_user_id();
		$temp_arr['create_time']=get_date_time();
		$temp_arr['update_time']=get_date_time();

		db('sys_event')->insert($temp_arr);
	}else{
		//更新sw_sys_event表,确定上月考勤数据已经生成
		$temp_arr=array();
		$temp_arr['id']=$is_have_event['id'];
		$temp_arr['log']=get_date_time().' '.get_cache_data('site_info',$site_id,'site').'考勤数据 由'.get_user_nickname().' 重新生成计算!<br>'.$is_have_event['log'];;
		$temp_arr['last_user_id']=get_user_id();
		$temp_arr['update_time']=get_date_time();

		db('sys_event')->update($temp_arr);
	}


}

//行考勤数据计算
function hr_row_cal($id){
	
	$site_id=$_SESSION['hr_trim']['site_id'];
	$row_info=db('hr_cal')->where('id='.$id)->find();
	if(!$row_info['is_lock']){
		if($site_id !=7){
			//非台籍判断上月结算年休扣除本月申请休假是否够扣
			if($row_info['last_annual_num']-$row_info['local_note_hour']>=0){
				$sql="update sw_hr_cal set local_annual_num=ifnull(last_annual_num,0)-ifnull(local_note_hour,0),local_repair_num=ifnull(last_repair_num,0),local_num=(ifnull(last_annual_num,0)-ifnull(local_note_hour,0))+ifnull(local_repair_num,0),casual_leave=0 where id=".$id;
				db()->query($sql);
			}else{
				//判断上月结算年休+补休 是否够本月申请休假扣
				if(($row_info['last_annual_num']+$row_info['last_repair_num'])-$row_info['local_note_hour']>=0){
					$sql="update sw_hr_cal set local_annual_num=0,local_repair_num=ifnull(".($row_info['last_repair_num']-($row_info['local_note_hour']-$row_info['last_annual_num'])).",0),local_num=ifnull(".($row_info['last_repair_num']-($row_info['local_note_hour']-$row_info['last_annual_num'])).",0),casual_leave=0 where id=".$id;
					db()->query($sql);
				}else{
					//上月结算年休+补休 不够本月申请休假扣 ,多余部分结算到事假,系统允许-16,累计到本月
					//$sql="update sw_hr_cal set local_annual_num=0,local_repair_num=0,local_num=0,casual_leave=ifnull(".((($row_info['last_annual_num']+$row_info['last_repair_num']))).",0) where id=".$id;
					//$sql="update sw_hr_cal set laste_repair_num=0,local_repair_num=0,local_num=0,casual_leave=ifnull(".((($row_info['last_annual_num']+$row_info['last_repair_num']))).",0) where id=".$id;
					$temp_arr=array();
					
					//如果上月结算时间数不够本月休假数扣,则本月结算补休时数一定为0,本次结算年休时数看是否-16,涉及到数据变化的栏位有
					$local_repair_num=0;		//本月结算补休时数为0
					$local_annual_num=0;		//本月结算年休时数
					$casual_leave=0;			//事假时数
					$temp_arr['last_repair_num']='上月月补休数:'.$row_info['last_repair_num'];
					$temp_arr['last_annual_num']='上月年休数:'.$row_info['last_annual_num'];
					$temp_arr['local_note_hour']='本月休假数'.$row_info['local_note_hour'];
					$temp_arr['local_repair_num']='本月补休数:';
					$temp_arr['local_annual_num']='本月年休数:';
					$temp_arr['casual_leave']='本月事假结算:';
					
					//上月已经-16了
					if(($row_info['last_annual_num']+$row_info['last_repair_num'])==-16){
						$local_repair_num=0;
						$local_annual_num=-16;
						$casual_leave=-$row_info['local_note_hour'];
					}else{						
						if(($row_info['local_note_hour']-($row_info['last_annual_num']+$row_info['last_repair_num']))>=16){
							//本月休假大于年休+补休,剩余时间大于-16
							$local_annual_num=-16;
							$local_repair_num=0;
							$casual_leave=-(($row_info['local_note_hour']-16)-($row_info['last_annual_num']+$row_info['last_repair_num']));
						}else{			
							//本月休假大于年休+补休,在-16个小时范围内
							if(($row_info['last_annual_num']+$row_info['last_repair_num'])>=0){
								//上月结算年休+补休>=0
								$local_repair_num=0;
								$casual_leave=0;
								$local_annual_num=-($row_info['local_note_hour']-($row_info['last_annual_num']+$row_info['last_repair_num']));
							}else{
								//上月结算年休+补休<0
								$local_repair_num=0;
								$casual_leave=0;
								$local_annual_num=-$row_info['local_note_hour']+($row_info['last_annual_num']+$row_info['last_repair_num']);
							}
						}
					}
					
// 					$temp_arr['local_repair_num']='本月补休数:'.$local_repair_num;
// 					$temp_arr['local_annual_num']='本月年休数:'.$local_annual_num;
// 					$temp_arr['casual_leave']='本月事假结算:'.$casual_leave;
// 					pr($temp_arr);

					$sql="update sw_hr_cal set local_annual_num=".$local_annual_num.",local_repair_num=".$local_repair_num.",local_num=".$local_annual_num.",casual_leave=ifnull(".$casual_leave.",0) where id=".$id;

					db()->query($sql);
				}
			}
		}else{
			//台籍判断上月结算(年休+本月加班转年休)扣除本月申请休假是否够扣
			if($row_info['last_annual_num']+$row_info['tw_out_work_time']-$row_info['local_note_hour']>0){
				$sql="update sw_hr_cal set local_annual_num=ifnull(last_annual_num,0)+ifnull(tw_out_work_time,0)-ifnull(local_note_hour,0),local_repair_num=ifnull(last_repair_num,0),local_num=(ifnull(last_annual_num,0)+ifnull(tw_out_work_time,0)-ifnull(local_note_hour,0))+ifnull(local_repair_num,0),casual_leave=0 where id=".$id;
				db()->query($sql);
			}else{
				//判断上月结算(年休+本月加班转年休+补休) 是否够本月申请休假扣
				if(($row_info['last_annual_num']+$row_info['last_repair_num']+$row_info['tw_out_work_time'])-$row_info['local_note_hour']>0){
					$sql="update sw_hr_cal set local_annual_num=0,local_repair_num=ifnull(".($row_info['last_repair_num']-($row_info['local_note_hour']-$row_info['last_annual_num']-$row_info['tw_out_work_time'])).",0),local_num=ifnull(".($row_info['last_repair_num']-($row_info['local_note_hour']-$row_info['last_annual_num']-$row_info['tw_out_work_time'])).",0),casual_leave=0 where id=".$id;
					db()->query($sql);
				}else{
					//上月结算年休+本月加班转年休+补休 不够本月申请休假扣 ,多余部分结算到事假
					$sql="update sw_hr_cal set local_annual_num=0,local_repair_num=0,local_num=0,casual_leave=ifnull(".((($row_info['last_annual_num']+$row_info['last_repair_num']+$row_info['tw_out_work_time'])-$row_info['local_note_hour'])).",0) where id=".$id;
					db()->query($sql);
				}
			}
				
				
		}

	}
}



//返回某月份某用户的考勤数据(日历数组)
function get_user_month_hr($date,$user_id){
	$return_arr=array();
	if(strlen($user_id)>0){
		$cur_user_info=config('user_info')[$user_id];
	}else{
		$cur_user_info=session('cur_user_info');
	}	

	if(strlen($date)==0){
		$date=date('Y-m-d',time());
	}
	$month_arr=get_begin_last_date($date);

	//如果是本月考勤,则截止日期为当前日期
	if(date('Y-m',strtotime($date))==date('Y-m',time())){
		$month_arr[1]=date('Y-m-d 23:59:59',time());
	}

	$user_hr=db('hr_table')->where(" hr_date between '".$month_arr[0]."' and '".$month_arr[1]."' and user_id=".$user_id)->select();
	
	//出差人员第一次打卡数据处理
	if($cur_user_info['out_site_id']!=0){
		$cur_user_info['site_id']=$cur_user_info['out_site_id'];
		$cur_user_info['card_id']=$cur_user_info['out_card_id'];
		//出差人员抓卡号
		$sql="select min(entry_dt) as first_card from sw_entry_dt_".get_site_code($cur_user_info['site_id'])." where entry_date='".date('Y-m-d',time())."' and card_no='".$cur_user_info['card_id']."'";
	}else{
		//非出差人员抓工号
		$sql="select min(entry_dt) as first_card from sw_entry_dt_".get_site_code($cur_user_info['site_id'])." where entry_date='".date('Y-m-d',time())."' and emp_no='".$cur_user_info['user_gh']."'";
	}	
	
	$first_card_arr=db()->query($sql);

	if(strlen($first_card_arr[0]['first_card'])<2){
		$first_card='无打卡!';
	}else{
		$first_card=date('H:i:s',strtotime($first_card_arr[0]['first_card']));
	}

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

	if(count($user_hr)>0){
		
		$note_sql="SELECT note_check_status,note_step,user_id,begin_time,end_time FROM sw_user_note where status=1 and user_id='$user_id' and (begin_time>='$month_arr[0]' and end_time<='$month_arr[1]' or end_time>'".date("Y-m-d",strtotime($month_arr[0] . "-1 day"))."')";
		$note_arr=db()->query($note_sql);
		
		//dump($note_arr);exit;
	
		//填充考勤数据
		foreach ($user_hr as $key=>$val){
			if($val['hr_status']==1){
				$color='primary';
				$title="正常";
			}else{
				$color='danger';
				$title="异常";
			}
			
			foreach ($note_arr as $not){
				if($not['user_id']==$val['user_id']){
					if(date('Y-m-d', strtotime($not['begin_time']))<=$val['hr_date']){
						if($val['hr_date']<=date('Y-m-d', strtotime($not['end_time']))){
							if($not['note_step']==4){
								if($not['note_check_status']==1){
									$title.="(通过)";
								}else{
									$title.="(未通过)";
								}
								break;
							}else{
								$color='warning';
								$title.="(待审核)";
								break;
							}
						}
					}
				}
			}
			
			
				
			if( $val['hr_date']==date('Y-m-d',time())){
				$return_arr['events'][$key]['title']=$val['title'].":&nbsp;&nbsp;".$title."&nbsp;&nbsp;".$first_card;
			}else{
				$return_arr['events'][$key]['title']=$val['title'].":&nbsp;&nbsp;".$title."&nbsp;&nbsp;";
			}

			$return_arr['events'][$key]['desc']=$val['hr_status_remark'].'  '.$val['hr_remark'];
			$return_arr['events'][$key]['calendar']=$color;
			$return_arr['events'][$key]['allDay']='true';
			$return_arr['events'][$key]['start']=$val['hr_date'];
			$return_arr['events'][$key]['end']=$val['hr_date'];
		}

		$return_arr['events'][$key+1]['title']=$first_card;
		$return_arr['events'][$key+1]['desc']='本日第一次打卡';
		$return_arr['events'][$key+1]['calendar']='danger';
		$return_arr['events'][$key+1]['allDay']=true;
		$return_arr['events'][$key+1]['start']=date('Y-m-d',time());
		$return_arr['events'][$key+1]['end']=date('Y-m-d',time());
	}else{
		//本月第一次
		$return_arr['events'][0]['title']=$first_card;
		$return_arr['events'][0]['desc']='本日第一次打卡';
		$return_arr['events'][0]['calendar']='danger';
		$return_arr['events'][0]['allDay']=true;
		$return_arr['events'][0]['start']=date('Y-m-d',time());
		$return_arr['events'][0]['end']=date('Y-m-d',time());
	}
	
	return $return_arr;
}


/**
 * 年资计算
 */
function user_seniority($in_date,$out_seniority){
	$seniority=array();
	$seniority['out_seniority']=$out_seniority;
	$cur_date=date('Y-m-d',time());
	$date_diff=timediff($in_date, $cur_date);
	$in_seniority=(int)($date_diff['day']/365);

	if((($date_diff['day'] % 365)/365)>0.5){
		$in_seniority = $in_seniority+1;
	}else{
		if((($date_diff['day'] % 365)/365)==0){
			$in_seniority = $in_seniority;
		}else{
			$in_seniority = $in_seniority+0.5;
		}
	}
	$seniority['in_seniority']=$in_seniority;
	$seniority['total_work_year']=$in_seniority+$out_seniority;

	return $seniority;
}



/**
 * 判断上月考勤数据是否已经生成
 */
function check_hr_base(){

	//上上月日期范围
	$llast_month_f=date('Y-m',strtotime('-2 month',time()));
	$llast_month_i=strtotime(date('Y-m',strtotime('-2 month',time())));

	//上月日期范围
	$last_month_range=getlastMonthDays();

	$last_month_f=$last_month_range[0];
	$last_month_i=strtotime($last_month_range[0]);

	//判断上月考勤数据是否已经生成,如果已经生成,则不重新计算
	$is_have=db('sys_event')->where(' year='.date('Y',$last_month_i).' and site_id='.$_SESSION['u_i']['site_id'].' and month='.date('n',$last_month_i).' and type_flag=1')->find();

	if(!$is_have){
		return false;
	}else{
		return true;
	}
}


/**
 * 计算某用户考勤
 */
function calculate_user_hr($date=0,$user_id=0){
	$return=array();
	if($date==0){
		$date=date('Y-m-d',time());
	}

	if($user_id==0){
		$user_id=get_user_id();
	}

	$month_arr=get_begin_last_date($date);
	$user_info=db('sys_user')->where('id='.$user_id)->find();

	//如果为本月考勤,且本月考勤数据未锁定,则重新计算本月考勤
	$is_lock=db('hr_table')->where(" hr_date between '".$month_arr[0]."' and '".$month_arr[1]."' and site_id=".$user_info['site_id'])->max('lock_flag');
	if($is_lock==0){
		$calculate_user_hr=calculate_user_hr_table($user_info['site_id'],$date,$user_id);
		foreach ($calculate_user_hr as $k=>$v){
			//判断本条记录在hr_table中是否存在,如果存在,则更新,如果不存在,则插入
			$hr_table_id=db('hr_table')->where('user_id='.$v['user_id']." and hr_date='".$v['hr_date']."'")->value('id');
			if($hr_table_id){
				$v['id']=$hr_table_id;
				db('hr_table')->update($v);
			}else{
				unset($v['id']);
				db('hr_table')->insert($v);
			}
		}
		$return['status']=1;
		$return['msg']="用户 ".config('user_info')[$user_id]['nickname'].' - '.date('m',strtotime($date)).'月考勤数据刷新成功!';
	}else{
		$return['status']=0;
		$return['msg']='已过考勤结算日,无法刷新!';
	}
	return $return;
}


/**
 * 计算实际出勤时间数,
 * =0情况,按所需时间数扣
 * 申请单时间数>所需时间数,按所需时间数扣
 * 申请单时间数<所需时间数,按申请单填写时间数
 * $note_time 申请单时间
 * $user_need_time 所需补充时间
 */
function get_note_need_time($user_need_time,$note_time){
	$return=0;
	if($user_need_time==$note_time){
		$return=$user_need_time;
	}else{
		
		/*
		//订单时间超过所需时间
		if($note_time>$user_need_time){
			//订单时间超所需时间是否大于0.5
			if(($note_time-$user_need_time)<0.5){
				$return=$note_time;
			}else{
				$return=$user_need_time;
			}
		}else{
			$return=$note_time;
		}
		*/
		$return=$note_time;
	}

	if($return>config('hr_work_act_time')){
		$return=config('hr_work_act_time');
	}

	return $return;
}



/**
 * 计算高标出勤时间,0.5起算,不足0.5舍弃
 */
function get_out_work_time($time_arr,$c_hr_work_time=0){
	
	if($c_hr_work_time==0){
		$hr_work_time=config('hr_work_time')*60*60;
	}else{
		$hr_work_time=$c_hr_work_time;
	}
	
	$over_time=($time_arr['s']-$hr_work_time)/60;
	//超过半小时,开始计算高标
	if($over_time>=30){
		$over_time=((int)(($over_time/60)*10))/10;
		//高标半小时起算
		if(substr($over_time,-1,1)>=5){
			$over_time=(int)$over_time+0.5;
		}else{
			$over_time=(int)$over_time;
		}
	}else{
		$over_time=0;
	}
	return $over_time;
}

/**
 * 计算出勤时间归整计算,不足0.5返回0,半小时进位运算,进来单位,秒
 * $flag ,标记是累加(com)还是累减(user). 如某用户高标考勤,不足0.5为舍弃. 如果用户做出勤计算,不足0.5需补足0.5
 */
function int_time($time,$flag){
	$time_source=$time;
	$time=$time/(60*60);
	$time=((int)($time*10))/10;
	if($flag=='add'){
		//累加(com) 1.3 变成1.5
		if(substr($time,-1,1)>0){
			if(substr($time,-1,1)>=5){
				$return_time=(int)$time+1;
			}else{
				$return_time=(int)$time+0.5;
			}
		}else{
			$return_time=(int)$time;
		}
		
		//缺勤29秒,计算出来$return_time为0,则返回 半小时
		if($time_source>0 && $return_time==0){
			$return_time=0.5;
		}
	}else{
		//累减(com), 1.3 变成1
		if(substr($time,-1,1)>=5){
			$return_time=(int)$time+0.5;
		}else{
			$return_time=(int)$time;
		}
	}
	if($return_time==0.5){
		return 1;
	}else{
		return $return_time;
	}

}

/**
 * 返回转上级时间数
 */
function get_adv_manage_h(){
	return config('hr_holiday_adv_manage_val')*8;
}

/*
 * 获取某用户的代理人列表
 */
function get_user_age_info($user_id){
	//默认显示本部门人员&主管,点击全部后显示所有
	$cur_user_info=session('cur_user_info');
	//$user_info=db('sys_user')->field('id,nickname,dep_id')->select();
	$sql="
			select id,nickname,dep_id,
				(case when (dep_id=".$cur_user_info['dep_id'].") or (id=".$cur_user_info['hr_user_id'].") then 1 else 0 end ) as show_flag,
				(case when id=".$cur_user_info['hr_user_id']." then 1 else 0 end) as hr_flag
			from
				sw_sys_user
			where
				id <> ".$cur_user_info['id']." and status=1 and hr_status in (1,3) and user_status=1
			order by email
			";

	$user_info=db()->query($sql);
	return $user_info;
}


/*
 * 计算某用户年休时间
 */
function get_user_year_holiday_day($user_id){
	$year_month=strtotime(get_last_month());
	//判断sw_hr_cal上月是否有数据
	$hr_cal_info=db('hr_cal')->where('user_id='.$user_id.' and year='.date('Y',$year_month).' and month='.date('m',$year_month))->find();
	if(!$hr_cal_info){
		//判断sw_hr_cal上上月是否有数据
		$year_month=strtotime(date("Y-m-d",mktime(0, 0 , 0,date("m")-2,1,date("Y"))));
		$hr_cal_info=db('hr_cal')->where('user_id='.$user_id.' and year='.date('Y',$year_month).' and month='.date('m',$year_month))->find();
		if(!$hr_cal_info){
			return session('cur_user_info')['annual_num'];
		}
	}
	return c_z($hr_cal_info['local_annual_num']);
}

/*
 * 计算某用户补休时间
 */
function get_user_hr_holiday($user_id){
	$year_month=strtotime(get_last_month());
	//判断sw_hr_cal上月是否有数据
	$hr_cal_info=db('hr_cal')->where('user_id='.$user_id.' and year='.date('Y',$year_month).' and month='.date('m',$year_month))->find();
	if(!$hr_cal_info){
		//判断sw_hr_cal上上月是否有数据
		$year_month=strtotime(date("Y-m-d",mktime(0, 0 , 0,date("m")-2,1,date("Y"))));
		$hr_cal_info=db('hr_cal')->where('user_id='.$user_id.' and year='.date('Y',$year_month).' and month='.date('m',$year_month))->find();
		if(!$hr_cal_info){
			return session('cur_user_info')['repair_num'];
		}
	}
	return c_z($hr_cal_info['local_repair_num']);
}

/**
 * 规避单位时间内多次打卡情况
 */
function set_entry_status($month_range=array(),$site_id){
	//header("Content-type: text/html; charset=utf-8");
	if(count($month_range)==0){
		$month_range=get_begin_last_date();
	}
	$site_code=get_site_code($site_id);
	
	//将某时间段中设置为无效的记录设置为有效
	$sql="update sw_entry_dt_".$site_code." set status=1 where entry_date between '".$month_range[0]." 00:00:00' and '".$month_range[1]." 23:59:59'";
	db()->query($sql);
	
	//统计打卡数超过2次的用户
	$sql="select * from (
				select
					e.emp_no,count(*) as count_entry_num,e.entry_date 
				from
					sw_entry_dt_".$site_code." e,sw_sys_user u
				where
					(e.emp_no=u.user_gh or e.card_no=u.out_card_id) and (u.site_id=1 or (u.out_site_id<>0 and length(u.out_card_id)>0)) and u.is_hr_user=1 and entry_dt between '".$month_range[0]." 00:00:00' and '".$month_range[1]." 23:59:59' and ctrl_id in (23,2) and left(emp_no,1) in ('s','S','z','Z','x','X')
				group by e.entry_date,e.emp_no
				order by count_entry_num desc
			) as t
			where
				t.count_entry_num>2
			order by
				t.emp_no,t.entry_date";
	$entry_arr=db()->query($sql);
	 
	foreach ($entry_arr as $key=>$val){
		//获取当前用户某日多次打卡时间数
		$sql="select * from sw_entry_dt_".$site_code." where emp_no='".$val['emp_no']."' and entry_dt between '".$val['entry_date']." 00:00:00' and '".$val['entry_date']." 23:59:59' and ctrl_id in (23,2) ;";
	
		$user_day_entry_arr=db()->query($sql);
		//数据合理判断
		$sec_num=config('allow_interval_time');
		$check_flag=false;
		$temp_arr=array();
		$temp_time="";
		foreach ($user_day_entry_arr as $k=>$v){
			//设置初始时间参数,以此为判断标准
			if($k==0){
				$temp_time=strtotime($v['entry_dt']);
				//echo $v['entry_dt'].' temp_time.<br>';
			}			
			 
			//第2条开始进行判断
			if($k>0){
				//echo $v['emp_no'].' 上一次打卡: '.date('Y-m-d H:i:s',$temp_time).' 对比时间: '.$v['entry_dt'].' 判断时间小时数为: '.date('H',strtotime($v['entry_dt'])).' 对比值: '.$temp_time.'<-->'.(strtotime($v['entry_dt'])+$sec_num).'<br>';
				//如果为上午,取早的时间为有效值,如果为下午,则取晚的时间为有效值
				if(date('H',strtotime($v['entry_dt']))<=12){
					//如果本次打卡时间小于30秒时间范围,设置为非有效打卡,继续采用连续打卡第一次为判断条件
					if(strtotime($v['entry_dt'])<=($temp_time+$sec_num)){
						db('entry_dt_'.$site_code)->where('id='.$v['id'])->setField('status',0);
						//echo 'change:'.$v['entry_dt'].' status to 0<br>';
					}else{
						//本次打卡时间大于30秒范围,更新打卡时间为判断条件
						$temp_time=strtotime($v['entry_dt']);
					}
				}else{
					//如果本次打卡时间
					if(strtotime($v['entry_dt'])>($temp_time+$sec_num)){
						$temp_time=strtotime($v['entry_dt']);
					}else{
						db('entry_dt_'.$site_code)->where('id='.$temp_arr['id'])->setField('status',0);
						//echo 'change:'.$temp_arr['entry_dt'].' status to 0<br>';
					}
				}
			}
			//缓存上一次打卡数据
			$temp_arr=$v;
			//echo '<br><hr>';
		}
	}
}
