<?php
namespace app\sys\controller;

use think\Controller;
use app\index\controller\Admin;

//用户管理
class User extends Admin
{
	/**
	 * 员工管理
	 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
	 */
	public function index() {
		$data=$this->param;
		$page_info=array();
		
		//列表过滤器，生成查询Map对象
		$map=array();
		
		//关键字搜索
		if(!isset($data['key'])){
			$key='';
		}else{
			$key=trim($data['key']);
		}
		
		if(strlen($key)>0){
			$map['exp_logic']='and';		
			$map['exp'] =" (user_gh like '%".$key."%') or (email like '%".$key."%') or (nickname like '%".$key."%')";
		}
		
		//状态字符过滤
		if(!isset($data['hr_status'])){
			$map['hr_status']=1;
			$page_info['hr_status']=1;
		}else{
			if($data['hr_status']=='all'){
				unset($map['hr_status']);
				$page_info['hr_status']='all';
			}else{
				$map['hr_status']=$data['hr_status'];
				$page_info['hr_status']=$map['hr_status'];
			}
		}
		
		//部门关键字过滤
		if(!isset($data['dep_id'])){
			$page_info['dep_id']='all';
		}else{
			if($data['dep_id']=='all'){
				unset($map['dep_id']);
				$page_info['dep_id']='all';
			}else{
				$map['dep_id']=$data['dep_id'];
				$page_info['dep_id']=$map['dep_id'];
			}
		}
		
		//岗位关键字过滤
		if(!isset($data['hr_job_type_id'])){
			$page_info['hr_job_type_id']='all';
		}else{
			if($data['hr_job_type_id']=='all'){
				unset($map['hr_job_type_id']);
				$page_info['hr_job_type_id']='all';
			}else{
				$map['hr_job_type_id']=$data['hr_job_type_id'];
				$page_info['hr_job_type_id']=$map['hr_job_type_id'];
			}
		}
				
		$name= "sys_user";
		$model = db($name);
		
		if($_SESSION['admin_flag'] != 1){
			$map['status']=1;
			$map['user_status']=1;
		}else{
			if(!isset($data['hr_status'])){
				unset($map['hr_status']);
				$page_info['hr_status']='all';
			}else{
				if($data['hr_status']=='all'){
					unset($map['hr_status']);
					$page_info['hr_status']='all';
				}else{
					$map['hr_status']=$data['hr_status'];
				}				
			}			
		}
		
		if(isset($data['site_id'])){
			if($data['site_id'] !='all'){
				$map['site_id']=$data['site_id'];
				$page_info['site_id']=$data['site_id'];
			}else{
				$map['site_id']=array('in',$_SESSION['u_i']['cal_site_id_str']);
				$page_info['site_id']='all';
			}			
		}else{
			$map['site_id']=array('in',$_SESSION['u_i']['cal_site_id_str']);
			$page_info['site_id']='all';
		}		
		
		if (! empty ( $model )) {
			//$page_info['list']=$model->where($map)->order('id desc')->paginate($paginate['list_rows']);
			$page_info['list']=$this->_list($model,$map,'id',false);
		}
		$page_info['page']=$page_info['list']->render();
		$page_info['key']=$key;
		
		$param=$this->param;
		if(isset($param['page'])){
			$page_info['cur_page']=$param['page'];
		}else{
			$page_info['cur_page']=1;
		}
		
		//准备岗位数据
		$page_info['hr_job_type_arr']=config('hr_job_type');
		
		//准备站点数据
		$page_info['site_arr']=db('sys_site')->where('id in ('.$_SESSION['u_i']['cal_site_id_str'].')')->select();
		
		//准备部门数据
		$dep_db_arr=db('sys_dep')->where('status=1')->field('id,pid,en_name,par_id')->order('par_id')->select();
		$page_info['dep_select']=get_dep_select($dep_db_arr,'filter','dep_id','','');
		
		if($page_info['list']->total()>0){
			$page_info['empty']=0;
		}else{
			$page_info['empty']=1;
		}
		
		$page_info['tit_str']="&nbsp;&nbsp;当前条件统计人数:&nbsp;&nbsp;<span class='red'>".$page_info['list']->total()."人</span>";
		
		
		/*获得当前用户的所有权限*/
		$pri_arr = get_session_privilege();
		/*获得当前模块和控制*/
		$module_controller = get_current_url();
		$page_info['pri_data'] = $pri_arr;
		$page_info['module_controller'] = $module_controller;
		
		$this->assign('page_info',$page_info);
		return $this->fetch();
	}
	
	//新增员工
	public function add(){

		if (IS_POST) {
			
			$data = $this->request->post();
			/*获得角色id*/
			$role_id = $data['parent_id'];
			unset($data['parent_id']);

			
			//如果为不签订合约,则转换日期为0000
			if($data['pact_end_date']==0){
				$data['pact_end_date']="0000-00-00 00:00:00";
			}
		
			//数据校验
			$result=$this->validate($data,'User');
		
			if($result !== true){
				echo setServerBackJson(0, $result);
				exit;
			}
			
			//用户权限组校验
			if($role_id==0){
				echo setServerBackJson(0,'请选择用户权限组!');
				exit;
			}
		
			//用户工号重复检查
			$id=db('sys_user')->where(" user_gh='".$data['user_gh']."'")->value('id');
			if($id>0){
				echo setServerBackJson(0,'用户工号重复!');
				exit;
			}
			
			//部门必须选择
			if($data['dep_id']==0){
				echo setServerBackJson(0,'请选择用户部门!');
				exit;
			}	
			
			//入职日期
			if(strlen($data['entry_date'])==0){
				echo setServerBackJson(0,'入职日期不能为空!');
				exit;
			}

			
			//初始密码
			//$data['d_pwd']=$data['password'];
			//生成盐值
			//$data['salt']=rand_string(7,0,'2345678');
			//$data['password']=set_user_pwd($data['d_pwd'],$data['salt']);
			$data['d_pwd']=$data['password'];
			$data['password']=md5($data['password']);			
			$data['create_time']=date('Y-m-d H:i:s',time());
			
			
			$id=db('sys_user')->insertGetId($data);
		
			if (false !== $id) {
				
				//重新缓存用户数据
				cache('user_cache_arr',null);

				//添加角色到数据库
				$insertData['role_id'] = $role_id;
				$insertData['user_id'] = $id;
				db('user_role')->insert($insertData);
				
				echo setServerBackJson(1, '用户'.$data['nickname'].'添加成功！','index','closeDialog');
			} else {
				echo setServerBackJson(0, '数据添加失败!请联系管理员');
				exit;
			}
		}else{
			$page_info=array();
			
			//准备部门数据
			$dep_db_arr=db('sys_dep')->where('status=1')->field('id,pid,en_name,par_id')->order('par_id')->select();
			$page_info['dep_select']=get_dep_select($dep_db_arr,'add','dep_id','','form-control');
			
			//准备站点数据
			$page_info['site_arr']=cache('site_cache_arr');
			
			//学历数据
			$page_info['hr_edu_arr']=config('hr_edu');
			
			//职位名称
			$page_info['hr_technical_arr']=config('hr_technical');
			
			//职等数据
			$page_info['hr_work_level_arr']=config('hr_work_level');
			
			//岗位数据
			$page_info['hr_job_type_arr']=config('hr_job_type');
			
			//在职状态
			$page_info['hr_status_arr']=config('hr_status');
			
			//考勤主管数据
			$page_info['hr_user_info']=db('sys_user')->field('id,dep_id,nickname')->where('is_hr_manage=1 and status=1 and user_status=1')->select();
			
			//考勤规则
			$page_info['hr_role_info']=db('sys_hr_role')->field('id,role_name')->where('status=1')->select();
			
			//默认密码生成
			$page_info['pwd']=rand_string(7,0,'2345678');
			
			//当前日期
			$page_info['cur_date']=date('Y-m-d',time());
			
			//权限组别数据
			$page_info['role_arr']=get_role_arr();

			//角色数据
			$role = new \app\sys\model\Role();
			$data = $role->getTree();
			$data = json_decode(json_encode($data));
			$data = object_array($data);
			foreach($data as $k =>$v){
				$role .= '<option value="'.$v['id'].'">'.str_repeat('&nbsp;', 8*$v['level']).$v['c_group_name'].'</option>';
			}
			$page_info['role'] = $role;
			$this->assign('page_info',$page_info);
			return $this->fetch();
		}
	}
	
	//编辑员工
	public function edit(){
		$id=input('id');
		$page_info=array();
		$page_info['cur_page']=$this->request->param('cur_page');

		if (IS_POST) {
			$data = $this->request->post();

		   /*获得角色id*/
			$role_id = $data['parent_id'];
			unset($data['parent_id']);

			$cur_page=$data['cur_page'];
			unset($data['cur_page']);
			$data['update_time']=date('Y-m-d H:i:s',time());
				
			//如果为不签订合约,则转换日期为0000
			if($data['pact_end_date']==0){
				$data['pact_end_date']="0000-00-00 00:00:00";
			}
		
			//数据校验
			$result=$this->validate($data,'User.edit');
		
			if($result !== true){
				echo setServerBackJson(0, $result);
				exit;
			}
			
			//用户权限组校验
			if($role_id==0){
				echo setServerBackJson(0,'请选择用户权限组!');
				exit;
			}
		
			//用户工号重复检查
			$id=db('sys_user')->where(" user_gh='".$data['user_gh']."' and user_status=1  and id<>".$id)->value('id');
			if($id>0){
				echo setServerBackJson(0,'用户工号重复!');
				exit;
			}
			
			//用户密码字段获取
			if(strlen($data['passwordd'])>0 && strlen($data['passwordd'])>5){
				$data['password']=md5($data['passwordd']);
			}elseif(strlen($data['passwordd'])>0 && strlen($data['passwordd'])<5){
				echo setServerBackJson(0,'用户密码不能小于6位');
				exit;
			}			
			
			if(isset($data['is_hr_cal_user']) || isset($data['is_pay_cal_user'])){
				//选择了是考勤管理人员&薪资管理人员,未设置站点
				if($data['is_hr_cal_user']==1 || $data['is_pay_cal_user']==1 ){
					if(!isset($data['cal_site_id'])){
						echo setServerBackJson(0,'设置了是考勤人员,咋不选站点了?');
						exit;
					}
				}		
			}
			
			//是否有设置管理站点
			if(isset($data['cal_site_id'])){
				if($data['is_hr_cal_user']==1 || $data['is_pay_cal_user']==1 ){
					$data['cal_site_id_str']=implode(',',$data['cal_site_id']);
					if(strlen($data['cal_site_id_str'])==0){
						echo setServerBackJson(0,'设置了是考勤人员,咋不选站点了?');
						exit;
					}
					unset($data['cal_site_id']);
				}else{
					$data['cal_site_id_str']='';
					unset($data['cal_site_id']);
				}
			}else{
				$data['cal_site_id_str']='';
			}
			
			unset($data['passwordd']);
			
			//非系统管理员禁止修改管理信息
			if($_SESSION['admin_flag']==0){
				unset($data['is_hr_cal_user']);
				unset($data['is_pay_cal_user']);
				unset($data['cal_site_id_str']);
				unset($data['user_status']);
				unset($data['cal_hr_user']);
				unset($data['manage_flag']);
			}
			
			$result=db('sys_user')->update($data);
		
			if (false !== $result){
				db('user_role')->where('user_id='.$data['id'])->delete();
				//更新数据user_role表的数据
				$temp_arr=array();
				$temp_arr['user_id']=$data['id'];
				$temp_arr['role_id']=$role_id;
				db('user_role')->where('user_id',$data['id'])->insert($temp_arr);
		
				//重新缓存用户数据
				cache('user_cache_arr',null);
				
				//如果编辑人员为当前用户,刷新当前用户session
				if($data['id']==get_user_id()){
					session('cur_user_info',$data);
				}
		
				echo setServerBackJson(1, '用户'.$data['nickname'].'编辑成功！');
			} else {
				echo setServerBackJson(0, '数据编辑失败!请联系管理员');
				exit;
			}
		}else{
			
			//员工数据准备
			$page_info['user_info']=db('sys_user')->where('id='.$id)->find();
		
			//准备部门数据
			$dep_db_arr=cache('dep_cache_arr');
			$page_info['dep_select']=get_dep_select($dep_db_arr,'edit','dep_id',$page_info['user_info']['dep_id'],'form-control');
				
			//准备站点数据
			$page_info['site_arr']=cache('site_cache_arr');
				
			//学历数据
			$page_info['hr_edu_arr']=config('hr_edu');
			
			//职位名称
			$page_info['hr_technical_arr']=config('hr_technical');
				
			//职等数据
			$page_info['hr_work_level_arr']=config('hr_work_level');
				
			//岗位数据
			$page_info['hr_job_type_arr']=config('hr_job_type');
			
			//在职状态
			$page_info['hr_status_arr']=config('hr_status');
			
			//考勤主管数据
			$page_info['hr_user_info']=db('sys_user')->field('id,dep_id,nickname')->where('is_hr_manage=1 and status=1 and user_status=1 and hr_status=1')->select();
			
			//考勤规则
			$page_info['hr_role_info']=db('sys_hr_role')->field('id,role_name')->where('status=1')->select();
			
			//当前日期
			$page_info['cur_date']=date('Y-m-d',time());
			
			//年资数据获取
			
			//权限组别数据
			$page_info['role_arr']=get_role_arr();
			
			$page_info['user_seniority']=user_seniority($page_info['user_info']['entry_date'],$page_info['user_info']['out_seniority']);

			//取出当前角色信息
			$role_id = db('user_role')->where('user_id',$id)->value('role_id');
			$role = new \app\sys\model\Role();
			$data = $role->getTree();
			$data = object_change_array($data);
			$role = '';
			foreach($data as $k =>$v){
				if($role_id == $v['id']){
					$check = 'selected="selected"';
				}else{
					$check = '';
				}
				$role .= '<option value="'.$v['id'].'" '.$check.'>'.str_repeat('&nbsp;', 8*$v['level']).$v['c_group_name'].'</option>';
			}
			$page_info['role'] = $role;


			$this->assign('page_info',$page_info);
			return  $this->fetch();
		}
	}
	
	//获取员工最大编号
	public function get_max_username(){
		$max_username=db('sys_user')->where('user_status=1')->order('user_gh desc')->field('user_gh')->find();
		echo $max_username['user_gh'];
	}
	
	//用户家庭成员管理
	function family(){
		if (IS_POST) {
			$data = $this->request->post();
			$family_model=db('sys_user_family');
			if($data['family_id']==""){
				$temp_arr=array();
				$temp_arr['name']=$data['name'];
				$temp_arr['hr_family_type_id']=$data['hr_family_type_id'];
				$temp_arr['address']=$data['address'];
				$temp_arr['conn_num']=$data['conn_num'];
				$temp_arr['user_id']=$data['user_id'];
				$temp_arr['create_time']=date('Y-m-d H:i:s',time());
				
				if(strlen($temp_arr['name'])==0){
					echo setServerBackJson(0, '家属姓名不能为空!');
					exit;
				}
				
				$is_have=$family_model->where('user_id='.$temp_arr['user_id']." and name='".$data['name']."' and status=1")->find();
				if($is_have){
					echo setServerBackJson(0, '家属姓名重复,请检查数据!');
					exit;
				}
				
				$id=$family_model->insertGetId($temp_arr);
				
			}else{
				$temp_arr=array();
				$temp_arr['id']=$data['family_id'];
				$temp_arr['name']=$data['name'];
				$temp_arr['hr_family_type_id']=$data['hr_family_type_id'];
				$temp_arr['address']=$data['address'];
				$temp_arr['conn_num']=$data['conn_num'];
				$temp_arr['user_id']=$data['user_id'];
				$temp_arr['edit_time']=date('Y-m-d H:i:s',time());
				
				if(strlen($temp_arr['name'])==0){
					echo setServerBackJson(0, '家属姓名不能为空!');
					exit;
				}
				
				$id=db('sys_user_family')->update($temp_arr);
			}
			
			//重新获取该员工所有家属信息,输出返回信息
			$family_arr=db('sys_user_family')->where('user_id='.$temp_arr['user_id'].' and status=1')->order('create_time desc')->select();
			
			//更新家庭成员与用户关系字段
			foreach ($family_arr as $key=>$val){
				$family_arr[$key]['hr_family_type']=get_cache_data('hr_family_type', $val['hr_family_type_id']);
			}
			echo setServerBackJson(1, json_encode($family_arr));
			
		}else{
			$page_info=array();
		
			$user_id=input('id');
			//获取本员工家庭成员信息
			$page_info['list']=db('sys_user_family')->where('user_id='.$user_id."  and status=1")->select();
			$page_info['user_id']=$user_id;
			$page_info['hr_family_type_arr']=config('hr_family_type');
			
			$this->assign('page_info',$page_info);
			return $this->fetch();
		}
	}
	
	//获取某家庭成员信息
	public function get_family_info(){
		$id=input('id');
		$family_user_arr=db('sys_user_family')->where('id='.$id)->find();
		echo json_encode($family_user_arr);
	}
	
	//删除某家庭成员
	function del_family(){
		$id=input('id');
		$user_id=db('sys_user_family')->where('id='.$id)->value('user_id');
		$family_user_arr=db('sys_user_family')->where('id='.$id)->delete();
		
		//重新获取该员工所有家属信息,输出返回信息
		$family_arr=db('sys_user_family')->where('user_id='.$user_id.' and status=1')->order('create_time desc')->select();
			
		//更新家庭成员与用户关系字段
		foreach ($family_arr as $key=>$val){
			$family_arr[$key]['hr_family_type']=get_cache_data('hr_family_type', $val['hr_family_type_id']);
		}
		echo setServerBackJson(1, json_encode($family_arr));
	}
	
	//查看某员工考勤
	public function user_hr(){
		$user_id=$this->request->param('user_id');		
		$page_info=array();
		$return_arr=array();
		$page_info['cur_page']=$this->request->param('cur_page');
		$user_info=db('sys_user')->where('id='.$user_id)->find();
		
		$date=date('Y-m-d',time());
		//获取某用户某月份的考勤数据
		$return_arr=get_user_month_hr($date,$user_id);
		
		$page_info['site_id']=$user_info['site_id'];
		
		$page_info['hr_json']=json_encode($return_arr);
		$page_info['user_info']=$user_info;
		
		$this->assign('page_info',$page_info);
		return $this->fetch();
	}
	
	/**
	 * 获取某用户某月份考勤数据,返回json值
	 */
	public function get_user_month_hr(){
		$date=$this->request->param('date');
		$user_id=$this->request->param('user_id');
		//获取某用户某月份的考勤数据
		$return_arr=get_user_month_hr($date,$user_id);
		echo json_encode($return_arr['events']);
	}
	
	
	//导出员工基本信息报表
	function ext_repo(){
		$tit_field=array('ID','姓名','性别','工号','门禁卡号','部门','站点','职等','状态','手机','分机','邮箱','学历','生日','身份证号','毕业院校','紧急联络人',
						 '联络人联系方式','地址','入职日期','职称','N次续约','总工作年资','考勤主管','考勤规则','合约到期日','是否考勤主管','银行帐号','公积金帐号',
				    	 '社保帐号','薪资审核人');
		$body_field=array('id','nickname','sex','user_gh','card_id','en_name','site','hr_work_level_id','hr_status','mobile','ext_tel','email','hr_edu_id','birthday','identity_card','university','eme_contact_name','eme_contact_info','address','entry_date','hr_technical_id','pact_num','total_work_year','hr_user_id','role_name','pact_end_date','is_hr_manage','bank_card','fund_account','social_security_account','pay_user_id_1');
		$file_name="user_info-".date('Y-m-d',time()).'.xls';
		
		$str_head="";
		$str_body="";
		
		$sql="select 
			u.id,u.nickname,(case when u.sex=1 then '男' else '女' end) as sex,u.user_gh,u.card_id,d.en_name,s.site,u.hr_work_level_id,u.hr_status,
			u.mobile,u.ext_tel,u.email,u.hr_edu_id,u.birthday,
		    u.identity_card,u.university,u.eme_contact_name,u.eme_contact_info,u.address,u.entry_date,u.hr_technical_id,u.pact_num,
			u.total_work_year,
			(select nickname from sw_sys_user su where su.id=u.hr_user_id) as hr_user_id, 
			r.role_name,u.pact_end_date,
		    (case when u.is_hr_manage=1 then '是' else '不是' end) as is_hr_manage,u.bank_card,u.fund_account,u.social_security_account,
			(select nickname from sw_sys_user su where su.id=u.pay_user_id_1) as pay_user_id  
		from 
			sw_sys_user u,sw_sys_site s,sw_sys_hr_role r,sw_sys_dep d
		where
			u.site_id=s.id and u.role_id=r.id and u.dep_id=d.id and u.user_status=1 and u.status=1
		order by
			hr_work_level_id,dep_id";
		 
		$user_info_arr=db()->query($sql);
		
		//用户数据字段填充
		foreach ($user_info_arr as $key=>$val){
			$user_info_arr[$key]['hr_work_level_id']=config('hr_work_level_id')[$val['hr_work_level_id']];
			$user_info_arr[$key]['hr_status']=config('hr_status')[$val['hr_status']];
			$user_info_arr[$key]['hr_edu_id']=config('hr_edu_id')[$val['hr_edu_id']];	
		}
		
		header('Content-Type: application/vnd.ms-execl; charset=utf-8');
		header('Content-Disposition: attachment; filename='.$file_name);
		header('Pragma: no-cache');
		header('Expires: 0');
		
		
		foreach ($tit_field as $key=>$val){
			$str_head .= $val.chr(9);
		}
		$str_head .= chr(13);
		
		foreach ($user_info_arr as $key=>$val){
			foreach($body_field as $k=>$v){
				$str_body .= $val[$v].chr(9);
			}
			 $str_body.=chr(13);
		}
		
		echo auto_charset($str_head.$str_body,'utf-8','gbk');
	}
	
	
	
	public function user_education(){
		$id=isset($_GET['id'])?$_GET['id']:'';
		if($id==''){
			$user_educa=array();
		}else{
			$sql="select id,user_gh,name,education,degree,schooling,major,in_school,out_school,school from sw_user_education where user_gh='".get_cache_data('user_info',$id,'user_gh')."'";
			$user_educa=db()->query($sql);
		}
		$div='<table class="wtq_o" style="margin: 0 auto;"><thead><tr>
				<td style="width:70px"><div>工号</div></td>
				<td style="width:70px"><div>名字</div></td>
				<td style="width:110px"><div>学历</div></td>
				<td style="width:80px"><div>学位</div></td>
				<td style="width:50px"><div>学制</div></td>
				<td style="width:120px"><div>专业</div></td>
				<td style="width:80px"><div>入校时间</div></td>
				<td style="width:80px"><div>毕业时间</div></td>
				<td><div>毕业学校</div></td>
				<td><div>操作</div></td>
				</tr></thead><tbody>';
		foreach ($user_educa as $user){
			$div=$div.'<tr wtq_id='.$user['id'].'>';
			foreach ($user as $key=>$u){
				if($key!='id'){
					$div=$div.'<td><div wtq_t="'.$key.'">'.$u.'</div></td>';
				}
			}
			$div=$div.'<td><div><button onclick="change_education('.$user['id'].',this)" class="btn btn-xs btn-info">改</button> <button onclick="del_education(\'old\',this,'.$user['id'].')" class="btn btn-xs btn-info">删</button></div></td></tr>';
		}
		$div=$div.'</tbody>';
		$div=$div.'<!--tfoot><tr><td colspan="9"><button style="width:100%" onclick="tr_add()" class="btn btn-xs btn-success">再来一列</button></td></tr><tfoot--></table>';
		echo json_encode($div);
	}
	public function save_educa(){
		$pd=isset($_POST['pd'])?$_POST['pd']:'';
		if($pd){
			$id=db('user_education')->insertGetId($_POST['msg']);
		}else{
			$id=isset($_POST['id'])?$_POST['id']:'';
			db('user_education')
			->where('id', $id)
			->update($_POST['msg']);
		}
		echo json_encode(array(1,'完成',$id));
	}
	public function del_educa(){
		$id=isset($_POST['id'])?$_POST['id']:'';
		if($id){
			db('user_education')->delete($id);
		}
		echo json_encode(array(1,'完成'));
	}
}











