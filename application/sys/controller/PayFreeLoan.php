<?php
namespace app\sys\controller;

use think\Controller;
use think\Db;
use app\index\controller\Admin;

class PayFreeLoan extends Admin{
	public function index(){	
		$free=db()->query("SELECT *,(SELECT SUM(free_all) FROM sw_pay_free_user b where b.free_id=a.id) as money FROM sw_pay_free a order by a.id");
		$this->assign('free',$free);
		$this->assign('title',array(
				'b.id'=>'编号',
				'name'=>'项目',
				'en_name'=>'部门',
				'user_gh'=>'工号',
				'nickname'=>'名字',
				'free_all'=>'借款总额',
				'balance'=>'剩余欠款',
				'free_data'=>'借款日期',
				'user_remarks'=>'备注',
				'remarks'=>'项目备注',
		));
		$this->assign('width',array(
				'b.id'=>'',
				'name'=>'',
				'en_name'=>'',
				'user_gh'=>'',
				'nickname'=>'',
				'free_all'=>'',
				'balance'=>'',
				'free_data'=>'',
				'user_remarks'=>'width:200px',
				'remarks'=>'width:100px',
		));
		return $this->fetch();
	}
	public function index_ajax(){
		$sql_ban=' where 1=1 ';
		$free_id=isset($_POST['free_id'])?$_POST['free_id']:'(select max(id) from sw_pay_free)';
		if($free_id){
			$sql_ban=$sql_ban." and b.free_id='$free_id' ";
		}
		$free_status=isset($_POST['free_status'])?$_POST['free_status']:'';
		if($free_status){
			$sql_ban=$sql_ban." and b.free_status='$free_status' ";
		}
		$sort_name=isset($_POST['ii'])?$_POST['ii']:'b.id';
		$zorf=isset($_POST['a'])?$_POST['a']:'1';
		$sql_ban=$sql_ban." order by $sort_name ";
		if($zorf){
			$sql_ban=$sql_ban." desc ";
		}
		
		
		//分页
		$page=isset($_POST['page'])?$_POST['page']:'1';
		$show=isset($_POST['show'])?$_POST['show']:'20';
		$all_shumu=db()->query("select count(*) from sw_pay_free_user b LEFT JOIN sw_pay_free a ON a.id=b.free_id LEFT JOIN sw_sys_user u ON u.user_gh=b.user_gh LEFT JOIN sw_sys_dep d on u.dep_id=d.id $sql_ban");
		//获取总分页数
		$zy = $all_shumu[0]['count(*)'] / $show;
		$zy = ceil($zy);
		//判断页码正确性
		if($page>$zy){$page=$zy;}
		if($page<=0){$page=1;}
		$star=$page*$show-$show;
		$sql_ban=$sql_ban." LIMIT $star,$show ";
		
		$free_user=db()->query("SELECT a.*,b.*,u.nickname,d.en_name,(b.free_all-(CASE WHEN (select SUM(give_money) from sw_pay_free_user_details cc where cc.free_user_id=b.id) THEN (select SUM(give_money) from sw_pay_free_user_details cc where cc.free_user_id=b.id) ELSE '0' END)) as balance FROM sw_pay_free_user b LEFT JOIN sw_pay_free a ON a.id=b.free_id LEFT JOIN sw_sys_user u ON u.user_gh=b.user_gh LEFT JOIN sw_sys_dep d on u.dep_id=d.id $sql_ban");
		echo json_encode(array($free_user,$page,$star,$all_shumu[0]['count(*)'],$zy,$show));
	}
	//新建项目
	public function new_free(){
		$name=isset($_POST['name'])?$_POST['name']:'';
		if($name==''){exit(json_encode(array(0,'请输入名称')));}
		$id=isset($_POST['id'])?$_POST['id']:'';
		$page_info['id']=$_POST['id'];
		$page_info['name']=$_POST['name'];
		$page_info['remarks']=$_POST['remarks'];
		if($id){
			db('pay_free')->update($page_info);
		}else{
			$id=db('pay_free')->insertGetId($page_info);			
		}
		echo json_encode(array(1,'完成',$id));
	}
	//删除项目
	public function del_free(){
		$id=isset($_POST['id'])?$_POST['id']:'';
		if($id==''){exit(json_encode(array(0,'缺少id')));}
		$free_user=db()->query("select * from sw_pay_free_user where free_id=$id");
		if($free_user){exit(json_encode(array(0,'该项目有数据，不能删除')));}
		db('pay_free')->delete($id);
		echo json_encode(array(1,'完成',$id));
	}
	//新增一个借款人
	public function new_free_user(){
		foreach ($_POST as $key=>$p){
			if($p=='' && $key!='user_remarks'){
				exit(json_encode(array(0,'存在空值')));
			}
		}
		if($_POST['free_all']<=0){
			exit(json_encode(array(0,'金额必须大于0')));
		}
		$user_gh=db()->query("select id,user_gh from sw_sys_user where user_gh='".$_POST['user_gh']."'");
		if(!$user_gh){exit(json_encode(array(0,'该工号不存在')));}
		$user_gh=db()->query("select id,user_gh from sw_pay_free_user where user_gh='".$_POST['user_gh']."' and free_status!=1");
		if($user_gh){exit(json_encode(array(0,'这个人已经有一笔欠款了')));}
		$user_gh=db()->query("select id from sw_pay_free where id='".$_POST['free_id']."'");
		if(!$user_gh){exit(json_encode(array(0,'该项目不存在')));}
		$page_info['free_data']=date("Y-m-d H:i:s");
		
		$page_info['free_id']=$_POST['free_id'];
		$page_info['user_gh']=$_POST['user_gh'];
		$page_info['free_all']=$_POST['free_all'];
		$page_info['user_remarks']=$_POST['user_remarks'];
		$new_free_id=Db::name('pay_free_user')->insertGetId($page_info);
		exit(json_encode(array(1,'完成')));
	}
	//修改贷款人备注
	public function user_remarks_change(){
		$page_info['id']=$_POST['id'];
		$page_info['user_remarks']=$_POST['user_remarks'];
		db('pay_free_user')
		->update($page_info);
		exit(json_encode(array(1,'修改完成')));
	}
	//修改用户的总借款金额
	public function change_free_user(){
		$id=isset($_POST['id'])?$_POST['id']:'';
		if($id==''){exit(json_encode(array(0,'缺少id')));}
		$user_pd=db()->query("select * from sw_pay_free_user where id=$id");
		if($user_pd){
			if($user_pd[0]['free_status']==1){
				exit(json_encode(array(0,'该项目已结案')));
			}
		}
		$page_info['log']=array('exp','CONCAT("'.$_POST['log'].',old:'.$_GET['free_old'].'。<br>",log)');
		$page_info['free_all']=isset($_POST['free_all'])?$_POST['free_all']:'';
		db('pay_free_user')
			->where('id', $id)
			->update($page_info);;
		exit(json_encode(array(1,'完成')));
	}
	//用户检索
	public function user_sour(){
		$sour=isset($_POST['sour'])?$_POST['sour']:'';
		$user_gh=db()->query("select d.en_name,u.user_gh,u.nickname from sw_sys_user u left join sw_sys_dep d on d.id=u.dep_id where (u.user_gh like '%$sour%' or u.nickname like '%$sour%' or u.id='$sour' or u.email like '%$sour%') and hr_status=1");
		$table='<table class="old_free" style="width: 195px;margin: 0;"><tr><td><div>部门</div></td><td><div>工号</div></td><td><div>名字</div></td></tr>';
		foreach ($user_gh as $u){
			$table=$table."<tr user_in='".$u['user_gh']."' onclick='user_in(\"".$u['user_gh']."\",\"".$u['nickname']."\")'><td><div>".$u['en_name']."</div></td><td><div>".$u['user_gh']."</div></td><td><div>".$u['nickname']."</div></td>";
		}
		$table=$table.'</table>';
		echo json_encode($table);
	}
	//用户还款详情
	public function user_money(){
		$id=isset($_POST['id'])?$_POST['id']:'';
		if($id==''){exit(json_encode(array(0,'缺少id')));}
		$user_money_arr=db()->query("SELECT a.*,b.*,c.*,u.nickname,d.en_name FROM sw_pay_free_user b LEFT JOIN sw_pay_free a ON a.id=b.free_id LEFT JOIN sw_pay_free_user_details c ON b.id=c.free_user_id LEFT JOIN sw_sys_user u ON u.user_gh=b.user_gh LEFT JOIN sw_sys_dep d on u.dep_id=d.id where b.id='$id'");
		$this->assign('user_money_arr',$user_money_arr);
		return $this->fetch();
	}
	//新的还款项目
	public function new_user_money_save(){
		foreach ($_POST as $key=>$p){
			if($p=='' && $key!='user_money_remarks'){
				exit(json_encode(array(0,'存在空值')));
			}
		}
		$user_gh=db()->query("select id from sw_pay_free_user where id='".$_POST['free_user_id']."'");
		if(!$user_gh){exit(json_encode(array(0,'该项目不存在')));}
		
		$free_user=db()->query("SELECT a.*,b.*,u.nickname,d.en_name,(b.free_all-(CASE WHEN (select SUM(give_money) from sw_pay_free_user_details cc where cc.free_user_id=b.id) THEN (select SUM(give_money) from sw_pay_free_user_details cc where cc.free_user_id=b.id) ELSE '0' END)) as balance FROM sw_pay_free_user b LEFT JOIN sw_pay_free a ON a.id=b.free_id LEFT JOIN sw_sys_user u ON u.user_gh=b.user_gh LEFT JOIN sw_sys_dep d on u.dep_id=d.id where b.id='".$_POST['free_user_id']."'");
		if($free_user[0]['balance']-$_POST['give_money']<0){
			exit(json_encode(array(0,'还款后数额不得数额小于0')));
		}	
		
		$page_info['give_data']=date("Y-m-d H:i:s");
		$page_info['statu']=1;
		
		$page_info['free_user_id']=$_POST['free_user_id'];
		$page_info['give_money']=$_POST['give_money'];
		$page_info['user_money_remarks']=$_POST['user_money_remarks'];
		$new_free_id=Db::name('pay_free_user_details')->insertGetId($page_info);
		exit(json_encode(array(1,'完成')));
	}
	//修改还款金额
	public function change_free_user_money(){
		foreach ($_POST as $p){
			if($p==''){
				exit(json_encode(array(0,'存在空值')));
			}
		}
		$free_user=db()->query("SELECT a.*,b.*,u.nickname,d.en_name,(b.free_all-(CASE WHEN (select SUM(give_money) from sw_pay_free_user_details cc where cc.free_user_id=b.id) THEN (select SUM(give_money) from sw_pay_free_user_details cc where cc.free_user_id=b.id) ELSE '0' END)) as balance FROM sw_pay_free_user b LEFT JOIN sw_pay_free a ON a.id=b.free_id LEFT JOIN sw_sys_user u ON u.user_gh=b.user_gh LEFT JOIN sw_sys_dep d on u.dep_id=d.id where b.id='".$_GET['id']."'");
		$pd_money=$free_user[0]['balance']+$_GET['old_money']-$_POST['give_money'];
		if($pd_money<0){
			exit(json_encode(array(0,'修改后数额不得数额小于0')));
		}
		$_POST['user_money_details_log']=array('exp','CONCAT("old:'.$_GET['old_money'].'。<br>",user_money_details_log)');
		//判断是否已结案
		$id=isset($_POST['id'])?$_POST['id']:'';
		$user_pd=db()->query("select * from sw_pay_free_user_details c left join sw_pay_free_user b on b.id=c.free_user_id where c.id=$id");
		if($user_pd){
			if($user_pd[0]['free_status']==1){
				exit(json_encode(array(0,'该项目已结案')));
			}
		}
		
		$page_info['id']=$_POST['id'];
		$page_info['give_money']=$_POST['give_money'];
		db('pay_free_user_details')
		->update($page_info);
		exit(json_encode(array(1,'完成'))); 
	}
	//发送邮件通知
	public function free_email(){
		foreach ($_POST as $p){
			if($p==''){
				exit(json_encode(array(0,'存在空值')));
			}
		}
		$id=isset($_POST['id'])?$_POST['id']:'';
		$user_email=db()->query("SELECT u.email,a.*,b.*,u.nickname,d.en_name,(b.free_all-(CASE WHEN (select SUM(give_money) from sw_pay_free_user_details cc where cc.free_user_id=b.id) THEN (select SUM(give_money) from sw_pay_free_user_details cc where cc.free_user_id=b.id) ELSE '0' END)) as balance FROM sw_pay_free_user b LEFT JOIN sw_pay_free a ON a.id=b.free_id LEFT JOIN sw_sys_user u ON u.user_gh=b.user_gh LEFT JOIN sw_sys_dep d on u.dep_id=d.id where b.id='$id' and free_status!=1");
		if($user_email){
			if($user_email[0]['balance']<=0){
				db('pay_free_user')->where('id',$id)->update(['free_status'=>1]);
				send_email($user_email[0]['email'], '贷款项目 ['.$user_email[0]['name'].'] 完结通知', $user_email[0]['name'].'已经完结,请及时登录内管薪资查看.<br><a href="http://hr.sinowealth.com">http://hr.sinowealth.com</a>');
				exit(json_encode(array(1,'完成')));
			}else{
				exit(json_encode(array(0,'还没有还清哦')));
			}
		}else{
				exit(json_encode(array(0,'没有该项数据或已发送过邮件')));
			}
		
	}
	//回写
	public function return_this(){
		$sour=isset($_POST['sour'])?$_POST['sour']:'';
		$user_gh=db()->query("select id,user_gh,nickname from sw_sys_user where user_gh like '%$sour%' or nickname like '%$sour%' or id='$sour'");
		$user='';
		foreach ($user_gh as $u){
			if($user==''){
				$user="'".$u['user_gh']."'";
			}else{
				$user=$user.",'".$u['user_gh']."'";
			}
		}
		$free=db()->query("SELECT a.*,b.*,u.nickname,d.en_name,(b.free_all-(CASE WHEN (select SUM(give_money) from sw_pay_free_user_details cc where cc.free_user_id=b.id) THEN (select SUM(give_money) from sw_pay_free_user_details cc where cc.free_user_id=b.id) ELSE '0' END)) as balance FROM sw_pay_free_user b LEFT JOIN sw_pay_free a ON a.id=b.free_id LEFT JOIN sw_sys_user u ON u.user_gh=b.user_gh LEFT JOIN sw_sys_dep d on u.dep_id=d.id where b.user_gh in ($user)");
		foreach ($free as $key=>$f){
			$return[$key]['工号']=$f['user_gh'];
			$return[$key]['用户名']=$f['nickname'];
			$return[$key]['项目名']=$f['name'];
			$return[$key]['欠款总额度']=$f['free_all'];
			$return[$key]['剩余额度']=$f['balance'];
			$return[$key]['借出时间']=$f['free_data'];
			$return[$key]['邮件状态']=$f['free_status'];
		}
		return $return;
	}
}