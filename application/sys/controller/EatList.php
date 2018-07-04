<?php
namespace app\sys\controller;

use think\Controller;
use app\index\controller\Admin;
use think\console\command\make\Model;

class EatList extends Admin
{
	/**
	 * 用餐初始数据
	 *///2018/2/27 wtq
	public function index() {
		$page_info=array();
		db()->execute('truncate table sw_sys_eat;');		
		$data=$this->param;
		$where_str="";
		if(!isset($data['begin_date'])){
			$data['begin_date']=date('Y-m-d',time());
		}
		if(!isset($data['end_date'])){
			$data['end_date']=date('Y-m-d',time());
		}
		
		if(!isset($data['dep_id'])){
			$data['dep_id']='all';
		}else{
			if($data['dep_id'] != 'all'){
				$where_str .= " and u.dep_id=".$data['dep_id'].' ';
			}			
		}
		
		
		if(!isset($data['nickname'])){
			$data['nickname']='';
		}else{
			$where_str .= " and u.nickname like '%".$data['nickname']."%' ";
		}
		
			$sql1="
				insert into sw_sys_eat(user_id,entry_date,user_gh,nickname,bf,lunc,supper)
					select
						u.id,e.entry_date,u.user_gh,u.nickname,
			            sum(case when e.entry_dt between concat(e.entry_date,' ".config('hr_breakfast_begein')."') and concat(e.entry_date,' ".config('hr_breakfast_end')."') then 1 else 0 end) as bf,
			            sum(case when e.entry_dt between concat(e.entry_date,' ".config('hr_lunch_begin')."') and concat(e.entry_date,' ".config('hr_lunch_end')."') then 1 else 0 end) as lunc,
			            (select count(*) from sw_user_note_item i where i.begin_time=concat(e.entry_date,' 00:00:00') and i.note_type=2 and i.user_id=u.id) as supper
					from
						sw_entry_dt_sh e,sw_sys_user u
			        where
						e.emp_no=u.user_gh and e.ctrl_id=24 and e.entry_date between '".$data['begin_date']."' and '".$data['end_date']."' and u.status=1 and u.user_status=1 ".$where_str."
					group by
						e.entry_date,u.user_gh
			        order by entry_dt
				";
		
			db()->execute($sql1);
			
			//添加有定晚餐,没有吃午餐的记录,取出有晚餐的记录,如果这一天午餐有吃,则删出,否则添加到SW_SYS_EAT表
			$sql="
					SELECT 
						u.id as user_id,DATE_FORMAT(i.begin_time,'%Y-%m-%d') as entry_date,u.user_gh,u.nickname,0 as bf,0 as lunc,1  as supper
					FROM 
						sw_user_note_item i,sw_sys_user u 
					WHERE 
						i.user_id=u.id $where_str AND i.begin_time BETWEEN '".$data['begin_date']."' AND '".$data['end_date']."' AND i.note_type=2 
					";
			$eat_arr=db()->query($sql);
			foreach ($eat_arr as $key=>$val){
				$is_have=db('sys_eat')->where("entry_date='".$val['entry_date']."' and user_id=".$val['user_id'])->find();
				if(count($is_have)==0){
					db('sys_eat')->insert($val);
				}
			}			
				
		
			$sql2="
					select 
						sum(bf) as bf_count,sum(lunc) as lunc_count,sum(supper) as supper_count
					from
						sw_sys_eat
					";
			$count_arr=db()->query($sql2);
			$page_info['eat_count']=$count_arr[0];

			$name= "sys_eat";
			$model = db($name);
			
			if (! empty ( $model )) {
				//$page_info['list']=$model->where($map)->order('id desc')->paginate($paginate['list_rows']);
				$page_info['list']=$this->_list($model,'','id',false);
			}
			$page_info['page']=$page_info['list']->render();
			
			$param=$this->param;
			if(isset($param['page'])){
				$page_info['cur_page']=$param['page'];
			}else{
				$page_info['cur_page']=1;
			}
			
			if($page_info['list']->total()>0){
				$page_info['empty']=0;
			}else{
				$page_info['empty']=1;
			}
			
			$page_info['dep_arr']=db('sys_dep')->where('is_show=1 and status=1')->select();
			
			$page_info['begin_date']=$data['begin_date'];
			$page_info['end_date']=$data['end_date'];
			$page_info['dep_id']=$data['dep_id'];
			$page_info['nickname']=$data['nickname'];
			
			//判断本日晚餐是否已经通知
			$is_note_supper=db('sys_log')->where(" log_type=1 and date_format(create_time,'%Y-%m-%d')='".date('Y-m-d',time())."'")->find();
			if($is_note_supper){
				$page_info['supper_flag']=1;
			}else{
				$page_info['supper_flag']=0;
			}
		
		$this->assign('page_info',$page_info);
		return $this->fetch();
	}
	
	/**
	 * 查看本日晚餐列表
	 */
	function cur_day_supper_list(){
		$page_info=array();
		
		$supper_arr=db('user_note_item')->where("note_type=2 and DATE_FORMAT(begin_time,'%Y-%m-%d')='".date('Y-m-d',time())."'")->select();
		$page_info['count']=count($supper_arr);
		
		if(IS_POST){
			$data = $this->request->post();
			//send_email($user_email,'基础资料答复通知!',$message)
			
			$to_arr=array();
			$to_arr[0]=config('supper_mail')['qt'];
			if(isset($data['ad'])){
				$to_arr[1]=config('supper_mail')['ad'];
			}
			$to_arr[2]='hank.zhou@sinowealth.com.cn';
			
			$message=date('Y-m-d',time())."日芯颖晚餐预订情况:<br><br>
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
			
			$message .= "</table><br>发送时间:".date('Y-m-d H:i:s');
			
			
			$is_send_ok=send_email($to_arr, date('Y-m-d',time()).'日芯颖晚餐预订(手动)!', $message);
			
			//邮件发送成功,写日志
			$log_arr=array();
			if($is_send_ok){
				$log_arr['log_type']=1;
				$log_arr['val']=date('Y-m-d',time()).'日芯颖晚餐预订邮件通知,通知人员:'.implode(';', $to_arr);
				$log_arr['json']=json_encode($supper_arr);
				$log_arr['user_id']=get_user_id();
				$log_arr['user_id_str']='';
				$log_arr['create_time']=date('Y-m-d H:i:s',time());
				db('sys_log')->insert($log_arr);
				echo setServerBackJson(1,'通知邮件发送成功!','','closeDialog');
			}else{
				echo setServerBackJson(0,'通知邮件发送失败!');
			}
						
		}else{
		
			$page_info['supper_arr']=$supper_arr;
			
			//获取日志-->本日是否已经邮件通知前台

			//判断本日晚餐是否已经通知
			$is_note_supper=db('sys_log')->where(" log_type=1 and date_format(create_time,'%Y-%m-%d')='".date('Y-m-d',time())."'")->find();
			if($is_note_supper){
				$page_info['supper_flag']=1;
				$page_info['mail_info']='晚餐总计:'.$page_info['count'].'份<br>通知邮件已发,发送时间: '.$is_note_supper['create_time'];
			}else{
				$page_info['supper_flag']=0;
				if($page_info['count']>0){
					$page_info['mail_info']=' 总计: '.$page_info['count'].'份.';
				}else{
					$page_info['mail_info']=' 无晚餐申请单数据.';
				}				
			}
			
			//晚餐统计数为0,已经通知过前台的话,不显示邮件通知发送按钮
			if($page_info['count']>0){
				if($page_info['supper_flag']==1){
					$page_info['mail_send_flag']=0;
				}else{
					$page_info['mail_send_flag']=1;
				}
			}else{
				$page_info['mail_send_flag']=0;
			}
			
			$page_info['cur_date']=date('Y-m-d',time());
			$this->assign('page_info',$page_info);
			return $this->fetch();
		}
		
	}
}











