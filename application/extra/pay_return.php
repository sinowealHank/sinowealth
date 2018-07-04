<?php
/**
 * 返回薪资字段数据来源类型
 */
	function get_data_from($id){
		$from_arr=array(
				1=>'系统获取',
				2=>'手动输入',
				3=>'栏位计算'
		);
		return $from_arr[$id];
	}

/**
 * 返回薪资加减项
 */
	function get_field_add_sub_type($type_id){
		$type_arr=array(
				0=>'无',
				1=>'加项',
				2=>'减项'
		);
		return $type_arr[$type_id];
	}

/**
 * 返回薪资字段类型
 */
function get_field_type($type_id){
	$type_arr=array(
			1=>'数字(小数点4位)',
			2=>'短字符',
			3=>'备注'
	);
	return $type_arr[$type_id];
}	

/**
 * 返回站点薪资字段
 */
function get_site_pay_flag_name($id){
	$type_arr=array(
			0=>'内地',
			1=>'香港',
			2=>'台湾'			
	);
	return $type_arr[$id];
}


























































































