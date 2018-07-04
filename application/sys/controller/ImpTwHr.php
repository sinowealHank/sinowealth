<?php
namespace app\sys\controller;

use think\Controller;
use app\index\controller\Admin;
use think\File;
use think\Request;

//导入台湾考勤数据
class ImpTwHr extends Admin
{	
	public function index() {

		if(IS_POST){
			// 获取表单上传文件 例如上传了001.jpg
			$file = request()->file('tw_hr_file');
			$data = $this->request->post();
			$year_month=$data['year_month'];
			$year=date('Y',strtotime($year_month));
			$month=(int)date('m',strtotime($year_month));
			
			// 移动到对应目录下
			$file_info=$file->getInfo();
			$file_move_info=$file->move(config('UPLOAD_FILE_URL').DS.'tw_entry_data');
			
			$upload_file_info=array();
			if($file_move_info){
				// 成功上传后 获取上传信息
				$upload_file_info['name_o']=$file_info['name'];		//原始文件名
				$upload_file_info['name_n']=$file_move_info->getFilename();		//保存的文件名
				$upload_file_info['ext']=$file_move_info->getExtension();		//保存的文件名
				//保存的完整路径
				$upload_file_info['url']=config('UPLOAD_FILE_URL').DS.'tw_entry_data'.DS.$file_move_info->getSaveName();
				$upload_file_info['size']=$file_info['size'];
				$upload_file_info['flag_key']='tw_hr';
				$upload_file_info['user_id']=get_user_id();
				$upload_file_info['create_time']=date('Y-m-d H:i:s',time());
				$upload_file_info['edit_time']=date('Y-m-d H:i:s',time());
				
				db('sys_file_upload')->insert($upload_file_info);
				
				$get_hr_data_result=read_excel_file($upload_file_info);
				
				$insert_arr=array();
				//写数据到台湾考勤表
				foreach ($get_hr_data_result as $key=>$val){
					$temp_arr=array();					
					$temp_arr['recno']=0;
					$temp_arr['ctrl_id']=31;
					$temp_arr['emp_no']=$val[0];
					$temp_arr['card_no']=$val[0];
					$temp_arr['entry_dt']=$val[2];
					$temp_arr['entry_date']=date('Y-m-d',strtotime($val[2]));
					$temp_arr['status']=1;
					array_push($insert_arr, $temp_arr);
				}
				
				$count_num=count($get_hr_data_result);				
				db('entry_dt_tw')->insertAll($insert_arr);
				
				//写考勤数据导入记录
				//判断当前月份是否有导入数据记录
				$hr_data=db('sys_imp_tw_hr')->where('year='.$year.' and month='.$month)->find();
				$imp_arr=array();
				if($hr_data){
					
					$hr_data['user_id']=get_user_id();
					$hr_data['log'] = date('Y-m-d H:i:s').' 由'.get_user_nickname().' 导入数据( '.$count_num.' )条!<br>'.$hr_data['log'];
					$hr_data['count_num']=$count_num;
					$hr_data['update_time']=date('Y-m-d H:i:s',time());
					db('sys_imp_tw_hr')->update($hr_data);					
					
				}else{
					$imp_arr['year']=$year;
					$imp_arr['month']=$month;
					$imp_arr['tit']=$year_month.'月考勤数据';
					$imp_arr['user_id']=get_user_id();
					$imp_arr['log']=date('Y-m-d H:i:s').' 由'.get_user_nickname().' 导入数据( '.$count_num.' )条!';
					$imp_arr['count_num']=$count_num;
					$imp_arr['create_time']=date('Y-m-d H:i:s',time());
					$imp_arr['update_time']=date('Y-m-d H:i:s',time());
					db('sys_imp_tw_hr')->insert($imp_arr);
				}
				
				echo setServerBackJson(1,$year_month.'导入数据 '.$count_num.' 条成功!',url(''));
				
			}else{
				// 上传失败获取错误信息
				echo $file->getError();
			}
		}else{
			$page_info=array();
			$map=array();
			$model=db('sys_imp_tw_hr');
			$page_info['list']=$this->_list($model,$map,'id',false);
			$page_info['page']=$page_info['list']->render();			
			
			//准备需要上传月份数据,当前月份和上个月月份,如果上个月考勤数据已经上传,则只显示当前月份
			//判断上月考勤数据是否已经上传
			$month_arr=array();
			$last_month=strtotime(get_last_month());
			$last_month_hr=db('sys_imp_tw_hr')->where('year='.date('Y',$last_month).' and month='.(int)date('m',$last_month))->find();
			
			if(!$last_month_hr){
				$date1=date('Y-m',$last_month);
				$month_arr[0]['tit']=$date1.'月 考勤';
				$month_arr[0]['value']=$date1;
				$month_arr[1]['tit']=date('Y-m',time()).'月 考勤';
				$month_arr[1]['value']=date('Y-m',time());
			}else{
				$month_arr[0]['tit']=date('Y-m',time()).'月 考勤';
				$month_arr[0]['value']=date('Y-m',time());
			}
			
			$page_info['month_arr']=$month_arr;
			
			if($page_info['list']->total()>0){
				$page_info['empty']=0;
			}else{
				$page_info['empty']=1;
			}
			
			$this->assign('page_info',$page_info);
			return $this->fetch();		
		}
		
	}
	
	//校验当前上传的月份是否已经有过考勤数据上传记录
	public function check_tw_hr_month(){
		$data=$this->param;
		
		$year=date('Y',strtotime($data['year_month']));
		$month=(int)date('m',strtotime($data['year_month']));
		$is_have=db('sys_imp_tw_hr')->where('year='.$year.' and month='.$month)->find();
		
		if($is_have){
			echo $data['year_month'].'月 考勤数据已经导入,确定覆盖?';			
		}
	}
	
	
	
	

}
