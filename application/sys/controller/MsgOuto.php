<?php
namespace app\sys\controller;

use think\Controller;
use app\index\controller\Admin;
/**
 * 
 * @author wangtianqi   卧槽这是怎么获取自动到的
 *
 */
class MsgOuto extends Controller{
	public function index(){
		
		//判断是否本机运行
		$ip=get_client_ip();
		
		$err_flag=false;
		
		//if($ip!="192.9.230.120"){
			if($ip!="127.0.0.1"){
			$err_flag=true;
		}
		
		if($err_flag){
			echo 'error!';
			send_email('hank.zhou@sinowealth.com.cn','考勤自动脚本预警!', '非管理员或本地运行,运行IP为'.$ip);
			exit();
		}else{
		
			header("Content-type: text/html; charset=utf-8");
			$month_range=isset($_GET['month_range'])?$_GET['month_range']:'';
			$name=isset($_GET['name'])?$_GET['name']:'';
			$arr=array('1','2','4','5');
			if($name=='' || $name<1 || $name>6 || !in_array($name,$arr)){
				exit('请传入正确的内容');
			}
			
			//本月日期范围
			$month_range_local=get_begin_last_date();
			$cur_date=date('Y-m-d',time());
			
			//上月日期范围
			$month_last_range=get_begin_last_date(get_last_month());
			
			//判断是否符合执行日期
			/*if($cur_date==$month_range_local[1] || (int)date('d',time())==5 || (int)date('d',time())==8){
				if($cur_date==$month_range_local[1]){
					$month_range=$month_range_local;
				}else{
					$month_range=$month_last_range;
				}
			}*/
			
			if($name==1 || $name==2){
				if($cur_date==$month_range_local[1]){
					$month_range=$month_range_local;
				}else{
					$month_range=$month_last_range;
				}
			}else{
				$month_range=$month_range_local;
			}
			
			
			$msg_arr=$this->{'index_'.$name}($month_range);
			if($msg_arr==''){
				exit('没有该类型数据');
			}
			db('sys_msg')->insertAll($msg_arr);
			echo 'OK';
		}
	}
	public function index_ajax(){
		header("Content-type: text/html; charset=utf-8");
		$month_range[0]=isset($_GET['month_range_star'])?$_GET['month_range_star']:'';
		$month_range[1]=isset($_GET['month_range_end'])?$_GET['month_range_end']:'';
		
		$name=isset($_GET['name'])?$_GET['name']:'';
		if($name=='' || $name<1 || $name>6){
			if($name!=4 && $name!=5){
			exit('请传入正确的内容');}
		}
		$this->{'index_'.$name.'_ajax'}($month_range,get_user_id());
	}
	/**
	 * 上月考勤异常
	 */
	public function index_1($month_range){
		$user_msg_arr='';
		//获取本月有旷职的用户
		$sql="select distinct(user_id) as user_id from sw_hr_table where hr_date between '".$month_range[0]." 00:00:00' and '".$month_range[1]." 23:59:59' and hr_status=0 and user_id in (select id from sw_sys_user where site_id in (1,2,3) and hr_status=1);";
		$user_arr=db()->query($sql);
		
		//构建需要通知的用户数据//待优化
		foreach ($user_arr as $k=>$v){
			$user_info=db('sys_user')->where('id='.$v['user_id'])->find();
			$user_arr[$k]['nickname']=$user_info['nickname'];
			$sql="select hr_date,hr_status_remark from sw_hr_table where hr_date between '".$month_range[0]." 00:00:00' and '".$month_range[1]." 23:59:59' and hr_status=0 and user_id=".$v['user_id'];;
			$user_arr[$k]['data']=db()->query($sql);
		}
			//通知用户本月有旷职日期
			foreach ($user_arr as $k=>$v){
				$have_day='';
				foreach ($v['data'] as $key=>$val){
					$have_day.=date('d', strtotime($val['hr_date'])).',';
				}
				$key++;
				$user_msg_arr[]=array(
						'user_id'=>$v['user_id'],
						'msg_tit'=>date('Y-m',strtotime($month_range[1]))."月 ".$v['nickname']."  有旷职情况,请及时登录系统处理",
						'msg_desc'=>json_encode(date('Y-m',strtotime($month_range[1])).'月('.$have_day.')'.$key.'天考勤异常，请及时处理'),
						'type_flag'=>1,
						'action_url'=>url('/user/user_main/index').'?time='.date('Y-m',strtotime($month_range[0])).'&',
						'create_time'=>date('Y-m-d H:m:s',time())
				);
		}
		return  $user_msg_arr;		
	}
	/**
	 *需要审核的单据
	 */
	public function index_2($month_range){
		$user_msg_arr='';
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
		
		//通知上月有单据未审核的主管
		foreach ($manage_arr as $k=>$v){
			$user_msg_arr[]=array(
					'user_id'=>$v['cur_user_id'],
					'msg_tit'=>date('Y-m',strtotime($month_range[0])).'月  '.$v['nickname'].' 有申请单未审核!!!',
					'msg_desc'=>json_encode('有未审核的单据，请尽快处理'),
					'type_flag'=>2,
					'action_url'=>url('/user/note_check/index'),
					'create_time'=>date('Y-m-d H:m:s',time())
			);
		}
		return  $user_msg_arr;
				
	}
	/**
	 *试用期转正
	 */
	public function index_3(){
	
	}
	/**
	 *年度考核
	 */
	public function index_4($month_range){
		$user_msg_arr='';
		$sql="select site_id,count(*),(select site from sw_sys_site where id=sw_sys_user.site_id) as site_name from sw_sys_user where hr_status in (1,3) and MONTH(entry_date)='".date('m', strtotime($month_range[0]))."' and YEAR(entry_date)!='".date('Y', strtotime($month_range[0]))."' group by site_id";
		$user_arr=db()->query($sql);
		
		$sql="select site_id,id,nickname,entry_date,(select site from sw_sys_site where id=sw_sys_user.site_id) as site_name from sw_sys_user where hr_status in (1,3) and MONTH(entry_date)='".date('m',strtotime($month_range[0]))."' and YEAR(entry_date)!='".date('Y', strtotime($month_range[0]))."' order by site_id";
		$user_arr_all=db()->query($sql);
		foreach ($user_arr as $u){
			$message=date('m',strtotime($month_range[1]))."月 ".$u['site_name']." 有".$u['count(*)']."人需要考核: <a href=".url('/sys/msg_outo/index_ajax')."?name=4&month_range_star=".date('m',time())."&month_range_end=".date('Y',time())."&tr_row=".$u['site_name']." class='btn btn-minier btn-primary' style='margin-left:20px;'>导出报表</a><br>
						<br><table width=90% border=1 bordercolor=#000000 style=border-collapse:collapse>
							<tr>
								<td>用户</td>
								<td>入职日期</td>
								<td>所属站点</td>
							</tr>";
			foreach ($user_arr_all as $a){
				if($a['site_id']==$u['site_id']){
					$message.="<tr><td>".$a['nickname']."</td>
						   <td>".$a['entry_date']."</td>
						   <td>".$a['site_name']."</td>
					   </tr>";
				}
			}
			$message=$message."</table>";
			$sql="select id from sw_sys_user where hr_status in (1,3) and cal_site_id_str like '%".$u['site_id']."%' and is_pay_cal_user=0";
			$site_user_arr=db()->query($sql);
			foreach ($site_user_arr as $s){
				$user_msg_arr[]=array(
						'user_id'=>$s['id'],
						'msg_tit'=>date('Y-m',strtotime($month_range[0])).'月 '.$u['site_name'].'  有'.$u['count(*)'].'人开始考核',
						'msg_desc'=>json_encode(str_replace(PHP_EOL, '', $message)),
						'type_flag'=>4,
						'action_url'=>'',
						'create_time'=>date('Y-m-d H:m:s',time())
				);
			}
		}
		return $user_msg_arr;
	}
	public function index_4_ajax($month_range){
		$tr_row=isset($_GET['tr_row'])?$_GET['tr_row']:'';
		if(session('cur_user_info.is_pay_cal_user')==1){exit('没有权限');}
		$sit_sql='(';
		foreach (explode(',',session('cur_user_info.cal_site_id_str')) as $key=>$si){
			if($key==0){
				$sit_sql.="site_id='$si' ";
			}else{
				$sit_sql.=" or site_id='$si' ";
			}
			
		}
		$sit_sql.=' )';
		$sql="select nickname,entry_date,(select site from sw_sys_site where id=sw_sys_user.site_id) as site_name from sw_sys_user where hr_status in (1,3) and MONTH(entry_date)='".$month_range[0]."' and YEAR(entry_date)!='".$month_range[1]."' and $sit_sql";
		$user_arr=db()->query($sql);
		$title=array('用户','入职日期','所属站点');
		$data[0]['style']['sheet']='全部';
		$data[0]['style']['style']=array('style');
		$data[0]['data']=$user_arr;
		array_unshift($data[0]['data'],$title);
		$i=1;
		$data[$i]['data'][]=$title;
		$data[$i]['style']['sheet']=$user_arr[0]['site_name'];
		$data[$i]['style']['style']=array('style');
		$data['sheet_show']=$i;
		foreach ($user_arr as $key=>$u){
			if($key>0){
				if($u['site_name']!=$user_arr[$key-1]['site_name']){
					$i++;
					$data[$i]['data'][]=$title;
					$data[$i]['style']['sheet']=$u['site_name'];
					$data[$i]['style']['style']=array('style');
					if($u['site_name']==$tr_row){
						$data['sheet_show']=$i;
					}
				}
			}
			$data[$i]['data'][]=$u;
		}
		if($i==1){unset($data[0]);$data['sheet_show']=0;}
		$data['style']=array(
				'freezePane'=>'1',
				'ret'=>'1',
				'cell'=>array(
						'B'=>array('width'=>12),
					)
		);
		$data['name']=$month_range[0].'月年度考核表';
		
		excel_css($data);
	}
	/**
	 *生日提醒
	 */
	public function index_5($month_range){
		$user_msg_arr='';
		//exit(session('site_id'));
		$sql="select site_id,count(*),(select site from sw_sys_site where id=sw_sys_user.site_id) as site_name from sw_sys_user where hr_status in (1,3) and MONTH(birthday)='".date('m', strtotime($month_range[0]))."' group by site_id";
		$user_arr=db()->query($sql);
		
		$sql="select site_id,id,nickname,birthday from sw_sys_user where hr_status in (1,3) and MONTH(birthday)='".date('m', strtotime($month_range[0]))."' order by site_id";
		$user_arr_all=db()->query($sql);
		foreach ($user_arr as $u){
			$message=date('Y-m',strtotime($month_range[1]))."月  ".$u['site_name']." 有".$u['count(*)']."人过生日:  <a href=".url('/sys/msg_outo/index_ajax')."?name=5&month_range_star=$month_range[0]&month_range_end=$month_range[1]&tr_row=".$u['site_name']." class='btn btn-minier btn-primary' style='margin-left:20px;'>导出报表</a><br>
						<br><table width=90% border=1 bordercolor=#000000 style=border-collapse:collapse>
							<tr>
								<td>用户</td>
								<td>生日日期</td>
							</tr>";
			foreach ($user_arr_all as $a){
				if($a['site_id']==$u['site_id']){
					$message.="<tr><td>".$a['nickname']."</td>
						   <td>".date('m-d', strtotime($a['birthday']))."</td>
					   </tr>";
				}
			}
			$message.='</table>';
			
			
			$sql="select id from sw_sys_user where hr_status in (1,3) and cal_site_id_str like '%".$u['site_id']."%' and is_pay_cal_user=0";
			$site_user_arr=db()->query($sql);
			foreach ($site_user_arr as $s){
				$user_msg_arr[]=array(
						'user_id'=>$s['id'],
						'msg_tit'=>date('Y-m',strtotime($month_range[0])).'月 '.$u['site_name'].'  有'.$u['count(*)'].'人过生日',
						'msg_desc'=>json_encode(str_replace(PHP_EOL, '', $message)),
						'type_flag'=>5,
						'action_url'=>'',
						'create_time'=>date('Y-m-d H:m:s',time())
				);
			}
			
		}
		return $user_msg_arr;
	}
	public function index_5_ajax($month_range,$user_id){
		$tr_row=isset($_GET['tr_row'])?$_GET['tr_row']:'';
		
		if(session('cur_user_info.is_pay_cal_user')==1){exit('没有权限');}
		$sit_sql='(';
		foreach (explode(',',session('cur_user_info.cal_site_id_str')) as $key=>$si){
			if($key==0){
				$sit_sql.="site_id='$si' ";
			}else{
				$sit_sql.=" or site_id='$si' ";
			}
				
		}
		$sit_sql.=' )';
		
		$sql="select nickname,birthday,(select site from sw_sys_site where id=sw_sys_user.site_id) as site_name from sw_sys_user where hr_status in (1,3) and MONTH(birthday)='".date('m', strtotime($month_range[0]))."' and $sit_sql";
		$user_arr=db()->query($sql);
		$title=array('用户','生日','所属站点');
		$data[0]['style']['sheet']='全部';
		$data[0]['style']['style']=array('style');
		$data[0]['data']=$user_arr;
		array_unshift($data[0]['data'],$title);
		$i=1;
		$data[$i]['data'][]=$title;
		$data[$i]['style']['sheet']=$user_arr[0]['site_name'];
		$data[$i]['style']['style']=array('style');
		$data['sheet_show']=$i;
		foreach ($user_arr as $key=>$u){
			if($key>0){
				if($u['site_name']!=$user_arr[$key-1]['site_name']){
					$i++;
					$data[$i]['data'][]=$title;
					$data[$i]['style']['sheet']=$u['site_name'];
					$data[$i]['style']['style']=array('style');
					if($u['site_name']==$tr_row){
						$data['sheet_show']=$i;
					}
				}
			}
			$data[$i]['data'][]=$u;
		}
		if($i==1){unset($data[0]);$data['sheet_show']=0;}
		$data['style']=array(
				'freezePane'=>'1',
				'ret'=>'1',
				'cell'=>array(
						'B'=>array('width'=>12),
				)
		);
		$data['name']=$month_range[0].'月生日人员';
		excel_css($data);
		
	}
	/**
	 *合约到期
	 */
	public function index_6(){
	
	}
}
