<?php
/**
 * 以逗号隔开字符串变成数组
 */
function cost_string_to_array($string){
	$array = explode(',',$string);
	$end = trim(end($array));
	if(!$end){
        array_pop($array);
	}

	return $array;
}
/**
 * 以逗号隔开数组变成字符串
 */
function cost_array_to_string($array){
	$string = implode(',',$array);
	return $string;
}
/**
 * 根据权限获取可见field(常用信息排除)
 */
function cost_get_field(){
	$where = 'field not in('.cost_config(3).')';
	
	$power = cost_get_authorily();
	if($power == 1 || $power == 2 || $power == 9){
		$page_info['field'] = db('cost_field')->where($where)->where('section','<>','4')->order('order','asc')->select();
	}else{
		$page_info['field'] = db('cost_field')->where($where)->where('section','<>','4')->where('section','<>','0')->order('order','asc')->select();
	}
	$page_info['authorily'] = $power;
	return $page_info;
}
/**
 * 获取用户权限
 */
function cost_get_authorily(){
	$user = session('cur_user_info');
	$id = $user['id'];
	$res = db('cost_user_type')->where('user_id',$id)->column('power');
	if($res){
		return $res['0'];
	}else{
		return 9;
	}	
}
/**
 * 信息配置
 * @param 1.计算得到数据的字段 2.数据内容较长的字段 3.常规字段 4.需要下拉框字段 5.修改之后需要重新走流程字段 
 */
function cost_config($category){
	//计算得到数据的字段
	$count_field = 'CP_Die,Ym,F_T,by45,U_Cost_US';
	//数据内容较长的字段
	$long_val_field = 'prdno,fab,by59,by61,by25';
	//常规字段
	$general = "'id','line','prdno','type'";
	//需要下拉框字段
	$select = "line,by58,fab_name_r,fab,by6,um,CP_factory,CP_factory_r,Tester,by59,by591,by16,by16release,Assy_ab,Assy_ab_r,wire_stock,PIN,F_T_Out,F_T_r,F_T_Tester";
	//修改之后需要重新走流程字段
	$reFlow = "line,prdno,type";
	//模糊查询字段
	$keySearch = "type,prdno,fab,CP_factory,Assy_ab,PIN,F_T_Out,tester,F_T_Tester";
	//返回结果
	if($category == 1){return $count_field;}
	if($category == 2){return $long_val_field;}
	if($category == 3){return $general;}
	if($category == 4){return $select;}
	if($category == 5){return $reFlow;}
	if($category == 6){return $keySearch;}
}

/**
 * 获取用户部门权限信息
 * @param 权限值
 * @param 1 返回部门 2 返回权限
 */
function power_show($power,$type){
	if($power == 1 || $power == 2){
		$branch = 'PP';
	}
	if($power == 3 || $power == 4){
		$branch = 'PE';
	}
	if($power == 5 || $power == 6){
		$branch = 'TE';
	}
	if($power == 7 || $power == 8){
		$branch = 'QA';
	}
	if($power == 9){
		$branch = 'MT';
	}
	if($power == 11){
		$branch = '部经理';
	}
	if($power%2 == 0){
		$auth = '编辑';
	}else{
		$auth = '查看';
	}
	if($type==1){
		return $branch;
	}
	if($type==2){
		return $auth;
	}
}

function lds_dump($data){
	echo '<pre>';
	var_dump($data);
	echo '</pre>';
	exit;
}
/**
 * 获取下拉框字段内容
 *  @param 字段
 */
function cost_get_select_content($field){
	$res = db('cost_config')->where('name',$field)->column('content');

	$res = cost_string_to_array($res['0']);
	return $res;
}
/**
 * 发送邮件
 *  @param 部门大写
 */
function cost_sent_email($dpmt){
	$content = db('cost_config')->where('name',$dpmt)->column('content');
	return $content['0'];
}
/**
 * 操作记入log
 *  @param 操作记录id
 *  @param log内容
 *  @param 0：与价格有关 1：与价格无关
 */
function cost_set_log($data_id,$log,$type='0'){
	$data = array();
	$data['data_id'] = $data_id;
	$data['user_name'] = get_user_nickname();
	$data['user_id'] = get_user_id();
	$data['log'] = $log;
	$data['type'] = $type;
	$data['time'] = date('Y-m-d H:i:s',time());
	$content = db('cost_log')->insert($data);
}
/**
 * 操作批量
 *  @param 批量修改记录
 *  @param log内容
 *  @param 0：与价格有关 1：与价格无关
 */
function cost_batch_log($data_ids,$log,$type='0'){
	$data = array();
	$data['data_ids'] = $data_ids;
	$data['user_name'] = get_user_nickname();
	$data['log'] = $log;
	$data['type'] = $type;
	$data['time'] = date('Y-m-d H:i:s',time());
	$content = db('cost_batch_log')->insert($data);
}
/**
 * 根据用户返回流程走向
 * 
 */
function cost_get_flow(){
	$res = cost_get_authorily();
	$branch = power_show($res,1);
	if($branch == 'PE'){
		$flow = 1;
	}
	if($branch == 'TE'){
		$flow = 2;
	}
	if($branch == 'QA'){
		$flow = 3;
	}
	if($branch == 'PP'){
		$flow = 4;
	}
	return $flow;
}
/**
 * 根据字段名返回显示名
 *
 */
function fieldGetShow($field){
	$show =db('cost_field')->where('field',$field)->column('field_show');
	$show = $show['0'];
	return $show;
}
/**
 * 修改时更新sqlserver数据库
 * @param id mysql中数据id
 */
function cost_edit_sqlSever($id){
	$newData = db('cost_cost')->where('id',$id)->find();//获取修改之前数据
	if($newData['flow'] == '4'){
		$o_id = $newData['o_id'];
		$newData['type'] = $newData['type_p'];
		unset($newData['id']);
		unset($newData['o_id']);
		unset($newData['show_type']);
		unset($newData['flow']);
		unset($newData['by16release']);
		unset($newData['type_p']);
		$newData['CP_Yld'] = str_ireplace('%','',$newData['CP_Yld']) ;

		if($newData['fab_name_r'] == '是'){
			$newData['by58'] = $newData['by58'].'(R)';
			
		}
		unset($newData['fab_name_r']);
		if($newData['CP_factory_r'] == '是'){
			$newData['CP_factory'] = $newData['CP_factory'].'(R)';
		
		}
		unset($newData['CP_factory_r']);
		if($newData['Assy_ab_r'] == '是'){
			$newData['Assy_ab'] = $newData['Assy_ab'].'(R)';
				
		}
		unset($newData['Assy_ab_r']);
		if($newData['F_T_r'] == '是'){
			$newData['F_T_Out'] = $newData['F_T_Out'].'(R)';
				
		}
		unset($newData['F_T_r']);
		unset($newData['wire_stock']);
		unset($newData['Drawing']);
		$str = '';
		foreach($newData as $k=>$v){
			$str .= $k."='".$v."',";
		}
		$str = rtrim($str,',');
		
		$sql = 'update dbo.pp_cost set '.$str.' where id ='.$o_id;

		//$sql=auto_charset($sql);
		get_mssql_info('mms',$sql);
	}
}


//插入sql server数据库
function cost_insert_sqlServer($id){
	$newData = db('cost_cost')->where('id',$id)->find();//获取修改之前数据
	if($newData['flow'] == '4'){
		$newData['type'] = $newData['type_p'];
		unset($newData['id']);
		unset($newData['o_id']);
		unset($newData['show_type']);
		unset($newData['flow']);
		unset($newData['by16release']);
		unset($newData['type_p']);
		$newData['CP_Yld'] = str_ireplace('%','',$newData['CP_Yld']);


		if($newData['fab_name_r'] == '是'){
			$newData['by58'] = $newData['by58'].'(R)';

		}
		unset($newData['fab_name_r']);
		if($newData['CP_factory_r'] == '是'){
			$newData['CP_factory'] = $newData['CP_factory'].'(R)';

		}
		unset($newData['CP_factory_r']);
		if($newData['Assy_ab_r'] == '是'){
			$newData['Assy_ab'] = $newData['Assy_ab'].'(R)';

		}
		unset($newData['Assy_ab_r']);
		if($newData['F_T_r'] == '是'){
			$newData['F_T_Out'] = $newData['F_T_Out'].'(R)';

		}
		unset($newData['F_T_r']);
		unset($newData['wire_stock']);
		unset($newData['Drawing']);
		$str1 = '';
		$str2 = '';
		foreach($newData as $k=>$v){
			$str1 .= $k.',';
			$str2 .= "'".$v."',";
		}

		$str1 = '('.rtrim($str1,',').')';
		$str2 = '('.rtrim($str2,',').')';
        $sql = 'insert into dbo.pp_cost'.$str1.'values'.$str2;
		get_mssql_info('mms',$sql);
	}

}



//获取QA部门的id
function get_QA_dep_id(){
	$id = config('QA_DEP_ID');
	return $id;
//	return 15;
}

//获取Pe、Te、Pp提交申请到QA部门审核的人员

function get_distinct_dep_user($id,$dep_name){
	//取出当前QA部门人员
	$user_data = config('user_info');
	$QA_user_Data = array();
	$temp = array();
	foreach($user_data as $k => $v){
		if($v['dep_id'] == get_QA_dep_id()){
			$temp['id'] = $v['id'];
			$temp['nickname'] = $v['nickname'];
			$temp['email'] = $v['email'];
			$QA_user_Data[] = $temp;
		}

	}

	$qa_user_str = '';
	$id_str = db('cost_delele_email_config')->where('id',$id)->value('id_str');
	$id_arr = explode(',',$id_str);
	foreach($QA_user_Data as $k => $v){
		if(in_array($v['id'],$id_arr)){
			$checked = 'checked=checked';
		}else{
			$checked = '';
		}
		$qa_user_str .= '<span style="margin-left:20px;width:70px;display:block; float:left;""><input '.$checked.' style="margin-left:3px;" name="'.$dep_name.'" value="'.$v['id'].'" type="checkbox">
                          <a class="node" style="font-size: 14px;color: black;text-decoration: none;padding-left: 4px">'.$v['nickname'].'</a></span> ';
	}
	return $qa_user_str;
};


//获得修改后的流程
function get_flow($flow){
	if($flow == 0){
		return '<span style="color:red">PE确认数据前</sapn>';
	}else if($flow == 1){
		return '<span style="color:red">PE已经提交数据,请Te确认</span>';
	}else if($flow == 2){
		return '<span style="color:red">TE已经提交数据,请QA确认</span>';
	}else if($flow == 3){
		return '<span style="color:red">QA已经提交数据,请PP确认</span>';
	}else if($flow == 4){
		return '<span style="color:red">PP确认后</span>';
	}


}




























































































