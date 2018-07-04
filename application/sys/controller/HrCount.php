<?php
namespace app\sys\controller;

use think\Controller;
use app\index\controller\Admin;

//生成部门统计数据
class HrCount extends Admin
{
    public function index(){ 
		//$this->cal_hr_count();
		//exit;
    	$data=$this->param;
    	$page_info=array();
    	if(!isset($data['year_month'])){
    		$year_month=date('Y-m',time());
    	}else{
    		$year_month=$data['year_month'];
    	}
    	$year=date('Y',strtotime($year_month));
    	$month=(int)date('m',strtotime($year_month));
    	$page_info['year_month']=$year_month;
    	
    	//判断本月人事数据是否已经生成,如果没有生成,则生成
    	$is_cr=db('sys_event')->where('type_flag=15 and year='.$year.' and month='.$month)->find();
    	if(!$is_cr){
    		$this->cal_hr_count();
    	}
	    	
    	//获取锁定标记
    	$is_lock=db('sys_event')->where('type_flag=16 and year='.$year.' and month='.$month)->find();
    	if($is_lock){
    		$page_info['lock_flag']=1;
    	}else{
    		$page_info['lock_flag']=0;
    	}
    	
    	//获取系统中已经生成了那些月份的数据
    	$sql="select distinct concat(year,'-',month) as yearMonth from sw_sys_hr_count order by year,month desc limit 12 ";
    	$temp_arr=db()->query($sql);
    		
    	$page_info['yearMonth']=$temp_arr[0]['yearMonth'];
    		
    	//当前操作数据获取
    		
    	//在职统计数
    	$page_info['user_tt']=db('sys_hr_count')->where('year='.$year.' and month='.$month.' and hr_status=1')->count();
    		
    	//部门人数统计
    	$sql="select max(dep_name_full) as dep_name,max(dep_name) as dep_name_s,count(*) as tt,dep_id,max(par_id) as par_id from sw_sys_hr_count where year=".$year." and month=".$month." and hr_status=1  group by dep_name_full order by order_id;";
    	$page_info['dep_tt']=db()->query($sql);
    		
    	//在职人员统计
    	$sql="select * from sw_sys_hr_count where year=".$year.' and month='.$month.' and hr_status=1 order by order_id,manage_flag desc,is_hr_manage desc,id';
    	$page_info['user_arr']=db()->query($sql);
    		
    	//左侧部门人员详细信息数组构建
    	$dep_arr=array();
    	foreach ($page_info['dep_tt'] as $key=>$val){
    		$i=0;
    		$j=0;
    		$dep_arr[$key]['dep_name']=$val['dep_name'];
    		$dep_arr[$key]['dep_name_s']=$val['dep_name_s'];
    		$dep_arr[$key]['dep_manage']=array();
    		$dep_arr[$key]['sh_user']=array();
    		$dep_arr[$key]['sz_user']=array();
    		$dep_arr[$key]['hk_user']=array();
    		$dep_arr[$key]['tw_user']=array();
    		$dep_arr[$key]['xa_user']=array();
    			
    		//MCU单独做个统计数
    		$mcu_str='1,6,8';
    		
    		foreach ($page_info['user_arr'] as $k=>$v){
    				//主管行
    				if($v['dep_id']==$val['dep_id'] && $v['manage_flag']==1){
    					array_push($dep_arr[$key]['dep_manage'],$v );
    					$i++;
    				}
    				//上海人员行
    				if($v['dep_id']==$val['dep_id'] && $v['site_id']==1  && $v['manage_flag']!=1){
    					array_push($dep_arr[$key]['sh_user'],$v);
    					$i++;
    				}
    				//深圳人员行
    				if($v['dep_id']==$val['dep_id'] && $v['site_id']==2 && $v['manage_flag']!=1){
    					array_push( $dep_arr[$key]['sz_user'],$v);
    					$i++;
    				}
    				//HK人员行
    				if($v['dep_id']==$val['dep_id'] && $v['site_id']==8 && $v['manage_flag']!=1){
    					array_push( $dep_arr[$key]['hk_user'],$v);
    					$i++;
    				}
    				//TW人员行
    				if($v['dep_id']==$val['dep_id'] && $v['site_id']==7 && $v['manage_flag']!=1){
    					array_push( $dep_arr[$key]['tw_user'],$v);
    					$i++;
    				}
    				//XA人员行
    				if($v['dep_id']==$val['dep_id'] && $v['site_id']==3 && $v['manage_flag']!=1){
    					array_push( $dep_arr[$key]['xa_user'],$v);
    					$i++;
    				}    					
    			}
    			
    			//如果该组别无主管,设定默认初始值
    			if(count($dep_arr[$key]['dep_manage'])==0){
    				$dep_arr[$key]['dep_manage'][0]['site_id']=1;
    				$dep_arr[$key]['dep_manage'][0]['nickname']='';
    				$dep_arr[$key]['dep_manage'][0]['user_gh']='';
    				$dep_arr[$key]['dep_manage_count']=0;
    			}else{
    				$dep_arr[$key]['dep_manage_count']=count($dep_arr[$key]['dep_manage']);
    			}
    			
    			//MCU组别人数特殊统计
    			if($val['par_id']==$mcu_str){
    				$dep_arr[$key]['dep_user_count']=db('sys_hr_count')->where('year='.$year.' and month='.$month." and hr_status=1 and left(par_id,5)='".$mcu_str."'")->count();
    				$dep_arr[$key]['mcu_flag']=1;
    			}else{
    				$dep_arr[$key]['dep_user_count']=$i;
    				$dep_arr[$key]['mcu_flag']=0;
    			}

    			//标记是否子目录
    			if(strpos($val['dep_name'],'/') != false){
    				$dep_arr[$key]['sub_flag']=1;
    			}else{
    				$dep_arr[$key]['sub_flag']=0;
    			}
    		}
    		$page_info['dep_user_arr']=$dep_arr;
    		
    		//地点人数统计
    		$sql="
    				select * from (select 
						dep_name_full,
						sum(case when site_id=1 then 1 else 0 end) as sh_count,
					    sum(case when site_id=2 then 1 else 0 end) as sz_count,
					    sum(case when site_id=8 then 1 else 0 end) as hk_count,
					    sum(case when site_id=7 then 1 else 0 end) as tw_count,
					    sum(case when site_id=3 then 1 else 0 end) as xa_count,
					    count(*) as tt_count
					from 
						sw_sys_hr_count 
					where 
						year=".$year." and month=".$month."
					group by 
						dep_name_full
					order by 
						order_id) as t1
					    
					union all
					select * from (select 
						'合计:',
						sum(case when site_id=1 then 1 else 0 end) as sh_count,
					    sum(case when site_id=2 then 1 else 0 end) as sz_count,
					    sum(case when site_id=8 then 1 else 0 end) as hk_count,
					    sum(case when site_id=7 then 1 else 0 end) as tw_count,
					    sum(case when site_id=3 then 1 else 0 end) as xa_count,
					    count(*) as tt_count
					from 
						sw_sys_hr_count 
					where 
						year=".$year." and month=".$month.") as t2
					    ;
    				
    				";
    		$page_info['dep_arr']=db()->query($sql);
    		
    		//离职入职人员统计
    		$sql="
    				select * from (select 
						dep_name_full,
					    sum(case when in_out_status=1 then 1 else 0 end) as in_num,
					    sum(case when in_out_status=2 then 1 else 0 end) as out_num,
					    (select group_concat(nickname)  from sw_sys_hr_count c2 where year=".$year." and month=".$month." and c2.dep_id=c1.dep_id and in_out_status=1 ) as in_str,
					    (select group_concat(nickname)  from sw_sys_hr_count c2 where year=".$year." and month=".$month." and c2.dep_id=c1.dep_id and in_out_status=2) as out_str
					from 
						sw_sys_hr_count  c1
					where 
						year=".$year." and month=".$month."
					group by 
						dep_name_full
					order by 
						order_id) as t1
					
					union all
					select * from (select 
						'实习生',
					    sum(case when in_out_status=1 then 1 else 0 end) as in_num,
					    sum(case when in_out_status=2 then 1 else 0 end) as out_num,
					    (select group_concat(nickname)  from sw_sys_hr_count c2 where year=".$year." and month=".$month." and hr_status=3 and in_out_status=1 ) as in_str,
					    (select group_concat(nickname)  from sw_sys_hr_count c2 where year=".$year." and month=".$month."  and hr_status=3 and in_out_status=2) as out_str
					from 
						sw_sys_hr_count  c1
					where 
						year=".$year." and month=".$month." and hr_status=3) as t2
					; ";
    		$page_info['in_out_arr']=db()->query($sql);
    	
    		$this->assign('page_info',$page_info);
    		return $this->fetch();
    }
    
    //重新计算本月人事统计数据
    function re_cal_hr_count(){
    	$this->cal_hr_count();
    	echo setServerBackJson(1,'计算完成!','index');
    }
    
    //生成某月份人事人员统计数据
    function cal_hr_count($yearMonth=''){   	
    	
    	if(strlen($yearMonth)==0){
    		$yearMonth=date('Y-m',time());
    	}
    	$year=date('Y',strtotime($yearMonth));
    	$month=(int)date('m',strtotime($yearMonth));
    	
    	//判断本月人事数据是否已经锁住,如果锁住,则不进行操作
    	$is_finish=db('sys_event')->where('type_flag=16 and year='.$year.' and month='.$month)->find();
    	
    	//锁定后不操作
    	if($is_finish){
    		exit;
    	}
    	//判断本月人事数据是否已经生成,如果已经生成,则刷新
    	$is_cr=db('sys_event')->where('type_flag=15 and year='.$year.' and month='.$month)->find();
    	if($is_cr){
    		$sql="delete from sw_sys_hr_count where year=".$year.' and month='.$month;
    		db()->execute($sql);
    	}
    	
    	//本月日期范围
    	$month_arr=get_begin_last_date();
    	
    	//获取本月在职,离职人员数据
    	$sql="
    			select 
					".$year." as year,".$month." as month,0 as is_lock,u.id as user_id,u.user_gh,u.nickname,d.par_id,d.id as dep_id,d.en_name as dep_name,'' as dep_name_full,d.order_id,is_hr_manage,manage_flag,u.hr_status,s.id as site_id,
					(case when u.entry_date between '".$month_arr[0]." 00:00:00' and '".$month_arr[1]." 23:59:59' then 1 else 0 end) as in_out_status
				from 
					sw_sys_dep d,sw_sys_site s, sw_sys_user u
				where
					d.id=u.dep_id and s.id=u.site_id and u.hr_status in(1,3) and user_status=1 and cal_hr_user=1
							
				union all
				
				select 
					".$year." as year,".$month." as month,0 as is_lock,u.id as user_id,u.user_gh,u.nickname,d.par_id,d.id as dep_id,d.en_name as dep_name,'' as dep_name_full,d.order_id,is_hr_manage,manage_flag,u.hr_status,s.id as site_id,2
				from 
					sw_sys_dep d,sw_sys_site s, sw_sys_user u
				where
					d.id=u.dep_id and s.id=u.site_id and u.id in 
		    			(select user_id from sw_sys_user_event where type_flag=2 and exec_time between '".$month_arr[0]." 00:00:00' and '".$month_arr[1]." 23:59:59')
				    ";
    	$user_arr=db()->query($sql);
    	
    	//填充dep_name_full字段
    	foreach ($user_arr as $k=>$v){
    		$user_arr[$k]['dep_name_full']=get_dep_tit_str($v['par_id'],'par_id');
    		if(strlen($user_arr[$k]['dep_name_full'])==0){
    			$user_arr[$k]['dep_name_full']=$v['dep_name'];
    		}
    	}
    	
    	db('sys_hr_count')->insertAll($user_arr);
    	
    	if(!$is_cr){
    		//人事考勤统计数据事件写入
	    	$temp_arr=array();
	    	$temp_arr['site_id']='';
	    	$temp_arr['title']=C_DAY.'由'.get_user_nickname().' 生成'.$year.'-'.$month.'月人事统计数据';
	    	$temp_arr['year']=$year;
	    	$temp_arr['month']=$month;
	    	$temp_arr['type_flag']=15;
	    	$temp_arr['log']=$temp_arr['title'];
	    	$temp_arr['last_user_id']=get_user_id();
	    	$temp_arr['create_time']=get_date_time();	    	
	    	db('sys_event')->insert($temp_arr);
    	}else{
    		//人事考勤统计数据事件更新
    		$temp_arr=array();
    		$temp_arr['id']=$is_cr['id'];
    		$temp_arr['site_id']='';
    		$temp_arr['title']=C_DAY.'由'.get_user_nickname().' 重新计算'.$year.'-'.$month.'月人事统计数据';
    		$temp_arr['year']=$year;
    		$temp_arr['month']=$month;
    		$temp_arr['type_flag']=15;
    		$temp_arr['log']= $temp_arr['title'].'<br>'.$is_cr['log'];
    		$temp_arr['last_user_id']=get_user_id();
    		$temp_arr['update_time']=get_date_time();    		
    		db('sys_event')->update($temp_arr);
    	}
    	
    }

}



















