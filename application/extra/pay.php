<?php
//校验用户基本薪资字段完整
function check_user_base_val($user_id){
	//获取目前系统中的基本薪资字段
	$base_field_arr=db()->query('select group_concat(field_id) as id_str from sw_pay_base_val where user_id='.$user_id);
	$base_field_id_str=$base_field_arr[0]['id_str'];

	//获取在pay_base_field中不存在的ID
	if(strlen($base_field_id_str)>0){
		$no_in_arr=db('pay_field')->where(" id not in (".$base_field_id_str.") and base_field=1")->select();
	}else{
		$no_in_arr=db('pay_field')->where(" base_field=1")->select();
	}

	foreach ($no_in_arr as $key=>$val){
		$insert_arr=array();
		$insert_arr['user_id']=$user_id;
		$insert_arr['field_id']=$val['id'];
		$insert_arr['log']=get_date_time().' 用户'.get_user_nickname().'创建,初始值为0<br>';
		$insert_arr['status']=1;
		$insert_arr['last_user_id']=get_user_id();
		$insert_arr['create_time']=get_date_time();
		db('pay_base_val')->insert($insert_arr);
	}
}

	//返回用户基本薪资字段SQL
	function get_base_val($user_id){
		$sql="
				select 
					bv.id,pf.field_name,pf.zh_name,bv.base_val,bv.user_id,bv.log
				from 
					sw_pay_field pf,sw_pay_base_val bv,sw_sys_site s,sw_sys_user u
				where 
					base_field=1 and pf.id=bv.field_id and bv.user_id=".$user_id." and pf.status=1 and s.id=u.site_id and u.id=bv.user_id and pf.site_pay_flag=s.site_pay_flag
				order by 
					pf.order_id
				";
		
		return db()->query("$sql");
	}
	
	//获取用户考勤主管上级&四级查找
	function get_hr_manage($user_id){
		$pay_arr=array();
		//考勤人
		$pay_arr[1]=get_cache_data('user_info',$user_id,'hr_user_id');
		$pay_arr[2]=get_cache_data('user_info',$pay_arr[1],'hr_user_id');
		$pay_arr[3]=get_cache_data('user_info',$pay_arr[2],'hr_user_id');
		$pay_arr[4]=get_cache_data('user_info',$pay_arr[3],'hr_user_id');
	
		if($pay_arr[1]=='-'){
			$pay_arr[1]='choose';
		}
		
		if($pay_arr[2]=='-'){
			$pay_arr[2]='choose';
		}
	
		if($pay_arr[3]=='-'){
			$pay_arr[3]='choose';
		}
	
		if($pay_arr[4]=='-'){
			$pay_arr[4]='choose';
		}
		return $pay_arr;
	
	}
	
	//判断当前用户管理薪资人员当月月奖单是否生成
	function check_pay_month_row($user_id=""){
		if(strlen($user_id)==0){
			$user_id=get_user_id();
		}
		
		//获取当前用户管理月奖单人员列表
		$user_arr=db('sys_user')->where('pay_user_id_1='.$user_id)->select();
		$year=date('Y',time());
		$month=date('m',time());
		
		foreach ($user_arr as $key=>$val){
			//判断当前用户本月月奖单是否已经生成
			$month_arr=array();
			$row_info=db('pay_month')->where("user_id=".$val['id']." and year=".$year." and month=".$month)->find();
			if(!$row_info){
				//新建月奖单记录
				$month_arr['user_id']=$val['id'];
				$month_arr['year']=$year;
				$month_arr['month']=$month;
				$month_arr['last_user_id']=get_user_id();
				$month_arr['create_time']=date('Y-m-d H:i:s',time());
				$month_arr['pay_user_id_1']=$val['pay_user_id_1'];
				$month_arr['pay_user_id_2']=$val['pay_user_id_2'];
				$month_arr['pay_user_id_3']=$val['pay_user_id_3'];
				$month_arr['pay_user_id_4']=$val['pay_user_id_4'];
				$month_arr['step']=0;
				$month_arr['log']=date('Y-m-d H:i:s',time()).' '.get_user_nickname().' 生成'.$year.'-'.$month.' 月奖单<br>';
				db('pay_month')->insert($month_arr);
			}
			/*
			else{
				//判断审核人是否已经更改,只有初建月奖单同步审核流程关系, 
				//此步骤更改到用户薪资基础资料维护修改薪资审核流程
				if($row_info['step']==0){
					$month_arr=array();
					$month_arr['id']=$row_info['id'];
					$month_arr['pay_user_id_1']=$val['pay_user_id_1'];
					$month_arr['pay_user_id_2']=$val['pay_user_id_2'];
					$month_arr['pay_user_id_3']=$val['pay_user_id_3'];
					$month_arr['pay_user_id_4']=$val['pay_user_id_4'];
					db('pay_month')->update($month_arr);
				}
			}
			*/
			
		}
	}

	/*
	 * 判断sw_pay表中列字段是否与 sw_pay_field表匹配
	 */
	function check_pay_field(){
		//获取当前字段数组
		$all_field_arr=db('pay_field')->where('site_pay_flag='.$_SESSION['pay_trim']['site_pay_flag'])->select();
		$pay_tb_name_arr=get_site_pay_tb($_SESSION['pay_trim']['site_pay_flag']);
		$tb_s=$pay_tb_name_arr['s'];
		$tb_f=$pay_tb_name_arr['f'];
				
		//获取sw_pay中列信息
		$pay_table_info_arr=db($tb_s)->getTableinfo();
		$pay_field_arr=$pay_table_info_arr['fields'];
	
		//sw_pay表中非检测字段数量
		$pay_field_no_cal_num=config('PAY_FIELD_NO_CAL_NUM');
	
		//判断sw_pay_table表中字段数是否与sw_pay_field数量对得上
		$dif_num=count($all_field_arr) - (count($pay_field_arr)-$pay_field_no_cal_num);
		
		$begin_num=count($pay_field_arr)-$pay_field_no_cal_num;
		
		if($dif_num>0 ){
			for($i=$begin_num;$i<($begin_num+$dif_num);$i++){
				//echo $all_field_arr[$i]['field_name'].'---'.$all_field_arr[$i]['zh_name'].'---'.$all_field_arr[$i]['field_type'].'<br>';
				$sql="ALTER TABLE ".$tb_f." ADD COLUMN ".$all_field_arr[$i]['field_name']." ";
				switch ($all_field_arr[$i]['field_type']){
					case 1:
						$sql .= " decimal(16,4) DEFAULT 0 COMMENT '".$all_field_arr[$i]['zh_name']."' ";
						break;
					case 2:
						$sql .= " varchar(200) CHARACTER SET utf8 NULL COMMENT '".$all_field_arr[$i]['zh_name']."' ";
						break;
					case 3:
						$sql .= " text CHARACTER SET utf8 NULL COMMENT '".$all_field_arr[$i]['zh_name']."' ";
						break;
				}
				db()->execute($sql);
			}
		}
	}

	//某字段修改为基础字段,自动补全基础字段信息
	function complement_base_val($field_id){		
		//获取该字段的区域归属信息
		$field_info=db('pay_field')->find($field_id);
		
		$site_pay_flag=$field_info['site_pay_flag'];
		$site_pay_tb_arr=get_site_pay_tb($site_pay_flag);
		$tb_s=$site_pay_tb_arr['s'];
		$tb_f=$site_pay_tb_arr['f'];
		
		$user_arr=db('sys_user')->where('hr_status=1 and status=1 and user_status=1 and site_id in (select id from sw_sys_site where  site_pay_flag='.$field_info['site_pay_flag'].')')->select();
		
		//将基础字段信息中此字段的status设置为1
		db()->execute("update sw_pay_base_val set status=1,log=concat('".get_date_time()." ".get_user_nickname()." 启用该字段基础字段属性!<br />"."',log) where field_id=".$field_id." and status=0");
		
		foreach ($user_arr as $key=>$val){
			//判断此字段在基础字段中是否已经存在,如果没有,则添加
			$is_have=db('pay_base_val')->where('user_id='.$val['id']." and field_id=".$field_id)->find();
			if(!$is_have){
				$insert_arr=array();
				//判断月工资表中上月此栏位是否有数据
				$last_month=strtotime(get_last_month());
				$pay_table_is_have=db($tb_s)->where(" user_id=".$val['id']." and year=".date('Y',$last_month)." and month=".date('n',$last_month))->value('pay_'.$field_id);
				if(!$pay_table_is_have){
					$pay_val=0;
				}else{
					$pay_val=$pay_table_is_have;
				}
				$insert_arr['user_id']=$val['id'];
				$insert_arr['field_id']=$field_id;
				$insert_arr['base_val']=$pay_val;
				$insert_arr['log']=get_date_time()." 由 ".get_user_nickname().' 创建!初始值为'.$pay_val;
				$insert_arr['status']=1;
				$insert_arr['last_user_id']=get_user_id();
				$insert_arr['create_time']=get_date_time();
				db('pay_base_val')->insert($insert_arr);
			}
		}

	}
	
	//判断员工入职是否已经6个月
	function user_is_six_month($user_id){
	 	$entry_date=db('sys_user')->where('id='.$user_id)->value('entry_date');
	 	
	 	$month=getMonthNum(date('Y-m-d',time()),$entry_date);
	 	
	 	if($month>5){
	 		return 1;
	 	}else{
	 		if(date('d',strtotime($entry_date))=='01'){
	 			return 1;
	 		}else{
	 			return 0;
	 		}	 		
	 	}
	}
	
	//获取本年度入职员工的信息
	function get_user_cyear_info($user_id){
		$return_arr=array();
		
		$user_info=db('sys_user')->where('id='.$user_id)->find();
		//入职日期
		$entry_date=$user_info['entry_date'];
		$c_date=date('Y-m-d',time());
		
		if($entry_date=='0000-00-00'){
			show_msg('入职日期错误!');
			return ;
		}
		
		//入职日期当前月份范围
		$month_arr=get_begin_last_date($entry_date);
		$month_last_day=$month_arr[1];	
		
		$return_arr['entry_date']=$entry_date;
		
		//判断入职日是否本月第一天
		if(date('d',strtotime($entry_date))=='01'){
			$return_arr['is_month_first_day']=1;			
			$return_arr['c_month_level_work_day']=0;
			$return_arr['level_month']=getMonthNum($c_date, $entry_date)+1;
		}else{
			$return_arr['is_month_first_day']=0;
			//计算入职日到本月底日期差
			$c_month_day_dif=get_interval_day($entry_date,$month_last_day);
			
			//计算入职日到本月月底假日
			$month_holiday=db('sys_holiday')->where('site_id='.$user_info['site_id']." and holiday_date between '".$entry_date."' and '".$month_last_day."'")->count();
			
			//本月剩余工作日天数
			$c_month_level_month_day=$c_month_day_dif-$month_holiday;
			
			$return_arr['c_month_level_work_day']=$c_month_level_month_day;
			$return_arr['level_month']=getMonthNum($c_date,$entry_date );
		}
		return $return_arr;
	}
	
	//端午,中秋,双薪计算函数
	function double_pay_cal($user_id,$pay_flag){
		$return_money=0;
		//获取该员工基本薪资
		$normal_money=get_user_normal_money($user_id);
		
		//判断入职日期是否大于6个月
		if(user_is_six_month($user_id)){
			//老员工,满6个月
			if($pay_flag=='all'){
				return $normal_money;
			}else{
				return $normal_money/2;
			}						
		}else{
			//获取本年度入职员工的入职参数
			$cyear_info=get_user_cyear_info($user_id);
			
			if($pay_flag=='all'){				
				//return round($normal_money*(($cyear_info['level_month']/12)+($cyear_info['c_month_level_work_day']/config('MONTH_WORK_DAY'))));
				return round(($normal_money)/6*$cyear_info['level_month']+$normal_money/6/config('MONTH_WORK_DAY')*$cyear_info['c_month_level_work_day']);
			}else{
				//return round(($normal_money/2)*(($cyear_info['level_month']/12)+($cyear_info['c_month_level_work_day']/config('MONTH_WORK_DAY'))));
				return round(($normal_money)/2/6*$cyear_info['level_month']+$normal_money/2/6/config('MONTH_WORK_DAY')*$cyear_info['c_month_level_work_day']);				
			}
		}		
	}
	
	//返回某员工基本薪资,目前由薪资+差额津贴组成
	function get_user_normal_money($user_id){
		$normal_money_arr=db()->query("select sum(base_val) as normal_money from sw_pay_base_val where field_id in (1,15) and user_id=".$user_id);
		return $normal_money_arr[0]['normal_money'];
	}

	//返回某用户某月份薪资计算sql
	function get_pay_sql($user_id){
		
		
	}
	
	//用户月奖计算公式
	function get_user_pay_month($user_id,$year="",$month="",$field_id=3){
		/*
		if(strlen($year)==0){
			$year=C_YEAR;
		}
		if(strlen($month)==0){
			$month=C_MONTH;
		}
		
		//获取用户的normal月奖
		$pay_normal=db('pay_base_val')->where('user_id='.$user_id." and field_id=".$field_id)->value('base_val');
		
		//获取用户对应月份调薪金额
		$pay_month_arr=db('pay_month')->where('year='.$year." and month=".$month." and user_id=".$user_id)->find();
				
		return round($pay_normal+$pay_month_arr['self_num']+$pay_month_arr['out_num']);	
		*/
		
		return db('pay_base_val')->where('user_id='.$user_id." and field_id=".$field_id)->value('base_val');
		
	}

	//获取月份的月奖单调整记录
	function get_pay_month_his($user_id,$type_flag="",$date=""){
		$return_str="";
		if(!isset($date)){
			$year=C_YEAR;
			$month=C_MONTH;
		}else{
			$year=date('Y',strtotime($date));
			$month=date('n',strtotime($date));
		}
	
		if(strlen($type_flag)==0){
			$type_flag="self";
		}
	
		$sql="select concat(year,'-',month,'  ',u.nickname,'  既定目标调整 ',self_num,' ') as self_num_str,concat(year,'-',month,'  ',u.nickname,'  额外工作调整 ',out_num,' ') as out_num_str from sw_pay_month p,sw_sys_user u where p.user_id=u.id order by month,year desc  limit ".config('PAY_MONTH_HIST_NUM');
	
		$his_arr=db()->query($sql);
	
		if(count($his_arr)==0){
			$return_str="最近无记录!";
		}else{
			foreach ($his_arr as $key=>$val){
				if($type_flag=='self'){
					$return_str .= $val['self_num_str'].'<br />';
				}else{
					$return_str .= $val['out_num_str'].'<br />';
				}
			}
		}	
		return  $return_str;
	}
	
	//判断该站点所有用户的基本薪资是否已经维护
	function check_pay_base($site_id=0){
		if($site_id==0){
			$site_id=$_SESSION['pay_trim']['site_id'];
		}
		
		$user_info=db('sys_user')->where('id='.get_user_id())->find();
		//获取当前系统中有效用户信息,设定校验薪资的人员
		$user_arr=db('sys_user')->where("hr_status in (1) and status=1 and user_status=1 and pay_check=1 and site_id=".$site_id)->select();
		
		//获取基础数据ID
		$base_field_id_arr=db()->query("select group_concat(id) as base_field_str,count(*) as count from sw_pay_field where base_field=1 and site_pay_flag=".$_SESSION['pay_trim']['site_pay_flag']);

		$id_str=$base_field_id_arr[0]['base_field_str'];
		$id_count=$base_field_id_arr[0]['count'];
	
		foreach ($user_arr as $key=>$val){
			//判断这些基础数据在pay_base_val中是否有
			$user_base_val_count=db('pay_base_val')->where(" user_id=".$val['id']." and field_id in (".$id_str.")")->count();
			$check_flag=false;
			
			//判断基础数据与设定基础数据字段是否相等
			if($user_base_val_count != $id_count){
				$check_flag=true;
			}
			
			//判断基础字段,薪资,津贴,月奖,月绩效奖,月奖累加是否为空?
			switch ($site_id){
				case 7:
					$sql="select sum(base_val) as tt from sw_pay_base_val where user_id=".$val['id'].' and field_id in (83,84,85,86,95,97,98)';
					break;
				default:
					$sql="select sum(base_val) as tt from sw_pay_base_val where user_id=".$val['id'].' and field_id in (1,2,4,5,15)';
					break;
			}
			
			$temp_arr=db()->query($sql);
			if($temp_arr[0]['tt']==0){
				$check_flag=true;
			}		
			
			if($check_flag){
				//用户基础数据未维护,中断操作
				echo header("Content-type: text/html; charset=utf-8");
				echo '用户 '.get_user_nickname($val['id'])." 工号(".get_cache_data('user_info',$val['id'],'user_gh').")基础数据未维护!";
				exit;
			}
		}
	}
	
	//判断薪资数据是否已经生成
	function check_is_cr_pay($site_id){
		$is_cr_pay=db('sys_event')->where('type_flag=8 and site_id='.$site_id)->find();
		if($is_cr_pay){
			return true;
		}else{
			return false;
		}
	}
	
	//内地批量获取用户基础数据,生成当月薪资数据
	function batch_pay_month($site_id=0,$user_id=0){
		if($site_id==0){
			$site_id=$_SESSION['pay_trim']['site_id'];
		}
		
		$c_user_info=db('sys_user')->where('id='.get_user_id())->find();
		
		$last_year_month=get_last_month();
		$year=date('Y',strtotime($last_year_month));
		$month=(int)date('m',strtotime($last_year_month));
		
		//判断该站点数据是否结转,如果已经结转,则不生成新数据
		$is_finish=db('sys_event')->where('site_id='.$site_id." and year=".C_YEAR." and month=".C_MONTH." and type_flag=9")->find();
		
		if(!$is_finish){			

			$where_str="";
			
			if($user_id<>0){
				$where_str=" and u.id=".$user_id." ";
			}
			
			//第一阶段,基本薪资信息资料获取,薪资,津贴,月绩效奖,Normal月奖,奖励金,差额津贴,养老金,医疗保险,失业保险,公积金,扣还款,银行帐号获取
			$sql="
				select
					u.id as user_id,".C_YEAR." as year,".C_MONTH." as month,0 as is_lock,now() as create_time,now() as update_time,
				    /*薪资*/
				    max(case when field_id=1 then base_val else 0 end) as pay_1,
				    /*津贴*/
				    max(case when field_id=2 then base_val else 0 end) as pay_2,
				    /*月绩效奖*/
				    max(case when field_id=4 then base_val else 0 end) as pay_4,
				    /*月奖*/
				    max(case when field_id=3 then base_val else 0 end) as pay_3,
				    /*奖励金*/
				    max(case when field_id=6 then base_val else 0 end) as pay_6,
				    /*差额津贴*/
				    max(case when field_id=15 then base_val else 0 end) as pay_15,
				    /*养老金*/
				    max(case when field_id=17 then base_val else 0 end) as pay_17,
				    /*医疗保险*/
				    max(case when field_id=18 then base_val else 0 end) as pay_18,
				    /*失业保险*/
				    max(case when field_id=19 then base_val else 0 end) as pay_19,
				    /*公积金*/
				    max(case when field_id=20 then base_val else 0 end) as pay_20,
				    /*扣还款*/
				    max(case when field_id=33 then base_val else 0 end) as pay_33,
				    /*银行帐号*/
				    u.bank_card as pay_45,
					/*境外收入*/
					max(case when field_id=54 then base_val else 0 end) as pay_54
				from
					sw_sys_user u,sw_pay_base_val bv
				where
					u.pay_status=1  and u.site_id=".$site_id." and length(bank_card)>0 and u.id=bv.user_id and u.status=1 and u.user_status=1 ".$where_str."
				group by u.id
				order by u.id
				;
				";
			$pay_month_arr=db()->query($sql);
			
			//更新数据到pay_table
			foreach ($pay_month_arr as $key=>$val){
				//判断本月数据是否已经存在
				$is_have= db('pay_table')->where('user_id='.$val['user_id'].' and year='.C_YEAR.' and month='.C_MONTH)->value('id');
				if(!$is_have){
					db('pay_table')->insert($val);
				}else{
					$val['id']=$is_have;
					db('pay_table')->update($val);
				}
			}
			
			//第二阶段,考勤数据结算
			//判断考勤数据是否已结转,如果已经结转,则进行考勤数据计算,如果未结转,则不进行操作
			$is_hr_cal_finish=db('sys_event')->where('year='.$year.' and month='.$month.' and site_id='.$site_id.' and type_flag=2')->find();
			
			if($is_hr_cal_finish){
				update_hr_cal_to_pay($cal_flag="batch",$user_id=0,$site_id);
			}
			
			/*
			//第三阶段
			//填充月奖信息
			foreach ($pay_month_arr as $key=>$val){
				$val['pay_3']=get_user_pay_month($val['user_id']);
			
				//更新数据到pay_table
				$row_id= db('pay_table')->where('user_id='.$val['user_id'].' and year='.C_YEAR.' and month='.C_MONTH)->value('id');
				$val['id']=$row_id;
				db('pay_table')->update($val);
			}
			*/
			
			//第四阶段
			//薪资表统一计算
			cal_pay_tb();
			
			if($user_id==0){
				//判断sw_sys_event中是否已经有本月薪资写入记录,如果有,则LOG标识
				$is_have_event=db('sys_event')->where(' year='.C_YEAR.' and month='.C_MONTH.' and site_id='.$site_id.' and type_flag=8')->find();
				if(!$is_have_event){
					//写入sw_sys_event表,确定上月考勤数据已经生成
					$temp_arr=array();
					$temp_arr['title']=C_YEAR.'-'.C_MONTH.' 薪资数据生成';
					$temp_arr['site_id']=$site_id;
					$temp_arr['year']=C_YEAR;
					$temp_arr['month']=C_MONTH;
					$temp_arr['type_flag']=8;
					$temp_arr['log']=get_date_time().' 由'.get_user_nickname().' 生成'.get_cache_data('site_info', $site_id,'site').'薪资数据!';
					$temp_arr['last_user_id']=get_user_id();
					$temp_arr['create_time']=get_date_time();
					$temp_arr['update_time']=get_date_time();
			
					db('sys_event')->insert($temp_arr);
				}else{
					//更新sw_sys_event表,确定上月考勤数据已经生成
					$temp_arr=array();
					$temp_arr['id']=$is_have_event['id'];
					$temp_arr['log']=get_date_time().' 由'.get_user_nickname().' 重新生成计算'.get_cache_data('site_info', $site_id,'site').'薪资数据!<br>'.$is_have_event['log'];;
					$temp_arr['last_user_id']=get_user_id();
					$temp_arr['update_time']=get_date_time();
			
					db('sys_event')->update($temp_arr);
				}
			}			
			
		}		
		
	}
	
	
	//香港批量获取用户基础数据,生成当月薪资数据
	function batch_pay_month_hk($site_id=0,$user_id=0){
		if($site_id==0){
			$site_id=$_SESSION['pay_trim']['site_id'];
		}
	
		$c_user_info=db('sys_user')->where('id='.get_user_id())->find();
	
		$last_year_month=get_last_month();
		$year=date('Y',strtotime($last_year_month));
		$month=(int)date('m',strtotime($last_year_month));
	
		//判断该站点数据是否结转,如果已经结转,则不生成新数据
		$is_finish=db('sys_event')->where('site_id='.$site_id." and year=".C_YEAR." and month=".C_MONTH." and type_flag=9")->find();
	
		if(!$is_finish){
	
			$where_str="";
				
			if($user_id<>0){
				$where_str=" and u.id=".$user_id." ";
			}
				
			//第一阶段,基本薪资信息资料获取,本薪,差额津贴,月奖
			$sql="
				select
					u.id as user_id,".C_YEAR." as year,".C_MONTH." as month,0 as is_lock,now() as create_time,now() as update_time,
				    /*本薪*/
				    max(case when field_id=65 then base_val else 0 end) as pay_65,
				    /*差额津贴*/
				    max(case when field_id=66 then base_val else 0 end) as pay_66,
				    /*月奖*/
				    max(case when field_id=67 then base_val else 0 end) as pay_67
				from
					sw_sys_user u,sw_pay_base_val bv
				where
					u.pay_status=1  and u.site_id=".$site_id." and u.id=bv.user_id and u.status=1 and u.user_status=1 ".$where_str."
				group by u.id
				order by u.id
				;
				";
			$pay_month_arr=db()->query($sql);
				
			//更新数据到pay_table
			foreach ($pay_month_arr as $key=>$val){
				//判断本月数据是否已经存在
				$is_have= db('pay_table_hk')->where('user_id='.$val['user_id'].' and year='.C_YEAR.' and month='.C_MONTH)->value('id');
				if(!$is_have){
					db('pay_table_hk')->insert($val);
				}else{
					$val['id']=$is_have;
					db('pay_table_hk')->update($val);
				}
			}

			/*
			//第二阶段,考勤数据结算
			//判断考勤数据是否已结转,如果已经结转,则进行考勤数据计算,如果未结转,则不进行操作
			$is_hr_cal_finish=db('sys_event')->where('year='.$year.' and month='.$month.' and site_id='.$site_id.' and type_flag=2')->find();
				
			if($is_hr_cal_finish){
				update_hr_cal_to_pay($cal_flag="batch",$user_id=0,$site_id);
			}
				
			//第三阶段
			//填充月奖信息
			foreach ($pay_month_arr as $key=>$val){
				$val['pay_3']=get_user_pay_month($val['user_id']);
					
				//更新数据到pay_table
				$row_id= db('pay_table')->where('user_id='.$val['user_id'].' and year='.C_YEAR.' and month='.C_MONTH)->value('id');
				$val['id']=$row_id;
				db('pay_table')->update($val);
			}
			
				
			//第四阶段
			//薪资表统一计算
			cal_pay_tb();
			*/
				
			if($user_id==0){
				//判断sw_sys_event中是否已经有本月薪资写入记录,如果有,则LOG标识
				$is_have_event=db('sys_event')->where(' year='.C_YEAR.' and month='.C_MONTH.' and site_id='.$site_id.' and type_flag=8')->find();
				if(!$is_have_event){
					//写入sw_sys_event表,确定上月考勤数据已经生成
					$temp_arr=array();
					$temp_arr['title']=C_YEAR.'-'.C_MONTH.' 薪资数据生成';
					$temp_arr['site_id']=$site_id;
					$temp_arr['year']=C_YEAR;
					$temp_arr['month']=C_MONTH;
					$temp_arr['type_flag']=8;
					$temp_arr['log']=get_date_time().' 由'.get_user_nickname().' 生成'.get_cache_data('site_info', $site_id,'site').'薪资数据!';
					$temp_arr['last_user_id']=get_user_id();
					$temp_arr['create_time']=get_date_time();
					$temp_arr['update_time']=get_date_time();
						
					db('sys_event')->insert($temp_arr);
				}else{
					//更新sw_sys_event表,确定上月考勤数据已经生成
					$temp_arr=array();
					$temp_arr['id']=$is_have_event['id'];
					$temp_arr['log']=get_date_time().' 由'.get_user_nickname().' 重新生成计算'.get_cache_data('site_info', $site_id,'site').'薪资数据!<br>'.$is_have_event['log'];;
					$temp_arr['last_user_id']=get_user_id();
					$temp_arr['update_time']=get_date_time();
						
					db('sys_event')->update($temp_arr);
				}
			}
				
		}
	
	}
	
	//台湾批量获取用户基础数据,生成当月薪资数据
	function batch_pay_month_tw($site_id=0,$user_id=0){
		if($site_id==0){
			$site_id=$_SESSION['pay_trim']['site_id'];
		}
	
		$c_user_info=db('sys_user')->where('id='.get_user_id())->find();
	
		$last_year_month=get_last_month();
		$year=date('Y',strtotime($last_year_month));
		$month=(int)date('m',strtotime($last_year_month));
	
		//判断该站点数据是否结转,如果已经结转,则不生成新数据
		$is_finish=db('sys_event')->where('site_id='.$site_id." and year=".C_YEAR." and month=".C_MONTH." and type_flag=9")->find();
	
		if(!$is_finish){
	
			$where_str="";
	
			if($user_id<>0){
				$where_str=" and u.id=".$user_id." ";
			}
	
			//第一阶段,基本薪资信息资料获取,基本工资,伙食津贴,差额津贴,月奖
			$sql="
				select
					u.id as user_id,".C_YEAR." as year,".C_MONTH." as month,0 as is_lock,now() as create_time,now() as update_time,
				    #基本工资
				    max(case when field_id=83 then base_val else 0 end) as pay_83,
				    #伙食津贴
				    max(case when field_id=84 then base_val else 0 end) as pay_84,
				    #差额津贴
				    max(case when field_id=85 then base_val else 0 end) as pay_85,
					#月奖
				    max(case when field_id=86 then base_val else 0 end) as pay_86,
					#个人所得税(固定薪资)
					max(case when field_id=95 then base_val else 0 end) as pay_95,
					#健保费用
					max(case when field_id=97 then base_val else 0 end) as pay_97,
					#劳保费用
					max(case when field_id=98 then base_val else 0 end) as pay_98
				from
					sw_sys_user u,sw_pay_base_val bv
				where
					u.pay_status=1  and u.site_id=".$site_id." and u.id=bv.user_id and u.status=1 and u.user_status=1 ".$where_str."
				group by u.id
				order by u.id
				;
				";
			$pay_month_arr=db()->query($sql);
	
			//更新数据到pay_table
			foreach ($pay_month_arr as $key=>$val){
				//判断本月数据是否已经存在
				$is_have= db('pay_table_tw')->where('user_id='.$val['user_id'].' and year='.C_YEAR.' and month='.C_MONTH)->value('id');
				if(!$is_have){
					$pay_tb_id=db('pay_table_tw')->insertGetId($val);
					pay_month_row_cal($pay_tb_id);
				}else{
					$val['id']=$is_have;
					db('pay_table_tw')->update($val);
				}
			}
	
			/*
				//第二阶段,考勤数据结算
				//判断考勤数据是否已结转,如果已经结转,则进行考勤数据计算,如果未结转,则不进行操作
				$is_hr_cal_finish=db('sys_event')->where('year='.$year.' and month='.$month.' and site_id='.$site_id.' and type_flag=2')->find();
	
				if($is_hr_cal_finish){
				update_hr_cal_to_pay($cal_flag="batch",$user_id=0,$site_id);
				}
	
				//第三阶段
				//填充月奖信息
				foreach ($pay_month_arr as $key=>$val){
				$val['pay_3']=get_user_pay_month($val['user_id']);
					
				//更新数据到pay_table
				$row_id= db('pay_table')->where('user_id='.$val['user_id'].' and year='.C_YEAR.' and month='.C_MONTH)->value('id');
				$val['id']=$row_id;
				db('pay_table')->update($val);
				}
					
	
				//第四阶段
				//薪资表统一计算
				cal_pay_tb();
				*/
	
			if($user_id==0){
				//判断sw_sys_event中是否已经有本月薪资写入记录,如果有,则LOG标识
				$is_have_event=db('sys_event')->where(' year='.C_YEAR.' and month='.C_MONTH.' and site_id='.$site_id.' and type_flag=8')->find();
				if(!$is_have_event){
					//写入sw_sys_event表,确定上月考勤数据已经生成
					$temp_arr=array();
					$temp_arr['title']=C_YEAR.'-'.C_MONTH.' 薪资数据生成';
					$temp_arr['site_id']=$site_id;
					$temp_arr['year']=C_YEAR;
					$temp_arr['month']=C_MONTH;
					$temp_arr['type_flag']=8;
					$temp_arr['log']=get_date_time().' 由'.get_user_nickname().' 生成'.get_cache_data('site_info', $site_id,'site').'薪资数据!';
					$temp_arr['last_user_id']=get_user_id();
					$temp_arr['create_time']=get_date_time();
					$temp_arr['update_time']=get_date_time();
	
					db('sys_event')->insert($temp_arr);
				}else{
					//更新sw_sys_event表,确定上月考勤数据已经生成
					$temp_arr=array();
					$temp_arr['id']=$is_have_event['id'];
					$temp_arr['log']=get_date_time().' 由'.get_user_nickname().' 重新生成计算'.get_cache_data('site_info', $site_id,'site').'薪资数据!<br>'.$is_have_event['log'];;
					$temp_arr['last_user_id']=get_user_id();
					$temp_arr['update_time']=get_date_time();
	
					db('sys_event')->update($temp_arr);
				}
			}
	
		}
	
	}
	
	//考勤数据更新计算
	function update_hr_cal_to_pay($cal_flag="one",$user_id=0,$site_id=""){
		$year_month=strtotime(get_last_month());
		$year=date('Y',$year_month);
		$month=date('m',$year_month);
		
		if($cal_flag=='one'){
			$where_str=" and u.id=".$user_id." and hrc.year=".$year.' and hrc.month='.$month;
		}else{
			$where_str=" and u.site_id=".$site_id." and u.id=hrc.user_id and hrc.year=".$year.' and hrc.month='.$month;
		}
		
		$sql="
					select
						u.id as user_id,".C_YEAR." as year,".C_MONTH." as month,
					    /*补休结算*/
					    0 as pay_13,
					    /*旷职*/
					    ifnull(hrc.abs_hour,0) as pay_21,
					    /*病假*/
					    ifnull(hrc.sick_leave,0) as pay_24,
					    /*事假*/
					    ifnull(hrc.casual_leave,0) as pay_25,
					    /*病假(时)*/
					    ifnull(hrc.sick_leave,0) as pay_29,
					    /*事假(时)*/
					    ifnull(hrc.casual_leave,0) as pay_30,
					    /*旷职(时)*/
					    ifnull(hrc.abs_hour,0) as pay_31,
					    /*补休(时)*/
					    ifnull(hrc.local_num,0) as pay_32,
					    /*国定假日因公出勤工作*/
					    0 as pay_35,
					    /*工作日加班(时)*/
					    0 as pay_39,
					    /*周休加班(时)*/
					    0 as pay_40,
					    /*年休结算(时)*/
					    0 as pay_41,
					    /*补休结算(时)*/
					    0 as pay_42,
					    /*考勤备注*/
					    ifnull(hrc.remark,0) as pay_43,
					    /*早餐(次)*/
					    ifnull(hrc.bf_num,0) as pay_52,
					    /*早餐费用*/
					    (ifnull(hrc.bf_num,0) *".config('bf_price').") as pay_46,
					    /*午餐(次)*/
					    ifnull(hrc.lunch_num,0) as pay_53,
					    /*午餐*/
					    (ifnull(hrc.lunch_num,0) *".config('lunch_price').") as pay_47,
					    /*晚餐*/
					    0 as pay_48,
					    /*本月出勤*/
					    0 as pay_49
					from
						sw_sys_user u,sw_hr_cal hrc
					where
						u.pay_status=1 and status=1 and user_status=1 ".$where_str;
		$hr_cal_arr=db()->query($sql);
		
		foreach ($hr_cal_arr as $k=>$v){
			$pay_table_id=db('pay_table')->where('user_id='.$v['user_id'].' and year='.C_YEAR.' and month='.C_MONTH)->value('id');
			//旷职
			if(abs($v['pay_31'])>0){
				$v['pay_21']=cal_casual_abs_leave($v['user_id'], $v['pay_31']);
			}
			
			//事假计算
			if(abs($v['pay_30'])>0){				
				$v['pay_25']=cal_casual_abs_leave($v['user_id'], $v['pay_30']);
			}
			
			//病假计算
			if(abs($v['pay_29'])>0){
				$v['pay_24']=cal_sick_leave($v['user_id'], $v['pay_29']);
			}
			
			$v['id']=$pay_table_id;
			
			db('pay_table')->update($v);
		}
		
	}
	
	//本月工资表自动计算,传入ID则只计算某行,不传ID则计算当前月份的所有行
	//只对本月进行数据操作,只有在生成了数据表格,未结转的情况下进行操作
	function cal_pay_tb($id=0){
		if(!pay_is_finish()){
			if(pay_is_create()){
				
				$site_id=$_SESSION['pay_trim']['site_id'];
				$site_pay_flag=$_SESSION['pay_trim']['site_pay_flag'];
				
				//内地薪资重新计算
				if($site_pay_flag==1){
					$where_str="";
					if($id != 0){
						$where_str= " and id=".$id;
					}
					
					//第三阶段计算,汇总栏计算,年终奖金,年终奖金所得税,扣项小计,应纳税所得额,工作日加班费,周休加班费,国定假日因公出勤(时)
					$sql="
						/*年终奖金,加项小计,所得税,年终奖金所得税,扣项小计,应纳税所得额,工作日加班费,周休加班费,国定假日因公出勤(时)*/
						update
							sw_pay_table
								set
						        /*年终奖金计算*/
						        pay_12=(pay_8+pay_7+pay_11+pay_6),
						        /*所得税*/
						        pay_22=0,
						        /*年终奖金所得税*/
						        pay_23=0,
						        /*工作日加班费*/
						        pay_36=0,
						        /*周休加班费*/
						        pay_37=0,
						        /*国定假日因公出勤(时)*/
						        pay_38=0
						where is_lock=0 and user_id in (select user_id from sw_sys_user where site_id=".$site_id.") and year=".C_YEAR." and month=".C_MONTH.".$where_str;";
							
							db()->execute($sql);
							
							//第四阶段,加项小计
							$sql="
						/*年终奖金,加项小计,所得税,年终奖金所得税,扣项小计,应纳税所得额,工作日加班费,周休加班费,国定假日因公出勤(时)*/
						update
							sw_pay_table
								set
						        /*加项小计*/
						        pay_16=(pay_1 + pay_2 + pay_3 + pay_6 + pay_7 + pay_8 + pay_9 + pay_10 + pay_11 + pay_14 + pay_15 + pay_35),
						        /*扣项小计*/
						        pay_26=(pay_17 + pay_18 + pay_19 + pay_20 + pay_21 + pay_24 + pay_25),
						        /*应纳税所得额*/
						        pay_27=(pay_16 - pay_26) + pay_54
						where is_lock=0 and  user_id in (select user_id from sw_sys_user where site_id=".$site_id.") and year=".C_YEAR." and month=".C_MONTH.".$where_str;
											";
							db()->execute($sql);
							
							//第五 所得税计算
							$pay_arr=db('pay_table')->where( "user_id in (select user_id from sw_sys_user where site_id=".$site_id.") and year=".C_YEAR.' and month='.C_MONTH.$where_str)->select();
							
							foreach ($pay_arr as $key=>$val){
								$is_mainLand=db('sys_user')->where('id='.$val['user_id'])->value('is_mainland');
								db()->execute('update sw_pay_table set pay_22='.cal_tax($val['pay_27'],$is_mainLand).' where id='.$val['id']);
							}
							
							//第六 实发,四舍五入
							$sql="update sw_pay_table set pay_28=round(pay_16 - (ifnull(pay_26,0) + ifnull(pay_22,0) + ifnull(pay_34,0) + ifnull(pay_33,0) + ifnull(pay_46,0) + ifnull(pay_47,0) + ifnull(pay_48,0))) where is_lock=0 and   user_id in (select user_id from sw_sys_user where site_id=".$site_id.") and year=".C_YEAR." and month=".C_MONTH.$where_str;
						db()->execute($sql);
							
							//第七阶段: 实发后扣款
			
							//手动keyin数据栏位,专案奖,绩效奖金,其他,扣还款,备注
					
				}
				
				//香港薪资重新计算
				if($site_pay_flag==2){
					
				}
				
				//台湾薪资重新计算
				if($site_pay_flag==3){
					$where_str="";
					if($id != 0){
						$where_str= " and id=".$id;
					}
						
					//计算 合计(94)   基本工资(83)+伙食津贴(84)+差额津贴(85)+月奖(86) ,
					$sql="
						update
							sw_pay_table_tw
								set
						        /*合计*/
						        pay_94=(pay_83+pay_84+pay_85+pay_86+pay_87+pay_88+pay_89+pay_90+pay_91+pay_92+pay_93)
						where is_lock=0 and user_id in (select user_id from sw_sys_user where site_id=".$site_id.") and year=".C_YEAR." and month=".C_MONTH.".$where_str;";
										
					//计算代扣个人所得税(奖金)
					$pay_arr=db('pay_table_tw')->where( "user_id in (select user_id from sw_sys_user where site_id=".$site_id.") and year=".C_YEAR.' and month='.C_MONTH.$where_str)->select();
						
					foreach ($pay_arr as $key=>$val){
						db()->execute('update sw_pay_table_tw set pay_96='.cal_tw_bonus(($val['pay_87']+$val['pay_88']+$val['pay_89']),($val['pay_90']+$val['pay_91'])).' where id='.$val['id']);
					}
					
					//健保费用计算
					
					//劳保费用计算
					
					//健保补充保费
					
					//计算实发工资
					$sql="
						update
							sw_pay_table_tw
								set
						        /*实发工资*/
						        pay_100=(pay_94-pay_95-pay_96-pay_97-pay_98-pay_99)
						where is_lock=0 and user_id in (select user_id from sw_sys_user where site_id=".$site_id.") and year=".C_YEAR." and month=".C_MONTH.".$where_str;";
						
					db()->execute($sql);
				}
				
			}
		}		
	}	

	//某行数据重新计算
	function pay_month_row_cal($id){
		$site_pay_flag=$_SESSION['pay_trim']['site_pay_flag'];
		
		if($site_pay_flag==1){
			//加项小计 = 薪资 + 津贴 + 月奖 + 奖励金 + 专案奖 + 绩效奖金 + 端午奖 + 中秋奖 + 双薪 + 其他 + 差额津贴 + 国定假日因公出勤工作 + 境外收入
			//扣项小计 = 养老金 + 医疗保险 + 失业保险 + 公积金 + 旷职 + 病假 + 事假
			$sql="update sw_pay_table set pay_16=(ifnull(pay_1,0)+ifnull(pay_2,0)+ifnull(pay_3,0)+ifnull(pay_6,0)+ifnull(pay_7,0)+ifnull(pay_8,0)+ifnull(pay_9,0)+ifnull(pay_10,0)+ifnull(pay_11,0)+ifnull(pay_14,0)+ifnull(pay_15,0)+ifnull(pay_35,0)),pay_26=(ifnull(pay_17,0)+ifnull(pay_18,0)+ifnull(pay_19,0)+ifnull(pay_20,0)+ifnull(pay_21,0)+ifnull(pay_24,0)+ifnull(pay_25,0)) where id=".$id;
			db()->execute($sql);
			
			//应纳税所得额=加项小计 - 扣项小计
			$sql="update sw_pay_table set pay_27=(ifnull(pay_16,0)-ifnull(pay_26,0))+ifnull(pay_54,0) where id=".$id;
			db()->execute($sql);
			
			//所得税计算,判断是否大陆籍
			$row_info=db('pay_table')->where('id='.$id)->find();
			
			$is_mainLand=db('sys_user')->where('id='.$row_info['user_id'])->value('is_mainland');
			db()->execute('update sw_pay_table set pay_22='.cal_tax($row_info['pay_27'],$is_mainLand).' where id='.$row_info['id']);
			
			//实发=加项小计 - 扣项小计 - 所得税 - 实发后扣款 - 扣还款- 早餐费用 - 午餐 -晚餐
			$sql="update sw_pay_table set pay_28=round(ifnull(pay_16,0)-(ifnull(pay_26,0)+ifnull(pay_22,0)+ifnull(pay_34,0)+ifnull(pay_33,0)+ifnull(pay_46,0)+ifnull(pay_47,0)+ifnull(pay_48,0))) where id=".$row_info['id'];
			db()->execute($sql);
		}
		
		if($site_pay_flag==2){
			
		}
		
		if($site_pay_flag==3){
			//计算 合计(94)   基本工资(83)+伙食津贴(84)+差额津贴(85)+月奖(86) ,
			$sql="
						update
							sw_pay_table_tw
								set
						        /*合计*/
						        pay_94=(pay_83+pay_84+pay_85+pay_86+pay_87+pay_88+pay_89+pay_90+pay_91+pay_92+pay_93)
						where id=".$id;
			
			db()->execute($sql);
			
			//计算代扣个人所得税(奖金)
			$row_info=db('pay_table_tw')->where('id='.$id)->find();
			db('pay_table_tw')->where('id='.$id)->setField('pay_96',cal_tw_bonus(($row_info['pay_87']+$row_info['pay_88']+$row_info['pay_89']),($row_info['pay_90']+$row_info['pay_91'])));
				
			//健保费用计算
				
			//劳保费用计算
				
			//健保补充保费
				
			//计算实发工资
			$sql="
						update
							sw_pay_table_tw
								set
						        /*实发工资*/
						        pay_100=(pay_94-pay_95-pay_96-pay_97-pay_98-pay_99)
						where id=".$id;
			
			db()->execute($sql);
		}
		
	}

	//台湾代扣个人所得税(奖金)计算公式  ((端午,中秋,年终)+(专案+绩效))*5% <2000不扣
	function cal_tw_bonus($val1=0,$val2=0){
		$return_val=0;
		if((($val1+$val2)*0.05)<2000){
			$return_val=0;
		}else{
			$return_val= ($val1+$val2)*0.05;
		}
		return round($return_val,0);
	}
	
	//个税计算
	function cal_tax($val=0,$is_mainLand=1){
		/*个税计算公式
			1: 获取应纳税所得额pay_27
			2: 应纳税所得额-基准值,判断对应扣税档位 pay_27-config('PAY_BASE_MONEY')
			3: 对应档位扣除税率计算 & 减去扣除数 
		*/
		if($is_mainLand){
			$cal_num=$val-config('PAY_BASE_MONEY_ML');
		}else{
			$cal_num=$val-config('PAY_BASE_MONEY_NML');
		}
		
		
		if($cal_num>0){			
			$num=0;			
			//不超过1500,税率为3%
			if($cal_num<=1500){
				$num= $cal_num*0.03;
			}
			
			//1500~4500 ,税率 10%  -105
			if($cal_num<=4500 && $cal_num>1500){
				$num=($cal_num*0.1-105);
			}
			
			//4500~9000 ,税率 20% -555
			if($cal_num<=9000 && $cal_num>4500){
				$num=($cal_num*0.2-555);
			}
			
			//9000~35000 ,税率 25%  -1005
			if($cal_num<=35000 && $cal_num>9000){
				$num=($cal_num*0.25-1005);
			}
			
			//35000~55000 ,税率 30% -2755
			if($cal_num<=55000 && $cal_num>35000){
				$num=($cal_num*0.3-2755);
			}
			
			//55000~80000 ,税率 35%  -5505
			if($cal_num<=80000 && $cal_num>55000){
				$num=($cal_num*0.35-5505);
			}
			
			//>80000 ,税率 45% -13505
			if($cal_num>80000){
				$num=($cal_num*0.45-13505);
			}
			
			$num=round($num,2);
		}else{
			$num=0;
		}
		
		return $num;
		
	}
	
	//病假扣款计算
	function cal_sick_leave($user_id,$val){
		$user_info=db('sys_user')->where('id='.$user_id)->find();
		$return_num=0;
		$user_seniority=user_seniority($user_info['entry_date'],$user_info['out_seniority']);
		$in_seniority=$user_seniority['total_work_year'];
		
		$year_day=config('PAY_YEAR_DAY');
		//上海病假算法
		if($user_info['site_id']<>2){	
			//获取用户基本薪资(薪资+差额津贴+月奖)
			$sql="select sum(base_val) as num from sw_pay_base_val where user_id=".$user_id.' and field_id in (1,15,3)';
			$num_arr=db()->query($sql);
			$num=$num_arr[0]['num'];
			//echo '用户'.get_user_nickname($user_id).'--工龄段'.$in_seniority.'--计算基础数'.$num.'--病假时数'.$val.'--计算扣款额';
			
			//如果系统内工龄大于或等于0 并且小于2 病假扣款 := (上月薪资*病假时数*(1-0.7*0.6)) / 174
			if($in_seniority>=0 and $in_seniority<2){
				$return_num=round(($num*$val*(1-0.7*0.6))/$year_day,2);
			}
			
			//如果系统内工龄大于或等于2 并且小于4 病假扣款 := (上月薪资*病假时数*(1-0.7*0.7)) / 174
			if($in_seniority>=2 and $in_seniority<4){
				$return_num=round(($num*$val*(1-0.7*0.7))/$year_day,2);
			}
			
			// 如果系统内工龄大于或等于4 并且小于6 病假扣款 := (上月薪资*病假时数*(1-0.7*0.8)) / 174
			if($in_seniority>=4 and $in_seniority<6){
				$return_num=round(($num*$val*(1-0.7*0.8))/$year_day,2);
			}
			
			//如果系统内工龄大于或等于6 并且小于8 病假扣款 := (上月薪资*病假时数*(1-0.7*0.9)) / 174
			if($in_seniority>=6 and $in_seniority<8){
				$return_num=round(($num*$val*(1-0.7*0.9))/$year_day,2);
			}
			
			//如果系统内工龄大于或等于8 病假扣款 := (上月薪资*病假时数*(1-0.7*1)) / 174
			if($in_seniority>=8){
				$return_num=round(($num*$val*(1-0.7*1))/$year_day,2);
			}
		}else{
			//深圳病假计算公式
			//获取用户基本薪资(薪资+差额津贴+月奖)
			$sql="select sum(base_val) as num from sw_pay_base_val where user_id=".$user_id.' and field_id in (1,3,15)';
			$num_arr=db()->query($sql);
			$num=$num_arr[0]['num'];
			//echo '用户'.get_user_nickname($user_id).'--工龄段'.$in_seniority.'--计算基础数'.$num.'--病假时数'.$val.'--计算扣款额';
			
			//如果系统内工龄大于或等于0 并且小于2 病假扣款 := (上月薪资*病假时数*(1-0.7*0.6)) / 174
			if($in_seniority>=0 and $in_seniority<2){
				$return_num=round((($num*(1-0.6))/174)*$val,2);
			}
			
			//如果系统内工龄大于或等于2 并且小于4 病假扣款 := (上月薪资*病假时数*(1-0.7*0.7)) / 174
			if($in_seniority>=2 and $in_seniority<4){
				$return_num=round((($num*(1-0.7))/174)*$val,2);
			}
			
			// 如果系统内工龄大于或等于4 并且小于6 病假扣款 := (上月薪资*病假时数*(1-0.7*0.8)) / 174
			if($in_seniority>=4 and $in_seniority<6){
				$return_num=round((($num*(1-0.8))/174)*$val,2);
			}
			
			//如果系统内工龄大于或等于6 并且小于8 病假扣款 := (上月薪资*病假时数*(1-0.7*0.9)) / 174
			if($in_seniority>=6 and $in_seniority<8){
				$return_num=round((($num*(1-0.9))/174)*$val,2);
			}
			
			//如果系统内工龄大于或等于8 病假扣款 := (上月薪资*病假时数*(1-0.7*1)) / 174
			if($in_seniority>=8){
				$return_num=round((($num*(1-1.0))/174)*$val,2);
			}
		}

		
		return abs($return_num);
	}
	
	//事假&旷职扣款
	function cal_casual_abs_leave($user_id,$val){
		
		//事假/曠職扣款 = (上月薪资*时数) / 174
		
		//获取用户基本薪资(薪资+差额津贴+月奖)
		$sql="select sum(base_val) as num from sw_pay_base_val where user_id=".$user_id.' and field_id in (1,15,3)';
		$num_arr=db()->query($sql);
		$num=$num_arr[0]['num'];
		return  abs(round((($num*$val)/config('PAY_YEAR_DAY')),2));		
	}
	

	//判断本月薪资是否已经结转
	function pay_is_finish($site_id=0,$year='',$month=''){
		if($site_id==0){
			$site_id=$_SESSION['pay_trim']['site_id'];
		}
		if(strlen($year)==0){
			$year=C_YEAR;
		}
		if(strlen($month)==0){
			$year=C_MONTH;
		}
		
		$is_finish=db('sys_event')->where(' year='.C_YEAR.' and month='.C_MONTH.' and site_id='.$site_id.' and type_flag=9')->find();
		if($is_finish){
			return true;
		}else{
			return false;
		}
	}
	
	//判断本月薪资是否已经创建
	function pay_is_create(){
		$is_finish=db('sys_event')->where(' year='.C_YEAR.' and month='.C_MONTH.' and site_id='.$_SESSION['pay_trim']['site_id'].' and type_flag=8')->find();
		if($is_finish){
			return true;
		}else{
			return false;
		}
	}

	//获取字段列表数组
	function get_pay_field(){
		$field_arr=db('pay_field')->select();
		$return_arr=array();
		foreach ($field_arr as $key=>$val){
			$return_arr[$key+1]=$val;
		}
		return $return_arr;
	}
	
	//缓存薪资字段
	function cache_pay_field(){
		//缓存薪资字段
		$field_arr=db('pay_field')->field('id,field_name,zh_name')->select();
		foreach ($field_arr as $k=>$v){
			$_SESSION['pay_field_arr'][$v['field_name']]=$v['zh_name'];
		}		
	}
	
	//根据所传字段返回对应表名称
	function get_site_pay_tb($site_pay_flag){
		$tb_name=array();
		switch ($site_pay_flag){
			case 1:
				$tb_name['s']='pay_table';
				$tb_name['f']='sw_pay_table';
				break;
			case 2:
				$tb_name['s']='pay_table_hk';
				$tb_name['f']='sw_pay_table_hk';
				break;
			case 3:
				$tb_name['s']='pay_table_tw';
				$tb_name['f']='sw_pay_table_tw';
				break;
		}
		return $tb_name;
	}
	
	//校验用户实发薪资是否小于0
	function check_pay_28($year,$month,$site_id){
		//检查实发是否有小于0的情况,避免数据出错
		$check_pay_28=db('pay_table')->where(" year=".$year." and month=".$month." and user_id in (select id from sw_sys_user where site_id=".$site_id." and pay_status=1) and pay_28<0")->select();
		if(count($check_pay_28)>0){
			$user_str="";
			foreach ($check_pay_28 as $k=>$v){
				$user_str .= get_cache_data('user_info', $v['user_id'],'nickname').',';
			}
			$user_str=check_douhao($user_str, 0);
			echo show_msg('用户 ['.$user_str.'] 实发数小于0,请校验数据');
			exit;
		}
		
	}
	
	//校验未设置财务部门人员
	function check_pay_dep($year,$month,$site_id){
		//检查未设置财务部门人员,避免数据出错
		$check_pay_dep=db('pay_table')->where(" year=".$year." and month=".$month." and user_id in (select id from sw_sys_user where site_id=".$site_id." and pay_status=1 and (pay_dep_id=1 or length(pay_dep_id)=0)  and user_status=1 and cal_hr_user=1)")->select();
		if(count($check_pay_dep)>0){
			$user_str="";
			foreach ($check_pay_dep as $k=>$v){
				$user_str .= get_cache_data('user_info', $v['user_id'],'nickname').',';
			}
			$user_str=check_douhao($user_str, 0);
			echo show_msg('用户 ['.$user_str.'] 未设置财务部门,请校验数据');
			exit;
		}
	
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
