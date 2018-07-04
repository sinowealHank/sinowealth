<?php
namespace app\sys\controller;

use think\Controller;
use app\index\controller\Admin;

class Site extends Admin
{
	/**
	 * 站点设置
	 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
	 */
	public function index() {
		$data=db('sys_site')->where('status=1 and id in ('.$_SESSION['u_i']['cal_site_id_str'].')')->select();		
		$this->assign('data',$data);
		return $this->fetch();
	}

	//新增功能
	public function add(){	 
		if (IS_POST) {
			$data = $this->request->post();
			//数据校验
			$result=$this->validate($data,'Site');
			if($result !== true){
				echo setServerBackJson(0, $result);
				exit;
			}
	
			//站点名称重复检测
			$id=db('sys_site')->where(" site='".$data['site']."' or zh_name='".$data['zh_name']."' or en_name='".$data['en_name']."'")->value('id');
			if($id>0){
				echo setServerBackJson(0,'站点名称&中文名&英文名称重复!');
				exit;
			}
	
			$id=db('sys_site')->insertGetId($data);
	
			if (false !== $id) {
				cache('site_cache_arr',null);
				echo setServerBackJson(1, '站点'.$data['site'].'添加成功！','index','closeDialog');
			} else {
				echo setServerBackJson(0, '数据添加失败!请联系管理员');
				exit;
			}
		}else{
			return $this->fetch();
		}
	}	
	
	//编辑功能
	public function edit(){
		if (IS_POST) {
			$data = $this->request->post();
			//数据校验
			$result=$this->validate($data,'Site');
			if($result !== true){
				echo setServerBackJson(0, $result);
				exit;
			}
	
			//站点名称重复检测
			$id=db('sys_site')->where(" (site='".$data['site']."' or zh_name='".$data['zh_name']."' or en_name='".$data['en_name']."') and id <>".$data['id'])->value('id');
			if($id>0){
				echo setServerBackJson(0,'站点名称&中文名&英文名称重复!');
				exit;
			}
	
			$id=db('sys_site')->update($data);
	
			if (false !== $id) {				 
				cache('site_cache_arr',null);				
				echo setServerBackJson(1, '站点'.$data['site'].'编辑成功！','index','closeDialog');
			} else {
				echo setServerBackJson(0, '数据添加失败!请联系管理员');
				exit;
			}
		} else {
			$page_info=array();
		
			$data=$this->request->param();
			$page_info=db('sys_site')->where('id='.$data['id'])->find();
			
			$this->assign('page_info',$page_info);
			return $this->fetch();
		}
	}
	
	//获取当前月份假期
	public function calendar(){
				
		$page_info=array();
		//获取站点信息
		$page_info['site_id'] = $this->request->param('id');
		
		$page_info['local_year']=date('Y',time());
		$page_info['next_year']=$page_info['local_year']+1;		

		//获取此站点假日信息
		$temp_arr=get_site_holiday(date('Y-m',time()),$page_info['site_id'],true);
		$page_info['holiday_json']=json_encode($temp_arr);
		
		//传递参数到页面
		$this->assign('page_info',$page_info);
		return $this->fetch();
	}
	
	//返回某月假期设置
	public function get_month_holiday(){
		$date=$this->request->param('month');
		$site_id=$this->request->param('site_id');
		echo json_encode(get_site_holiday($date,$site_id));
	}
	
	//设定本年度,下年度周末为假期
	public function set_weekend(){
		$flag=$this->request->param('flag');
		$site_id=$this->request->param('site_id');
		if($flag=='local_year'){
			$year_val=date('Y',time());
		}else{
			$year_val=date('Y',time())+1;
		}
		
		//本年度周末假日删除
		$sql="delete from sw_sys_holiday where year(holiday_date)='".$year_val."' and site_id=".$site_id." and (dayofweek(holiday_date)=1 or dayofweek(holiday_date)=7)";
		db()->query($sql);
			
		//本年度周六日期插入
		$sun_arr=$day_month=get_year_weekend($year_val,7);
		$data_arr=array();
		foreach ($sun_arr as $key=>$val){
			$data_arr[$key]['site_id']=$site_id;
			$data_arr[$key]['holiday_name']='周六';
			$data_arr[$key]['holiday_date']=$val;
			$data_arr[$key]['create_user_id']=get_user_id();
			$data_arr[$key]['create_date']=date('Y-m-d H:i:s',time());
		}
		db('sys_holiday')->insertAll($data_arr);
			
		//本年度周日期插入
		$sun_arr=$day_month=get_year_weekend($year_val,1);
		$data_arr=array();
		foreach ($sun_arr as $key=>$val){
			$data_arr[$key]['site_id']=$site_id;
			$data_arr[$key]['holiday_name']='周日';
			$data_arr[$key]['holiday_date']=$val;
			$data_arr[$key]['create_user_id']=get_user_id();
			$data_arr[$key]['create_date']=date('Y-m-d H:i:s',time());
		}
		db('sys_holiday')->insertAll($data_arr);
			
		echo $year_val.'周末假日设置成功!';		
	}
	
	//某日是否假日切换
	public function switch_holiday(){
		$data=$this->param;
		
		$holiday_name=$data['holiday_name'];
		$holiday_type=$data['holiday_type'];
		$holiday_data=$data['holiday_date'];
		$site_id=$data['site_id'];
		$current_month=$data['current_month'];
	
		if($this->is_current_month($current_month,$holiday_data)){
			if(strlen($holiday_name)==0){
				$holiday_name='假日';
			}
			
			$return=array();
			$data_arr=array();
			
			//判断当前日期是否已经设定为假日
			$id=db('sys_holiday')->where("holiday_date='".$holiday_data."' and site_id=".$site_id)->find();
			if($id){
				//返回是假日,删除当前假日
				$return['is_holiday']=1;
				db('sys_holiday')->where('id='.$id['id'])->delete();
			}else{
				//返回非假日,添加当前假日
				$return['is_holiday']=0;
				
				$data_arr['site_id']=$site_id;
				$data_arr['holiday_name']=$holiday_name;
				$data_arr['holiday_type']=$holiday_type;
				$data_arr['holiday_date']=$holiday_data;
				$data_arr['create_user_id']=get_user_id();
				$data_arr['create_date']=date('Y-m-d H:i:s',time());
				db('sys_holiday')->insert($data_arr);
			}
			$return['is_current_month']=1;
			echo json_encode($return);
		}else{
			$return['is_current_month']=0;
			$return['msg']='非本月范围,不操作!';
			echo json_encode($return);
		}
	}
	
	//删除某假日
	public function del_event(){
		$holiday_date=$this->request->param('holiday_date');
		$current_month=$this->request->param('current_month');
		if(isDate($holiday_date)){
			db('sys_holiday')->where("holiday_date='".$holiday_date."'")->delete();			
		}
	}
	
	//判断是否为当前操作月份事项,非当前月份操作事项不予以操作
	public function is_current_month($current_month,$month=""){
		$current_month_arr=get_begin_last_date($current_month);
		if(strtotime($month)>=strtotime($current_month_arr[0]) && strtotime($month)<=strtotime($current_month_arr[1])){
			return true;
		}else{
			return false;
		}
	}
	
	
	
}
