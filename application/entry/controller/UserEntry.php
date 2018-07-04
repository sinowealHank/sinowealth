<?php
namespace app\entry\controller;
use think\Cache;
use think\Db;
use app\index\controller\Admin;

class UserEntry extends Admin {	
	//数据拉取
	public function entry_data($role_id,$input=''){
		$page_info=array();
		//准备部门数据
		$dep_db_arr=db('sys_dep')->where('status=1')->field('id,pid,en_name,par_id')->order('par_id')->select();
		$page_info['dep_select']=get_dep_select($dep_db_arr,'add','dep_id','','form-control');
		//准备站点数据
		$page_info['site_arr']=cache('site_cache_arr');
		//学历数据
		$page_info['hr_edu_arr']=config('hr_edu');
		//家庭成员信息
		$page_info['hr_family_type_arr']=config('hr_family_type');
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
		$page_info['hr_role_info']=db('sys_hr_role')->field('id,role_name,site_id')->where('status=1')->select();
		
		//权限组别数据
		$page_info['role_arr']=get_role_arr();
		//取出当前角色信息??真·权限组别数据
		$role = new \app\sys\model\Role();
		$data = $role->getTree();
		$data = object_change_array($data);
		$role = '';
		$check_pd='';
		foreach($data as $k =>$v){
			if($role_id == $v['id']){
				$check = 'selected="selected"';$check_pd=1;
			}else{
				$check = '';
			}
			if($input){
				if($check){
					$role='<input value="'.$v['c_group_name'].'">';
				}
				
			}else{
				$role .= '<option value="'.$v['id'].'" '.$check.'>'.str_repeat('&nbsp;', 8*$v['level']).$v['c_group_name'].'</option>';
			}
			
		}
		if($check_pd==''){$role=$role.'<option selected="selected"></option>';}
		$page_info['role'] = $role;
		$this->assign('page_info', $page_info);
	}
		
	public function index_all(){
		//拉取数据
		$user=db()->query("select *,
								(SELECT en_name FROM sw_sys_dep where id=sw_extend_user.dep_id) as `dep_name`
							from
								sw_extend_user left join sw_extend_user_new_or_old
							on
								sw_extend_user.id=sw_extend_user_new_or_old.new_user_id
							where
								sw_extend_user_new_or_old.is_new='1'
							order
								by sw_extend_user.id desc");
		$this->assign('user', json_encode($user));
		$request = \think\Request::instance();
		return $this->fetch('index');
	}
	//异步查询排序
	public function index_ajax(){
		//判断排序方式
		$ii=isset($_GET['ii'])?$_GET['ii']:'id';
		$a=isset($_GET['a'])?$_GET['a']:'1';
		if($ii){$sql_paixu=' order by sw_extend_user.'.$ii;}
		if($a){$sql_paixu=$sql_paixu.' desc';}
		
		//查询备用
		//$nickname=isset($_POST['nickname'])?$_POST['nickname']:'';
		$sql='';
		//if($nickname){$sql=$sql." and sw_extend_user.nickname like '%".$nickname."%'";}
		$user=db()->query("select *,
								(SELECT en_name FROM sw_sys_dep where id=sw_extend_user.dep_id) as `dep_name`
							 from
								sw_extend_user left join sw_extend_user_new_or_old
							on
								sw_extend_user.id=sw_extend_user_new_or_old.new_user_id
							where
								sw_extend_user_new_or_old.is_new='1' ".$sql."
							".$sql_paixu);
		echo json_encode($user);
	}
	
	//拉取主页面第一步
	public function index(){
		return $this->index_all();
	}
	//个人信息添加页面
	public function new_user_add(){
		$id=isset($_GET['id'])?$_GET['id']:'';
		$this->assign('id', $id);
		//判断是否有id
		if($id){
			//查询数据是否存在或有查看权限
			$user=db()->query("select *,
								(select end_time from sw_extend_user_new_or_old where sw_extend_user_new_or_old.new_user_id='".$id."')
							as
								pact_end_date_time
							from
								sw_extend_user left join sw_extend_user_new_or_old
							on
								sw_extend_user.id=sw_extend_user_new_or_old.new_user_id
							where
								sw_extend_user_new_or_old.is_new='1'
							and
								sw_extend_user.id='".$id."'");
			if($user){
				$this->assign('user', $user[0]);
			}else{
				echo '该条数据不存在或已录入';
				exit;
			}
		}else{
			//定义一些必要数据
			$user[0]['id']='';
			$user[0]['dep_id']='';
			$user[0]['site_id']='';
			$user[0]['role_id']='';
		}
		//拉取对应数据
		$this->entry_data($user[0]['role_id']);
		
		
		$this->this_user_dep($user);
		$this->this_user_site($user);

		return $this->fetch();
	}
	//家庭数据
	public function new_user_family(){
		$id=isset($_GET['id'])?$_GET['id']:'';
		$this->assign('id', $id);
		//家庭成员称呼信息
		$page_info['hr_family_type_arr']=config('hr_family_type');
		$this->assign('page_info', $page_info);
		if($id){
			//拉取成员信息
			$user_family=db()->query("select *	from sw_extend_user_family where user_id='".$id."'");
			if($user_family){
				$this->assign('user_family', $user_family);
			}
		}
		return $this->fetch();
	}
	//个人信息添加&完善？？？？？？？？？？？校验？？？？？
	public function new_user_add_star(){
		$id=isset($_GET['id'])?$_GET['id']:'';
		//判断必填项
		if($_POST['nickname']==''){
			echo json_encode(array('0','名字是必须的'));
			exit;
		}
		if($_POST['identity_card']==''){
			echo json_encode(array('0','身份证是必须的'));
			exit;
		}		
		//判断合约时间
		if($_POST['try_month_flag']==='0'){
			//没有填默认为1年
			$_POST['pact_end_date']=isset($_POST['pact_end_date'])?$_POST['pact_end_date']:'';
			if($_POST['pact_end_date']==''){$_POST['pact_end_date']=1;}
			//提取时间
			$pact_end_date=$_POST['pact_end_date'];
			//判断时间为0则为0，不为0则计算时间
			if($_POST['pact_end_date']==='0'){
				$_POST['pact_end_date']='0000-00-00';
			}else{
				//如果有入职时间则在该基础上加，反之则在当前时间加
				if($_POST['entry_date']){
					$_POST['pact_end_date']=date("Y-m-d", strtotime("+".$pact_end_date." year", strtotime($_POST['entry_date'])));
				}else{
					$_POST['pact_end_date']=date("Y-m-d", strtotime("+".$pact_end_date." year"));
				}
			}
		}else{
			$_POST['pact_end_date']='';
		}
		if($id){
			//判断是实习则时间为空
			if($_POST['try_month_flag']==='0'){
				db('extend_user_new_or_old')
					->where('new_user_id',$id)
					->update(['end_time'=>$pact_end_date]);
			}else{
				db('extend_user_new_or_old')
					->where('new_user_id',$id)
					->update(['end_time'=>null]);
			}
			//重复校验
			if($_POST['user_gh']){$user_sql="or	sw_extend_user.user_gh='".$_POST['user_gh']."'";}else{$user_sql='';}
			$user=db()->query("select identity_card,user_gh
							from
								sw_extend_user left join sw_extend_user_new_or_old
							on
								sw_extend_user.id=sw_extend_user_new_or_old.new_user_id
							where
								sw_extend_user_new_or_old.is_new='1'
							and
								sw_extend_user.id!='".$id."'
							and
								(sw_extend_user.identity_card='".$_POST['identity_card']."'
							".$user_sql.")");
			if($user){
				echo json_encode(array('0','该身份证或工号已存在'));
				exit;
			}
			db('extend_user')
				->where('id', $id)
				->update($_POST);
		}else{
			//校验重复
			if($_POST['user_gh']){$user_sql="or	sw_extend_user.user_gh='".$_POST['user_gh']."'";}else{$user_sql='';}
			$user=db()->query("select identity_card
							from
								sw_extend_user left join sw_extend_user_new_or_old
							on
								sw_extend_user.id=sw_extend_user_new_or_old.new_user_id
							where
								sw_extend_user_new_or_old.is_new='1'
							and
								(sw_extend_user.identity_card='".$_POST['identity_card']."'
							".$user_sql.")");
			
			if($user){
				echo json_encode(array('0','已存在'));
				exit;
			}
			//获取时间
			if($_POST['entry_date']==''){
				$_POST['entry_date']=date("Y-m-d H:i:s");
			}
			
			//录入
			$user_id=db('extend_user')->insertGetId($_POST);
			//流程表格
			$process=db()->query("select id from sw_extend_process order by process_sort");
			foreach ($process as $p){
				$page_info[]=array('user_id'=>$user_id,'process_id'=>$p['id'],'log'=>get_user_id().'_'.date("Y-m-d H:i:s").'_添加了');
			}
			db('extend_process_and_user')->insertAll($page_info);
			//关联判断表
			if($_POST['try_month_flag']==='0'){
				$page_info=array('new_user_id'=>$user_id,'end_time'=>$pact_end_date);
			}else{
				$page_info=array('new_user_id'=>$user_id,'end_time'=>null);
			}
			db('extend_user_new_or_old')->insert($page_info);
		}
		echo json_encode(array('1',isset($user_id)?$user_id:$id));
	}
	//家庭信息添加&完善？？？？？？？？？？？校验？？？？？
	public function new_user_family_add_star(){
		//判断id
		if(!isset($_GET['id'])){
			echo json_encode(array('0','缺少id'));
			exit;
		}
		//生成数组
		$page_info='';
		foreach ($_POST['family_name'] as $key=>$p){
			if($p){
				$page_info[]=array(
						'name'=>$p,
						'hr_family_type_id'=>$_POST['family_relationship'][$key],
						'conn_num'=>$_POST['family_tel'][$key],
						'address'=>$_POST['family_adress'][$key],
						'user_id'=>$_GET['id'],
						'create_time'=>date("Y-m-d H:i:s")
				);
			}
		}
		//如果有值则录入
		if($page_info){
			db('extend_user_family')->insertAll($page_info);
		}
		//如果有修改的部分则进入修改
		if(isset($_POST['family_name_change'])){
			$this->new_user_family_change_star();
			exit;
		}
		echo json_encode(array('1','<!--家庭成员-->信息录入完成'));
	}
	//家庭信息修改..no
	public function new_user_family_change_star(){
		//循环修改
		foreach ($_POST['family_name_change'] as $key=>$p){
			if($p){
				$page_info=array(
						'name'=>$p,
						'hr_family_type_id'=>$_POST['family_relationship_change'][$key],
						'conn_num'=>$_POST['family_tel_change'][$key],
						'address'=>$_POST['family_adress_change'][$key],
						'user_id'=>$_GET['id'],
						'create_time'=>date("Y-m-d H:i:s")
				);
				db('extend_user_family')
				->where('id', $_POST['family_id'][$key])
				->update($page_info);
			}
		}
		echo json_encode(array('1','<!--家庭成员-->信息录入完成'));
	}
	
	//拉取主页面第二步
	public function index_e(){
		return $this->index_all();
	}
	//分机号，邮箱录入页面
	public function goods_grant(){
		$id=isset($_GET['id'])?$_GET['id']:'';
		$this->assign('id', $id);
		//判断id是否存在
		if($id){
			//拉取数据
			$user=db()->query("select nickname,ext_tel,email
							from
								sw_extend_user left join sw_extend_user_new_or_old
							on
								sw_extend_user.id=sw_extend_user_new_or_old.new_user_id
							where
								sw_extend_user_new_or_old.is_new='1'
							and
								sw_extend_user.id='".$id."'");
			if($user){
				$this->assign('user', $user[0]);
			}else{
				echo '该条数据不存在或已录入';
				exit;
			}
			return $this->fetch();
		}else{
			echo json_encode(array('0','缺少id'));
			exit;
		}
	}
	//分机号，邮箱录入？？？？？？？？？？？校验？？？？？
	public function goods_grant_star(){
		$id=isset($_GET['id'])?$_GET['id']:'';
		//ext_tel,email
		//录入数据
		if($id){
			//防止夹带其他数据
			db('extend_user')
			->where('id', $id)
			->update(array('ext_tel'=>$_POST['ext_tel'],'email'=>$_POST['email']));
		}else{
			echo json_encode(array('0','缺少id'));
			exit;
		}
		echo json_encode(array('1','完成'));
	}
	
	
	//拉取主页面第四步
	public function index_s(){
		return $this->index_all();
	}
	//查看员工信息
	public function look_user_information(){
		$id=isset($_GET['id'])?$_GET['id']:'';
		$user=db()->query("select *,
							(SELECT en_name FROM sw_sys_dep where id=sw_extend_user.dep_id) as `dep_name`,
							(SELECT site FROM sw_sys_site where id=sw_extend_user.site_id) as `site_name`
							from
								sw_extend_user left join sw_extend_user_new_or_old
							on
								sw_extend_user.id=sw_extend_user_new_or_old.new_user_id
							where
								sw_extend_user_new_or_old.is_new='1'
							and
								sw_extend_user.id='".$id."'");
		if($user){
			$this->assign('user', $user[0]);
			$this->entry_data($user[0]['role_id'],'yes');
		}else{
			echo '该条数据不存在或已录入';
			exit;
		}		
		$user_family=db()->query("select *	from sw_extend_user_family where user_id='".$id."'");
		$this->assign('user_family', $user_family);
		return $this->fetch();
	}
	//发送邮件页面
	public function user_email(){
		$id=isset($_GET['id'])?$_GET['id']:'';
		$this->assign('id', $id);
		$name=isset($_GET['name'])?$_GET['name']:'';
		$this->assign('name', $name);
		return $this->fetch();
	}
	//录入正式数据库
	public function new_user_is_old(){
		$id=isset($_GET['id'])?$_GET['id']:'';
		//拉取需要判断的字符串
		$user=db()->query("select nickname,sex,identity_card,
									birthday,university,
									hr_edu_id,eme_contact_name,eme_contact_info,
									address,address1,
									dep_id,site_id,role_id,
									user_gh,email,
									technical_ng,hr_work_level_id,hr_job_type_id,
									entry_date,
									out_seniority,
									ext_tel,pact_end_date,
									hr_user_id,hr_role_id,is_hr_user,
									card_id,hr_status,is_hr_manage,
									bank_card,try_month 
							from
								sw_extend_user left join sw_extend_user_new_or_old
							on
								sw_extend_user.id=sw_extend_user_new_or_old.new_user_id
							where
								sw_extend_user_new_or_old.is_new='1'
							and
								sw_extend_user.id='".$id."'");
		//判断是否存在
		if(!$user){echo json_encode(array('0','该条数据已经录入或不存在'));exit;}
		//判断字段是否为空
		foreach ($user[0] as $key=>$u){ 
			if($u===''){
				echo json_encode(array('0','有为空字段，请填写完毕后在通过...'.$key));
				exit;
			}
		}
		//拉取除了id外所有的字段名，并在后边加一个逗号
		$table_COLUMN=db()->query("select CONCAT(COLUMN_NAME,',') as name from information_schema.COLUMNS where table_name = 'sw_extend_user' and COLUMN_NAME!='id' and table_schema='".\think\Config::get('database')['database']."';");
		//录入正式表
		$table_name='';
		//生成密码
		$_POST['password']=rand_string(7,0,'2345678');
		//循环生成字段sql
		foreach ($table_COLUMN as $t){
			if($t['name']=='password,'){
				$table_name=$table_name."'".md5($_POST['password'])."' as password,";
			}else{
				$table_name=$table_name.$t['name'];
			}
			
		}
		//过滤最后一个逗号
		$table_name=rtrim($table_name, ',');
		//转移数据
		db()->query("insert into sw_sys_user select NULL as id,".$table_name." from sw_extend_user where id='".$id."';");
		//返回新id
		$new_id=db()->query("SELECT LAST_INSERT_ID() as id");
		

		//录入家庭信息表格式同上
		$table_COLUMN=db()->query("select CONCAT(COLUMN_NAME,',') as name from information_schema.COLUMNS where table_name = 'sw_extend_user_family' and COLUMN_NAME!='id' and table_schema='".\think\Config::get('database')['database']."';");
		$table_name='';
		foreach ($table_COLUMN as $t){
			if($t['name']=='user_id,'){
				$table_name=$table_name.$new_id[0]['id'].' as user_id,';
			}else{
				$table_name=$table_name.$t['name'];
			}
		}
		$table_name=rtrim($table_name, ',');
		db()->query("insert into sw_sys_user_family select NULL as id,".$table_name." from sw_extend_user_family where user_id='".$id."'");
		//录入合约数据   读取数据
		$contract_user=db()->query("select id,end_time 
								from 
									sw_extend_user left join sw_extend_user_new_or_old 
								on 
									sw_extend_user.id=sw_extend_user_new_or_old.new_user_id
								where 
									sw_extend_user.id='".$id."'");
		//录入信息
		db('extend_contract_user')->insert(['user_id'=>$new_id[0]['id'],'end_time'=>$contract_user[0]['end_time'],'end_shield'=>1]);
		//修改状态
		db()->query("UPDATE sw_extend_user_new_or_old SET is_new='0' WHERE new_user_id = '".$id."' ");
		//发邮件
		$this->new_user_email($user,$_POST['password']);
		//拉取修改后数据并返回
		$user=db()->query("select *,
								(SELECT en_name FROM sw_sys_dep where id=sw_extend_user.dep_id) as `dep_name`
							from
								sw_extend_user left join sw_extend_user_new_or_old
							on
								sw_extend_user.id=sw_extend_user_new_or_old.new_user_id
							where
								sw_extend_user_new_or_old.is_new='1'
							order
								by sw_extend_user.id desc");
		echo json_encode(array('1','完成',$user));
	}
	
	//发送邮件
	public function new_user_email($user='',$pwd){
		//员工
		$body=isset($_POST['new_o'])?$_POST['new_o']:'';
		$title='入职完成';
		$body=$body.'您的初始密码为：'.$pwd;
		
		send_email($user[0]['email'], $title, $body, 'ad@chipwealth.com', "芯颖内管通知");
		
		//剩下的
		$body=isset($_POST['new_old'])?$_POST['new_old']:'';
		$title='已入职';
		
		$user_dep=db()->query("select email from sw_sys_user where id='".$user[0]['hr_user_id']."'");
		if($user_dep){
			send_email($user_dep[0]['email'], $title, $body, 'ad@chipwealth.com', "芯颖内管通知");
		}else{
			echo json_encode(array(0,'缺少主管id'));exit;
		}
		send_email(\think\Config::get('Entry_admin_email'), $title, $body, 'ad@chipwealth.com', "芯颖内管通知");
	}
	
	
	//流程表格
	public function process(){
		$process=db()->query("select * from sw_extend_process order by process_sort");
		$this->assign('process', json_encode($process));
		return $this->fetch();
	}
	//修改流程
	public function process_change(){
		//判断id
		$id=isset($_POST['id'])?$_POST['id']:'';
		if($id==''){
			echo json_encode(array('0','缺少id'));
			exit;
		}
		//修改排序
		$process_sort=isset($_POST['process_sort'])?$_POST['process_sort']:'';
		if($process_sort){
			db('extend_process')
				->where('id', $id)
				->update(['process_sort'=>$process_sort]);
			echo json_encode(array('1','完成'));
			exit;
		}
		//修改名称
		$process_name=isset($_POST['process_name'])?$_POST['process_name']:'';
		if($process_name){
			db('extend_process')
				->where('id', $id)
				->update(['process_name'=>$process_name]);
			echo json_encode(array('1','完成'));
			exit;
		}
		//修改是否允许跳过
		$process_skip=isset($_POST['process_skip'])?$_POST['process_skip']:'';
		if($process_skip!==''){
			db('extend_process')
				->where('id', $id)
				->update(['process_skip'=>$process_skip]);
			echo json_encode(array('1','完成'));
			exit;
		}
		//修改属于第几步
		$process_step=isset($_POST['process_step'])?$_POST['process_step']:'';
		if($process_step!==''){
			db('extend_process')
				->where('id', $id)
				->update(['process_step'=>$process_step]);
			echo json_encode(array('1','完成'));
			exit;
		}
		//修改是否屏蔽
		$process_shield=isset($_POST['process_shield'])?$_POST['process_shield']:'';
		if($process_shield!==''){
			db('extend_process')
			->where('id', $id)
			->update(['process_shield'=>$process_shield]);
			echo json_encode(array('1','完成'));
			exit;
		}
		
		echo json_encode(array('0','不能为空'));
	}
	//添加流程
	public function process_add(){
		$process_sort=isset($_POST['process_sort'])?$_POST['process_sort']:'';
		$process_name=isset($_POST['process_name'])?$_POST['process_name']:'';
		$process_skip=isset($_POST['process_skip'])?$_POST['process_skip']:'0';
		if($process_name==''){
			echo json_encode(array('0','缺少必要数据'));
			exit;
		}
		//如果排序为空则获取最大数并加一如果没有则为一
		if($process_sort==''){
			$sort=db()->query("select max(process_sort) from sw_extend_process");
			if($sort[0]['max(process_sort)']){
				$process_sort=$sort[0]['max(process_sort)']+1;
			}else{
				$process_sort=$sort[0]['max(process_sort)']=1;
			}
		}
		$data=array(
				'process_sort'=>$process_sort,
				'process_name'=>$process_name,
				'process_skip'=>$process_skip
		);
		$id=db('extend_process')->insertGetId($data);
		echo json_encode(array('1','完成',$id,$process_sort));
	}
	
	
	//查看进度详情
	public function progress(){
		$id=isset($_GET['id'])?$_GET['id']:'';
		//拉取用户数据
		$user=db()->query("SELECT *,(SELECT en_name FROM sw_sys_dep where id=sw_extend_user.dep_id) as `dep_name` FROM sw_extend_user where id='".$id."'");
		$this->assign('user', $user);
		//拉取表格数据
		$p_and_u=db()->query("SELECT  *,
								(SELECT nickname FROM sw_extend_user where id=sw_extend_process_and_user.user_id) as `user_name`,
								(SELECT nickname FROM sw_extend_user where id=sw_extend_process_and_user.ok_user_id) as `ok_user_name`,
		
								CASE `is_ok`
									WHEN 0 THEN 'class=\"btn btn-minier btn-danger\">未完成'
									WHEN 1 THEN 'class=\"btn btn-minier btn-success\">已完成'
									ELSE 'class=\"btn btn-minier btn-primary\">待完成'
								END as
									is_ok_name,
								CASE `process_step`
									WHEN 1 THEN 'progress_o'
									WHEN 2 THEN 'progress_e'
									WHEN 3 THEN 'progress_g'
									ELSE ' '
								END as
									process_step_name
								FROM
									sw_extend_process_and_user left join sw_extend_process
								on
									sw_extend_process_and_user.process_id=sw_extend_process.id
								where
									sw_extend_process_and_user.user_id='".$id."'
								and
									sw_extend_process.process_shield='0'
								order by
									sw_extend_process.process_sort");
		$page_info=array();
		//职位名称
		$page_info['hr_technical_arr']=config('hr_technical');
		//职等数据
		$page_info['hr_work_level_arr']=config('hr_work_level');
		$this->assign('page_info', $page_info);
		
		$this->assign('p_and_u', json_encode($p_and_u));
		return $this->fetch('progress');
	}
	//查看进度详情第一步
	public function progress_o(){
		return $this->progress();
	}
	//查看进度详情第二步
	public function progress_e(){
		return $this->progress();
	}

	//查看进度详情第四步
	public function progress_s(){
		return $this->progress();
	}
	//步骤完成跳过
	public function progress_change(){
		$user_id=isset($_POST['user_id'])?$_POST['user_id']:'';
		$process_id=isset($_POST['process_id'])?$_POST['process_id']:'';
		$is_ok=isset($_POST['is_ok'])?$_POST['is_ok']:'';
		$statu=isset($_POST['statu'])?$_POST['statu']:'';
		if($user_id=='' || $process_id=='' || $is_ok==''){
			echo json_encode(array('0','缺少数据'));
			exit;
		}
		$creat_time=date("Y-m-d H:i:s");
		$ok_user_id=get_user_id();
		//判断是否允许跳过
		if($is_ok==0){
			$process_skip=db()->query("select process_skip from sw_extend_process where id='".$process_id."'");
			if($process_skip[0]['process_skip']==0){
				echo json_encode(array('0','不允许跳过'));
				exit;
			}
		}
		
		//记录log
		if($is_ok==1){
			$log=array('exp','CONCAT("'.get_user_id().'_'.date("Y-m-d H:i:s").'完成了<br>'.'",log)');
		}else if($is_ok==0){
			$log=array('exp','CONCAT("'.get_user_id().'_'.date("Y-m-d H:i:s").'跳过了<br>'.'",log)');
		}else if($is_ok==2){
			$log=array('exp','CONCAT("'.get_user_id().'_'.date("Y-m-d H:i:s").'备注了<br>'.'",log)');
		}
		
		
		//进行修改
		db('extend_process_and_user')
			->where(['user_id'=> $user_id,'process_id'=>$process_id])
			->update(['is_ok'=>$is_ok,'statu'=>$statu,'creat_time'=>$creat_time,'ok_user_id'=>$ok_user_id,'log'=>$log]);
		//拉取新数据返回
		$p_and_u=db()->query("SELECT  *,
								(SELECT nickname FROM sw_extend_user where id=sw_extend_process_and_user.user_id) as `user_name`,
								(SELECT nickname FROM sw_extend_user where id=sw_extend_process_and_user.ok_user_id) as `ok_user_name`,
								CASE `is_ok`
									WHEN 0 THEN 'class=\"btn btn-minier btn-danger\">未完成'
									WHEN 1 THEN 'class=\"btn btn-minier btn-success\">已完成'
									ELSE 'class=\"btn btn-minier btn-primary\">待完成'
								END as
									is_ok_name,
								CASE `process_step`
									WHEN 1 THEN 'progress_o'
									WHEN 2 THEN 'progress_e'
									WHEN 3 THEN 'progress_g'
									ELSE ' '
								END as
									process_step_name
								FROM
									sw_extend_process_and_user left join sw_extend_process
								on
									sw_extend_process_and_user.process_id=sw_extend_process.id
								where
									sw_extend_process_and_user.user_id='".$user_id."'
								order by
									sw_extend_process.process_sort");
		echo json_encode(array('1','完成',$p_and_u));
	}
	/**
	 * 导出
	 */
	public function Entry_excel_out(){
		include \think\Config::get('public').'/../thinkphp/extend/PHPExcel/PHPExcel.php';//引入文件
		//创建对象
		$excel = new \PHPExcel();
		$excel->setActiveSheetIndex(0);
		$excel->getActiveSheet()->freezePane('A2');//冻结首行  2行则为3
		//Excel表格式,这里简略写了8列
		$letter = array('A','B','C','D','E','F','F','G');//可随动
		
		
		$id=isset($_GET['id'])?$_GET['id']:'';
		$user=db()->query("SELECT * FROM sw_extend_user where id='".$id."'");
		$this->assign('user', $user);
		$p_and_u=db()->query("SELECT  *,
								(SELECT nickname FROM sw_extend_user where id=sw_extend_process_and_user.user_id) as `user_name`,
								(SELECT nickname FROM sw_extend_user where id=sw_extend_process_and_user.ok_user_id) as `ok_user_name`,
								CASE `is_ok` 
									WHEN 0 THEN sw_extend_process_and_user.statu
									WHEN 1 THEN '√' 
									ELSE '' 
								END as 
									is_ok_name 
								FROM 
									sw_extend_process_and_user left join sw_extend_process 
								on 
									sw_extend_process_and_user.process_id=sw_extend_process.id 
								where 
									sw_extend_process_and_user.user_id='".$id."' 
								and
									sw_extend_process.process_shield='0'
								order by 
									sw_extend_process.process_sort");
		
		$endd=count($p_and_u)+6;
		//边框样式
		$color='auto';
		$styleArray = array(
				'borders' => array(
						'allborders' => array(
								//'style' => \PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
								'style' => \PHPExcel_Style_Border::BORDER_THIN,//细边框
								'color' => array('argb' => $color),
						),
				),
		);
		
		$excel->getActiveSheet()->getStyle('A3:E4')->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle('A6:E'.$endd)->applyFromArray($styleArray);
		//边框
		//$excel->getActiveSheet()->getStyle('A3:E4')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
		//$excel->getActiveSheet()->getStyle('B5:D5')->getBorders()->getAllBorders()->setBorderStyle();
		//$excel->getActiveSheet()->getStyle('A6:E'.$endd)->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
		$styleArray = array(
				'borders' => array(
						'left' => array(
								'style' => \PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
						),
						'right' => array(
								'style' => \PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
						),
				),
		);
		$excel->getActiveSheet()->getStyle('A5:E5')->applyFromArray($styleArray);
		$styleArray = array(
				'borders' => array(
						'top' => array(
								'style' => \PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
						),
						'left' => array(
								'style' => \PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
						),
						'right' => array(
								'style' => \PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
						),
				),
		);
		$excel->getActiveSheet()->getStyle('A3:E4')->applyFromArray($styleArray);
		$styleArray = array(
				'borders' => array(
						'bottom' => array(
								'style' => \PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
						),
						'left' => array(
								'style' => \PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
						),
						'right' => array(
								'style' => \PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
						),
				),
		);
		$excel->getActiveSheet()->getStyle('A6:E'.$endd)->applyFromArray($styleArray);
		
		
		
		
		//加粗和居中
		$excel->getActiveSheet()->getStyle('A1')->applyFromArray(
				array(
						'font' => array (
								'bold' => true
						),
						'alignment' => array(
								'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER
						)
				)
		);
		$excel->getActiveSheet()->getStyle('A6:E6')->applyFromArray(
				array(
						'alignment' => array(
								'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER
						)
				)
		);
		$excel->getActiveSheet()->getStyle('A1')->getFont()->setName('宋体')->setSize(18) ;//字体大小//->setBold(true); 字体加粗
		
		$excel->getActiveSheet()->getStyle('A3:E'.$endd)->getFont()->setBold(true);
		
		
		
		
		
		//工作表名称
		$excel->getActiveSheet()->setTitle('Type number分类'); //设置工作表名称
	
		
		$excel->getActiveSheet()->getColumnDimension('A')->setWidth(3);
		$excel->getActiveSheet()->getColumnDimension('B')->setWidth(44);
		$excel->getActiveSheet()->getColumnDimension('C')->setWidth(30);
		$excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
		$excel->getActiveSheet()->getColumnDimension('E')->setWidth(20);//行宽
		$a=count($p_and_u)+6;
		//设置单元格“自动换行”属性
		$excel->getActiveSheet()->getStyle('A1:E'.$a)->getAlignment()->setWrapText(true);
		$excel->getActiveSheet()->getStyle('A1:E'.$a)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
		//$excel->getActiveSheet()->setAutoFilter($excel->getActiveSheet()->calculateWorksheetDimension());//搜索

		$excel->getActiveSheet()->getRowDimension(1)->setRowHeight(40);
		$excel->getActiveSheet()->mergeCells("A1:E1");//合并
		$excel->getActiveSheet()->setCellValue("$letter[0]1","新人录用报到后须办事项");
		
		$excel->getActiveSheet()->getRowDimension(2)->setRowHeight(10);
		
		$excel->getActiveSheet()->getRowDimension(3)->setRowHeight(40);
		$excel->getActiveSheet()->mergeCells("A3:B3");//合并
		$excel->getActiveSheet()->setCellValue("$letter[0]3","姓名：".$user[0]['nickname']);
		
		$excel->getActiveSheet()->setCellValue("$letter[2]3","部门：".$user[0]['dep_id']);
		
		$excel->getActiveSheet()->mergeCells("D3:E3");//合并
		$excel->getActiveSheet()->setCellValue("$letter[3]3","邮件用户名：".$user[0]['email']);
		
		$excel->getActiveSheet()->getRowDimension(4)->setRowHeight(40);
		$excel->getActiveSheet()->mergeCells("A4:B4");//合并
		$excel->getActiveSheet()->setCellValue("$letter[0]4","职能：".$user[0]['technical_ng']);
		
		$excel->getActiveSheet()->setCellValue("$letter[2]4","职等：".$user[0]['hr_work_level_id']);
		
		$excel->getActiveSheet()->mergeCells("D4:E4");//合并
		$excel->getActiveSheet()->setCellValue("$letter[3]4","入职日期：".$user[0]['entry_date']);
		
		$excel->getActiveSheet()->getRowDimension(5)->setRowHeight(10);
		
		$excel->getActiveSheet()->getRowDimension(6)->setRowHeight(40);
		
		$excel->getActiveSheet()->getStyle('A6:E6')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('ABBAC3');
		//表头数组
		$tableheader = array('应办事项',"完成进度\n（√/未完成标注具体事项）",'操作日期','经办人签名');//可随动
		//填充表头信息
		$excel->getActiveSheet()->mergeCells("A6:B6");//合并
		$excel->getActiveSheet()->setCellValue("$letter[0]6","$tableheader[0]");
		$excel->getActiveSheet()->setCellValue("$letter[2]6","$tableheader[1]");
		$excel->getActiveSheet()->setCellValue("$letter[3]6","$tableheader[2]");
		$excel->getActiveSheet()->setCellValue("$letter[4]6","$tableheader[3]");
		
		
		for ($i = 7;$i <= count($p_and_u) + 6;$i++) {
			$excel->getActiveSheet()->getRowDimension($i)->setRowHeight(40);
			$excel->getActiveSheet()->setCellValue("$letter[0]$i",$i-6);
			$excel->getActiveSheet()->setCellValue("$letter[1]$i",$p_and_u[$i-7]['process_name']);
			$excel->getActiveSheet()->setCellValue("$letter[2]$i",$p_and_u[$i-7]['is_ok_name']);
			$excel->getActiveSheet()->setCellValue("$letter[3]$i",$p_and_u[$i-7]['creat_time']);
			$excel->getActiveSheet()->setCellValue("$letter[4]$i",$p_and_u[$i-7]['ok_user_name']);
		}
		//创建Excel输入对象
		$write = new \PHPExcel_Writer_Excel5($excel);
		ob_end_clean();//清除缓冲区,避免乱码
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
		header("Content-Type:application/force-download");
		header("Content-Type:application/vnd.ms-execl");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");;
		header('Content-Disposition:attachment;filename="testdata.xls"');//filename生成文件的名字//可随动
		header("Content-Transfer-Encoding:binary");
		$write->save('php://output');
	}
	//部门拼接
	public function this_user_dep($user){
		$dep=db()->query("select id,en_name,zh_name,par_id from sw_sys_dep where order_id > 0");
		$ii=0;
		foreach ($dep as $d){
			$arr=explode(',',$d['par_id']);
			$qzhui='';
			for($i=0;$i<count($arr)-1;$i++){
				if($i<count($arr)-2){
					$qzhui=$qzhui.'| ';
				}else{
					$qzhui=$qzhui.'|-';
				}
				
			}
			if($ii<count($arr)-1){$ii=count($arr)-1;}
			$shu=count($arr)-1;
			${'select_'.$shu}=isset(${'select_'.$shu})?${'select_'.$shu}:'';
			if($d['id']==$user[0]['dep_id']){$this_dep_ok='selected="selected"';$this_pd=1;}else{$this_dep_ok='';}
			${'select_'.$shu}=${'select_'.$shu}.'<option '.$this_dep_ok.' value="'.$d['id'].'"> '.$qzhui.$d['en_name'].'</option>';
		}
		$select_dep='';
		for($i=1;$i<=$ii;$i++){
			$select_dep=$select_dep.${'select_'.$i};
		}
		if(isset($this_pd)!=1){
			$select_dep='<option></option>'.$select_dep;
		}
		$this->assign('select_dep', $select_dep);
	}
	//站点拼接
	public function this_user_site($user){
		$site=db()->query("select id,site from sw_sys_site");
		$select_site='';
		foreach ($site as $s){
			if($s['id']==$user[0]['site_id']){$this_site_ok='selected="selected"';$this_pd=1;}else{$this_site_ok='';}
			$select_site=$select_site.'<option '.$this_site_ok.' value="'.$s['id'].'"> '.$s['site'].'</option>';
		}
		if(isset($this_pd)!=1){
			$select_site='<option></option>'.$select_site;
		}
		$this->assign('select_site', $select_site);
	}
}

/*//查看进度详情第三步
 public function progress_g(){
return $this->progress();
}*/

/*//拉取主页面第三步
 public function index_g(){
return $this->index_all();
}
//公司基本信息录入页面？？？？？？合约到期日？？？？？
public function new_user_perfect(){
$page_info=array();
//准备部门数据
$dep_db_arr=db('sys_dep')->where('status=1')->field('id,pid,en_name,par_id')->order('par_id')->select();
$page_info['dep_select']=get_dep_select($dep_db_arr,'add','dep_id','','form-control');
//准备站点数据
$page_info['site_arr']=cache('site_cache_arr');
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
//权限组别数据
$page_info['role_arr']=get_role_arr();
$this->assign('page_info', $page_info);

$id=isset($_GET['id'])?$_GET['id']:'';
$this->assign('id', $id);
if($id){
$user=db()->query("select *
		from
		sw_extend_user left join sw_extend_user_new_or_old
		on
		sw_extend_user.id=sw_extend_user_new_or_old.new_user_id
		where
		sw_extend_user_new_or_old.is_new='1'
		and
		sw_extend_user.id='".$id."'");
if($user){
$this->assign('user', $user[0]);
}else{
echo '该条数据不存在或已录入';
exit;
}

$this->this_user_dep($user);
$this->this_user_site($user);

return $this->fetch();
}else{
echo json_encode(array('0','缺少id'));
exit;
}
}
//公司基本信息录入？？？？？？？？？？？校验？？？？？
public function new_user_perfect_add(){
$id=isset($_GET['id'])?$_GET['id']:'';
if($id){
db('extend_user')
->where('id', $id)
->update($_POST);
}else{
echo json_encode(array('0','缺少id'));
exit;
}
echo json_encode(array('1','完成'));
}
*/

/*//默认密码生成
 $page_info['pwd']=rand_string(7,0,'2345678');
	
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
dump($page_info);

echo date("Y-m-d H:i:s", strtotime("+21 year"));
		echo time().'<br>';
		echo strtotime("+20 year").'<br>';
		
		$d = new \DateTime('8887-06-19');
		echo $d->format('U').'<br>'; //2444486400
		
		$d = new \DateTime('@'.$d->format('U'));
		$d->setTimezone(new \DateTimeZone('PRC'));
		echo $d->format('Y-m-d');
*/

