<?php
/**
 * 返回问题类型
 */
	function get_q_a_type($type_id){
		$type_arr=array(
				1=>'基础资料修改',
				2=>'考勤问题反馈'
		);
		return $type_arr[$type_id];
	}


	/**
	 * 返回假单类型
	 */
	function get_note_type_name($note_type){
		$note_type_arr=array(
				1=>'请假单',
				2=>'晚餐预订',
				3=>'晚到'
		);
		return $note_type_arr[$note_type];
	}
	
	/**
	 * 返回假单流程说明
	 */
	function get_note_step($note_step){
		$note_step_arr=array(
				0=>'无需审核',
				1=>'代理人审核',
				2=>'考勤主管审核',
				3=>'转上级考勤主管',
				4=>'流程结束'
		);
		return $note_step_arr[$note_step];
	}
	
	/**
	 * 返回用户查看申请单内容
	 */
	function get_note_info($note_info){
		$return_str="";
		$user_id=get_user_id();
	
		switch ($note_info['note_step']){
			case 1:
				$return_str="代理人[".get_user_nickname($note_info['age_user_id']).']审核中!<br />';
				break;
			case 2:
				if($note_info['age_user_id']>0){
					$return_str = "代理人[".get_user_nickname($note_info['age_user_id']).']审核通过,给定意见:'.$note_info['age_check_remark'].'<br />';
				}
				$return_str .= "考勤主管[".get_user_nickname($note_info['hr_user_id']).']审核中!<br />';
				break;
			case 3:
				if($note_info['age_user_id']>0){
					$return_str = "代理人[".get_user_nickname($note_info['age_user_id']).']审核通过,给定意见:'.$note_info['age_check_remark'].'<br />';
				}
				$return_str .= "考勤主管[".get_user_nickname($note_info['hr_user_id']).']审核通过,给定意见:'.$note_info['hr_check_remark'].'<br />';
				$return_str .= "考勤上级主管[".get_user_nickname($note_info['hr_adv_user_id']).']审核中!<br />';
				break;
			case 4:
				if($note_info['age_user_id']>0){
					if($note_info['age_check_status']==0){
						$return_str = "代理人[".get_user_nickname($note_info['age_user_id']).']审核不通过,给定意见:'.$note_info['age_check_remark'].'<br />申请单流程完成!';
						break;
					}else{
						$return_str = "代理人[".get_user_nickname($note_info['age_user_id']).']审核通过,给定意见:'.$note_info['age_check_remark'].'<br />';
						//如果当前用户为代理审核人,则后续审核记录不显示
						if($user_id==$note_info['age_user_id']){
							$return_str .= "申请单流程已结束!";
							break;
						}
					}
				}
	
				if($note_info['hr_check_status']==1){
					$return_str .= "考勤主管[".get_user_nickname($note_info['hr_user_id']).']审核通过,给定意见:'.$note_info['hr_check_remark'].'<br />';
					if($note_info['note_hour']<=get_adv_manage_h()){
						$return_str .= "申请单流程完成!申请单已经生效!";
						break;
					}
				}else{
					$return_str .= "考勤主管[".get_user_nickname($note_info['hr_user_id']).']审核不通过,给定意见:'.$note_info['hr_check_remark'].'<br />申请单流程完成!';
					break;
				}
	
				if($note_info['note_hour']>get_adv_manage_h()){
					if($note_info['hr_adv_check_status']==1){
						$return_str .= "考勤上级主管[".get_user_nickname($note_info['hr_adv_user_id']).']审核通过,给定意见:'.$note_info['hr_adv_check_remark'].'<br />';
						$return_str .= "申请单流程完成!申请单已经生效!";
						break;
					}else{
						$return_str .= "考勤上级主管[".get_user_nickname($note_info['hr_adv_user_id']).']审核不通过,给定意见:'.$note_info['hr_adv_check_remark'].'<br />申请单流程完成!';
						break;
					}
				}
				break;
		}
	
		return $return_str;
	}
	
//返回考勤计算中文字段名
	function get_hr_field_zh($field_name){
		$return_str="";
		switch ($field_name){
			case 'last_annual_num':
				$return_str="上月结算年休";
				break;
			case 'last_repair_num':
				$return_str="上月结算补休";
				break;
			case 'holiday_hour':
				$return_str="节假日结加班费时数";
				break;
			case 'local_note_hour':
				$return_str="本月申请休假";
				break;
			case 'local_annual_num':
				$return_str="本次结算年休时数";
				break;
			case 'local_repair_num':
				$return_str="本次结算补休时数";
				break;
			case 'local_num':
				$return_str="本次年休+补休";
				break;
			case 'casual_leave':
				$return_str="事假时数";
				break;
			case 'sick_leave':
				$return_str="病假时数";
				break;
			case 'abs_hour':
				$return_str="旷职时数";
				break;
			case 'bf_num':
				$return_str="早餐";
				break;
			case 'lunch_num':
				$return_str="午餐";
				break;
			case 'remark':
				$return_str="备注";
				break;
		}
		return $return_str;
	}


























































































