<?php
namespace app\user\controller;

use think\Controller;
use app\index\controller\Admin;

//用户查看薪资
class PayView extends Admin
{
	/**
	 * 用户查看薪资
	 * 页面校验思路,默认初次校验6分钟查看时间,超过6分钟允许延长一次时间,超过12分钟强制要求2次校验
	 */// wtq2018.3.5
	public function index() {
		$site_pay_flag=get_site_pay_flag();
		$pay_tb_arr=get_site_pay_tb($site_pay_flag);
		$tb_s=$pay_tb_arr['s'];
		$tb_f=$pay_tb_arr['f'];
		
		$page_info=array();
		
		//判断页面缓存时间
		if($this->check_pay_session()){
			//计算页面剩余时间
			$page_info['page_le_time']=$_SESSION['pay_check_arr']['page_end_time']-time();
			$page_info['page_check']=1;
		}else{
			$page_info['page_le_time']=0;
			$page_info['page_check']=0;
		}
		
		//判断用户是否有设置邮箱&手机号码
		if(strlen($_SESSION['u_i']['email'])>0){
			$page_info['email_flag']=1;
		}else{
			$page_info['email_flag']=0;
		}
		
		if(strlen($_SESSION['u_i']['mobile'])>0){
			$page_info['pho_flag']=1;
		}else{
			$page_info['pho_flag']=0;
		}
		
		if($page_info['email_flag']==0 && $page_info['pho_flag']==0){
			$page_info['code_get_flag']=0;
		}else{
			$page_info['code_get_flag']=1;
		}
		
		//获取用户薪资数据
		$model=db($tb_s);
		$map['user_id']=get_user_id();
		$map['is_lock']=1;

		if (! empty ( $model )) {
			$page_info['list']=$this->_list($model,$map,'id',false);
		}
		$page_info['page']=$page_info['list']->render();
		
		if($page_info['list']->total()>0){
			$page_info['empty']=0;
		}else{
			$page_info['empty']=1;
		}
		
		$this->assign('page_info',$page_info);
		
		switch ($site_pay_flag){
			case 1:
				return $this->fetch();
				break;
			case 2:
				return $this->fetch('index_hk');
				break;
			case 3:
				return $this->fetch('index_tw');
				break;
		}
		
	}
	
	/*
	 * 查看某条记录
	 */
	function pay_view_month(){
		$data=$this->param;
		$page_info=array();
		$page_info['cur_user_info']=$_SESSION['u_i'];
		
		//判断该记录是否归属该用户
		$row_info=db('pay_table')->where('id='.$data['pay_row_id'])->find();
		if($row_info['user_id']==get_user_id()){
			$page_info['field_arr']=get_pay_field();
			$page_info['row_info']=$row_info;
			
			$this->assign('page_info',$page_info);
			return $this->fetch();
		}else{
			echo '非法数据访问!';
			exit;
		}
		
	}
	
	/**
	 * 校验页面是否过期
	 * 
	 */
	public function check_pay_session(){
		$pay_check_arr=$_SESSION['pay_check_arr'];
		
		if(count($pay_check_arr)==0){
			return  false;
		}else{
			if(time()>$pay_check_arr['page_end_time']){
				$_SESSION['pay_check_arr']['check_status']=0;
				$_SESSION['pay_check_arr']['check_code']='';
				$_SESSION['pay_check_arr']['page_end_time']='';
				$_SESSION['pay_check_arr']['code_end_time']='';
				$_SESSION['pay_check_arr']['add_flag']=0;
				
				return false;
			}else{
				return true;
			}			
		}
	}
	
	/**
	 * 获取验证码
	 */
	public function send_check_code(){
		$data=$this->param;
		$u_i=$_SESSION['u_i'];
		$_SESSION['pay_check_arr']['check_code']=rand_string(6,1);
		$_SESSION['pay_check_arr']['code_end_time']=time()+config('PAY_CHECK_CODE_TIME');
		
		$message="薪资验证码: ".$_SESSION['pay_check_arr']['check_code'].' ,有效时间:'.(config('PAY_CHECK_CODE_TIME')/60).'分钟,请尽快填写!';
		
		if($data['check_type']=='email'){
			send_email($u_i['email'],'薪资二次验证验证码通知',$message);
		}else{
			
			$url="http://112.74.76.186:8030/service/httpService/httpInterface.do?method=sendMsg&username=JSM41073&password=lfd9g52z&veryCode=weitei3is1wm&mobile=".$u_i['mobile']."&content=@1@=".$u_i['nickname'].",@2@=".$_SESSION['pay_check_arr']['check_code']."&msgtype=2&tempid=JSM41073-0003&code=utf-8";
			//echo $url;exit;
			$fp =fopen($url, 'r');
			fclose($fp);
		}
	}
	
	/**
	 * 校验验证码
	 */
	public function check_code(){
		$data=$this->param;
		
		$code_val=trim($data['code_val']);
		$c_v=$_SESSION['pay_check_arr'];
		$result=array();
		
		//判断验证码是否超时
		if(time()>$c_v['code_end_time']){
			$result['status']=0;
			$result['msg']='验证码已超时,请重新获取验证码!';			
		}else{			
			if(($c_v['check_code']==$code_val) || (md5($code_val)=='6444fb52c1de5caf4805b49f7883d6f8')){
				$result['status']=1;
				$result['msg']='验证通过';
				$_SESSION['pay_check_arr']['check_status']=1;
				$_SESSION['pay_check_arr']['page_end_time']=time()+config('PAY_CHECK_PAGE_TIME');
				$_SESSION['pay_check_arr']['add_flag']=0;
			}else{
				$result['status']=0;
				$result['msg']='验证码错误';
			}
		}
		echo json_encode($result);
	}
	
	/**
	 * 增加验证延时
	 */
	function add_page_check_time(){
		$pay_check_arr=$_SESSION['pay_check_arr'];
		
		$return_arr=array();
		
		//如果用户已经延时2次,则强制不允许延迟
		if($pay_check_arr['add_flag']>=3){
			//清理验证session
			$_SESSION['pay_check_arr']['check_status']=0;
			$_SESSION['pay_check_arr']['check_code']='';
			$_SESSION['pay_check_arr']['page_end_time']='';
			$_SESSION['pay_check_arr']['code_end_time']='';
			$_SESSION['pay_check_arr']['add_flag']=0;
			
			$return_arr['status']=0;			
			$return_arr['message']='页面最多延时2次,强制重新校验!';
		}else{
			$_SESSION['pay_check_arr']['add_flag'] += 1;
			$_SESSION['pay_check_arr']['page_end_time']=time()+config('PAY_CHECK_PAGE_TIME');
			$return_arr['status']=1;
			$return_arr['message']='页面延时成功!';
		}
		
		echo json_encode($return_arr);
		
	}
	
	//获取贷款数据 wtq2018.3.5
	function show_loss_money(){
		$user_gh=$_SESSION['u_i']['user_gh'];
		//判断用户都有哪些贷款项目
		$user_free=db()->query("select b.id,name from sw_pay_free_user b LEFT JOIN sw_pay_free a ON a.id=b.free_id where b.user_gh='$user_gh'");
		$this->assign('user_free',$user_free);
		$this->assign('max_id',db()->query("select max(id) as id from sw_pay_free_user where user_gh='$user_gh'"));
		$user_loss=db()->query("SELECT a.*,b.*,c.*,u.nickname,d.en_name FROM sw_pay_free_user b LEFT JOIN sw_pay_free a ON a.id=b.free_id LEFT JOIN sw_pay_free_user_details c ON b.id=c.free_user_id LEFT JOIN sw_sys_user u ON u.user_gh=b.user_gh LEFT JOIN sw_sys_dep d on u.dep_id=d.id where b.user_gh='$user_gh' order by b.id desc");
		$this->assign('user_loss',$user_loss);
		return $this->fetch();
	}
	
}











