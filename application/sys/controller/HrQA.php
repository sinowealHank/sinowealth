<?php
namespace app\sys\controller;

use think\Controller;
use app\index\controller\Admin;
use think\console\command\make\Model;

class HrQA extends Admin
{
	/**
	 * 用户基础数据提交修改意见
	 */
	public function index() {
		$page_info=array();
		$data=$this->param;

		if(! isset($data['hr_is_a'])){
			$page_info['hr_is_a']='all';
		}else{
			$page_info['hr_is_a']=$data['hr_is_a'];
		}
		if(! isset($data['hr_is_t'])){
			$page_info['hr_is_t']='all';
		}else{
			$page_info['hr_is_t']=$data['hr_is_t'];
		}
		
		$name= "q_a";
		$model = db($name);
		
		switch($page_info['hr_is_a']){
			case 'all':
				$map=" user_id in (select user_id from sw_sys_user u where site_id in (".$_SESSION['u_i']['cal_site_id_str']."))";
				break;
			case 'q':
				$map="isnull(a_time)=true and user_id in (select user_id from sw_sys_user u where site_id in (".$_SESSION['u_i']['cal_site_id_str']."))";
				break;
			case 'a':
				$map="isnull(a_time)=false and user_id in (select user_id from sw_sys_user u where site_id in (".$_SESSION['u_i']['cal_site_id_str']."))";
				break;
		}
		
		switch($page_info['hr_is_t']){
			case 'all':
				break;
			case '1':
				$map="q_type=1 and ".$map;
				break;
			case '2':
				$map="q_type=2 and ".$map;
				break;
		}
		if (! empty ( $model )) {
			//$page_info['list']=$model->where($map)->order('id desc')->paginate($paginate['list_rows']);
			$page_info['list']=$this->_list($model,$map,'id',false);
		}
		$page_info['page']=$page_info['list']->render();

		if(isset($param['page'])){
			$page_info['cur_page']=$data['page'];
		}else{
			$page_info['cur_page']=1;
		}
		
		if($page_info['list']->total()>0){
			$page_info['empty']=0;
		}else{
			$page_info['empty']=1;
		}
		
		$this->assign('page_info',$page_info);
		return $this->fetch();
	}
	
	/**
	 * 更新QA
	 */
	function qa_a(){
		$page_info=array();
		if(IS_POST){
			$data = $this->request->post();
			$qa_info=db('q_a')->where('id='.$data['id'])->find();
			
			$data['a_time']=date('Y-m-d H:i:s',time());						
			
			if($data['mail_flag']){
				//send_email
				
				//判断用户是否有邮箱地址
				$user_email=get_cache_data('user_info',$qa_info['user_id'],'email');
				if(strlen($user_email)==0){
					$temp_str="用户邮箱获取失败,通知邮件发送失败!";
				}else{
					$message='你提交的问题<br>'.$qa_info['q_val']."<br/><br/>".
					 	 '管理部回复如下:<br><br>'.$data['a_val'].'<br/><br/>'.
						 '答复时间:'.date('Y-m-d H:i:s')
						;
					
					if(send_email($user_email,'基础资料答复通知!',$message)){
						$data['mail_send_flag']=1;
						$temp_str="回复邮件发送成功!";
					}else{
						$data['mail_send_flag']=0;
						$temp_str="回复邮件发送失败!";
					}					
				}				
			}else{
				$temp_str="";
			}

			if(db('q_a')->update($data)){
				echo setServerBackJson(1,'回复用户成功!'.$temp_str);
			}
			
		}else{
			$data=$this->param;
			$page_info['qa_info']=db('q_a')->where('id='.$data['id'])->find();
			$page_info=$this->deta_data($page_info);//王天棋2017.10.26
			$this->assign('page_info',$page_info);
			return $this->fetch();
		}
	}
	
	/*
	 * 查看反馈的问题
	 */
	function view_qa(){
		$data=$this->param;
		$page_info['qa_info']=db('q_a')->where('id='.$data['id'])->find();
		
		if(strlen($page_info['qa_info']['a_val'])==0){
			$page_info['qa_info']['a_val'] = '暂未答复!';
		}		
		$page_info=$this->deta_data($page_info);//王天棋2017.10.26
		$this->assign('page_info',$page_info);
		return $this->fetch();
	}
	/**
	 * 王天棋2017.10.26
	 * @param unknown $page_info
	 * @return \think\db\mixed
	 */
	public function deta_data($page_info){
		$note_begin_time=$page_info['qa_info']['q_time'];
		
		if(strtotime($note_begin_time)){
			$page_info['pd_time']=$note_begin_time;
			////获取申请人id
			$note_user_id=$page_info['qa_info']['user_id'];
			$ctrl_id='23,2,31';
			$sql="SELECT *,
						(case when ctrl_id=24 then '食堂' when ctrl_id=2 then '车库' ELSE '前台' end) as card_site,
						(case when status=1 then '有效考勤打卡' ELSE '无效考勤打卡' end) as card_status,
						(select hr_status_remark from sw_hr_table where user_id='".$note_user_id."' and hr_date =en.entry_date) as hr_status_remark
				FROM (
					select 
							*,'1' as type
						from 
							sw_entry_dt_sh as sh where emp_no='".config('user_info')[$note_user_id]['user_gh']."'
						union all
						select 
							*,'2' as type
						from 
							sw_entry_dt_sz as sz where emp_no='".config('user_info')[$note_user_id]['user_gh']."'
						union all
						select 
							*,'7' as type
						from 
							sw_entry_dt_tw as tw where emp_no='".config('user_info')[$note_user_id]['user_gh']."'
						union all
						select 
							*,'3' as type
						from 
							sw_entry_dt_xa as x where emp_no='".config('user_info')[$note_user_id]['user_gh']."'
					) as en where ctrl_id in (".$ctrl_id.") and status=1 and entry_date='".$note_begin_time."' ORDER BY entry_date,entry_dt";
			$card=db()->query($sql);
			if ($card){
				$site_id=$card[0]['type'];
				
				$card_num=count($card);
				if($site_id==1){
					if($card_num>3){
						$page_info['card_time']=get_user_work_time_more(config('user_info')[$page_info['qa_info']['user_id']]['user_gh'],$note_begin_time,$site_id)['s'];
					}
				}
				if(!isset($page_info['card_time'])){
					$page_info['card_time']['h']=date('H', strtotime(end($card)['entry_dt']))-date('H', strtotime($card[0]['entry_dt']));
					$page_info['card_time']['m']=date('i', strtotime(end($card)['entry_dt']))-date('i', strtotime($card[0]['entry_dt']));
					$page_info['card_time']['s']=date('s', strtotime(end($card)['entry_dt']))-date('s', strtotime($card[0]['entry_dt']));
					$page_info['card_time']=$page_info['card_time']['h']*60*60+$page_info['card_time']['m']*60+$page_info['card_time']['s'];
				}
				$page_info['ok_time']=get_user_work_time($site_id,config('user_info')[$page_info['qa_info']['user_id']]['user_gh'],$note_begin_time);
			}else{
				$page_info['card_time']=0;
				$page_info['ok_time']=8*60*60;
			}
			
			
			
			
			
			
			$page_info['user_id']=get_user_id();
			$page_info['note_info']=db('user_note')->where('user_id='.$page_info['qa_info']['user_id'].' and begin_time<="'.$note_begin_time.' 23:59:59"'.' and end_time>="'.$note_begin_time.'"')->select();
			
			
			
			//根据当前时间判断是否有考勤记录
			if(date("Y-m-d")>=$note_begin_time){
				//获取结束时间
				$note_end_time=$note_begin_time;
				
				//获取申请人数据
				$note_user=config("user_info")[$note_user_id];
			
			
				//判断获取打卡记录的时间范围
				if(date("Y-m-d")<=$note_end_time){
					$note_end_time=date("Y-m-d");
				}
				//判断是否出差
				if($note_user['out_site_id']==0){
					$sql_site_id='site_id';
				}else{
					$sql_site_id='out_site_id';
				}
				//获取申请人站点简写
				$page_info['site_arr']=db('sys_site')->where('id='.$note_user[$sql_site_id])->select();
				//获取打卡记录
				/*$ctrl_id='23,2,31';//抓取的门卡id
				$sql="select *,
						(case when ctrl_id=23 then '前台' when ctrl_id=2 then '车库' ELSE '前台' end) as card_site,
						(case when status=1 then '有效考勤打卡' ELSE '无效考勤打卡' end) as card_status,
						(select hr_status_remark from sw_hr_table where user_id='".$note_user_id."' and hr_date =s.entry_date) as hr_status_remark
							FROM
						sw_entry_dt_".strtolower($page_info['site_arr'][0]['site_code'])." s
							where
						emp_no='".$note_user['user_gh']."' and entry_date between '".$note_begin_time."' and '".$note_end_time."' and ctrl_id in (".$ctrl_id.") and status=1;";
				$page_info['card_info']=db()->query($sql);*/
				$page_info['card_info']=$card;
				if($note_user['site_id']==1){
						
				}else{
					$s_entry_date='';
					foreach ($page_info['card_info'] as $key=>$s){
						$a_card_info=isset($page_info['card_info'][$key+1]['entry_date'])?$page_info['card_info'][$key+1]['entry_date']:'';
						if($a_card_info==$s_entry_date){
							unset($page_info['card_info'][$key]);
						}else{
								
						}
						$s_entry_date=$s['entry_date'];
					}
				}
			}else{
				$page_info['card_info']='';
			}
		}else{
			$page_info['pd_time']='';
		}
		return $page_info;
	}
	
}











