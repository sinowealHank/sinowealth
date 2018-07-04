<?php
namespace app\erp\controller;

use think\Controller;
use think\Db;
use app\index\controller\Admin;

class CheckPrice extends Admin{
	public function index(){
		$fac_name=db()->query("SELECT * FROM sw_erp_p_config");
		$this->assign('fac_name',$fac_name);
		
		return $this->fetch();
	}
	public function save_all_change(){
		//dump($_GET);
		//dump($_POST);
		$data=isset($_GET['site_code'])?$_GET['site_code']:'';
		$p_num=isset($_GET['p_num'])?$_GET['p_num']:'';
		$change=array('PMJ10','TA_PMJ33','TA_PMJ35','TA_PMJ36','PMJ07');
		if($p_num){
			//修改
			foreach ($_POST as $key=>$name_val){
				$sql="update $data.PMJ_file set ";
				foreach ($change as $c){
					if(isset($name_val[$c])){
						$sql=$sql." $c='$name_val[$c]', ";
					}
				}
				$sql=rtrim($sql, ', ');
				$sql=$sql." where pmj01='$p_num' and pmj02='$key'";
				//echo $sql.'<br>';
				get_oracle_info($sql);
			}
		}else{
			
		}
		echo json_encode(array(1,'修改完成'));
	}
	public function index_ajax(){
		$p_num=isset($_POST['p_num'])?$_POST['p_num']:'';
		$ima06=isset($_POST['ima06'])?$_POST['ima06']:'';
		$site_code=isset($_POST['site_code'])?$_POST['site_code']:'';
		
		
		$sql_ban=" where b.pmj01='$p_num' and PMJ10 not like '31%' ";
		//排序
		$sort_name=isset($_POST['ii'])?$_POST['ii']:'PMJ02';
		$this->assign('sort_name',$sort_name);
		$zorf=isset($_POST['a'])?$_POST['a']:'1';
		$this->assign('zorf',$zorf);
		$sql_ban=$sql_ban." order by $sort_name ";
		if($zorf==1){
			$sql_ban=$sql_ban." desc ";
		}
		
		//分页
		$page=isset($_POST['page'])?$_POST['page']:'1';
		$show=isset($_POST['show'])?$_POST['show']:'20';
		$all_shumu=get_oracle_info("SELECT count(*) FROM $site_code.pmj_file b LEFT JOIN $site_code.ima_file c on c.IMA01=b.PMJ03  LEFT JOIN $site_code.ecd_file d on d.ECD01=b.PMJ10 $sql_ban ");
		//获取总分页数
		$zy = $all_shumu[0]['COUNT(*)'] / $show;
		$zy = ceil($zy);
		//判断页码正确性
		if($page>$zy){$page=$zy;}
		if($page<=0){$page=1;}
		$star=$page*$show-$show;
		
		$this->assign('all_shumu',$all_shumu[0]['COUNT(*)']);
		$this->assign('all_page',$zy);
		$this->assign('show_page',$show);
		$this->assign('page',$page);
		$this->assign('star',$star);
		$end=$star+$show;$star=$star+1;
		//内容
		$body=get_oracle_info("SELECT * FROM(select a1.*, ROWNUM RN from (SELECT b.ta_Pmj33,b.ta_Pmj35,b.ta_Pmj36,/*b.id,*/b.PMJ01,b.PMJ10,ECD02,b.PMJ13,b.PMJICD14,b.PMJ02,b.PMJ03,b.PMJ031,b.PMJ032,b.PMJ04,c.ima44,c.ima908,b.PMJ05,b.PMJ06,b.PMJ06T,b.PMJ07,b.PMJ07T,b.PMJ08,b.PMJ09,(b.ta_Pmj35/3600*b.ta_Pmj36) as money,b.PMJ02 as id FROM $site_code.pmj_file b LEFT JOIN $site_code.ima_file c on c.IMA01=b.PMJ03  LEFT JOIN $site_code.ecd_file d on d.ECD01=b.PMJ10 $sql_ban ) a1 where ROWNUM <= $end) WHERE RN >= $star");
		$this->assign('body',$body);
		//1:input,2:select
		
		$site=strtolower("TE");
		//那些部门可以改那些
		$inp_sel=array(
				"te"=>array('TA_PMJ33'=>2,'PMJ10'=>2,'PMJ07'=>1),
				"pp"=>array()
		);
		$this->assign('inp_sel',isset($inp_sel[$site])?$inp_sel[$site]:array());
		//标题
		$title=array(
				'PMJ02'=>'项次',
				'PMJ03'=>'料件编号',
				'PMJ031'=>'品名',
				'IMA44'=>'采购单位',
				'IMA908'=>'计价单位',
		);
		if($ima06!='1_WF'){
			$title=array_merge($title,array(
					'PMJ10'=>'作业编号',
					'ECD02'=>'作业说明',
					'PMJ13'=>'单元编号',
					'PMJICD14'=>'等级',
						
			));
			if($ima06=='2_CP' || $ima06=='4_FT'){
				$title=array_merge($title,array(
						'TA_PMJ33'=>'机台',
						'TA_PMJ35'=>'h_r',
						'TA_PMJ36'=>'时间',
						'MONEY'=>'标准价',
				));
			}
		}
		$title=array_merge($title,array(
				'PMJ05'=>'币种',
				'PMJ07'=>'新税前单价',
		
		));
		//那些栏位不显示出来
		$no_show=array(
				'te'=>array(),
				'pp'=>array(),
		);
		if(isset($no_show[$site])){
			foreach ($no_show[$site] as $n){
				unset($title[$n]);
			}
		}
		$this->assign('title',$title);
		//待定
		$this->assign('show',array(
				'PMI01'=>'核价单号',
				'PMI09'=>'申请人',
				'PMI03'=>'厂商编号',
				'PMI08'=>'税种',
		));
		
		
		//add专用
		//是否出现按钮配合使用点击事件
		$but=array('PMJ03','PMJ10','TA_PMJ33');
		$this->assign('but',$but);
		//需要点击事件
		$button=array('PMJ03'=>'PMJ03_click()','PMJ10'=>'PMJ10_click()','TA_PMJ33'=>'TA_PMJ33_click()');
		$this->assign('button',$button);
		//需要计算带值事件
		$change=array('PMJ07'=>'PMJ07_change(this)','PMJ07T'=>'PMJ07T_change(this)');
		$this->assign('change',$change);
		//必填
		$must=array('PMJ03','PMJ10','PMJ07','PMJ07T','TA_PMJ33');
		$this->assign('must',$must);
		//其他关联信息		不传值
		$no_save=array('ECD02','PMI01','PMI09','PMI03','PMI08','IMA44','IMA908');
		$this->assign('no_save',$no_save);
		//直接填入
		$input_val=array('PMJ09'=>date("Y-m-d"),'PMJ05'=>'RMB');
		$this->assign('input_val',$input_val);
		//手动
		$readonly=array('PMJ07','PMJ07T');
		$this->assign('readonly',$readonly);
		//作业编号信息
		if($site_code){
			$ecd=Db::connect("wtq_orc")->query("select ECD01,ECD02 from $site_code.ECD_FILE");
		}else{
			$ecd=array();
		}
		$this->assign('ecd',$ecd);
		//机台信息
		$fac_name=isset($_POST['fac_name'])?$_POST['fac_name']:'';
		if($fac_name){
			$fac_sql=" 1=1 ";
			//if($fac_name){$fac_sql=" ECI01='$fac_name' ";}
			$machine=Db::connect("wtq_orc")->query("select * from $site_code.ECI_file where $fac_sql");
		}else{
			$machine=array();
		}
		$this->assign('machine',$machine);
		//料件编号信息
		$site_code=isset($_POST['site_code'])?$_POST['site_code']:'';
		if($site_code){
			$ima_sql=" 1=1 ";
			if($ima06){$ima_sql=" ima06='$ima06' ";}
			$ima=Db::connect("wtq_orc")->query("select * from $site_code.IMA_FILE where $ima_sql");
		}else{
			$ima=array();
		}
		$this->assign('ima',$ima);
		
		
		
		
		
		$need_add_sql="select * from DATA.PMH_FILE where pmh02='$fac_name'";
		$need_add=get_oracle_info($need_add_sql);
		$need_add_sql="SELECT pmh01 FROM (SELECT pmh01,pmj01,pmi01,pmi03 FROM DATA.PMH_FILE LEFT JOIN DATA.PMI_FILE ON PMI03=PMH02  LEFT JOIN DATA.PMj_FILE ON PMj03=PMh01 WHERE PMH02='$fac_name') WHERE pmj01 is NULL";
		$need_add=get_oracle_info($need_add_sql);
		$this->assign('need',$need_add);
		
		
		
		//拉取两个页面
		$a=$this->fetch();
		$b=$this->fetch('index_page');
		return array($a,$b);
	}
	//新的核价信息
	public function save_new_msg(){
		$ima06=isset($_GET['ima06'])?$_GET['ima06']:'';
		$site_code=isset($_GET['site_code'])?$_GET['site_code']:'';
		$fac_num=isset($_GET['fac_name'])?$_GET['fac_name']:'';
		$p_num=isset($_GET['p_num'])?$_GET['p_num']:'';
		$_POST['PMJ01']=$p_num;
		//$PMJ02=db()->query("select max(PMJ02) from sw_erp_pmj_file where PMJ01='$p_num'");
		$PMJ02=get_oracle_info("select max(PMJ02) from $site_code.pmj_file where PMJ01='$p_num'");
		$id=isset($_GET['id'])?$_GET['id']:'';
		if($id){
			$_POST['PMJ02']=$PMJ02[0]['max(PMJ02)'];
		}else{
			if($PMJ02){
				$_POST['PMJ02']=$PMJ02[0]['max(PMJ02)']+1;
			}else{
				$_POST['PMJ02']=1;
			}
		}
		$must=array('PMJ03','PMJ10','PMJ07','TA_PMJ33','PMJ01','PMJ02');
		foreach ($must as $m){
			if($_POST[$m]==''){
				exit(json_encode(array(0,'有必填项未完成')));
			}
		}
		
		if($id){
			$_POST['id']=$id;
			unset($_POST['PMJ01']);
			unset($_POST['PMJ02']);
			db('erp_pmj_file')->update($_POST);
		}else{
			$id=db('erp_pmj_file')->insertGetId($_POST);
		}
		
		unset($_POST['TA_PMJ33']);
		unset($_POST['TA_PMJ35']);
		unset($_POST['TA_PMJ36']);
		//回写oracle
		if($id){
			$oracle_sql='';
			foreach ($_POST as $key=>$p){
				if($oracle_sql==''){
					$oracle_sql="$key='$p'";
				}else{
					$oracle_sql="$oracle_sql,$key='$p'";
				}
			}
			$sql="UPDATE $site_code.pmj_file SET $oracle_sql WHERE PMJ01 = '$p_num' and PMJ02 = '".$PMJ02[0]['max(PMJ02)']."'";
			
		}else{
			$lie='';$val='';
			foreach ($_POST as $key=>$p){
				if($lie==''){
					$lie="'$key'";
					$val="'$p'";
				}else{
					$lie="$lie,'$key'";
					$val="$val,'$p'";
				}
			}
			$sql="insert into $site_code.pmj_file ($lie) values($val)";
		}
		//get_oracle_info($sql);
		//$body=Db::connect("wtq_orc")->query($sql);
		exit(json_encode(array(1,'完成'.$sql)));
	}
	public function save_new_msg_two(){
		$site_code=isset($_GET['site_code'])?$_GET['site_code']:'';
		$fac_num=isset($_GET['fac_name'])?$_GET['fac_name']:'';
		$p_num=isset($_GET['p_num'])?$_GET['p_num']:'';
		$page_info=$_POST;
		foreach ($page_info as $key=>$p){
			if($p[1]==''){
				unset($page_info[$key]);
			}
		}
		if(!$page_info){
			exit(json_encode(array(0,'标准价全部为空')));
		}
		$val='';
		$PMJ02=get_oracle_info("select max(PMJ02) from $site_code.pmj_file where PMJ01='$p_num'");
		$PMJ02=$PMJ02[0]['MAX(PMJ02)'];
		foreach ($page_info as $key=>$p){
			$PMJ02++;
			if($val==''){
				$val="SELECT '$p_num',$PMJ02,'$key','$p[0]','$p[1]' ,'1','1','1','1','1','1' FROM DUAL";
			}else{
				$val="$val UNION ALL SELECT '$p_num',$PMJ02,'$key','$p[0]','$p[1]' ,'1','1','1','1','1','1' FROM DUAL";
			}
		}
		$sql="insert into $site_code.pmj_file (pmj01,pmj02,pmj03,PMJ05,PMJ07 ,PMJ10,PMJ12,PMJ13,PMJPLANT,PMJLEGAL,PMJ06T)  $val";
		get_oracle_info($sql);
		exit(json_encode(array(1,'完成'))); 
	}
	//配置页面
	public function index_config(){
		$ok_shop='';
		foreach (\think\Config::get('site_code') as $sit){
			$ok_shop_{$sit}=Db::connect("wtq_orc")->query("select pmc01,pmc03 from $sit.PMC_FILE");
			$ok_shop[$sit]=$ok_shop_{$sit};
		}
		$this->assign('ok_shop',$ok_shop);
		$fac_num=db()->query("SELECT distinct(fac_num) as fac_num  FROM sw_erp_p_config");
		$this->assign('fac_num',$fac_num);
		$fac_name=db()->query("SELECT distinct(fac_name) as fac_name  FROM sw_erp_p_config");
		$this->assign('fac_name',$fac_name);
		return $this->fetch();
	}
	//配置页面数据
	public function index_config_ajax(){
		$page_info['site_code']=isset($_POST['site_code'])?$_POST['site_code']:'';
		$page_info['fac_num']=isset($_POST['fac_num'])?$_POST['fac_num']:'';
		$page_info['fac_name']=isset($_POST['fac_name'])?$_POST['fac_name']:'';
		$page_info['ima06']=isset($_POST['ima06'])?$_POST['ima06']:'';
		$page_info['p_num']=isset($_POST['p_num'])?$_POST['p_num']:'';
		$sql_ban='';
		foreach ($page_info as $key=>$p){
			if($p){
				if($key=='p_num'){
					$sql_ban_o=" $key like '%$p%' ";
				}else{
					$sql_ban_o=" $key = '$p' ";
				}
				if($sql_ban==''){
					$sql_ban=" where $sql_ban_o ";
				}else{
					$sql_ban=$sql_ban." and $sql_ban_o ";
				}
			}
		}
		//排序
		$sort_name=isset($_POST['ii'])?$_POST['ii']:'id';
		$this->assign('config_sort_name',$sort_name);
		$zorf=isset($_POST['a'])?$_POST['a']:'1';
		$this->assign('config_zorf',$zorf);
		$sql_ban=$sql_ban." order by $sort_name ";
		if($zorf==1){
			$sql_ban=$sql_ban." desc ";
		}
		$this->assign('config_title',array(
				'id'=>'id',
				'site_code'=>'营运中心',
				'fac_num'=>'厂商编号',
				'fac_name'=>'厂商名称',
				'ima06'=>'段',
				'p_num'=>'核价单号',
		));
	
		$fac_name=db()->query("SELECT id,site_code,fac_num,fac_name,ima06,p_num FROM sw_erp_p_config $sql_ban");
		$this->assign('fac_name',$fac_name);
		return $this->fetch();
	}
	//保存新的配置
	public function save_new_config(){
		$page_info['site_code']=isset($_POST['site_code'])?$_POST['site_code']:'';
		$page_info['fac_num']=isset($_POST['fac_num'])?$_POST['fac_num']:'';
		$page_info['fac_name']=isset($_POST['fac_name'])?$_POST['fac_name']:'';
		$page_info['ima06']=isset($_POST['ima06'])?$_POST['ima06']:'';
		$page_info['p_num']=isset($_POST['p_num'])?$_POST['p_num']:'';
		$id=isset($_POST['id'])?$_POST['id']:'';
		if($id){
			$pd_p_num=db()->query("select * from sw_erp_p_config where p_num='".$page_info['p_num']."' and id!='$id'");
			if($pd_p_num){exit(json_encode(array(0,'该单号已存在')));}
			$page_info['id']=$id;
			db('erp_p_config')->update($page_info);
		}else{
			$pd_p_num=db()->query("select * from sw_erp_p_config where p_num='".$page_info['p_num']."'");
			if($pd_p_num){exit(json_encode(array(0,'该单号已存在')));}
			$id=Db::name('erp_p_config')->insertGetId($page_info);
		}
		$tr="<td wtq_type='id'><div>$id</div></td>";
		foreach ($page_info as $key=>$p){
			if($key=='id'){
				
			}else{
				$tr=$tr."<td wtq_type='$key'><div>$p</div></td>";
			}
		}
		$tr=$tr."<td><div><button onclick=\"add_config('$id')\">修改</button><button onclick=\"del_config('$id')\">删除</button></div></td>";
		echo json_encode(array('1','修改完成',$id,$tr));
	}
	//删除配置
	public function del_config(){
		$id=isset($_POST['id'])?$_POST['id']:'';
		if($id){
			db('erp_p_config')->delete($id);
			exit(json_encode(array('1','删除完成')));
		}else{
			exit(json_encode(array('0','缺少数据')));
		}
		
	}
	//新的厂商配置信息
	public function factory_con(){
		$ok_shop='';
		foreach (\think\Config::get('site_code') as $sit){
			$ok_shop_{$sit}=Db::connect("wtq_orc")->query("select pmc01,pmc03 from $sit.PMC_FILE");
			$ok_shop[$sit]=$ok_shop_{$sit};
		}
		$this->assign('ok_shop',$ok_shop);
		$fac_num=db()->query("SELECT distinct(fac_num) as fac_num  FROM sw_erp_factory_config");
		$this->assign('fac_num',$fac_num);
		$fac_name=db()->query("SELECT distinct(fac_name) as fac_name  FROM sw_erp_factory_config");
		$this->assign('fac_name',$fac_name);
		return $this->fetch();
	}
	public function factory_config_ajax(){
		$page_info['site_code']=isset($_POST['site_code'])?$_POST['site_code']:'';
		$page_info['fac_num']=isset($_POST['fac_num'])?$_POST['fac_num']:'';
		$page_info['fac_name']=isset($_POST['fac_name'])?$_POST['fac_name']:'';
		$page_info['ima06']=isset($_POST['ima06'])?$_POST['ima06']:'';
		$page_info['type_flag']=isset($_POST['type_flag'])?$_POST['type_flag']:'';
		$sql_ban='';
		foreach ($page_info as $key=>$p){
			if($p){
				$sql_ban_o=" $key = '$p' ";
				if($sql_ban==''){
					$sql_ban=" where $sql_ban_o ";
				}else{
					$sql_ban=$sql_ban." and $sql_ban_o ";
				}
			}
		}
		//排序
		$sort_name=isset($_POST['ii'])?$_POST['ii']:'id';
		$this->assign('config_sort_name',$sort_name);
		$zorf=isset($_POST['a'])?$_POST['a']:'1';
		$this->assign('config_zorf',$zorf);
		$sql_ban=$sql_ban." order by $sort_name ";
		if($zorf==1){
			$sql_ban=$sql_ban." desc ";
		}
		
		//分页
		$page=isset($_POST['page'])?$_POST['page']:'1';
		$show=isset($_POST['show'])?$_POST['show']:'20';
		$all_shumu=db()->query("select count(*) from sw_erp_factory_config $sql_ban");
		//获取总分页数
		$zy = $all_shumu[0]['count(*)'] / $show;
		$zy = ceil($zy);
		//判断页码正确性
		if($page>$zy){$page=$zy;}
		if($page<=0){$page=1;}
		$star=$page*$show-$show;
		$sql_ban=$sql_ban." LIMIT $star,$show ";
		
		$prev=$zy-1;
		$next=$zy+1;
		if($zy<=1){
			$foot_div='';
		}else{
			$foot_div="<div><button onclick='factory_page(1)'><<</button><button onclick='factory_page($prev)'><</button><input value='$page' style='width:50px' wtq='little_time'><button onclick='factory_page(".$next.")'>></button><button onclick='factory_page($zy)'>>></button>共".$zy."页,每页".$show."条</div>";
		}
		
		
		
		
		$config_title=array(
				'id'=>'id',
				'site_code'=>'营运中心',
				'fac_num'=>'厂商编号',
				'fac_name'=>'厂商名称',
		);
		$type_flag=isset($_POST['type_flag'])?$_POST['type_flag']:'';
		if($type_flag==1){
			$config_title=array_merge($config_title,array(
					'factory_val1'=>'联系人',
					'factory_val2'=>'邮箱',
			));
		}else if($type_flag==2){
			$config_title=array_merge($config_title,array(
					'factory_val1'=>'机台',
					'factory_val2'=>'h_r',
					'factory_val3'=>'p_sec',
			));
		}else{
			$config_title=array_merge($config_title,array(
					'factory_val1'=>'机台 or 联系人',
					'factory_val2'=>'h_r or 邮箱',
					'factory_val3'=>'p_sec',
			));
		}
		$this->assign('config_title',$config_title);
		$sql_zi='';
		foreach ($config_title as $key=>$c){
			if($sql_zi==''){
				$sql_zi=$key;
			}else{
				$sql_zi=$sql_zi.','.$key;
			}
		}
		$fac_name=db()->query("SELECT $sql_zi,type_flag FROM sw_erp_factory_config $sql_ban");
		$this->assign('fac_name',$fac_name);
		return array($this->fetch(),$foot_div);
	}
	//保存新的配置
	public function save_new_factory_config(){
		$page_info['site_code']=isset($_POST['site_code'])?$_POST['site_code']:'';
		$page_info['fac_num']=isset($_POST['fac_num'])?$_POST['fac_num']:'';
		$page_info['fac_name']=isset($_POST['fac_name'])?$_POST['fac_name']:'';
		$page_info['factory_val1']=isset($_POST['factory_val1'])?$_POST['factory_val1']:'';
		$page_info['factory_val2']=isset($_POST['factory_val2'])?$_POST['factory_val2']:'';
		$page_info['factory_val3']=isset($_POST['factory_val3'])?$_POST['factory_val3']:'';
		$page_info['type_flag']=isset($_POST['type_flag'])?$_POST['type_flag']:'';
		
		$id=isset($_POST['id'])?$_POST['id']:'';
		if($id){
			$page_info['id']=$id;
			db('erp_factory_config')->update($page_info);
		}else{
			$id=Db::name('erp_factory_config')->insertGetId($page_info);
		}
		echo json_encode(array('1','修改完成',$id));
	}
	
	
	//机台信息
	public function eci_con(){
		return $this->fetch();
	}
	//机台信息数据来源
	public function eci_con_ajax(){
		$eci03=isset($_POST['eci03'])?$_POST['eci03']:'DATA';
		
		$this->assign('eci_con_title',array(
				'ECI01'=>'机台',
				'ECI02'=>'阶段',
				'ECI03'=>'工作站',
				'ECI04'=>'机器成本率($/时)',
				'ECI05'=>'机器产能(时/日)',
				'ECI08'=>'顺序',
		));
		$this->assign('con_show',array(1=>'Cp',2=>'Ft',3=>'Cp&Ft'));
		$eci01=isset($_POST['eci01'])?$_POST['eci01']:'';
		$sql_ban="where eci01 not like '31%'";
		if($eci01){
			$sql_ban=$sql_ban." and ECI01 like '%$eci01%' ";
		}
		$eci02=isset($_POST['eci02'])?$_POST['eci02']:'';
		if($eci02){
			$sql_ban=$sql_ban." and ECI02 = '$eci02' ";
		}
		//排序
		$sort_name=isset($_POST['ii'])?$_POST['ii']:'ECI08';
		$this->assign('eci_con_sort_name',$sort_name);
		$zorf=isset($_POST['a'])?$_POST['a']:'1';
		$this->assign('eci_con_zorf',$zorf);
		$sql_ban=$sql_ban." order by $sort_name ";
		if($zorf==1){
			$sql_ban=$sql_ban." desc ";
		}
		$sql="SELECT * FROM $eci03.ECI_FILE $sql_ban";
		//$val=get_oracle_info($sql);
		$val=Db::connect("wtq_orc")->query($sql);
		$this->assign('eci_con_val',$val);
		return $this->fetch();
	}
	//新的机台信息
	public function save_eci_con(){
		//dump($_POST);
		$eci=isset($_POST['eci'])?$_POST['eci']:'';
		$data=isset($_POST['ECI03'])?$_POST['ECI03']:'';
		$save_name=array('ECI02','ECI04','ECI05','ECI08');
		foreach ($save_name as $s){
			$save[$s]=isset($_POST[$s])?$_POST[$s]:'';
		}
		if($eci){
			//修改
			$sql="update $data.eci_file set ";
			foreach ($save as $key=>$s){
				$sql=$sql." $key='$s', ";
			}
			$sql=rtrim($sql, ', ');
			$sql=$sql." where eci01='$eci' ";
		}else{
			$save['ECI01']=isset($_POST['ECI01'])?$_POST['ECI01']:'';
			$save['ECI03']=isset($_POST['ECI03'])?$_POST['ECI03']:'';
			$pd=get_oracle_info("select * from $data.eci_file where eci01='".$save['ECI01']."'");
			if($pd){exit(json_encode(array(0,'机台编号已存在')));}
			//新增
			$sql="INSERT INTO $data.eci_file ( ";
			$sql_end=' ( ';
			foreach ($save as $key=>$s){
				$sql=$sql." $key, ";
				$sql_end=$sql_end." '$s', ";
			}
			$sql=rtrim($sql, ', ').' ) ';
			$sql_end=rtrim($sql_end, ', ').' ) ';
			$sql="$sql VALUES $sql_end";
		}
		get_oracle_info($sql);
		echo json_encode(array(1,'完成'));
	}
	//机台信息删除
	public function del_eci_con(){
		$data=isset($_POST['eci03'])?$_POST['eci03']:'';
		$del_name=isset($_POST['del'])?$_POST['del']:'';
		if($del_name==''){
			exit(json_encode(array(0,'缺少名称')));
		}
		$sql="delete from $data.eci_file where eci01='$del_name';";
		get_oracle_info($sql);
		echo json_encode(array(1,'删除完成'));
	}
}