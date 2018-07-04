<?php
namespace app\user\controller;

use think\Controller;
use app\index\controller\Admin;

//申请单审核
class NoteCheck extends Admin
{
	/**
	 * 列出当前需要审核的单
	 *///王天棋2017.9.27修改  2018/2/27 wtq
	public function index() {
		$data=$this->param;
		$page_info=array();
		//列表过滤器，生成查询Map对象
		$map = $this->_search ('user_note');		
		
		if (method_exists ( $this, '_filter' )) {
			$this->_filter ( $map );
		}
		
		/*过滤条件
		 * 1: 当前用户管理员工
		 * 2: 代理人
		 * 3: 转上级申请单
		 * 4: 审核流程中的单据
		*/
		$user_id=get_user_id();
		
		//获取当前用户管理用户user_id
		$manage_user_id_str=session('manage_user_id_str');
		if($_SESSION['role_id']==2){
			//$manage_user_id_arr=db()->query("select group_concat(id) as id_str from sw_sys_user where  hr_status in (1,3) and status=1 and user_status=1");
			$manage_user_id_str=get_user_sub_user($user_id);
		}

		if(!isset($data['show_flag'])){
			$data['show_flag']="show_cur";
		}
		
		//默认显示当前需要处理的申请单
		if($data['show_flag'] != 'show_all'){
			$map['cur_user_id']=array('eq',$user_id);
		}else{
			$map['hr_user_id']=array('exp',"=".$user_id." or (user_id in (".$manage_user_id_str.")) or (hr_adv_user_id=".$user_id." and note_hour>=".(config('hr_holiday_manage_val')*8).")");
			
		}
				
		//$map['cur_user_id']=$user_id;
		$map['status']=1;
		
		$name= "user_note";
		$model = db($name);
		
		
		$map['status']=1;
		
		//排序
		$sort_name=isset($_GET['ii'])?$_GET['ii']:'id';
		$zorf=isset($_GET['a'])?$_GET['a']:1;
		$page_info['ii']=array($sort_name,$zorf);
		if($zorf==1){$zorf=false;}else{$zorf=true;}
		
		//默认查询当月1号到当前日期
		$month_arr=get_begin_last_date();
		//限制日期控件最大最小值
		$page_info['date_min']='2017-01-01';
		$page_info['date_max']=date('Y-m-d',time());
		
		$page_info['begin_date']=$month_arr[0];
		$page_info['end_date']=$month_arr[1];
		//时间查询
		$page_info['begin_date']=isset($_POST['begin_date'])?$_POST['begin_date']:'0000-00-00';
		if($page_info['begin_date']){date('Y-m-d',strtotime($page_info['begin_date']." -1 day"));}
		//$page_info['end_date']=isset($_POST['end_date'])?$_POST['end_date']:date('Y-m-d',time());
		$page_info['end_date']=isset($_POST['end_date'])?$_POST['end_date']:'9999-12-12';
		if($page_info['begin_date']==''){$page_info['begin_date']="0000-00-00";}
		if($page_info['end_date']==''){$page_info['end_date']=date('Y-m-d',time());}
		$map['exp_logic']='and';
		$map['exp']="c_time between '".$page_info['begin_date']."' and '".$page_info['end_date']."'";
		if($page_info['begin_date']=="0000-00-00"){$page_info['begin_date']='';}
		//$map['c_time']="2017-08-30 15:03:31";
		if (! empty ( $model )) {
			$page_info['list']=$this->_list($model,$map,$sort_name,$zorf);
		}
		
		$page_info['page']=$page_info['list']->render();
		
		$page_info['user_id']=$user_id;
		if($page_info['end_date']=='9999-12-12'){$page_info['end_date']='';}
		if(isset($_GET['excel'])){
			$this->excel_out($page_info['list'],$page_info['user_id']);
			exit;
		}
		$page_info['row_total']=$page_info['list']->total();
		$page_info['show_flag']=$data['show_flag'];
		
		
		
		
		//搜索条件合集，站点，部门，人员
		//页面数据准备
		$page_info['site_arr']=db('sys_site')->where('status=1 and id in (1,2,3)')->select();
		if(!isset($data['site_id'])){
			$site_id=1;
		}else{
			$site_id=$data['site_id'];
		}
		$page_info['site_id']=$site_id;
		//准备部门数据
		$dep_db_arr=db('sys_dep')->where('status=1 and is_show=1')->field('id,pid,en_name,par_id')->order('par_id')->select();
		$page_info['dep_select']=get_dep_select($dep_db_arr,'filter','dep_id','','');
		//部门
		if(isset($data['user_id'])){
			$page_info['user_idd']=$data['user_id'];
		}else{
			$page_info['user_idd']='all';
		}
		//部门
		if(isset($data['dep_id'])){
			$page_info['dep_id']=$data['dep_id'];
		}else{
			$page_info['dep_id']='all';
		}
		if(get_cache_data('user_info',$user_id,'all_card_flag')==0){
			//用户
			$manage_user_id=get_user_sub_user($user_id);
			$page_info['user_arr']=db('sys_user')->where('hr_status in (1,3) and id in('.$manage_user_id.') and user_status=1 and site_id in (1,2,3)')->field('id,dep_id,nickname,site_id')->select();
		}else{
			$page_info['user_arr']=db('sys_user')->where('hr_status in (1,3) and user_status=1 and site_id in (1,2,3)')->field('id,dep_id,nickname,site_id')->select();
		}
		
		
		
		
		
		$this->assign('page_info',$page_info);
		return $this->fetch();
		
	}
	
	/*
	 * 审核申请单界面显示
	 * 王天棋2017.10.20更新
	 */
	public function check_note(){
				
		$data=$this->param;
		
		$page_info=array();
		$page_info['note_info']=db('user_note')->where('id='.$data['id'])->find();
		
		$page_info['user_id']=get_user_id();
		$page_info['flag']=$data['flag'];
		
		if($page_info['note_info']['note_hour']>get_adv_manage_h()){
			if($page_info['note_info']['hr_adv_user_id']==$page_info['user_id']){
				$page_info['adv_manage_flag']=0;
			}else{
				$page_info['adv_manage_flag']=1;
			}
			
		}else{
			$page_info['adv_manage_flag']=0;
		}
		
		//判断用户是否还允许对此单据操作
		if($page_info['note_info']['cur_user_id'] != $page_info['user_id']){
			$page_info['flag']='note_view';
		}
		
		//如果为晚到单,则抓取此用户申请日期第一次打卡时间
		if($page_info['note_info']['note_type']==3){
			$page_info['card_first']=db('hr_table')->where('user_id='.$page_info['note_info']['user_id']." and hr_date ='".date('Y-m-d',strtotime($page_info['note_info']['begin_time']))."'")->value('hr_card_first');
		}
		
		//更新
		$pd_arr=array(1,3);
		//判断是否是需要列出来的申请单
		if(in_array($page_info['note_info']['note_type'],$pd_arr)){
			//获取开始时间转换成Y-M-D格式方便比较
			$note_begin_time=date('Y-m-d',strtotime($page_info['note_info']['begin_time']));
			//根据当前时间判断是否有考勤记录
			if(date("Y-m-d")>=$note_begin_time){
				//获取结束时间
				$note_end_time=date('Y-m-d',strtotime($page_info['note_info']['end_time']));
				//获取申请人id
				$note_user_id=$page_info['note_info']['user_id'];
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
				$ctrl_id='23,2,31';//抓取的门卡id
				$sql="select *,
					(case when ctrl_id=23 then '前台' when ctrl_id=2 then '车库' ELSE '前台' end) as card_site,
					(case when status=1 then '有效考勤打卡' ELSE '无效考勤打卡' end) as card_status,
					(select hr_status_remark from sw_hr_table where user_id='".$note_user_id."' and hr_date =s.entry_date) as hr_status_remark
						FROM 
					sw_entry_dt_".strtolower($page_info['site_arr'][0]['site_code'])." s 
						where 
					emp_no='".$note_user['user_gh']."' and entry_date between '".$note_begin_time."' and '".$note_end_time."' and ctrl_id in (".$ctrl_id.") and status=1;";
				$page_info['card_info']=db()->query($sql);
				
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
			}
		}
		
		
		
		
		
		$this->assign('page_info',$page_info);
		return $this->fetch();
	}
	
	/*
	 * 提交审核结果
	 */
	function update_check_note(){
		$data=$this->param;
		
		$note_info=db('user_note')->where('id='.$data['id'])->find();
		$update_arr['id']=$data['id'];
		$dep_id=get_cache_data('user_info',$note_info['user_id'],'dep_id');
		
		//判断当前申请单流程状态
		switch ($note_info['note_step']){
			case 1:
				if($data['check_val']==0){
					$update_arr['note_step']=4;
					$update_arr['age_check_status']=0;
					$update_arr['age_check_remark']=$data['remark'];
					$update_arr['cur_user_id']=0;
					$update_arr['note_check_status']=0;
					$update_arr['note_log']=$note_info['note_log'].'<br />['.date('Y-m-d H:i:s',time()).'] 代理人'.get_user_nickname()."审核不通过,申请单流程结束!";
					
					//邮件通知申请人代理人审核不通过
					send_email(get_cache_data('user_info',$note_info['user_id'],'email'), '内管代理审核通知--不通过', $update_arr['note_log'],'','',1);
					
				}else{
					$update_arr['note_step']=2;
					$update_arr['age_check_status']=1;
					$update_arr['age_check_remark']=$data['remark'];
					$update_arr['cur_user_id']=$note_info['hr_user_id'];
					$update_arr['note_log']= $note_info['note_log'].'<br />['.date('Y-m-d H:i:s',time()).'] 代理人'.get_user_nickname()."审核通过,下一个流程,考勤主管审核!";
					
					//邮件通知申请人代理人审核通过
					send_email(get_cache_data('user_info',$note_info['user_id'],'email'), '内管代理审核通知--通过', $update_arr['note_log']);
				}
				break;
			case 2:
				if($data['check_val']==0){
					//主管审核不通过
					$update_arr['note_step']=4;
					$update_arr['hr_check_status']=0;
					$update_arr['hr_check_remark']=$data['remark'];
					$update_arr['cur_user_id']=0;
					$update_arr['note_check_status']=0;
					$update_arr['note_log']=$note_info['note_log'].'<br />['.date('Y-m-d H:i:s',time()).'] 考勤主管'.get_user_nickname()."审核不通过,申请单流程结束!";
				}else{
					
					//判断是否晚到申请单
					if($note_info['note_type']==3){
						
						$update_arr['note_step']=4;
						$update_arr['hr_check_status']=1;
						$update_arr['hr_check_remark']=$data['remark'];
						$update_arr['cur_user_id']=0;
							
						//拆单到item表
						$item_arr=array();
						$item_arr['note_id']=$note_info['id'];
						$item_arr['user_id']=$note_info['user_id'];
						$item_arr['note_type']=$note_info['note_type'];
						$item_arr['note_type_2_flag']=$note_info['note_type_2_flag'];
						$item_arr['hr_note_id']=$note_info['hr_note_id'];
						$item_arr['begin_time']=$note_info['begin_time'];
						$item_arr['end_time']=$note_info['end_time'];
						
						db('user_note_item')->insert($item_arr);
							
						$update_arr['note_log']=$note_info['note_log'].'<br />['.date('Y-m-d H:i:s',time()).'] 考勤主管'.get_user_nickname()."审核通过,意见:".$data['remark'].",申请单生效,流程结束!";
						
						
					}else{						
						//如果申请时间小于转上级主管数,则流程结束,直接拆单,插入note_item
						if($note_info['note_hour']<=config('hr_holiday_adv_manage_val')*8){
							
							//如果是MCU的,大于一天,小于3天,需要走到  mcu_3_day
							$par_id=get_cache_data('dep_info', $dep_id,'par_id');
							if(substr($par_id, 0,6)=="1,6,8," && $note_info['note_hour']>8 && $note_info['note_hour']<=24 && $note_info['cur_user_id']<>config('MCU_3_DAY')){
								$hr_user_id=get_cache_data('user_info', $note_info['cur_user_id'],'hr_user_id');
								$update_arr['note_step']=3;
								$update_arr['hr_check_status']=1;
								$update_arr['hr_check_remark']=$data['remark'];
								$update_arr['cur_user_id']=$hr_user_id;
								$update_arr['note_log']=$note_info['note_log'].'<br />['.date('Y-m-d H:i:s',time()).'] 考勤主管'.get_user_nickname($note_info['cur_user_id'])."审核通过,下一个流程,考勤上级主管审核!";
							}else{
								$update_arr['note_step']=4;
								$update_arr['hr_check_status']=1;
								$update_arr['hr_check_remark']=$data['remark'];
								$update_arr['cur_user_id']=0;
									
								//拆单
								$this->split_note($note_info['id']);
									
								$update_arr['note_log']=$note_info['note_log'].'<br />['.date('Y-m-d H:i:s',time()).'] 考勤主管'.get_user_nickname($note_info['cur_user_id'])."审核通过,申请单生效,流程结束!";								
							}				
												
						}else{
							
							//如果走到了总经理室,则不往上走
							if($note_info['cur_user_id']==config('manage_id')){
								$update_arr['note_step']=4;
								$update_arr['hr_adv_check_status']=1;
								$update_arr['hr_adv_check_remark']=$data['remark'];
								$update_arr['cur_user_id']=0;
									
								//拆单
								$this->split_note($note_info['id']);
								$update_arr['note_log']=$note_info['note_log'].'<br />['.date('Y-m-d H:i:s',time()).'] 考勤上级主管'.get_user_nickname()."审核通过,申请单生效,流程结束!";
								
							}else{
								$update_arr['note_step']=3;
								$update_arr['hr_check_status']=1;
								$update_arr['hr_check_remark']=$data['remark'];
								$update_arr['cur_user_id']=$note_info['hr_adv_user_id'];
								$update_arr['note_log']=$note_info['note_log'].'<br />['.date('Y-m-d H:i:s',time()).'] 考勤主管'.get_user_nickname()."审核通过,下一个流程,考勤上级主管".get_user_nickname($note_info['hr_adv_user_id'])."审核!";
							}
						}
					}			
					
				}
				break;
			case 3:
				if($data['check_val']==0){
					$update_arr['note_step']=4;
					$update_arr['hr_adv_check_status']=0;
					$update_arr['hr_adv_check_remark']=$data['remark'];
					$update_arr['cur_user_id']=0;
					$update_arr['note_check_status']=0;
					$update_arr['note_log']=$note_info['note_log'].'<br />['.date('Y-m-d H:i:s',time()).'] 考勤上级主管'.get_user_nickname()."审核不通过,申请单流程结束!";
				}else{				

					//如果当前用户属于MCU(抓部门par_id,  1,6,8 路径) ,且时间数大于3天,则继续走流程3,走到Simpson
					//抓取当前用户的部门par_id
					$par_id=get_cache_data('dep_info', $dep_id,'par_id');
					//MCU事业部,2~3天,走到config('MCU_3_DAY')
					if(substr($par_id, 0,6)=="1,6,8," && $note_info['note_hour']>=16 && $note_info['note_hour']<=24 && $note_info['cur_user_id']<>config('MCU_3_DAY')){
						$update_arr['note_step']=3;
						$update_arr['hr_check_status']=1;
						$update_arr['hr_check_remark']=$data['remark'];
						$update_arr['cur_user_id']=config('MCU_3_DAY');
						$update_arr['note_log']=$note_info['note_log'].'<br />['.date('Y-m-d H:i:s',time()).'] 考勤主管'.get_user_nickname()."审核通过,下一个流程,考勤上级主管".get_user_nickname(config('MCU_3_DAY'))."审核!";
					}else{						
						//非MCU,大于3天的单走到manage_id
						if($note_info['note_hour']>24 && $note_info['cur_user_id']<>config('manage_id')){
							
							//查找当前审核人的上级
							$hr_user_id=get_cache_data('user_info', $note_info['cur_user_id'],'hr_user_id');							
							$update_arr['note_step']=3;
							$update_arr['hr_check_status']=1;
							$update_arr['hr_check_remark']=$data['remark'];
							$update_arr['cur_user_id']=$hr_user_id;
							$update_arr['note_log']=$note_info['note_log'].'<br />['.date('Y-m-d H:i:s',time()).'] 考勤主管'.get_user_nickname($note_info['cur_user_id'])."审核通过,下一个流程,上级考勤主管".get_user_nickname($hr_user_id)."审核!";
						}else{
							$update_arr['note_step']=4;
							$update_arr['hr_adv_check_status']=1;
							$update_arr['hr_adv_check_remark']=$data['remark'];
							$update_arr['cur_user_id']=0;
							
							//拆单
							$this->split_note($note_info['id']);
								if($note_info['cur_user_id']==config('mange_id')){
									$update_arr['note_log']=$note_info['note_log'].'<br />['.date('Y-m-d H:i:s',time()).'] 总经理室'.get_user_nickname(config('manage_id'))."审核通过,申请单生效,流程结束!";
								}else{
									$update_arr['note_log']=$note_info['note_log'].'<br />['.date('Y-m-d H:i:s',time()).'] 考勤上级主管'.get_user_nickname()."审核通过,申请单生效,流程结束!";
								}
							}
						}					
					}
		}
		
		db('user_note')->update($update_arr);
		exit(json_encode(array(1,'数据更新成功!')));
		echo setServerBackJson(1, '数据更新成功!',url('/user/Note_check/index/show_flag/show_cur'),'closeDialog');
	}

	

	/*
	 * 拆单动作
	 */
	function split_note($id){
		//获取用户请假单信息
		$note_info=db('user_note')->where('id='.$id)->find();
	
		//获取用户信息
		$note_user_info=db('sys_user')->where('id='.$note_info['user_id'])->find();
		//获取用户上班次信息
		$hr_role_info=config('hr_role_info')[$note_user_info['hr_role_id']];
	
		$note_begin_date=date('Y-m-d',strtotime($note_info['begin_time']));
		
		//计算用户申请单开始日出勤时间
		$user_work_arr=get_user_work_time_more($note_user_info['user_gh'],$note_begin_date,$note_user_info['site_id']);
		
		$user_work_time=int_time($user_work_arr['s'],'sub');
		
		//判断该用户申请单开始日考勤是按照9小时算还是8小时算
		$user_need_work_time_s=get_user_work_time($note_user_info['site_id'],$note_user_info['user_gh'],$note_begin_date);
		if($user_need_work_time_s/(60*60)>8){
			$user_need_work_time=9;
		}else{
			$user_need_work_time=8;
		}
	
		$note_hour=$note_info['note_hour'];
	
		$note_item_arr=array();
		$note_item_arr['note_id']=$note_info['id'];
		$note_item_arr['user_id']=$note_info['user_id'];
		$note_item_arr['note_type']=$note_info['note_type'];
		$note_item_arr['note_type_2_flag']=$note_info['note_type_2_flag'];
		$note_item_arr['hr_note_id']=$note_info['hr_note_id'];
	
		//获取假单日期范围内有那些天
		$date_range_temp=date_rang(date('Y-m-d',strtotime($note_info['begin_time'])),date('Y-m-d',strtotime($note_info['end_time'])));
	
		//如果请假时间小于=8小时,且为事后补单,则直接根据当日考勤计算所缺时间拆单
		if($note_info['note_hour']<=8 && (date('Y-m-d',strtotime($note_info['begin_time'])) == date('Y-m-d',strtotime($note_info['end_time']))) ){
				
			//按照申请单申请时间拆单
			$note_item_arr['note_hour']=$note_info['note_hour'];
			$note_item_arr['begin_time']=$note_info['begin_time'];
			$note_item_arr['end_time']=$note_info['end_time'];
				
			//拆单数据异常通知
			if(strlen($note_item_arr['note_hour'])==0 || $note_item_arr['note_hour']==0){
				send_email('hank.zhou@sinowealth.com.cn',config('company_name_en_short').'--请假单拆单异常!', '主单号['.$note_item_arr['note_id'].'],申请人工号:'.get_cache_data('user_info', $note_info['user_id'],'user_gh').',审核人工号:'.get_cache_data('user_info', get_user_id(),'user_gh'));
			}
				
			db('user_note_item')->insert($note_item_arr);
				
			/*
				//获取当日还差多少工作时
				$hr_table_info=db('hr_table')->where('user_id='.$note_info['user_id']." and hr_date='".date('Y-m-d',strtotime($note_info['begin_time']))."'")->find();
				if($note_info['note_hour']>=$hr_table_info['z_work_need_time']){
	
				$note_item_arr['note_hour']=$hr_table_info['z_work_need_time'];
	
				//非考勤人员,按照申请单时间直接拆单
				if($note_user_info['is_hr_user']==0){
				$note_item_arr['note_hour']=$note_info['note_hour'];
				}
	
				$note_item_arr['begin_time']=$note_info['begin_time'];
				$note_item_arr['end_time']=$note_info['end_time'];
				db('user_note_item')->insert($note_item_arr);
				}else{
				$note_item_arr['note_hour']=$note_info['note_hour'];
				$note_item_arr['begin_time']=$note_info['begin_time'];
				$note_item_arr['end_time']=$note_info['end_time'];
				db('user_note_item')->insert($note_item_arr);
				}
			*/
		}else{
				
			if($user_work_time<$user_need_work_time && $user_work_time>=1){
				//请假单第一天有出勤
				$note_item_arr['note_hour']=$user_need_work_time-$user_work_time;
				
				//echo $user_need_work_time.'--'.$user_work_time.'--'.$note_item_arr['note_hour'].'<br>';
				
				
				$note_item_arr['begin_time']=$note_info['begin_time'];
				$note_item_arr['end_time']=date('Y-m-d',strtotime($note_info['begin_time'])).' '.$hr_role_info['end_time'];
				
				//申请单剩余时间=当日需计算工作时间(8&9)-当日已出勤时间
				$note_hour = $note_hour-$note_item_arr['note_hour'];
	
				//拆单数据异常通知
				if(strlen($note_item_arr['note_hour'])==0 || $note_item_arr['note_hour']==0){
					send_email('hank.zhou@sinowealth.com.cn',config('company_name_en_short').'--请假单拆单异常!', '主单号['.$note_item_arr['note_id'].'],申请人工号:'.$note_user_info['user_gh'].',审核人工号:'.get_cache_data('user_info', $note_user_info['hr_user_id'],'user_gh'));
				}
				
				db('user_note_item')->insert($note_item_arr);
				unset($date_range_temp[0]);	//如果第一天补时差,则清理第一天
			}
				
			$date_range=array();
			//构建数组,假日数组数据完善
			//构建日期数据,如果为假期,则工作时间算0,如果为工作日,则工作时间算8小时
			foreach ($date_range_temp as $key=>$val){
				$date_range[$key]['day']=$val;
				$is_holiday=db('sys_holiday')->where("holiday_date='".$val."' and site_id=".$note_user_info['site_id'])->find();
				if($is_holiday){
					$date_range[$key]['is_holiday']=1;
					$date_range[$key]['note_hour']=0;
				}else{
					//echo $note_hour.'--<br>';
					$date_range[$key]['is_holiday']=0;
					if($note_hour>8){
						$date_range[$key]['note_hour']=8;
						$note_hour = $note_hour-8;
					}else{
						if($note_hour>0){
							$date_range[$key]['note_hour']=$note_hour;
							$note_hour=0;
						}else{
							$date_range[$key]['note_hour']=0;
						}
					}
				}
			}
			//pr($date_range);
	
			foreach ($date_range as $key=>$val){
				if($val['is_holiday']==0){
					$note_item_arr['note_hour']=$val['note_hour'];
					$note_item_arr['begin_time']=$val['day'].' '.$hr_role_info['begin_time'];
					$note_item_arr['end_time']=$val['day'].' '.$hr_role_info['end_time'];
						
					//拆单数据异常通知
					if(strlen($note_item_arr['note_hour'])==0 || $note_item_arr['note_hour']==0){
						send_email('hank.zhou@sinowealth.com.cn',config('company_name_en_short').'--请假单拆单异常!', '主单号['.$note_item_arr['note_id'].'],申请人工号:'.get_cache_data('user_info', $note_info['user_id'],'user_gh').',审核人工号:'.get_cache_data('user_info', get_user_id(),'user_gh'));
					}
						
					db('user_note_item')->insert($note_item_arr);
				}
			}
				
		}
	}
	
	
	
	
	
	
	
	
	
	
	
	/*
	 * 拆单动作_备份(按照本日用户考勤,离下班还有多少时间的算法)
	 */
	function split_note_back($id){
		//获取用户请假单信息
		$note_info=db('user_note')->where('id='.$id)->find();
		
		//获取用户信息
		$note_user_info=db('sys_user')->where('id='.$note_info['user_id'])->find();
		//获取用户上班次信息
		$hr_role_info=config('hr_role_info')[$note_user_info['hr_role_id']];
		
		$note_begin_date=date('Y-m-d',strtotime($note_info['begin_time']));
		//计算请假开始日期离本班次下班时间差多少时间
		$time_firstD_diff=timediff($note_info['begin_time'], $note_begin_date.' '.$hr_role_info['end_time']);
		
		$note_hour=$note_info['note_hour'];
		
		$note_item_arr=array();
		$note_item_arr['note_id']=$note_info['id'];
		$note_item_arr['user_id']=$note_info['user_id'];
		$note_item_arr['note_type']=$note_info['note_type'];
		$note_item_arr['note_type_2_flag']=$note_info['note_type_2_flag'];
		$note_item_arr['hr_note_id']=$note_info['hr_note_id'];	
		
		//获取假单日期范围内有那些天
		$date_range_temp=date_rang(date('Y-m-d',strtotime($note_info['begin_time'])),date('Y-m-d',strtotime($note_info['end_time'])));
		
		//如果请假时间小于=8小时,且为事后补单,则直接根据当日考勤计算所缺时间拆单
		if($note_info['note_hour']<=8 && (date('Y-m-d',strtotime($note_info['begin_time'])) == date('Y-m-d',strtotime($note_info['end_time']))) ){
			
			//按照申请单申请时间拆单
			$note_item_arr['note_hour']=$note_info['note_hour'];
			$note_item_arr['begin_time']=$note_info['begin_time'];
			$note_item_arr['end_time']=$note_info['end_time'];
			
			//拆单数据异常通知
			if(strlen($note_item_arr['note_hour'])==0 || $note_item_arr['note_hour']==0){
				send_email('hank.zhou@sinowealth.com.cn',config('company_name_en_short').'--请假单拆单异常!', '主单号['.$note_item_arr['note_id'].'],申请人工号:'.get_cache_data('user_info', $note_info['user_id'],'user_gh').',审核人工号:'.get_cache_data('user_info', get_user_id(),'user_gh'));
			}
			
			db('user_note_item')->insert($note_item_arr);
			
			/*
			//获取当日还差多少工作时
			$hr_table_info=db('hr_table')->where('user_id='.$note_info['user_id']." and hr_date='".date('Y-m-d',strtotime($note_info['begin_time']))."'")->find();
			if($note_info['note_hour']>=$hr_table_info['z_work_need_time']){
				
				$note_item_arr['note_hour']=$hr_table_info['z_work_need_time'];				

				//非考勤人员,按照申请单时间直接拆单
				if($note_user_info['is_hr_user']==0){
					$note_item_arr['note_hour']=$note_info['note_hour'];
				}
				
				$note_item_arr['begin_time']=$note_info['begin_time'];
				$note_item_arr['end_time']=$note_info['end_time'];
				db('user_note_item')->insert($note_item_arr);
			}else{				
				$note_item_arr['note_hour']=$note_info['note_hour'];
				$note_item_arr['begin_time']=$note_info['begin_time'];
				$note_item_arr['end_time']=$note_info['end_time'];
				db('user_note_item')->insert($note_item_arr);
			}
			*/
		}else{			
			
			if($time_firstD_diff['h']<8){
				//请假第一日有工作时间,需补时差
				$note_item_arr['note_hour']=$time_firstD_diff['h'];
				$note_item_arr['begin_time']=$note_info['begin_time'];
				$note_item_arr['end_time']=date('Y-m-d',strtotime($note_info['begin_time'])).' '.$hr_role_info['end_time'];
				$note_hour = $note_hour-$time_firstD_diff['h'];
				
				//拆单数据异常通知
				if(strlen($note_item_arr['note_hour'])==0 || $note_item_arr['note_hour']==0){
					send_email('hank.zhou@sinowealth.com.cn',config('company_name_en_short').'--请假单拆单异常!', '主单号['.$note_item_arr['note_id'].'],申请人工号:'.get_cache_data('user_info', $note_info['user_id'],'user_gh').',审核人工号:'.get_cache_data('user_info', get_user_id(),'user_gh'));
				}
				
				db('user_note_item')->insert($note_item_arr);
				unset($date_range_temp[0]);	//如果第一天补时差,则清理第一天
			}
			
			$date_range=array();
			//构建数组,假日数组数据完善
			//构建日期数据,如果为假期,则工作时间算0,如果为工作日,则工作时间算8小时
			foreach ($date_range_temp as $key=>$val){
				$date_range[$key]['day']=$val;
				$is_holiday=db('sys_holiday')->where("holiday_date='".$val."' and site_id=".$note_user_info['site_id'])->find();
				if($is_holiday){
					$date_range[$key]['is_holiday']=1;
					$date_range[$key]['note_hour']=0;
				}else{
					$date_range[$key]['is_holiday']=0;
					if($note_hour>8){
						$date_range[$key]['note_hour']=8;
						$note_hour = $note_hour-8;
					}else{
						if($note_hour>0){
							$date_range[$key]['note_hour']=$note_hour;
							$note_hour=0;
						}else{
							$date_range[$key]['note_hour']=0;
						}
					}
				}
			}
				
			foreach ($date_range as $key=>$val){
				if($val['is_holiday']==0){
					$note_item_arr['note_hour']=$val['note_hour'];
					$note_item_arr['begin_time']=$val['day'].' '.$hr_role_info['begin_time'];
					$note_item_arr['end_time']=$val['day'].' '.$hr_role_info['end_time'];
					
					//拆单数据异常通知
					if(strlen($note_item_arr['note_hour'])==0 || $note_item_arr['note_hour']==0){
						send_email('hank.zhou@sinowealth.com.cn',config('company_name_en_short').'--请假单拆单异常!', '主单号['.$note_item_arr['note_id'].'],申请人工号:'.get_cache_data('user_info', $note_info['user_id'],'user_gh').',审核人工号:'.get_cache_data('user_info', get_user_id(),'user_gh'));
					}
					
					db('user_note_item')->insert($note_item_arr);
				}
			}
			
		}
	}

//王天棋2017.9.27修改	
	
	public function excel_out($list,$user_id){
		foreach ($list as $l){
			foreach ($l as $key=>$ll){
				//echo $key.' '.$ll.'<br>';
			}
			//echo '<br><br>';
		}
		
		$data[]=array('序号','申请人','请假类型','申请时间','时间范围','代理人','申请单类型','状态');
		$data_title=array('id','user_id','note_type','note_hour','begin_time','age_user_id','hr_user_id','note_check_status');
		foreach ($list as $l){
			$data_body='';
			foreach ($data_title as $d){
				foreach ($l as $key=>$ll){
					if($key==$d){echo $d.'<br>';
						if($d=='user_id'){
							$ll=get_cache_data('user_info',$ll,'nickname');
						}
						if($d=="note_type"){
							$ll=get_note_type_name($ll).'('.config('hr_note_type')[$l['hr_note_id']].')';
						}
						if($d=="begin_time"){
							$ll=$ll.'~'.$l['end_time'];
						}
						if($d=="age_user_id"){
							$ll=get_cache_data('user_info',$ll,'nickname');
						}
						if($d=="hr_user_id"){
							/*{if condition="$vo.age_user_id eq $vo.hr_user_id"}
                                    	代理人&考勤申请单
                                    {else /}
                                    	{eq name="$vo.age_user_id" value="$page_info.user_id"}代理人申请单{else/}考勤申请单{/eq}
                                    {/if}*/
							if($l['age_user_id']==$ll){
								$ll="代理人&考勤申请单";
							}else{
								if($l['age_user_id']==$user_id){
									$ll="代理人申请单";
								}else{
									$ll="考勤申请单";
								}
							}
						}
						if($d=="note_check_status"){							
							if($l['note_step']==4){
								if($ll==1){
									$ll="审核通过,";
								}else{
									$ll="审核未通过,";
								}
							}
							$ll=$ll.get_note_step($l['note_step']);
						}
						$data_body[$key]=$ll;
					}
				}
				
				
			}
			$data[]=$data_body;
		}
		$excel=array(
				'name'=>'申请单列表',
				array(
						'data'=>$data
				)
		);
		excel_css($excel);
	}
}




















