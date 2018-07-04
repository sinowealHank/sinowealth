<?php
/**
 * 系统公共库文件
 * 主要定义系统公共函数库
 */

/**
 * 检测用户是否登录
 * @return integer 0-未登录，大于0-当前登录用户ID
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function is_login(){		
    $user = session('user_auth');
    if (empty($user)) {
        return 0;
    } else {
        return session('user_auth_sign') == data_auth_sign($user) ? $user['id'] : 0;
    }
}

/**
 * 检测当前用户是否为管理员
 * @return boolean true-管理员，false-非管理员
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function is_administrator($uid = null){
    $uid = is_null($uid) ? is_login() : $uid;
    return $uid && (intval($uid) === config('ADMIN_ID'));
}

/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @return mixed
 */
function get_client_ip($type = 0) {
	$type       =  $type ? 1 : 0;
	static $ip  =   NULL;
	if ($ip !== NULL) return $ip[$type];
	if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
		$pos    =   array_search('unknown',$arr);
		if(false !== $pos) unset($arr[$pos]);
		$ip     =   trim($arr[0]);
	}elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
		$ip     =   $_SERVER['HTTP_CLIENT_IP'];
	}elseif (isset($_SERVER['REMOTE_ADDR'])) {
		$ip     =   $_SERVER['REMOTE_ADDR'];
	}
	// IP地址合法验证
	$long = sprintf("%u",ip2long($ip));
	$ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
	return $ip[$type];
}

/**
 * 数据签名认证
 * @param  array  $data 被认证的数据
 * @return string       签名
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function data_auth_sign($data) {
	//数据类型检测
	if(!is_array($data)){
		$data = (array)$data;
	}
	ksort($data); //排序
	$code = http_build_query($data); //url编码并生成query字符串
	$sign = sha1($code); //生成签名
	return $sign;
}

/**
 * 数据缓存
 * @param $tbname string  	需要缓存的数据
 */
function cache_data($tb_name,$clear_flag=''){
	switch ($tb_name){
		case 'config':
			/* 缓存数据库中的配置 */
			$config = cache('db_config_data');
			if($clear_flag=='reset'){
				$config=array();
			}
			
			if (!$config) {
				$config = model('Config')->lists();
				cache('db_config_data', $config);
			}
			config($config);
			break;
		case 'dep':
			/*缓存部门信息*/
			$dep_cache_arr=cache('dep_cache_arr');
			if($clear_flag=='reset'){
				$dep_cache_arr=array();
			}
			if(!$dep_cache_arr){
				$dep_db_arr=db('sys_dep')->where('status=1')->select();
				$dep_cache_arr=array();
				foreach ($dep_db_arr as $key=>$val){
					$dep_cache_arr[$val['id']]=$val;
				}
				cache('dep_cache_arr',$dep_cache_arr);
			}
			config('dep_info',$dep_cache_arr);
			break;
		case 'site':
			/*缓存站点信息*/
			$site_cache_arr=cache('site_cache_arr');
			if($clear_flag=='reset'){
				$site_cache_arr=array();
			}
			if(!$site_cache_arr){
				$site_db_arr=db('sys_site')->where('status=1')->select();
				$site_cache_arr=array();
				foreach ($site_db_arr as $key=>$val){
					$site_cache_arr[$val['id']]=$val;
				}
				cache('site_cache_arr',$site_cache_arr);
			}
			config('site_info',$site_cache_arr);
			break;
		case 'user':
			/*缓存人员基础信息*/
			$user_cache_arr=cache('user_cache_arr');
			if($clear_flag=='reset'){
				$user_cache_arr=array();
			}
			if(!$user_cache_arr){
				$user_db_arr=db('sys_user')->select();
				$user_cache_arr=array();
				foreach ($user_db_arr as $key=>$val){
					$user_cache_arr[$val['id']]=$val;
				}
				cache('user_cache_arr',$user_cache_arr);
			}
			config('user_info',$user_cache_arr);
		break;
		case 'hr_role':
			/*缓存考勤规则信息*/
			$hr_role_cache_arr=cache('hr_role_cache_arr');
			if($clear_flag=='reset'){
				$hr_role_cache_arr=array();
			}
			if(!$hr_role_cache_arr){
				$hr_role_db_arr=db('sys_hr_role')->where('status=1')->select();
				$hr_role_cache_arr=array();
				foreach ($hr_role_db_arr as $key=>$val){
					$hr_role_cache_arr[$val['id']]=$val;
				}
				cache('hr_role_cache_arr',$hr_role_cache_arr);
			}
			config('hr_role_info',$hr_role_cache_arr);
		break;
	}
}

/**
 * 输出缓存数据
 * @param $tbname string  	需要缓存的数据
 */
function get_cache_data($data_type,$key,$field=''){
	if(empty(config($data_type)[$key][$field])){
		if(strlen($field)==0){
			return config($data_type)[$key];
		}else{
			return '-';
		}
		
	}else{
		return config($data_type)[$key][$field];
	}
}

/**
 * 输出精简日期格式
 * @param $date  日期
 */
function s_date($date){
	if(!is_int($date)){
		return date('Y-m-d',strtotime($date));
	}else{
		return date('Y-m-d',$date);
	}	
}

/**
 * 输出短日期格式
 * @param $date  日期
 */
function m_d($date){
	if(!is_int($date)){
		return date('m / d',strtotime($date));
	}else{
		return date('m / d',$date);
	}
}


function check_data($val,$type){
	// $type  时间,日期,邮箱,电话号码,身份证,数字,是否带小数点,
	
}

/**
 * 小数点后0清理
 */
function clear_zero($s){
	$s = trim(strval($s));
	if (preg_match('#^-?\d+?\.0+$#', $s)) {
		return preg_replace('#^(-?\d+?)\.0+$#','$1',$s);
	}
	if (preg_match('#^-?\d+?\.[0-9]+?0+$#', $s)) {
		return preg_replace('#^(-?\d+\.[0-9]+?)0+$#','$1',$s);
	}
	return $s;
}

/**
 * 小数点后0清理
 */
function c_z($s){
	$s = trim(strval($s));
	if (preg_match('#^-?\d+?\.0+$#', $s)) {
		return preg_replace('#^(-?\d+?)\.0+$#','$1',$s);
	}
	if (preg_match('#^-?\d+?\.[0-9]+?0+$#', $s)) {
		return preg_replace('#^(-?\d+\.[0-9]+?)0+$#','$1',$s);
	}
	return $s;
}

/**
 * 输出设定字符字符串
 * @param $str  输入字符串
 * @param $len  设定长度
 */
function s_str($str,$len,$out_flag='...'){
	if(mb_strwidth($str, 'utf8')>$len){
		// 此处设定从0开始截取，取10个追加...，使用utf8编码
		// 注意追加的...也会被计算到长度之内
		$str = mb_strimwidth($str, 0, $len, $out_flag, 'utf8');
	}	
	return $str;
}

/**
 * 判断是否日期格式
 */
function is_date($date){
	$is_date=strtotime($date) ? strtotime($date) : false;
	if($is_date===false){
		return false;
	}else{
		return true;
	}
}

/**
 * 返回指定月份的第一天和最后一天,未指定为当前月份
 * @param date $date 'Y-m-d' 指定日期
 * @return array($firstDay,$lastDay) 数组,第一天和最后一天
 */
function get_begin_last_date($date="",$flag=""){
	if(!$date){
		$date=date('Y-m-d',time());
	}
	switch($flag){
		case 'last_jd':
			$season = ceil((date('n'))/3)-1;//上季度是第几季度
			$firstday=date('Y-m-d', mktime(0, 0, 0,$season*3-3+1,1,date('Y')));
			$lastday=date('Y-m-d', mktime(23,59,59,$season*3,date('t',mktime(0, 0 , 0,$season*3,1,date("Y"))),date('Y')));
			break;
		case 'local_jd':
			$season = ceil((date('n'))/3);
			$firstday=date('Y-m-d H:i:s', mktime(0, 0, 0,$season*3-3+1,1,date('Y')));
			$lastday= date('Y-m-d H:i:s', mktime(23,59,59,$season*3,date('t',mktime(0, 0 , 0,$season*3,1,date("Y"))),date('Y')));
			break;
		default:
			$firstday = date('Y-m-01', strtotime($date));
			$lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));
			break;
	}
	
	return array($firstday,$lastday);
}

/**
 * 返回某年份所有周六,周日数组
 */
function get_year_weekend($year,$flag){
	if($flag==7){
		$first_Sat=date('Y-m-d',strtotime('this Saturday',strtotime($year."-01-01")));
	}else{
		$first_Sat=date('Y-m-d',strtotime('this Sunday',strtotime($year."-01-01")));
	}
	$date_arr=array();
	for($i=0;;$i+=7){
		$current_time=strtotime($i.' days',strtotime($first_Sat));
		if($current_time>strtotime($year.'-12-31')){
			break;
		}
		$date_arr[]=date('Y-m-d',$current_time);
	}
	return $date_arr;
}

/**
 * 返回指定日期的上一个月日期范围
 * @param unknown $date
 * @return multitype:unknown
 */
function getlastMonthDays($date=""){
	if(strlen($date)==0){
		$date=time();
	}
	
	if(!is_int($date)){
		$timestamp=strtotime($date);
	}else{
		$timestamp=$date;
	}
	$firstday=date('Y-m-01',strtotime(date('Y',$timestamp).'-'.(date('m',$timestamp)-1).'-01'));
	$lastday=date('Y-m-d',strtotime("$firstday +1 month -1 day"));
	return array($firstday,$lastday);
}

//获取指定日期月份范围
function getMonthBEArr($date)
{
	$firstday = date('Y-m-01', strtotime($date));
	$lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));
	return array($firstday, $lastday);
}

/**
 * 返回指定日期下一个月日期范围
 */
function getNextMonthDays($date){
	$timestamp=strtotime($date);
	$arr=getdate($timestamp);
	if($arr['mon'] == 12){
		$year=$arr['year'] +1;
		$month=$arr['mon'] -11;
		$firstday=$year.'-0'.$month.'-01';
		$lastday=date('Y-m-d',strtotime("$firstday +1 month -1 day"));
	}else{
		$firstday=date('Y-m-01',strtotime(date('Y',$timestamp).'-'.(date('m',$timestamp)+1).'-01'));
		$lastday=date('Y-m-d',strtotime("$firstday +1 month -1 day"));
	}
	return array($firstday,$lastday);
}

/**
 * 获取2个月份差
 */
function getMonthNum( $date1, $date2, $tags='-'){
	$time1 = strtotime($date1);
	$time2 = strtotime($date2);
	$date1 = explode($tags,$date1);
	$date2 = explode($tags,$date2);
	$months =abs($date1[0]-$date2[0])*12;
	if($time1 > $time2){
		return $months+$date1[1]-$date2[1];
	}else{
		return -($months+$date2[1]-$date1[1]);
	}
}

/**
 * 判断是否本月第一天
 */
function check_month_begin($date){
	if(!$date){
		$date=date('Y-m-d',time());
	}
	if($date==date('Y-m-d', mktime(0,0,0,date('n'),1,date('Y')))){
		return true;
	}else{
		return false;
	}
}

/**
 * 返回本月的上一个月第一天
 */
function get_last_month(){
	$temp_date=getlastMonthDays();
	return $temp_date[0];
}

/**
 * 生成从开始月份到结束月份的月份数组
 * 该方法仿照党子皓getDateArr()方法
 * @param unknown_type $start
 * @param unknown_type $end
 */
function getMonthArr($start, $end)
{
	$start = empty($start) ? date('Y-m',strtotime('-1 month')) : $start;
	$end = empty($end) ? date('Y-m') : $end;

	//转为时间戳
	$st = strtotime($start.'-01');
	$et = strtotime($end.'-01');

	$t = $st;
	$i = 0;
	while($t <= $et)
	{
		//这里累加每个月的的总秒数 计算公式：上一月1号的时间戳秒数减去当前月的时间戳秒数
		$d[$i] = trim(date('Y-m',$t),' ');
		$t += strtotime('+1 month', $t)-$t;
		$i++;
	}
	return $d;
}

/**
 * 获取当月天数
 * @param $date
 * @param $rtype 1天数 2具体日期数组
 * @return
 */
function get_day( $date ,$rtype = '1')
{
	$tem = explode('-' , $date);    //切割日期 得到年份和月份
	$year = $tem['0'];
	$month = $tem['1'];
	if( in_array($month , array( 1 , 3 , 5 , 7 , 8 , 01 , 03 , 05 , 07 , 08 , 10 , 12)))
	{
		// $text = $year.'年的'.$month.'月有31天';
		$text = '31';
	}
	elseif( $month == 2 )
	{
		if ( $year%400 == 0 || ($year%4 == 0 && $year%100 !== 0) )    //判断是否是闰年
		{
			// $text = $year.'年的'.$month.'月有29天';
			$text = '29';
		}
		else{
			// $text = $year.'年的'.$month.'月有28天';
			$text = '28';
		}
	}
	else{
		// $text = $year.'年的'.$month.'月有30天';
		$text = '30';
	}
	if ($rtype == '2') {
		for ($i = 1; $i <= $text ; $i ++ ) {
			if($i<10){
				$x='0'.$i;
			}else{
				$x=$i;
			}
			$r[] = $year."-".$month."-".$x;
		}
	} else {
		$r = $text;
	}
	return $r;
}

//获取2个日期之间的相隔天数
function get_interval_day($day1,$day2){
	if(!is_int($day1)){
		$day1=strtotime($day1);
	}
	
	if(!is_int($day2)){
		$day2=strtotime($day2);
	}
	return round(($day2-$day1)/3600/24)+1;	
}

//判断是否日期
function isDate( $dateString ) {
	return strtotime( date('Y-m-d', strtotime($dateString)) ) === strtotime( $dateString );
}

//从当前日期倒推前面多少个月
function get_year_month($val=0,$is_last=true){
	$return_arr=array();
	if($val>0){
		$is_last ? $i=1 : $i=0;
		for($i;$i<=$val;$i++){
			$date=strtotime(date('Y-m',time()));
			array_push($return_arr,date('Y-m',strtotime('-'.$i.' month',$date)));
		}
	}
	return $return_arr;
}

//二维数组中查找某个值
function deep_in_array($value, $array) {
	foreach($array as $item) {
		if(!is_array($item)) {
			if ($item == $value) {
				return true;
			} else {
				continue;
			}
		}

		if(in_array($value, $item)) {
			return true;
		} else if(deep_in_array($value, $item)) {
			return true;
		}
	}
	return false;
}

//获取当前用户ID
function get_user_id(){
	return session('user_auth')['id'];
}

//获取某用户nickname,缓存中获取数据
function get_user_nickname($id=0){
	if($id==0){
		$id=get_user_id();
	}
	
	return get_cache_data('user_info',$id,'nickname');
}

/**
 +----------------------------------------------------------
 * 产生随机字串，可用来自动生成密码
 * 默认长度6位 字母和数字混合 支持中文
 +----------------------------------------------------------
 * @param string $len 长度
 * @param string $type 字串类型
 * 0 字母 1 数字 其它 混合
 * @param string $addChars 额外字符
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function rand_string($len = 6, $type = '', $addChars = '') {
	$str = '';
	switch ($type) {
		case 0 :
			$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
			break;
		case 1 :
			$chars = str_repeat ( '0123456789', 3 );
			break;
		case 2 :
			$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
			break;
		case 3 :
			$chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
			break;
		default :
			// 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
			$chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789' . $addChars;
			break;
	}
	if ($len > 10) { //位数过长重复字符串一定次数
		$chars = $type == 1 ? str_repeat ( $chars, $len ) : str_repeat ( $chars, 5 );
	}
	if ($type != 4) {
		$chars = str_shuffle ( $chars );
		$str = substr ( $chars, 0, $len );
	} else {
		// 中文随机字
		for($i = 0; $i < $len; $i ++) {
			$str .= msubstr ( $chars, floor ( mt_rand ( 0, mb_strlen ( $chars, 'utf-8' ) - 1 ) ), 1 );
		}
	}
	return $str;
}

// 自动转换字符集 支持数组转换
function auto_charset($fContents, $from='gbk', $to='utf-8') {
	$from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
	$to = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
	if (strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents))) {
		//如果编码相同或者非字符串标量则不转换
		return $fContents;
	}
	if (is_string($fContents)) {
		if (function_exists('mb_convert_encoding')) {
			return mb_convert_encoding($fContents, $to, $from);
		} elseif (function_exists('iconv')) {
			return iconv($from, $to, $fContents);
		} else {
			return $fContents;
		}
	} elseif (is_array($fContents)) {
		foreach ($fContents as $key => $val) {
			$_key = auto_charset($key, $from, $to);
			$fContents[$_key] = auto_charset($val, $from, $to);
			if ($key != $_key)
				unset($fContents[$key]);
		}
		return $fContents;
	}
	else {
		return $fContents;
	}
}

/**
 * 获取MSSQL数据,gbk码转utf8码
 * @param string $flag    标记使用的MS数据库  sinocom,nx,wx,default:sinocom   
 * @param $c_code:是否做转CODE动作.sqlserver 繁体字转mysql转码反而会出乱码.
 * @return array
 */
function get_mssql_info($flag,$sql,$c_code=true){
	$hr_db_arr=CONFIG('hr_souce_data')[$flag];

	$result_info=array();
	
	if(IS_WIN){
		$connectionInfo = array( "Database"=>$hr_db_arr['DB_NAME'], "UID"=>$hr_db_arr['USER'], "PWD"=>$hr_db_arr['PWD']);
		//print_r($hr_db_arr['HOST']);exit;
		$conn = sqlsrv_connect($hr_db_arr['HOST'], $connectionInfo);
		
		if($conn==false){
			die (print_r(sqlsrv_errors(),true));
		}
		$sql_info=sqlsrv_query($conn,$sql);
		if($sql_info==false){
			die(print_r(sqlsrv_errors(),true));
		}
		
		while($row=sqlsrv_fetch_array($sql_info,SQLSRV_FETCH_ASSOC)){
			array_push($result_info,auto_charset($row,'gbk','utf-8'));
		}
		
	}else{
		$return_flag=false;	//标记语句是否有返回值
		
		putenv("FREETDSCONF=/etc/freetds.conf");
		putenv("TDSVER=80");
		$conn=mssql_connect($hr_db_arr['HOST'],$hr_db_arr['USER'],$hr_db_arr['PWD']);
		mssql_select_db($hr_db_arr['DB_NAME'],$conn);
		$sql_info=mssql_query($sql);
		
		try{
			$num=mssql_num_rows($sql_info);
			$return_flag=true;
		}catch(Exception $e){
			$return_flag=false;
		}
		
		if($return_flag){
			$num=mssql_num_rows($sql_info);
			$result_info = array();
			for($i=0;$i<$num;$i++) {
				$row = mssql_fetch_array($sql_info,MYSQL_ASSOC);
				//print_r($row);exit;
				//$data[] = $row;
				if($c_code){
					array_push($result_info,auto_charset($row,'gbk','utf-8'));
				}else{
					array_push($result_info,$row);
				}
			}
		}else{
			$result_info=array();
		}
	}
	return $result_info;
}

function get_oracle_info($sql){
	
	$dsn_con="oci:dbname=//erpx.sinowealth.com:1521/topprod;charset=UTF8";
    try{
        $db= new PDO($dsn_con,"data","data",array(PDO::ATTR_PERSISTENT => true));
    } catch (PDOException $e) {
        print "oci: " . $e->getMessage() . "<br/>";
        die();
    }
    
     $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);//调用查询函数query(),并以关联数组的形式储存,  
            //PDO::FETCH_ASSOC是关联数组的形式，其他的还有  PDO::FETCH_NUM -- 数字索引数组形式    PDO::FETCH_BOTH -- 关联和索引两者数组形式都有，这是缺省的          // PDO::FETCH_OBJ -- 按照对象的形式，类似于以前的 mysql_fetch_object()  
        //下面是通过foreach函数依次输出查询结果  
        $rs = array();  
        foreach($rows as $row)   
        {            
            $rs[] = $row;      
        }       
        $db = null;  //注销PDO对象  
    
        return $rs;
	
}

/**
 * 字符串逗号补全
 * @param string $str    输入带逗号的字符串
 * @param string $flag 输出字符串类型,0为去除首位逗号,1为补全首位逗号.
 * @return string
 */
function check_douhao($str,$flag){
	$left_str=substr($str,0,1);
	$right_str=substr($str,-1,1);
	$need_douhao=$str;
	$del_douhao=$str;
	if($left_str<>','){
		$need_douhao=','.$need_douhao;
	}else{
		$del_douhao=ltrim($del_douhao,',');
	}

	if($right_str<>','){
		$need_douhao=$need_douhao.',';
	}else{
		$del_douhao=rtrim($del_douhao,',');
	}


	if($flag==0){
		return $del_douhao;
	}else{
		return $need_douhao;
	}
}

/**
 * 生成小时的select
 * @param string $name select 名称
 * @param int $val  选择值
 */
function get_h_select($name="select_h",$val=0){
	if($val>23){
		$val="00";
	}
	
	$temp_str="<select name='".$name."'>";

		for($i=7;$i<=19;$i++){
			if(strlen($i)==1){
				$m_str= '0'.$i;
			}else{
				$m_str=$i;
			}
		
			
			if($val==$m_str){
				$temp_str .= "<option value='".$m_str."' selected='selected'>".$m_str."</option>";
			}else{
				$temp_str .= "<option value='".$m_str."'>".$m_str."</option>";
			}
		}

		
	$temp_str .="</select>";
	return $temp_str;	
}

/**
 * 生成分钟的select
 * @param string $name select 名称
 * @param string $val  选择值
 */
function get_m_select($name="select_m",$val=""){
	if($val>59){
		$val="00";
	}
	$temp_str="<select name='".$name."'>";
	if($val=='30'){
		$temp_str .= "<option value='0'>00</option>";
		$temp_str .= "<option value='30' selected='selected'>30</option>";
	}else{
		$temp_str .= "<option value='0' selected='selected'>00</option>";
		$temp_str .= "<option value='30'>30</option>";
	}	
	
	$temp_str .="</select>";
	return $temp_str;
}

/**
 * 计算2个时间差
 */
function timediff( $begin_time, $end_time )
{
	if(!is_int($begin_time)){
		$begin_time=strtotime($begin_time);
	}
	
	if(!is_int($end_time)){
		$end_time=strtotime($end_time);
	}
	
	if ( $begin_time < $end_time ) {
		$starttime = $begin_time;
		$endtime = $end_time;
	} else {
		$starttime = $end_time;
		$endtime = $begin_time;
	}
	
	$timediff = $endtime - $starttime;
	
	$days = intval( $timediff / 86400 );
	$remain = $timediff % 86400;
	$hours = intval( $remain / 3600 );
	$remain = $remain % 3600;
	$mins = intval( $remain / 60 );
	$secs = $remain % 60;
	$h=  $timediff / 60 / 60;
	$m= $timediff/60;
	$s= $timediff;
	$res = array( "day" => $days, "hour" => $hours, "min" => $mins, "sec" => $secs ,"h"=>$h,"m"=>$m,"s"=>$s);
	return $res;
}

/*传入时间秒数,转换为考勤完整计算格式*/
function hr_time_format($sec){
	$timediff=$sec;
	$days = intval( $timediff / 86400 );
	$remain = $timediff % 86400;
	$hours = intval( $remain / 3600 );
	$remain = $remain % 3600;
	$mins = intval( $remain / 60 );
	$secs = $remain % 60;
	$h=  $timediff / 60 / 60;
	$m= $timediff/60;
	$s= $timediff;
	$res = array( "day" => $days, "hour" => $hours, "min" => $mins, "sec" => $secs ,"h"=>$h,"m"=>$m,"s"=>$s);
	return $res;
}

/**
 * 获取2个日期之间差的日期数组
 */
function date_rang($start_date, $end_date){
	return array_map(function($n){return date('Y-m-d', $n);}, range(strtotime($start_date), strtotime($end_date), 24*3600));
}

/**获取某个日期的字段**/
function get_date_time($date="",$time_format='Y-m-d H:i:s'){
	$timestamp=0;
	$return_str="";
	
	if(strlen($date)==0){
		$date=time();
	}
	
	if(is_int($date)){
		$timestamp=$date;
	}else{
		$timestamp=strtotime($date);
	}
	
	return date($time_format,$timestamp);	
}

/**
 * 返回用户权限数组
 */
function get_role_arr(){
	$return_arr=array();
	//$return_arr[4]['id']=1;
	//$return_arr[4]['role_name']='超级管理员';
	$return_arr[1]['id']=1;
	$return_arr[1]['role_name']='用户';
	$return_arr[5]['id']=5;
	$return_arr[5]['role_name']='财务';
	$return_arr[3]['id']=2;
	$return_arr[3]['role_name']='管理员';
	
	return $return_arr;
}

/**
 * 根据用户组别ID返回权限组信息
 */
function get_role_name($role_id){
	$rule_arr=get_role_arr();
	return $rule_arr[$role_id]['role_name'];
}

function send_email($to, $subject, $message,$from='Finance.sh@sinowealth.com',$title="中颖内管通知",$priority=3,$file_url='') {
	$config = config('mail');
	
	if(get_client_ip()=='127.0.0.1' && IS_WIN){
		$to='hank.zhou@sinowealth.com.cn';
		//$to='renjie.zhou@sinowealth.com.cn';
		//$to='tianqi.wang@sinowealth.com.cn';
		}
	
	if(strlen($from)==0){
		$from='Finance.sh@sinowealth.com';
	}
	
	if(strlen($title)==0){
		$title="中颖内管通知";
	}

	$email = new \com\Email($config);
	$email->from($from, $title);
	$email->to($to);
	$email->set_priority($priority);


    $email->attach($file_url);
	$email->subject($subject);
	$email->message($message);

	return $email->send();
}

/**
 * 调试打印函数
 */
function pr($data,$type='print_r'){
	header("Content-type: text/html; charset=utf-8");
	if($type=='d'){
		dump($data);
		exit;
	}else{
		print_r($data);
		exit;
	}
}

/**
 * 页面报错信息输出
 */
function show_msg($msg){
	header("Content-type: text/html; charset=utf-8");
	echo $msg;
}

/**
 * 传入数组,导出Excel
 * $action_type new,normal,如果为demo,则要求指定sw_sys_file_normal表中的对应关键字file_key
 * 所要求传入数组格式
 * $data['file_name']-->文件名,$data['tb_tit']-->标题,$data['tb_head']-->表头,$data['tb_body']-->表格内容,如果为模板文件构建,则需要有字段$data['file_key']对应数据表sw_sys_file_normal
 * $data['file_info']-->Excel文件信息,默认设置项, $data['file_type']-->输出文件类型,默认Excel5
 */
function ext_excel($action_type='new',$data=array()){
	
	//判断工作环境
	if(IS_WIN){
		$file_path=config('WIN_FILE_PATH');
	}else{
		$file_path=config('LIN_file_path');
	}	
	
	//获取demo 文件表格信息
	if(isset($data['file_key'])){
		$file_info=db('sys_file_normal')->where("file_key='".$data['file_key']."'")->find();
	}
	
	//配置默认表格初始信息
	if(!isset($data['file_info'])){
		//文件作者
		$p_creator=config('company_name_en');
		//最后文件编辑人员
		$p_lastModifiedBy='CW';
		//文件标题
		$p_title=config('system_name_en');
		//文件主题
		$p_subject="";
		//文件标记
		$p_key="";
		//文件类别
		$p_category="";
		//文件备注
		$p_desc="";		
	}
	
	//设置默认输出文件类型
	if(!isset($data['file_type'])){
		$file_type='Excel5';
		$file_ext=".xls";
	}
	
	/** Load PHPExcel */
	\think\Loader::import('.PHPExcel.PHPExcel');
	
	/** Error reporting */
	error_reporting(E_ALL);
	ini_set('display_errors', TRUE);
	ini_set('display_startup_errors', TRUE);

	//模板文件创建文件
	if($action_type=='normal'){
		//加载模板文件
		//$file_url=APP_PATH .'\\upload\\'.$file_info['file_url'];
		//$file_url="d:\\1web\\hr\\application\\upload\\excel\\pay\\pay_tb.xls";
		
		if(IS_WIN){
			$file_url=$file_path.'/'.$file_info['win_file_url'];
		}else{
			$file_url=$file_path.DS.$file_info['lin_file_url'];
		}
		
		//根据不同的文件后缀加载不同的读取器
		$extension = strtolower( pathinfo($file_url, PATHINFO_EXTENSION) );
		if($extension=='xlsx'){
			$file_type='Excel2007';
			$file_ext=".xlsx";
		}
		
		$objReader = \PHPExcel_IOFactory::createReader($file_type);	
		$objPHPExcel=$objReader->load($file_url);
		$sheet=$objPHPExcel->getSheet(0);
		
		if(strlen($file_info['title_cell'])>0){
			//写excel标题,导出到银行数据不需要标题
			$sheet->setCellValue($file_info['title_cell'],$data['tb_tit']);	
		}
		
		//写表格主体内容
		//行循环
		for($i=0;$i<count($data['tb_body']);$i++){
			$j=$i+$file_info['begin_row'];
			//列循环
			foreach ($data['tb_head'] as $k=>$v){
				$cell_val=\PHPExcel_Cell::stringFromColumnIndex($k).$j;				
				
				//如果有定义列格式,则按照设定格式需求输出
				if(isset($data['td_format'])){
					switch ($data['td_format'][$k]){
						case 'string':
							$sheet->setCellValueExplicit($cell_val,$data['tb_body'][$i][$v],PHPExcel_Cell_DataType::TYPE_STRING);
							break;
						default:
							$sheet->setCellValue($cell_val,$data['tb_body'][$i][$v]);
							break;
					}
				}else{
					$sheet->setCellValue($cell_val,$data['tb_body'][$i][$v]);
				}
			}
		}
		
	}else{
		//新建Excel文件导出,占未写此部分功能
		/*
		// Create new PHPExcel object
		$objPHPExcel = new \PHPExcel();
		// Set document properties
		$objPHPExcel->getProperties()
		->setCreator($p_creator)
		->setLastModifiedBy($p_lastModifiedBy)
		->setTitle($p_title)
		->setSubject($p_subject)
		->setDescription($p_desc)
		->setKeywords($p_key)
		->setCategory($p_category);
		// Add some data
		$objPHPExcel->setActiveSheetIndex(0)
		->setCellValue('A1', 'Hello')
		->setCellValue('B2', 'world!')
		->setCellValue('C1', 'Hello')
		->setCellValue('D2', 'world!');
		// Miscellaneous glyphs, UTF-8
		$objPHPExcel->setActiveSheetIndex(0)
		->setCellValue('A4', 'Miscellaneous glyphs')
		->setCellValue('A5', 'éàèùâêîôûëïüÿäöüç');
		
		// Rename worksheet
		$objPHPExcel->getActiveSheet()->setTitle('Simple');
		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);
		*/
	}
	
	//设置输出文件格式信息
	// Redirect output to a client’s web browser (Excel5)
	header('Content-Type: application/vnd.ms-excel');
	header('Content-Disposition: attachment;filename="'.$data['file_name'].$file_ext.'"');
	header('Cache-Control: max-age=0');
	// If you're serving to IE 9, then the following may be needed
	header('Cache-Control: max-age=1');
	// If you're serving to IE over SSL, then the following may be needed
	header ('Expires: '.gmdate('D, d M Y H:i:s').' GMT'); // Date in the past
	header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
	header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
	header ('Pragma: public'); // HTTP/1.0
	/** Load IOFactory */
	\think\Loader::import('PHPExcel.IOFactory.PHPExcel_IOFactory');
	$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, $file_type);
	$objWriter->save('php://output');
	
}

/**
 * 传入Excel文件信息,返回数组
 * $file_info包含文件所在路径,
 *
 */
function read_excel_file($file_info=array()){
	$file_url=$file_info['url'];
	$file_ext='xls';
	
	if(!is_file($file_url)){
		show_msg($file_url.'<br>文件不存在!');
		exit;
	}
	
	/** Load PHPExcel */
	\think\Loader::import('.PHPExcel.PHPExcel');
	//根据不同的文件后缀加载不同的读取器
	$extension = strtolower( $file_ext );
	if(!isset($data['file_type'])){
		$file_type='Excel5';
		$file_ext=".xls";
	}
	if($file_ext=='xlsx'){
		$file_type='Excel2007';
		$file_ext=".xlsx";
	}else{
		$file_type='Excel5';
		$file_ext=".xls";
	}
	
	$objReader = \PHPExcel_IOFactory::createReader($file_type);
	$objPHPExcel=$objReader->load($file_url);
	
	$sheet = $objPHPExcel->getSheet(0);
	$row_count = $sheet->getHighestRow();
	$col_max = $sheet->getHighestColumn();
	$col_count=\PHPExcel_Cell::columnIndexFromString($col_max);
	
	$return_arr=array();
	
	//从第二行开始读取数据	
	for($j=2;$j<=$row_count;$j++){  
		for($k=0;$k<$col_count;$k++){
			//拼接单元格坐标
			$cell_val=\PHPExcel_Cell::stringFromColumnIndex($k).$j;	
			$return_arr[$j][$k]=auto_charset($objPHPExcel->getActiveSheet()->getCell($cell_val)->getValue(),'utf-8');
		}
	}	
	
	return $return_arr;
	
}

/**
 * 获取员工site_pay_flag
 */
function get_site_pay_flag($user_id=''){
	if(strlen($user_id)==0){
		$user_id=get_user_id(0);
	}
	$sql="select site_pay_flag from sw_sys_site where id=(select site_id from sw_sys_user where id=".$user_id.")";
	$temp_arr=db()->query($sql);
	return $temp_arr[0]['site_pay_flag'];
}

/**
 * 判断某主机某端口通讯是否正常
 */
function check_host_port($host='localhost',$port=1433){
	if($fp=fsockopen($host,$port,$errno,$errstr,10)){
		return true;
	}else{
		return false;
	}
}
/**
 * 不套模板导出excel
 */
function excel_css($data){
	\think\Loader::import('.PHPExcel.PHPExcel');//引入文件

	//ext_excel();
	//创建对象
	$objPHPExcel = new \PHPExcel();
	$obpe_pro = $objPHPExcel->getProperties();
	if(isset($data['attribute'])){
		foreach ($data['attribute'] as $key=>$d){
			$obpe_pro->$key($d);
		}
	}
	/*$obpe_pro->setCreator('midoks')//设置创建者
	 ->setLastModifiedBy('2013/2/16 15:00')//设置时间
	 ->setTitle('data')//设置标题
	 ->setSubject('beizhu')//设置备注
	 ->setDescription('miaoshu')//设置描述
	 ->setKeywords('keyword')//设置关键字 | 标记
	 ->setCategory('catagory');//设置类别*/
	$objPHPExcel->setActiveSheetIndex(0); //设置第一个工作表为活动工作表
	$i=0;


	///$objPHPExcel->getActiveSheet()->getTabColor()->setARGB( 'FF0094FF');     //设置标签颜色

	foreach(range('A','ZZ') as $v){
		$a[]=$v;
	}
	foreach(range('A','ZZ') as $v){
		foreach(range('A','ZZ') as $vv){
			$a[]=$v.$vv;
		}
	}
	$letter = $a;//可随动
	foreach ($data as $key=>$a){
		if(is_numeric($key)){
			$style=array();
			if(isset($a['style'])){
				$style[]=$a['style'];
			}
			if(isset($a['style']['style'])){
				foreach ($a['style']['style'] as $s){
					if(isset($data[$s])){
						$style[]=$data[$s];
					}else if($s=='this'){
						$style[]=$a['style'];
					}
				}
			}
			//工作表命名
			$sheet_name='';
			foreach ($style as $s){
				if(isset($s['sheet'])){
					$sheet_name=$s['sheet'];
					break;
				}
			}
			if($sheet_name==''){$sheet_name='sheet'.$i;}
			if($i>0){
				$msgWorkSheet = new \PHPExcel_Worksheet($objPHPExcel, $sheet_name); //创建一个工作表
				$objPHPExcel->addSheet($msgWorkSheet); //插入工作表
				$objPHPExcel->setActiveSheetIndex($i); //切换到新创建的工作表
			}else{
				$objPHPExcel->getActiveSheet()->setTitle($sheet_name); //设置工作表名称
			}
			if(isset($s['sheet_color'])){
				if($s['sheet_color']){
					$objPHPExcel->getActiveSheet()->getTabColor()->setARGB($s['sheet_color']);
				}
			}

			$keyy=1;
			foreach ($style as $s){
				if(isset($s['star'])){
					$keyy=$s['star'];
					break;
				}
			}
			$best=0;$best_shu=1;
			if(isset($a['data'])){
				foreach ($a['data'] as $ke=>$dd){
					$kee='-1';$ky=0;
					foreach ($dd as $key=>$d){
						if($ky=='-1'){
							if($key!==0){
								$kee=$key-2;
							}
						}
						if(preg_match("/[^0-9]+/", $key) && $key>0){
							$kee=$kee+$key;
						}

						$kee++;
						$objPHPExcel->getActiveSheet()->setCellValue("$letter[$kee]$keyy","$d");
						if($keyy>$best_shu){$best_shu=$keyy;}
						$ky++;
					}
					$keyy++;
					if($kee>$best){$best=$kee;}
				}
			}else{
				$a['data']=array();
			}

			$ret=0;$freezePane=0;
			foreach ($style as $s){
				if(isset($s['ret'])){
					if($s['ret']==1 && $ret==0){
						$objPHPExcel->getActiveSheet()->setAutoFilter($objPHPExcel->getActiveSheet()->calculateWorksheetDimension());//搜索
					}
					$ret=1;
				}
				if(isset($s['freezePane']) && $freezePane==0){
					$row=$s['freezePane']+1;
					$objPHPExcel->getActiveSheet()->freezePane('A'.$row);//冻结首行  2行则为3
					$freezePane=1;
				}
				if(isset($s['font'])){
					$styleArray=array();
					foreach ($s['font'] as $ke=>$ff){
						if($ke=='all'){
							$endd=count($a['data']);
							$ke="A1:$letter[$best]$best_shu";//循环数据后确定
						}

						foreach ($ff as $key=>$f){
							switch ($key){
								case 'bold':
									switch ($f){
										case 1:
											$f=true;
											break;
										default:
											$f=false;
									}
									$styleArray['font']['bold']=$f;
									break;
								case 'size':
									$styleArray['font']['size']=$f;
									break;
								case 'color':
									$objPHPExcel->getActiveSheet()->getStyle($ke)->getFont()->getColor()->setARGB($f);
									//$objPHPExcel->getActiveSheet()->getStyle('A1:Z100')->getFont()->getColor()->setARGB(\PHPExcel_Style_Color::COLOR_DARKGREEN);
									break;
								case 'alignment':
									switch ($f){
										case 'centrol':
											//$styleArray['alignment']= array('horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
											$objPHPExcel->getActiveSheet()->getStyle($ke)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
											$objPHPExcel->getActiveSheet()->getStyle($ke)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
											break;
										case 'left':
											$objPHPExcel->getActiveSheet()->getStyle($ke)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_JUSTIFY);
											break;
										case 'right':
											$objPHPExcel->getActiveSheet()->getStyle($ke)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
											break;
										case '水平':
											$objPHPExcel->getActiveSheet()->getStyle($ke)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
											break;
										case '垂直':
											$objPHPExcel->getActiveSheet()->getStyle($ke)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
											break;
									}
									break;
								case 'typeface':
									$objPHPExcel->getActiveSheet()->getStyle($ke)->getFont()->setName($f);
									break;
								case 'br':
									switch ($f){
										case 1:
											$f=true;
											break;
										default:
											$f=false;
									}
									$objPHPExcel->getActiveSheet()->getStyle($ke)->getAlignment()->setWrapText($f);
								case 'underline'://下划线
									switch ($f){
										case 1:
											$objPHPExcel->getActiveSheet()->getStyle($ke)->getFont()->setUnderline(\PHPExcel_Style_Font::UNDERLINE_SINGLE);
											break;
										default:
									}
									break;
								case 'italic'://斜体
									switch ($f){
										case 1:
											$f=true;
											break;
										default:
											$f=false;
									}
									$objPHPExcel->getActiveSheet()->getStyle($ke)->getFont()->setItalic($f);
									break;
							}
						}
						$objPHPExcel->getActiveSheet()->getStyle($ke)->applyFromArray($styleArray);
					}
				}
				if(isset($s['cell'])){
					foreach ($s['cell'] as $ke=>$cc){
						$styleArray=array();
						if($ke=='all'){
							$endd=count($a['data']);
							$ke="A1:$letter[$best]$best_shu";//循环数据后确定
						}
						foreach ($cc as $key=>$c){
							switch ($key){
								case 'backgroundcolor':
									$objPHPExcel->getActiveSheet()->getStyle($ke)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB($c);
									break;
								case 'width':
									if($c){
										$objPHPExcel->getActiveSheet()->getColumnDimension($ke)->setWidth($c);//行宽
									}
									break;
								case 'height':
									if($c){
										$objPHPExcel->getActiveSheet()->getRowDimension($ke)->setRowHeight($c);//行高
									}
									break;
								case 'Password':
									if($c){
										$objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);
										$objPHPExcel->getActiveSheet()->protectCells("$ke",$c);
									}
									break;
								case 'border':
									foreach ($c as $bor_n=>$bor){
										switch ($bor_n){
											case 'all':
												$styleArray=$this->ex($bor,$styleArray,'allborders');
												break;
											case 'out':
												$styleArray=$this->ex($bor,$styleArray,'outline');
												break;
											case 'top':
												$styleArray=$this->ex($bor,$styleArray,'top');
												break;
											case 'bottom':
												$styleArray=$this->ex($bor,$styleArray,'bottom');
												break;
											case 'left':
												$styleArray=$this->ex($bor,$styleArray,'left');
												break;
											case 'right':
												$styleArray=$this->ex($bor,$styleArray,'right');
												break;
											case 'in'://设置内部边框，占时没有
												//$styleArray['borders']['']=;
												break;
										}
									}

									break;
								case 'merge':
									foreach ($c as $k=>$cc){
										switch ($k){
											case 'up_down'://竖？？？待定
												break;
											case 'ordinary':
												foreach ($cc as $ccc){
													$objPHPExcel->getActiveSheet()->mergeCells($ccc);
												}

												break;
										}
									}

							}

						}
						if($ke!='merge'){
							$objPHPExcel->getActiveSheet()->getStyle($ke)->applyFromArray($styleArray);
						}

						//exit;
					}
				}
			}
			$i++;
		}
	}
	if(isset($data['sheet_show'])){
		$objPHPExcel->setActiveSheetIndex($data['sheet_show']);
	}
	
	//设置SHEET
	//$excel->setActiveSheetIndex(0);
	//创建Excel输入对象
	$write = new \PHPExcel_Writer_Excel5($objPHPExcel);
	ob_end_clean();//清除缓冲区,避免乱码
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
	header("Content-Type:application/force-download");
	header("Content-Type:application/vnd.ms-execl");
	header("Content-Type:application/octet-stream");
	header("Content-Type:application/download");
	$name=isset($data['name'])?$data['name']:'wtqde';
	header('Content-Disposition:attachment;filename="'.$name.'.xls"');//filename生成文件的名字//可随动
	header("Content-Transfer-Encoding:binary");
	$write->save('php://output');
}

function exec_oracle($sql){
    if(strlen($sql)==0)
	{
        exit;
    }else
	{
		$tns = "
     (DESCRIPTION=
        (ADDRESS=
          (PROTOCOL=TCP)
          (HOST=192.9.231.201)
          (PORT=1521)
        )
        (CONNECT_DATA=
          (SERVER=dedicated)
          (SERVICE_NAME=topprod)
        )
      )";
        try
		{
            $conn = new PDO("oci:dbname=".$tns, "t_shsino", "t_shsino");
        }
        catch (PDOException $e)
		{
            echo "Failed to obtain database handle " . $e->getMessage();
        }
        return $conn->exec($sql);
    }

}


//wtq压缩
	function zip_little($name,$path){
		//$name='/data/wwwroot/hr-t.sinowealth.com/application/upload/aaa.zip';  写的路径
		//$path='../application/upload/file/1/';                                 来源路径
		HZip::zipDir($path, $name);
	}
	//wtq压缩
	class HZip{  
	/**   
	* 添加文件和子目录的文件到zip文件   
	* * @param string $folder   
	* * @param ZipArchive $zipFile   
	* * @param int $exclusiveLength Number of text to be exclusived from the file path.   */  
		private static function folderToZip($folder, &$zipFile, $exclusiveLength) { 
			$handle = opendir($folder);    
			while (false !== $f = readdir($handle)) {    
				if ($f != '.' && $f != '..') {       
					$filePath = "$folder/$f";        
					// Remove prefix from file path before add to zip.       
					 $localPath = substr($filePath, $exclusiveLength);        
					 if (is_file($filePath)) {          
					 	$zipFile->addFile($filePath, $localPath);       
					  } elseif (is_dir($filePath)) {        
					  	// 添加子文件夹       
					  	   $zipFile->addEmptyDir($localPath);
					  	   self::folderToZip($filePath, $zipFile, $exclusiveLength);      
					    }     
				 }   
			 }    
			 closedir($handle);  
		}  
		/**   
		 * * Zip a folder (include itself).   
		 * * Usage:   
		 * *   HZip::zipDir('/path/to/sourceDir', '/path/to/out.zip');   *
		 *    * @param string $sourcePath Path of directory to be zip.   * 
		 *    @param string $outZipPath Path of output zip file.   
		 *    */  
		public static function zipDir($sourcePath, $outZipPath)  {
			$pathInfo = pathInfo($sourcePath);    
			$parentPath = $pathInfo['dirname'];    
			$dirName = $pathInfo['basename'];    
			$sourcePath=$parentPath.'/'.$dirName;
			//防止传递'folder' 文件夹产生bug    
			$z = new ZipArchive();    
			$z->open($outZipPath, ZIPARCHIVE::CREATE);
			//建立zip文件   
			 $z->addEmptyDir($dirName);
			 //建立文件夹    
			 self::folderToZip($sourcePath, $z, strlen("$parentPath/"));    
			 $z->close();  
		}
	}













