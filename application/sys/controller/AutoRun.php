<?php
namespace app\sys\controller;

set_time_limit(0);
ini_set("memory_limit","-1");
use think\Controller;
use app\index\controller\Admin;

//自动运行控制器,计划任务处理
class AutoRun extends Controller
{	
	//计划任务脚本
	public function index(){
		//判断是否本机运行
		$ip=get_client_ip();

		$err_flag=false;
		
		if($ip!="192.9.231.211"){
		//if($ip!="127.0.0.1"){
			$err_flag=true;
		}

		if($err_flag){
			echo 'error!';
			send_email('hank.zhou@sinowealth.com.cn','考勤自动脚本预警!', '非管理员或本地运行,运行IP为'.$ip);
			exit();
		}else{
			$mail_message="";
			G('run');	
			
			//常用数据缓存
			cache_data('config');
			cache_data('dep');
			cache_data('site');
			cache_data('user');
			cache_data('hr_role');
			
			$data=$this->request->param();
			
			switch ($data['flag']){
				case 'auto_supper':
					//自动发送晚餐通知
					$this->auto_supper();
					echo 'send_ok';	
					break;
				case 'get_hr_rec':
					//抓取最新打卡记录
					if($data['site_flag']=='all'){
						get_card_hr_rec('sh');
						get_card_hr_rec('xa');
						get_card_hr_rec('sz');
						//get_card_hr_rec('tw');
					}else{
						get_card_hr_rec($data['site_flag']);
					}
					break;
				case 'calculate_hr':
					//计算本月考勤
					G('run');
					$mail_message="";
					$data_last_month=get_last_month();
					$date=get_date_time();
					
					db()->query('truncate table sw_sys_temp_log');
					
					if($data['site_flag']=='all'){
						$site_arr=db('sys_site')->select();
						foreach ($site_arr as $key=>$val){
							calculate_hr('2017-09-01',$val['id']);
							calculate_hr($data_last_month,$val['id']);
							calculate_hr($date,$val['id']);
						}						
					}else{
						calculate_hr('2017-09-01',$val['id']);
						calculate_hr($data_last_month,$data['site_id']);
						calculate_hr($date,$data['site_id']);
					}
					
					//月底&月初发送考勤异常通知邮件
					$this->send_hr_warn();				
					
					$mail_message .= '---SINO-------考勤计算用时'.G('run','run1').'----------<br>';
					send_email('hank.zhou@sinowealth.com.cn', date('Y-m-d',time()).'考勤计算(sino自动)!', $mail_message);
				case 'month_event':
					//月头处理事情
					
					break;
			}			
		}
	}
	
	//午餐邮件通知
	function auto_supper(){
		//判断本日是否假期
		$is_holiday=db('sys_holiday')->where(" holiday_date='".date('Y-m-d',time())."' and site_id=1")->find();
		if($is_holiday){
			exit;
		}
		
		//判断本日是否有晚餐
		$supper_arr=db('user_note_item')->where("note_type=2 and DATE_FORMAT(begin_time,'%Y-%m-%d')='".date('Y-m-d',time())."'")->select();
		$page_info['count']=count($supper_arr);
		$to_arr=array();
		$to_arr[0]=config('supper_mail')['qt'];
		$to_arr[1]='hank.zhou@sinowealth.com.cn';
			
		if($page_info['count']>0){
			$message=date('Y-m-d',time())."日".config('company_name_en_short')."晚餐预订情况:<br><br>
						<table width=500 border=1 bordercolor='#000000' style='border-collapse:collapse'>
							<tr>
								<td>用户</td>
								<td>晚餐</td>
								<td>代购?</td>
							</tr>";
			foreach ($supper_arr as $key=>$val){
				$supper_arr[$key]['nickname']=get_cache_data('user_info',$val['user_id'],'nickname');
				$message .= "<tr><td>".get_cache_data('user_info',$val['user_id'],'nickname')."</td><td>1</td>";
				if($val['note_type_2_flag']==1){
					$message .= "<td>是</td>";
				}else{
					$message .= "<td>否</td>";
				}
				$message .= "</tr>";
			}
	
			$message .= "</table><br>发送时间:".get_date_time();
		}else{
			$message=" <br><br>本日无晚餐预订!发送时间:".get_date_time();
		}
			
		$is_send_ok=send_email($to_arr, date('Y-m-d',time()).'日'.config('company_name_en_short').'晚餐预订(自动)!', $message);
			
		//邮件发送成功,写日志
		$log_arr=array();
		if($is_send_ok){
			$log_arr['log_type']=1;
			$log_arr['val']=date('Y-m-d',time()).'日'.config('company_name_en_short').'晚餐预订邮件通知,通知人员:'.implode(';', $to_arr);
			$log_arr['json']=json_encode($supper_arr);
			$log_arr['user_id']=get_user_id();
			$log_arr['user_id_str']='';
			$log_arr['create_time']=date('Y-m-d H:i:s',time());
			db('sys_log')->insert($log_arr);
		}
			
	}
	
	//月底考勤异常邮件通知
	function send_hr_warn(){
		//本月日期范围
		$month_range_local=get_begin_last_date();
		$cur_date=date('Y-m-d',time());	
		
		//上月日期范围
		$month_last_range=get_begin_last_date(get_last_month());

		//判断是否符合执行日期
		if($cur_date==$month_range_local[1] || (int)date('d',time())==5 || (int)date('d',time())==8){
			
			if($cur_date==$month_range_local[1]){
				$month_range=$month_range_local;
			}else{
				$month_range=$month_last_range;
			}
			
			//获取本月有旷职的用户
			$sql="select distinct(user_id) as user_id from sw_hr_table where hr_date between '".$month_range[0]." 00:00:00' and '".$month_range[1]." 23:59:59' and hr_status=0 and user_id in (select id from sw_sys_user where site_id in (1,2,3) and hr_status=1);";
			$user_arr=db()->query($sql);
				
			//构建需要通知的用户数据
			foreach ($user_arr as $k=>$v){
				$user_info=db('sys_user')->where('id='.$v['user_id'])->find();
				$user_arr[$k]['email']=$user_info['email'];
				$user_arr[$k]['nickname']=$user_info['nickname'];
				$sql="select hr_date,hr_status_remark from sw_hr_table where hr_date between '".$month_range[0]." 00:00:00' and '".$month_range[1]." 23:59:59' and hr_status=0 and user_id=".$v['user_id'];;
				$user_arr[$k]['data']=db()->query($sql);
			}
			
			//获取上月需要主管审核的申请单数据
			$sql="
				select
					distinct cur_user_id
				from
					sw_user_note
				where
					((begin_time between '".$month_range[0]." 00:00:00' and '".$month_range[1]." 23:59:59') or  (end_time between '".$month_range[0]." 00:00:00' and '".$month_range[1]." 23:59:59') ) and
				    note_type in (1,3) and status=1 and cur_user_id<>0 and isnull(cur_user_id)=false
				";
			$manage_arr=db()->query($sql);
			
			//构建需要通知的申请单审核数据
			foreach ($manage_arr as $k=>$v){
				$user_info=db('sys_user')->where('id='.$v['cur_user_id'])->find();
				$manage_arr[$k]['nickname']=$user_info['nickname'];
				$manage_arr[$k]['email']=$user_info['email'];
				$sql="
				select
					n.id,n.user_id,u.nickname,n.note_hour,n.hr_note_name,concat(begin_time,' ~ ',end_time) as note_time,n.note_title,n.note_desc
				from
					sw_user_note n,sw_sys_user u
				where
					n.user_id=u.id and
					((n.begin_time between '".$month_range[0]." 00:00:00' and '".$month_range[1]." 23:59:59') or  (n.end_time between '".$month_range[0]." 00:00:00' and '".$month_range[1]." 23:59:59') ) and
				    n.note_type in (1,3) and n.status=1 and n.cur_user_id<>0 and n.cur_user_id=".$v['cur_user_id']." and isnull(cur_user_id)=false";
					
				$manage_arr[$k]['data']=db()->query($sql);
			}
			
			if($cur_date==$month_range[1]){
				//通知用户本月有旷职日期
				foreach ($user_arr as $k=>$v){
					$message=date('Y-m',strtotime($month_rang[1]))."月 ".$v['nickname']." 有旷职情况,请及时登录系统处理.<br>
							系统登录地址:<a href='".config('SYS_URL')."'>".config('SYS_URL')."</a>
							<br><br>
						<table width='90%' border=1 bordercolor='#000000' style='border-collapse:collapse'>
							<tr>
								<td>日期</td>
								<td>考勤信息</td>
							</tr>";
					foreach ($v['data'] as $key=>$val){
						$message .= "<tr><td>".$val['hr_date']."</td><td>".$val['hr_status_remark']."</td></tr>";
					}					
					$message .= "</table><br>发送时间:".get_date_time();
					
					if($k>3){
						echo '通知完成!';
						exit;
					}
					
					send_email($v['email'], date('Y-m',strtotime($month_range[0])).'月 '.$v['nickname'].' 考勤异常通知(自动)!', $message);
				}
				
			}
			
			//判断是否为5号
			if((int)date('d',time())==5){
				//提醒上月有旷职的用户&上月有单据未审核的主管
				
				//上月有旷职的用户
				//通知用户本月有旷职日期
				foreach ($user_arr as $k=>$v){
					$message=date('Y-m',strtotime($month_range[1]))."月 ".$v['nickname']." 有旷职情况,请及时登录系统处理.<br>
							系统登录地址:<a href='".config('SYS_URL')."'>".config('SYS_URL')."</a>
							<br><br>
						<table width='90%' border=1 bordercolor='#000000' style='border-collapse:collapse'>
							<tr>
								<td>日期</td>
								<td>考勤信息</td>
							</tr>";
					foreach ($v['data'] as $key=>$val){
						$message .= "<tr><td>".$val['hr_date']."</td><td>".$val['hr_status_remark']."</td></tr>";
					}
					$message .= "</table><br>发送时间:".get_date_time();
						
					send_email($v['email'], date('Y-m',strtotime($month_range[0])).'月 '.$v['nickname'].'考勤异常通知(自动)!', $message);
				}

				//通知上月有单据未审核的主管
				foreach ($manage_arr as $k=>$v){
					$message=date('Y-m',strtotime($month_range[1]))."月    ".$v['nickname']." 有申请单未审核,请及时登录系统处理.<br>
							系统登录地址:<a href='".config('SYS_URL')."'>".config('SYS_URL')."</a>
							<br><br>
						<table width='90%' border=1 bordercolor='#000000' style='border-collapse:collapse'>
							<tr>
								<td>用户</td>
								<td>假单类型</td>
								<td>申请时间数</td>
								<td>申请时间范围</td>
								<td>表单标题</td>
								<td>申请单说明</td>
							</tr>";
					foreach ($v['data'] as $key=>$val){
						$message .= "<tr><td>".$val['nickname']."</td>
										 <td>".$val['hr_note_name']."</td>
										 <td>".$val['note_hour']."</td>
										 <td>".$val['note_time']."</td>
										 <td>".$val['note_title']."</td>
										 <td>".$val['note_desc']."</td>
									 </tr>";
					}
					$message .= "</table><br>发送时间:".get_date_time();
					send_email($v['email'], date('Y-m',strtotime($month_range[0])).'月  '.$v['nickname'].' 有申请单未审核(自动)!', $message);
				}
			}
			
			//判断是否为8号
			if((int)date('d',time())==8){
				//通知上月有单据未审核的主管
				foreach ($manage_arr as $k=>$v){
					$message=date('Y-m',strtotime($month_range[1]))."月    ".$v['nickname']." 有申请单未审核,请及时登录系统处理.<br>
							系统登录地址:<a href='".config('SYS_URL')."'>".config('SYS_URL')."</a>
							<br><br>
						<table width='90%' border=1 bordercolor='#000000' style='border-collapse:collapse'>
							<tr>
								<td>用户</td>
								<td>假单类型</td>
								<td>申请时间数</td>
								<td>申请时间范围</td>
								<td>表单标题</td>
								<td>申请单说明</td>
							</tr>";
					foreach ($v['data'] as $key=>$val){
						$message .= "<tr><td>".$val['nickname']."</td>
										 <td>".$val['hr_note_name']."</td>
										 <td>".$val['note_hour']."</td>
										 <td>".$val['note_time']."</td>
										 <td>".$val['note_title']."</td>
										 <td>".$val['note_desc']."</td>
									 </tr>";
					}
					$message .= "</table><br>发送时间:".get_date_time();
					send_email($v['email'], date('Y-m',strtotime($month_range[0])).'月  '.$v['nickname'].' 有申请单未审核(自动)!', $message);
				}
					
			}
		}
	}
	
	//月头事件通知
	function month_begin_event(){
		//本月日期范围
		$month_range_local=get_begin_last_date();
		$cur_date=date('Y-m-d',time());
		
		//转正提醒
		
		//生日提醒
		
		//年度调薪, 1: 生成本月份需要进行年度调薪的人员字符串  2: 生成对应调薪表格目录
		$sql="select * from sw_sys_user where date_format(entry_date,'%m')=date_format(now(),'%m') and hr_status=1 and date_format(entry_date,'%Y')<date_format(now(),'%Y') order by entry_date;";
		
		
	}
	
}
