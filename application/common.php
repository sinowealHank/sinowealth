<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

// 分析枚举类型配置值 格式 a:名称1,b:名称2
function parse_config_attr($string) {
	$array = preg_split('/[,;\r\n]+/', trim($string, ",;\r\n"));
	if (strpos($string, ':')) {
		$value = array();
		foreach ($array as $val) {
			list($k, $v) = explode(':', $val);
			$value[$k]   = $v;
		}
	} else {
		$value = $array;
	}
	return $value;
}

/**
 * 设置DWZ操作返回json数据
 * @param $statusCode '1':操作成功  '0':操作失败  '301':会话超时
 * @param $callbackType:closeDialog
 */
function setServerBackJson($statusCode,$message,$forwardUrl='',$callbackType='',$time='3000'){
	$temp_arr=array("statusCode"=>$statusCode,"message"=>$message,"forwardUrl"=>$forwardUrl,"callbackType"=>$callbackType,"closeTime"=>$time);
	return json_encode($temp_arr);
}


/**
 * 记录和统计时间（微秒）和内存使用情况
 * 使用方法:
 * <code>
 * G('begin'); // 记录开始标记位
 * // ... 区间运行代码
 * G('end'); // 记录结束标签位
 * echo G('begin','end',6); // 统计区间运行时间 精确到小数后6位
 * echo G('begin','end','m'); // 统计区间内存使用情况
 * 如果end标记位没有定义，则会自动以当前作为标记位
 * 其中统计内存使用需要 MEMORY_LIMIT_ON 常量为true才有效
 * </code>
 * @param string $start 开始标签
 * @param string $end 结束标签
 * @param integer|string $dec 小数位或者m
 * @return mixed
 */
function G($start,$end='',$dec=4) {
	static $_info       =   array();
	static $_mem        =   array();
	if(is_float($end)) { // 记录时间
		$_info[$start]  =   $end;
	}elseif(!empty($end)){ // 统计时间和内存使用
		if(!isset($_info[$end])) $_info[$end]       =  microtime(TRUE);
		if(MEMORY_LIMIT_ON && $dec=='m'){
			if(!isset($_mem[$end])) $_mem[$end]     =  memory_get_usage();
			return number_format(($_mem[$end]-$_mem[$start])/1024);
		}else{
			return number_format(($_info[$end]-$_info[$start]),$dec);
		}

	}else{ // 记录时间和内存使用
		$_info[$start]  =  microtime(TRUE);
		if(MEMORY_LIMIT_ON) $_mem[$start]           =  memory_get_usage();
	}
}




// 应用公共文件
//对象转为为数组
function object_array($array) {
	if(is_object($array)) {
		$array = (array)$array;
	} if(is_array($array)) {
		foreach($array as $key=>$value) {
			$array[$key] = object_array($value);
		}
	}
	return $array;
}

/**
 * 把返回的数据集转换成Tree
 * @param array $list 要转换的数据集
 * @param string $pk 自增字段（栏目id）
 * @param string $pid parent标记字段
 * @return array
 * @author dqs <1696232133@qq.com>
 */
function  make_tree($list,$pk='id',$pid='parent_id',$child='children',$root=0){
	$tree=array();
	$packData=array();
	foreach ($list as $data){
		$packData[$data[$pk]] = $data;
	}
	foreach ($packData as $key =>$val){
		if($val[$pid] == $root){//代表跟节点
			$tree[]= & $packData[$key];
		}else{
			//找到其父类
			$packData[$val[$pid]][$child][]=& $packData[$key];
		}
	}
	return $tree;
}



/*
 * 从model返回的数据，转化为数组
 */
function object_change_array($data){
	$data = json_decode(json_encode($data));
	$data = object_array($data);
	return $data;
}

/*
 * 递归函数的调用*/
function _reSort($data,$parent_id=0, $level=0, $isClear=TRUE){
	static $ret = array();
	if($isClear){
		$ret = array();
	}
	foreach ($data as $k => $v)
	{
		if($v['parent_id'] == $parent_id)
		{
			$v['level'] = $level;
			$ret[] = $v;
			_reSort($data, $v['id'], $level+1, FALSE);
		}
	}
	return $ret;
}

/*
 * 树形结构的打印*/
function tree($pri){
	foreach ($pri as $k => $v){
		// 找顶级权限
		if($v['parent_id'] == 0){
			// 再循环把这个顶级权限的子权限
			foreach ($pri as $k1 => $v1) {
				if($v1['parent_id'] == $v['id']) {
					$v['children'][] = $v1;
				}
			}
			$btn[] = $v;

		}

	}
	return $btn;
}



/**
 * 把返回的数据返回成一个复选框树形结构
 * @param array $data 树形结构的数组 ，其中有name、id 、children字段，
 * @param array $id_Group 数组是你选择哪些框id的集合，为二维数组,默认为空数组
 * @param string $parameter 为$id_group的字段，默认为空，列如可以为pri_id，可以作为权限设定的依据
 * @param int $is_float为0是分开的，如果为1是不开的
 * @return string $tree
 * @author zhourenjie
 */

function set_MakeTree($data,$id_Group = array(),$parameter = '',$is_float = '0'){
	$tree = '<ul style="cursor:default;">';
	foreach($data as $k => $v){
		if($is_float == '0'){
			$tree .= '<li style="float: left;width: 300px;margin-right: 20px;height: 400px;overflow: auto;border: 1px solid #CCC;background:#FFF">';
		}else{
			$tree .= '<li style="width: 300px;margin-right: 20px;overflow: auto;background:#FFF">';
		}
		$tree .= '<span style="margin-left: 10px">';
		if(isset($v['children'])){
			$tree .= '<i class="icon-folder-open"></i>';
		}else{
			$tree .= '<i class="icon-folder-close"></i>';
		}
		$tree .= '</span>';
		$check = '';
		foreach($id_Group as $key => $val){
			if($val[$parameter] == $v['id']){
				$check .= 'checked="checked"';
				break;
			}
		}
		$tree .= '<input '.$check.'style="margin-left:3px;" type="checkbox" name="pri_id" value="'.$v['id'].'"><a nodeid="'.$v['id'].'" class="node" style="font-size: 14px;color: black;text-decoration: none;padding-left: 4px">';
		$tree .= ''.$v['name'].'</a>';
		if(isset($v['children'])){
			$tree .= '<ul>';
			foreach($v['children'] as $k1 => $v1 ){
				$tree .= '<li>';
				$tree .= '<span>';
				if(isset($v1['children'])){
					$tree .= '<i class="icon-folder-open"></i>';
				}else{
					$tree .= '<i class="icon-folder-close"></i>';
				}
				$tree .= '</span>';
				$check1 = '';
				foreach($id_Group as $key1 => $val1){
					if($val1[$parameter] == $v1['id']){
						$check1 .= 'checked="checked"';
						break;
					}
				}
				$tree .= ' <input '.$check1.'style="margin-left:3px;" type="checkbox" name="pri_id" value="'.$v1['id'].'"><a nodeid="'.$v1['id'].'" class="node" style="font-size: 14px;color: black;text-decoration: none;padding-left: 4px;">';
				$tree .= '<a>'.$v1['name'].'</a>';
				if(isset($v1['children'])){
					$tree .= '<ul>';
					foreach($v1['children'] as $k2 =>$v2){
						$tree .= '<li>';
						$tree .= '<span><i class="icon-folder-close"></i></span>';
						$check2 = '';
						foreach($id_Group as $key2 => $val2){
							if($val2[$parameter] == $v2['id']){
								$check2 .= 'checked="checked"';
								break;
							}
						}
						$tree .= '<input '.$check2.'style="margin-left:3px;" type="checkbox" name="pri_id" value="'.$v2['id'].'"><a nodeid="'.$v2['id'].'" class="node" style="font-size: 14px;color: black;text-decoration: none;padding-left: 4px;">';
						$tree .= '<a style="font-size: 14px;color: black;text-decoration: none;padding-left: 4px;">'.$v2['name'].'</a>';
						$tree .= '</li>';
					}
					$tree .= '</ul>';
				}
				$tree .= '</li>';
			}
			$tree .= '</ul>';
		}
		$tree .= '</li>';
	}
	$tree .= '</ul>';
	return $tree;
}


/*无限极复选框树形菜单*/
function setMakeTree($data,$name = '',$id_Group = array(),$parameter = '',$isClear=TRUE,$is_float = '0'){
	static $tree = '';
	if($isClear){
		$tree = '';
	}
	$tree .= '<ul style="cursor:default;">';
	foreach($data as $k => $v){
		if($is_float == '0'){
			$tree .= '<li style="float: left;width: 300px;margin-right: 30px;height: 400px;overflow: auto;border: 1px solid #CCC; background:#FFF">';
		}else{
			$tree .= '<li style="width: 300px;background:#FFF;">';
		}
		$tree .= '<span style="margin-left: 10px">';
		if(isset($v['children'])){
			$tree .= '<i class="icon-folder-open"></i>';
		}else{
			$tree .= '<i class="icon-folder-close" title="下面没有子类"></i>';
		}
		$tree .= '</span>';
		$check = '';
		foreach($id_Group as $key => $val){
			if($val[$parameter] == $v['id']){
				$check .= 'checked="checked"';
				break;
			}
		}
		$tree .= '<input level = "'.$v['level'].'"'.$check.'style="margin-left:3px;" type="checkbox" name="'.$name.'" value="'.$v['id'].'"><a nodeid="'.$v['id'].'" class="node" style="font-size: 14px;color: black;text-decoration: none;padding-left: 4px">';
		$tree .= ''.$v['name'].'</a>';
		if(isset($v['children'])){
			setMakeTree($v['children'],$name,$id_Group,$parameter,false,1);
		}
		$tree .= '</li>';
	}
	$tree .= '</ul>';
	return $tree;
}


function make_Nav($data,$id_Group = array(),$parameter = '',$is_float = '0'){
	$tree = '<ul style="cursor:default;">';
	foreach($data as $k => $v){
		if($is_float == '0'){
			$tree .= '<li style="float: left;width: 300px;margin-right: 20px;height: 400px;overflow: auto;border: 1px solid #CCC;background:#FFF">';
		}else{
			$tree .= '<li style="width: 300px;margin-right: 20px;overflow: auto;background:#FFF">';
		}
		$tree .= '<span style="margin-left: 10px">';
		if(isset($v['children'])){
			$tree .= '<i class="icon-folder-open"></i>';
		}else{
			$tree .= '<i class="icon-folder-close"></i>';
		}
		$tree .= '</span>';
		$check = '';
		foreach($id_Group as $key => $val){
			if($val[$parameter] == $v['id']){
				$check .= 'checked="checked"';
				break;
			}
		}
		$tree .= '<input '.$check.'style="margin-left:3px;" parent_id="'.$v['parent_id'].'" type="checkbox" name="pri_id" value="'.$v['id'].'"><a nodeid="'.$v['id'].'" class="node" style="font-size: 14px;color: black;text-decoration: none;padding-left: 4px;color:black;">';
		$tree .= ''.$v['name'].'</a>';
		if(isset($v['children'])){
			$tree .= '<ul>';
			foreach($v['children'] as $k1 => $v1 ){
				$tree .= '<li>';
				$tree .= '<span>';
				$tree .= '<i class="icon-folder-close"></i>';
				$tree .= '</span>';
				$check1 = '';
				foreach($id_Group as $key1 => $val1){
					if($val1[$parameter] == $v1['id']){
						$check1 .= 'checked="checked"';
						break;
					}
				}
				$tree .= ' <input '.$check1.'style="margin-left:3px;" parent_id="'.$v1['parent_id'].'"  type="checkbox" name="pri_id" value="'.$v1['id'].'"><a nodeid="'.$v1['id'].'" class="node" style="font-size: 14px;color: black;text-decoration: none;padding-left: 4px;">';
				$tree .= '<a style="color:#428bca;">'.$v1['name'].'</a>';

				$tree .= '</li>';
			}
			$tree .= '</ul>';
		}
		$tree .= '</li>';
	}
	$tree .= '</ul>';
	return $tree;
}

/*获得当前访问的url*/
function get_now_url(){
	$request = \think\Request::instance();
	$module = $request->module();
	$controller = $request->controller();
	$action = $request->action();
	/*取出所有的权限*/
	$requestUrl['module_name'] = strtolower($module);
	$requestUrl['controller_name'] = strtolower($controller);
	$requestUrl['action_name'] = strtolower($action);
	return $requestUrl;
}

/*获得当前用户存储的权限数据*/
function get_session_privilege(){
	$data = session('RBAC');
	$arr = array();
	foreach($data as $key => $val){
		$temp['module_name'] = strtolower(preg_replace('/_/','',$val['module_name']));
		$temp['controller_name'] = strtolower(preg_replace('/_/','',$val['controller_name']));
		$temp['action_name'] = $val['action_name'];
		$url =   stripslashes($temp['module_name']."/".$temp['controller_name']."/".$temp['action_name']);
		$arr[] = $url;
	}
	//添加个人模块权限验证
	$person_auth = session('person_auth');
	$check_arr = [];
	foreach($person_auth as $key1 => $val1)
	{
		$temp['module_name'] = strtolower(preg_replace('/_/','',$val1['module_name']));
		$temp['controller_name'] = strtolower(preg_replace('/_/','',$val1['controller_name']));
		$temp['action_name'] = strtolower(preg_replace('/_/','',$val1['action_name']));
		$url =   stripslashes($temp['module_name']."/".$temp['controller_name']."/".$temp['action_name']);
		$check_arr[] = $url;

	}
	$array = array_merge($arr,$check_arr);
	return json_encode($array);
//	return json_encode($arr);
}

/*获得当前用户模块名、控制器名*/
function get_current_url(){
	$request = \think\Request::instance();
	$module = strtolower($request->module());
	$controller =  strtolower($request->controller());
	$module_controller = $module."/".$controller;
	return $module_controller;
}

function change_filed($data,$change_field,$filed,$filed1 = ''){
	if($filed1 == ''){
		foreach($data as $k => &$v){
			$v[$change_field] = $v[$filed];
			unset($v[$filed]);
		}
		return $data;
	}else{
		foreach($data as $k => &$v){
			$v[$change_field] = $v[$filed].' ('.$v[$filed1].')';
			unset($v[$filed]);
		}
		return $data;
	}

}
















