<?php
namespace app\sys\controller;

set_time_limit(0);
ini_set("memory_limit","-1");
use think\Controller;
use app\index\controller\Admin;

//数据同步操作
class SysAction extends Admin
{
	
	public function index(){
		if(IS_POST){
			$data=$this->param;			
			$site_id=$data['site_id'];
			$msg="";
			G('run');
			
			switch ($data['action_flag']){
				case 'sync_card':
					$record_count=$this->sync_card($site_id);
					$msg .= get_cache_data('site_info', $site_id,'site')."考勤数据(".$record_count.")已同步,用时".G('run','run1')."!";
					//TODO 写日志
					break;
				case 'calculate_hr':
					$month=$data['month'];
					if($month>date('m',time())){
						echo setServerBackJson(0,'本月以后考勤无法计算!');
						exit();
					}else{
						$date=date('Y',time()).'-'.$month.'-01';
						$msg=calculate_hr($date,$site_id).',用时'.G('run','run1').'!';
					}
					break;	
				case 'batch_user_pay_base':
					$record_count=$this->batch_user_pay_base($site_id);
					$msg .= get_cache_data('site_info', $site_id,'site')."薪资基础数据( ".$record_count." 条)已同步,用时".G('run','run1')."!";
					//TODO 写日志
					break;
				case 'batch_user_pay_row':
					$record_count=$this->batch_user_pay_row($site_id);
					$msg .= get_cache_data('site_info', $site_id,'site')."薪资数据( ".$record_count." 条)重新计算完成,用时".G('run','run1')."!";
					break;
			}	
			
			echo setServerBackJson(1,$msg,'','close_form_send');
			
		}else{
			$page_info=array();
			
			//准备站点数据
			$page_info['site_arr']=cache('site_cache_arr');
			
			//当前月份
			$page_info['cur_month']=date('m',time());
			
			$page_info['site_id']=1;
			$this->assign('page_info',$page_info);
			return $this->fetch();
		}
		
	}
	
	//抓取最新打卡数据
	public function sync_card($site_id=1){
		$site_code=get_site_code($site_id);
		return get_card_hr_rec($site_code,'get');
	}
	
	//重新计算某站点数据
	public function batch_user_pay_row($site_id=1){
		cal_pay_tb();
	}
	
	//批量用户薪资基础数据
	public function batch_user_pay_base($site_id){
		/*
		 * 1: 导入当月用户薪资表
		 * 2: 增加用户ID字段,删除第一行,修改字段名  user_gh, pay_1->薪资,pay_2->津贴,pay_5->月奖,pay_6->奖励金,pay_8->绩效奖金,
		 *    pay_15->差额津贴,pay_17->养老金,pay_18->医疗保险,pay_19->失业保险,pay_20->公积金,pay_33->扣还款
		 * 3: 修改$local_month_val表数据来源
		 *  update sh_xz x set user_id=(select id from sw_sys_user u where x.user_gh=u.user_gh);
			update sz_xz x set user_id=(select id from sw_sys_user u where x.user_gh=u.user_gh);
			update xa_xz x set user_id=(select id from sw_sys_user u where x.user_gh=u.user_gh);
		 */
		
		
		$user_arr=db('sys_user')->where('hr_status=1 and status=1 and user_status=1 and site_id='.$site_id)->select();
		//获取基础字段信息
		$pay_base_arr=db('pay_field')->where('base_field=1 and site_pay_flag=1')->select();
		$i=0;
				
		foreach ($user_arr as $key=>$val){
			
			//判断某字段在base_val中是否已经存在
			foreach ($pay_base_arr as $k=>$v){
				//将base_val表中此字段status设置为1
				db()->execute("update sw_pay_base_val set status=1,log=concat('".get_date_time()." 用户 ".get_user_nickname()." 启用该字段基础字段属性!"."',log) where field_id=".$v['id']." and status=0");
				
				$is_have=db('pay_base_val')->where('user_id='.$val['id'].' and field_id='.$v['id'])->find();

				if(!$is_have){
					//获取上月此栏位是否有值
					$last_month=strtotime(get_last_month());
					$pay_val=0;
					$last_month_val=db('pay_table')->where('user_id='.$val['id']." and year=".date('Y',$last_month)." and month=".date('n',$last_month))->value('pay_'.$v['id']);
					//上月数据未获取,抓取本月数据
					if(!$last_month_val){
						switch ($site_id){
							case 1:
								$db_tb_name='sh_xz';
								break;
							case 2:
								$db_tb_name="sz_xz";
								break;
							case 3:
								$db_tb_name="xa_xz";
								break;
						}
						
						$local_month_val=db($db_tb_name)->where('user_id='.$val['id'])->value('pay_'.$v['id']);
						if($local_month_val){
							$pay_val=$local_month_val;
						}
					}else{
						$pay_val=$last_month_val;
					}
					
					$insert_arr=array();
					$insert_arr['user_id']=$val['id'];
					$insert_arr['field_id']=$v['id'];
					$insert_arr['base_val']=$pay_val;
					$insert_arr['log']=get_date_time()." 由用户 ".get_user_nickname()." 创建,初始值".$pay_val;
					$insert_arr['status']=1;
					$insert_arr['last_user_id']=get_user_id();
					$insert_arr['create_time']=get_date_time();
					db('pay_base_val')->insert($insert_arr);
					$i++;
				}
			}
		}
		return $i;
	}
	
	//用户基础数据抓取
	public  function user_base_data_transfer(){
		
		$user_info_sql="select * from user_info";
		$user_info_arr=db()->query($user_info_sql);
		
		foreach ($user_info_arr as $k=>$v){
			if($v['lzbz']=='*'){
				$user_info_arr[$k]['hr_status']=2;
			}else{
				$user_info_arr[$k]['hr_status']=1;
			}
			$user_info_arr[$k]['password']=md5($v['d_pwd']);
			$user_info_arr[$k]['dep_id']=1;
			$user_info_arr[$k]['status']=1;
			$user_info_arr[$k]['create_time']=date('Y-m-d H:i:s',time());
			$user_info_arr[$k]['update_time']=date('Y-m-d H:i:s',time());
			
			db('sys_user')->insert($user_info_arr[$k]);
		}	
		echo setServerBackJson(1,'数据转移成功!');
	}


}
