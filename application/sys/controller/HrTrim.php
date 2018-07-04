<?php
namespace app\sys\controller;

set_time_limit(0);
ini_set("memory_limit","-1");

use think\Controller;
use app\index\controller\Admin;

class HrTrim extends Admin
{
	/**
	 * 用户考勤数据维护
	 */
	public function index() {
		$page_info=array();
		$data=$this->param;
		$page_info['cal_check_flag']=0;
		
		cal_tb_param('hr_trim',$data);
		//准备站点数据
		$site_info=db('sys_site')->where("id in (".$_SESSION['u_i']['cal_site_id_str'].")")->select();
		$site_id=$_SESSION['hr_trim']['site_id'];
		
		//页面初始信息设置
		if(count($data)==0){
			//判断上月考勤数据是否已经生成
			if(!check_hr_base()){
				//判断是否超过本月20日,未超过则需用户确认后生成考勤数据
				if(date('d',time())<=config('hr_cr_date')){
					$page_info['cal_check_flag']=1;
				}else{
					$page_info['cal_check_flag']=0;
					foreach ($site_info as $k=>$v){
						cal_hr($v['id']);
					}
				}
			}else{
				$page_info['cal_check_flag']=0;
			}
		}else{
			//确认生成考勤数据表		
			if(isset($data['confirm_cal_hr'])){
				if($data['confirm_cal_hr']=='cal_hr'){
					foreach ($site_info as $k=>$v){
						cal_hr($v['id']);
					}
				}
			}			
		}
		$last_month=get_last_month();
		
		$year=date('Y',strtotime($_SESSION['hr_trim']['year_month']));
		$month=date('m',strtotime($_SESSION['hr_trim']['year_month']));
		
		//如果当前缓存中的日期小于本月,则不出现提醒生成考勤数据
		if(strtotime($_SESSION['hr_trim']['year_month'])<strtotime($last_month)){
			$page_info['cal_check_flag']=0;
		}
		
		//准备部门数据
		$page_info['dep_info']=config('dep_info');
		$page_info['site_info']=$site_info;
		
		$page_info['site_name']=get_cache_data('site_info',$_SESSION['hr_trim']['site_id'],'site');
		
		//准备可查看历史数据,最多12个月
		$sql="select distinct concat(year,'-',month) as yearMonth from sw_hr_cal where user_id in (select id from sw_sys_user where site_id=".$_SESSION['hr_trim']['site_id'].") and concat(year,'-',month)<>'".date('Y',strtotime($last_month)).'-'.(int)(date('m',strtotime($last_month)))."' order by year,month desc limit 12";
		$year_month_arr=db()->query($sql);
		
		$last_month=get_last_month();
		$page_info['year_month'][0]=date('Y',strtotime($last_month)).'-'.(int)(date('m',strtotime($last_month)));
		
		foreach ($year_month_arr as $key=>$val){
			array_push($page_info['year_month'],$val['yearMonth']);
		}
		if(!isset($page_info['year_month'])){
			$page_info['year_month'][0]=$_SESSION['hr_trim']['year_month'];
		}
		
		//判断上月份数据是否结转,如果结转,则不显示编辑框
		$event_info=db('sys_event')->where(' type_flag=2 and year='.$year." and site_id=".$_SESSION['hr_trim']['site_id']." and month=".$month)->find();
		
		if(!$event_info){
			$page_info['finish_flag']=0;
		}else{
			$page_info['finish_flag']=1;
		}
		
		$page_info['year']=$year;
		$page_info['month']=(int)$month;
		
		//判断传递过来的月份是否小于上月,如果小于上月,则直接设置标识为已经结转
		if(strtotime($_SESSION['hr_trim']['year_month'])<strtotime(' -4 month',time())){
			$page_info['finish_flag']=1;
		}
		
		//判断当前月份的数据是否已经生成,如果未生成,则不显示导出报表,重新计算,结转等工具按钮
		$event_info=db('sys_event')->where(' site_id='.$site_id.' and type_flag=1 and year='.$year." and month=".$month)->find();
		if(!$event_info){
			$page_info['data_cr_flag']=0;
		}else{
			$page_info['data_cr_flag']=1;
		}
		
		$this->assign('page_info',$page_info);
		return $this->fetch();		
	}

	//输出表格数据
	public function get_hr_cal_data(){
		//接收排序参数
		$data=$this->param;
		if(isset($data['sort'])){
			$order_str=$data['sort'];
		}else{
			$order_str=" user_gh ";
		}
		
		$site_id=$_SESSION['hr_trim']['site_id'];
		$dep_id=$_SESSION['hr_trim']['dep_id'];
		$key=$_SESSION['hr_trim']['key'];
		$year_month=$_SESSION['hr_trim']['year_month'];
		$year=date('Y',strtotime($year_month));
		$month=(int)date('m',strtotime($year_month));
		
		//排序过滤
		if(isset($data['order'])){
			if($data['order']=='asc'){
				$order_str .= " ";
			}else{
				$order_str .= " desc";
			}			
		}
		
		$where_str=" 1=1 ";
		
		//站点过滤
					
		$where_str .= " and user_id in (select id from sw_sys_user where site_id=".$site_id." and status=1 and user_status=1)";
		
		
		//部门过滤
		if(isset($dep_id)){
			if($dep_id==0){
				$where_str .= " ";
			}else{ 
				$where_str .= " and user_id in (select id from sw_sys_user where dep_id=".$dep_id." and status=1 and user_status=1)";
			}
		}else{
			$where_str .= " ";
		}
		
		//关键字过滤
		if(isset($key)){
			if(strlen($key)==0){
				$where_str .= "";
			}else{
				$where_str .= " and user_id in (select id from sw_sys_user where (email like '%".$key."%' or user_gh like '%".$key."%' or nickname like '%".$key."%') and status=1 and user_status=1) ";
			}
		}else{
			$where_str .= "";
		}

		//判断当前月份是否有生成考勤数据,如果有生成,则输出数据,否则不输出数据
		$sql="select * from sw_sys_event where year=".$year.' and month='.$month.' and type_flag=1';
		$is_hava_data=db()->query($sql);
		
		if($is_hava_data){

			//月份过滤
			$where_str .= " and year=".$year." and month=".$month;
			
			//构建表格数据
			$tb_arr=array();
			
			//考勤计算数据获取
			$hr_cal_arr=db('hr_cal')->alias('c')->join('sys_user u','c.user_id=u.id')->where($where_str)->order($order_str)->field('c.id,u.user_gh,u.nickname,c.user_id,c.year,c.month,c.last_annual_num,c.last_repair_num,c.holiday_hour,c.local_note_hour,c.local_annual_num,c.tw_out_work_time,c.local_repair_num,c.local_num,c.intern_day,c.casual_leave,c.sick_leave,c.abs_hour,c.bf_num,c.lunch_num,c.remark,c.is_lock')->select();
			$tb_arr['total']=count($hr_cal_arr);
			$tb_arr['rows']=array();
			
			foreach($hr_cal_arr as $key=>$val){
				$temp_arr=array();
				$temp_arr['id']=$val['id'];
				$temp_arr['user_id']=$val['user_id'];
				$temp_arr['user_gh']=get_cache_data('user_info',$val['user_id'],'user_gh');
				$temp_arr['nickname']=get_cache_data('user_info',$val['user_id'],'nickname');
					
				$temp_arr['last_annual_num']=c_z($val['last_annual_num']);
				if($site_id==7){
					$temp_arr['tw_out_work_time']=c_z($val['tw_out_work_time']);
				}
				$temp_arr['last_repair_num']=c_z($val['last_repair_num']);
				$temp_arr['holiday_hour']=c_z($val['holiday_hour']);
				$temp_arr['local_note_hour']=c_z($val['local_note_hour']);
				$temp_arr['local_annual_num']=c_z($val['local_annual_num']);
				$temp_arr['local_repair_num']=c_z($val['local_repair_num']);
				$temp_arr['local_num']=c_z($val['local_num']);
				$temp_arr['casual_leave']=c_z($val['casual_leave']);
				$temp_arr['sick_leave']=c_z($val['sick_leave']);
				$temp_arr['abs_hour']=c_z($val['abs_hour']);
				$temp_arr['bf_num']=c_z($val['bf_num']);
				$temp_arr['lunch_num']=c_z($val['lunch_num']);
				$temp_arr['intern_day']=$val['intern_day'];
				$temp_arr['remark']=$val['remark'];
				$temp_arr['cur_date']=$year.'-'.$month;
				array_push($tb_arr['rows'],$temp_arr);
			}
			
			//统计行数据获取
			$sql="select '' as id,'' as user_id,'".count($tb_arr['rows'])."' as user_gh,'合计:' as nickname,
				FORMAT(round(sum(ifnull(last_annual_num,0)),1),1) as last_annual_num,
				FORMAT(round(sum(ifnull(last_repair_num,0)),1),1) as last_repair_num,
				FORMAT(round(sum(ifnull(holiday_hour,0)),1),1) as holiday_hour,
				FORMAT(round(sum(ifnull(local_note_hour,0)),1),1) as local_note_hour,
				FORMAT(round(sum(ifnull(local_annual_num,0)),1),1) as local_annual_num,
				FORMAT(round(sum(ifnull(tw_out_work_time,0)),1),1) as tw_out_work_time,	
				FORMAT(round(sum(ifnull(local_repair_num,0)),1),1) as local_repair_num,
				FORMAT(round(sum(ifnull(local_num,0)),1),1) as local_num,
				FORMAT(round(sum(ifnull(casual_leave,0)),1),1) as casual_leave,
				FORMAT(round(sum(ifnull(sick_leave,0)),1),1) as sick_leave,
				FORMAT(round(sum(ifnull(abs_hour,0)),1),1) as abs_hour,
				FORMAT(round(sum(ifnull(bf_num,0)),1),1) as bf_num,
				FORMAT(round(sum(ifnull(lunch_num,0)),1),1) as lunch_num,
				FORMAT(round(sum(ifnull(intern_day,0)),1),1) as intern_day,
				'' as remark,'' as cur_date
			from sw_hr_cal
			where
				".$where_str;
			
			$count_arr=db()->query($sql);
			$tb_arr['footer']=$count_arr;
			echo json_encode($tb_arr);
		}
			
	}
	
	//更新某个栏位数据
	public function update_field_val(){
		
		$data=$this->param;
		$row_id=$data['id'];
		unset($data['id']);
		
		//取data的更新栏位数值
		foreach ($data as $k=>$v){
			$data['field']=$k;
			$data['value']=htmlspecialchars($v);
			unset($data[$k]);
		}
		
		if(isset($data['value'])){
			
			$row_info=db('hr_cal')->where('id='.$row_id)->find();
			
			//判断是否已经结转,如果结转,则不进行数据修改
			if(!$row_info['is_lock']){
				//考勤数据计算修改,日志写入
				$log_str='<br>'.date('Y-m-d H:i:s',time()).' 由用户 ['.get_user_nickname().'] 修改 ['.get_hr_field_zh($data['field']).'] 为'.$row_info[$data['field']].'->'.$data['value'];
				$u_arr=array();
				$u_arr['id']=$row_id;
				$u_arr[$data['field']]=$data['value'];
				$u_arr['log'] = array('exp',"concat(ifnull(log,''),'".$log_str."')");
				db('hr_cal')->update($u_arr);
			}else{
				echo '已结转锁定,无法修改!';
				exit;
			}
		}

		//行数据计算
		hr_row_cal($row_id);
		
		$return_arr=array();
		//返回当前行数据
		$return_arr['row_info']=db('hr_cal')->where('id='.$row_id)->find();
		
		$site_id=$_SESSION['hr_trim']['site_id'];
		$dep_id=$_SESSION['hr_trim']['dep_id'];
		$key=$_SESSION['hr_trim']['key'];
		$year_month=$_SESSION['hr_trim']['year_month'];
		
		//站点过滤
		if(isset($site_id)){
			if($site_id==0){
				$where_str="";
			}else{
				$where_str=" and user_id in (select id from sw_sys_user where site_id=".$site_id." and status=1 and user_status=1)";
			}
		}else{
			$where_str="";
		}
		
		//部门过滤
		if(isset($dep_id)){
			if($dep_id==0){
				$where_str .="";
			}else{
				$where_str .=" and user_id in (select id from sw_sys_user where dep_id=".$dep_id." and status=1 and user_status=1)";
			}
		}else{
			$where_str .="";
		}
		
		//关键字过滤
		if(isset($key)){
			if(strlen($key)==0){
				$where_str .= "";
			}else{
				$where_str .= " and user_id in (select id from sw_sys_user where (email like '%".$key."%' or user_gh like '%".$key."%' or nickname like '%".$key."%') and status=1 and user_status=1) ";
			}
		}else{
			$where_str .= "";
		}
				
		$year=date('Y',strtotime($year_month));
		$month=date('m',strtotime($year_month));
		
		//统计行数据获取
		$sql="select '' as id,'' as user_id,'' as user_gh,'合计:' as nickname,
				FORMAT(round(sum(ifnull(last_annual_num,0)),1),1) as last_annual_num,
				FORMAT(round(sum(ifnull(tw_out_work_time,0)),1),1) as tw_out_work_time,
				FORMAT(round(sum(ifnull(last_repair_num,0)),1),1) as last_repair_num,
				FORMAT(round(sum(ifnull(holiday_hour,0)),1),1) as holiday_hour,
				FORMAT(round(sum(ifnull(local_note_hour,0)),1),1) as local_note_hour,
				FORMAT(round(sum(ifnull(local_annual_num,0)),1),1) as local_annual_num,
				FORMAT(round(sum(ifnull(local_repair_num,0)),1),1) as local_repair_num,
				FORMAT(round(sum(ifnull(local_num,0)),1),1) as local_num,
				FORMAT(round(sum(ifnull(casual_leave,0)),1),1) as casual_leave,
				FORMAT(round(sum(ifnull(sick_leave,0)),1),1) as sick_leave,
				FORMAT(round(sum(ifnull(abs_hour,0)),1),1) as abs_hour,
				'' as remark,'' as cur_date
			from sw_hr_cal				
			where year=".$year." and month=".$month."
				".$where_str;
		
		$count_arr=db()->query($sql);
		
		$return_arr['count_arr']=$count_arr[0];
		
		echo json_encode($return_arr);
		
	}
	
	//重新计算考勤数据
	public function re_cal_hr(){
		$site_id=$_SESSION['hr_trim']['site_id'];
		$site_name=get_cache_data('site_info', $site_id,'site');
		$year_month=$_SESSION['hr_trim']['year_month'];		
		calculate_hr($year_month,$site_id,'err_date');
		cal_hr($site_id,$year_month);
		echo $year_month.' '.$site_name.'  考勤数据计算完成!';
	}
	
	//结转考勤数据到薪资
	public function finish_hr_pay(){
		$data=$this->param;
		if(isset($data['year_month'])){
			$last_month_f=$data['year_month'];
		}else{
			$last_month_f=$_SESSION['hr_trim']['year_month'];
		}
		
		$last_month_i=strtotime($last_month_f);
		
		$year=date('Y',$last_month_i);
		$month=date('n',$last_month_i);
		
		$site_id=$_SESSION['hr_trim']['site_id'];
		
		//更改上月考勤结算数据is_lock标记
		$sql="update sw_hr_cal set is_lock=1 where year=".$year.' and month='.$month.' and user_id in (select id from sw_sys_user where site_id='.$site_id." and status=1 and user_status=1)";
		db()->execute($sql);
		
		//判断薪资数据是否已经生成,如果已经生成薪资数据,则更新考勤数据到薪资
		$is_cr_pay_table=db('sys_event')->where('year='.C_YEAR.' and month='.C_MONTH.' and site_id='.$site_id.' and type_flag=8')->find();
		if($is_cr_pay_table){
			update_hr_cal_to_pay($cal_flag="batch",$user_id=0,$site_id);
		}
		
		$temp_arr=array();
		$temp_arr['title']=$year.'-'.$month.' 考勤结转信息';
		$temp_arr['year']=$year;
		$temp_arr['month']=$month;
		$temp_arr['type_flag']=2;
		$temp_arr['site_id']=$site_id;
		$temp_arr['log']=get_date_time().' '.get_user_nickname().' 结转'.get_cache_data('site_info', $site_id,'site').' '.$year.'-'.$month.' 月考勤&发送到薪资成功!';
		$temp_arr['last_user_id']=get_user_id();
		$temp_arr['create_time']=get_date_time();
		$temp_arr['update_time']=get_date_time();
		db('sys_event')->insert($temp_arr);

		//发送邮件通知到对应薪资结算人员
		//获取该站点薪资结算人员信息
		$site_pay_user_info=db('sys_user')->where(" is_pay_cal_user=1 and concat(',',cal_site_id_str,',') like '%".check_douhao($site_id, 1)."%'")->select();
		foreach ($site_pay_user_info as $k=>$v){
			$tit= $year.'-'.$month.'月 '.get_cache_data('site_info', $site_id,'site').' 考勤数据结转通知!';
			send_email($v['email'],$tit, $temp_arr['log']);
			send_email('hank.zhou@sinowealth.com.cn',$tit, $temp_arr['log']);
		}
		
		echo $temp_arr['log'];
	}
	
	//获取某用户旷职时数大于0的数据
	public function get_abs_info(){
		$data=$this->param;
		$hr_trim_row_info=db('hr_cal')->where('id='.$data['row_id'])->find();
		$date=$hr_trim_row_info['year'].'-'.$hr_trim_row_info['month'].'-01';
		
		//获取月份日期范围
		$month_rang=get_begin_last_date($date);
		
		$abs_info=db('hr_table')->where(" user_id=".$hr_trim_row_info['user_id']." and isnull(holiday_id)=true and hr_date between '".$month_rang[0]."' and '".$month_rang[1]."' and hr_status=0 and abs_time>0")->select();
		
		

		$tt=0;
		$content="<div style='height:400px; overflow:auto;'><table  border=1 bordercolor=#000000 style='border-collapse:collapse;width:98%;'>
				<thead>
					<tr>
						<th style='width:120px; text-align:center'>日期</th>
						<th style='width:80px; text-align:center'>旷职时数</th>
						<th>详细信息</th>
						<th>有单未审核?</th>
					</tr>
				</thead>
				";
		
		
		foreach ($abs_info as $key=>$val){
			
			//判断此时间段是否已经添加申请单,主管未审核?
			$sql="select *  from sw_user_note where user_id=".$val['user_id']." and begin_time between '".$val['hr_date']." 00:00:00' and '".$val['hr_date']." 23:59:59' and note_step<>4 and status=1 limit 1";
			
			$is_have_note_arr=db()->query($sql);
			
			if(count($is_have_note_arr)>0){
				$note_str="申请单:".$is_have_note_arr[0]['id']."";
			}else{
				$note_str="无申请单";
			}
			
			$content .= "<tr>
							<td>".$val['hr_date']."</td>
							<td style=' text-align:center'>".$val['abs_time']."</td>
							<td style=' width:800px;'>".$val['hr_status_remark']."</td>
							<td style='width:100px;'>".$note_str."</td>
						 </tr>";
			$tt += $val['abs_time'];
		}
		
		$content.="
				<tr>
					<td  style='text-align:right;'>合计:&nbsp;</td>
					<td style=' text-align:center'>".$tt."&nbsp;小时</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
				</tr>
				</table></div>";
		
		$result=array();
		$result['title']=$hr_trim_row_info['year'].'-'.$hr_trim_row_info['month'].' '.get_user_nickname($hr_trim_row_info['user_id']).' 旷职考勤数据查看!';
		$result['content']=$content;
		echo json_encode($result);
	}
	
	//导出考勤报表到Excel
	public function ext_hr_tb(){

		$last_month=strtotime($_SESSION['hr_trim']['year_month']);
		
		$year=date('Y',$last_month);
		$month=(int)date('m',$last_month);
		$year_month=$year.'-'.$month;
		
		$data=array();
		
		$data['file_key']='hr_cal_tb';
		$data['file_name']=$year_month.'_hr_tb';
		$data['tb_tit']=$year_month.'月考勤结算数据';
		
		
		//考勤计算数据获取
		$sql="
				select 
					u.user_gh,				#员工工号
				    u.nickname,				#姓名
				    c.last_annual_num,   	#上月结算年休
				    c.last_repair_num,		#上月结算补休
				    c.local_note_hour,		#本月申请休假
				    c.local_annual_num,		#本次结算年休时数
				    c.local_repair_num,		#本次结算补休时数
				    c.local_num,			#本次年休+补休
				    c.casual_leave,			#事假
					c.sick_leave,			#病假
				    c.abs_hour,				#旷职
				    c.remark				#备注
				from 
					sw_hr_cal c,sw_sys_user u
				where 
					year=".$year." and month=".$month." and c.user_id=u.id and u.site_id in (".$_SESSION['u_i']['cal_site_id_str'].") 
				order by 
					u.site_id 
				";
		$data['tb_head']=array('user_gh','nickname','last_annual_num','last_repair_num','local_note_hour','local_annual_num','local_repair_num','local_num','casual_leave','sick_leave','abs_hour','remark');
		$data['tb_body']=db()->query($sql);
		
		ext_excel('normal',$data);
	}
	
	
	
	
	
	
}
