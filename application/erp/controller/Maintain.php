<?php
namespace app\erp\controller;

use think\Controller;
use app\index\controller\Admin;
use think\Db;

class Maintain extends Admin{
	public function index() {
		//同步数据
		$name_arr=Db::connect("wtq_orc")->query("select * from tc_prc_file");
		$imaicd=db()->query("TRUNCATE TABLE tc_prc_file");
		Db::table('tc_prc_file')->insertAll($name_arr);
		return $this->fetch('css');
	}
	//原始打卡数据列表
	public function index_ajax() {
		//设置所有字段对应名称
		$body=array(
				'id'=>'id<i wtq_up_index_i="i" class="icon-level-up" style="padding-left:5px;color:red;"></i>',
				'TC_PRC01'=>'产品编号',
				'TC_PRC02'=>'客户编号',
				'TC_PRC03'=>'客户产品编号',
				'TC_PRC04'=>'Project Code',
				'TC_PRC05'=>'Project Code说明',
				'TC_PRC06'=>'最终客户产品名词说明',
				'TC_PRC07'=>'核定本币',
				'TC_PRC08'=>'核定外币',
				'TC_PRC09'=>'指定汇率',
				'TC_PRC10'=>'汇率来源',
				'TC_PRC11'=>'核定税种',
				'TC_PRC12'=>'核定订单销售单位',
				'TC_PRC13'=>'本币单价',
				'TC_PRC14'=>'外币单价',
				'TC_PRC15'=>'最近订单日',
				'TC_PRC16'=>'最近订单数量',
				'TC_PRC17'=>'最近订单金额',
				'TC_PRC18'=>'生效预计日期',
				'TC_PRC19'=>'期限终止日',
				'TC_PRC20'=>'指定PM',
				'TC_PRC21'=>'BPM审批单号',
				'TC_PRC22'=>'资料有效码',
				'TC_PRC23'=>'自定义栏位一',
				'TC_PRC24'=>'自定义栏位二',
				'TC_PRC25'=>'自定义栏位三',
				'TC_PRC26'=>'自定义栏位四',
				'TC_PRCGRUP'=>'资料所有部门',
				'TC_PRCUSER'=>'资料所有者',
				'TC_PRCORIG'=>'资料建立部门',
				'TC_PRCORIU'=>'资料建立者',
				'TC_PRCDATE'=>'最近更改日',
				'TC_PRCMODU'=>'资料更改者',
				'客户简称'=>'客户',
		);
		//设置固定栏位的显示内容
		$title=array('id','客户简称','TC_PRC01','TC_PRC04');
		//拼出改组的字段名
		$id="body_Jur_0";
		//jy：1排除(包含title,1下标不会包含在内)2这些3融合(1)(前在前后再后)4融合(2)
		//ch:如果jy是1的话使用   1这些修改 2这些不修改
		${$id}=array('jy'=>1,'ch'=>2,0=>array(array_flip($body)),1=>array());
		//1排除(包含title,1下标不会包含在内)2这些3融合(1)(前在前后再后)4融合(2)
		//循环出所需字段
		//拼出后半部分sql输出的 字段 名
		$field='';
		$id_arr=${$id}[0];
		$id_arr=array_merge($id_arr,$title);
		//如果为2判断那些不能改
		if(${$id}['ch']==2){
			$del=${$id}[1];
			${$id}[1]='';
		}else{
			$del=array();
		}
		foreach ($body as $key=>$b){
			$ok=1;
			foreach ($id_arr as $i){
				if($key==$i){
					$ok=2;
				}
			}
			if($ok==1){
				if($field==''){
					$field=$key;
				}else{
					$field=$field.','.$key;
				}
				//判断那些能改
				if(${$id}['ch']==2 && !in_array($key, $del)){
					${$id}[1][]=$key;
				}
			}
		}
		//拼出前半部分sql输出的 字段 名
		$ding='';
		foreach ($title as $t){
			if($ding==''){
				$ding=$t;
			}else{
				$ding=$ding.','.$t;
			}
		}
		//检索条件
		$sour_arr=isset($_POST['sour'])?$_POST['sour']:'';
		$sour_sql='';
		if($sour_arr){
			foreach ($sour_arr as $key=>$s){
				if($s){
					$s=trim($s);
					$sour_sql=$sour_sql." and $key like '%$s%'";
				}
			}
		}
		
		//判断是否要进行导出表格
		$excel=isset($_GET['excel'])?$_GET['excel']:'';
		if($excel=='all'){
			$sour_arr=isset($_GET['sour'])?$_GET['sour']:'';
			$sour_sql='';
			if($_GET){
				foreach ($_GET as $key=>$s){
					if($s && $key!='excel'){
						$s=trim($s);
						$sour_sql=$sour_sql." and $key like '%$s%'";
					}
				}
			}
			$data=db()->query("select $ding,$field from tc_prc_file LEFT JOIN a_zero_d ON 客户编号=TC_PRC02 where 1=1 $sour_sql");
			$title=array();
			foreach ($data[0] as $key=>$d){
				if($key=='id'){
					$body[$key]='id';
				}
				$title[]=$body[$key];
			}
			array_unshift($data,$title);
			$data=array('name'=>'特殊价格代码',array('data'=>$data,'style'=>array('ret'=>1)));
			
			excel_css($data);
			exit;
		}
		
		
		
		//传递搜索条件
		$this->assign('sour_arr',$sour_arr);
		
		//获取分页信息
		$page=isset($_POST['page'])?$_POST['page']:'1';
		$num=isset($_POST['num'])?$_POST['num']:'5';
		$page_info['all']=db()->query("select COUNT(*) as al from tc_prc_file where 1=1 $sour_sql");
		$page_info['all']=$page_info['all'][0]['al'];
		
		
		//获取总分页数
		$zy = $page_info['all'] / $num;
		$zy = ceil($zy);
		//判断页码正确性
		if($page>$zy){$page=$zy;}
		if($page<=0){$page=1;}
		$page_num=$this->ajax_page_re($page_info['all'], $num, $page,$zy);
		
		//获取排序规则
		$ii=isset($_POST['ii'])?$_POST['ii']:'id';
		$a=isset($_POST['a'])?$_POST['a']:'1';
		$page_num[]=$ii;$page_num[]=$a;
		$fuc=isset($_POST['fuc'])?$_POST['fuc']:'';
		$page_num[]=$fuc;
		//传递分页信息
		$this->assign('page',$page_num);
		
		//拼接分页sql
		$LIMIT=$num*($page-1).','.$num;
		//拼接排序sql
		$sql_paixu='';
		if($ii){$sql_paixu=' order by '.$ii;}
		if($a && $sql_paixu){$sql_paixu=$sql_paixu.' desc';}
		//获取数据
		$page_info['field']=db()->query("select $field from tc_prc_file LEFT JOIN a_zero_d ON 客户编号=TC_PRC02 where 1=1 $sour_sql $sql_paixu LIMIT ".$LIMIT);
		$page_info['ding'] =db()->query("select $ding,客户简称 from tc_prc_file LEFT JOIN a_zero_d ON 客户编号=TC_PRC02 where 1=1 $sour_sql $sql_paixu LIMIT ".$LIMIT);
		//传递是否属于可修改项
		$this->assign('change',${$id}[1]);
		//命名传过去
		$this->assign('body',$body);
		//数据传输
		$this->assign('page_info',$page_info);
		//传递表头
		$this_title['field']= explode(',',$field);
		$this_title['ding']= explode(',',$ding);
		$this->assign('this_title',$this_title);
		return $this->fetch('css_ajax');
	}
	//分页
	public function ajax_page_re($all,$num,$page,$zy) {
		$s=$page-1;
		$s_class='';
		if($s<=0){$s_class=' l-btn-disabled l-btn-plain-disabled';}

		$x=$page+1;
		$x_class='';
		if($x>$zy){$x_class=' l-btn-disabled l-btn-plain-disabled';}
		if($page*$num>$all){$end=$all;}else{$end=$page*$num;}
		$star=$num*($page-1)+1;
		$page_num=array($s,$s_class,$page,$x,$x_class,$zy,$num,$all,$star,$end);
		return $page_num;
	}
}
