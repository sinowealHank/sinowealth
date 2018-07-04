<?php
namespace app\cost\controller;

use think\Controller;
use app\index\controller\Admin;
use think\Db;
class CostConfig extends Admin{
	
	public function index(){
		$res =db('cost_config')->select();
		$page_info['field'] = array();
		$page_info['email'] = array();
		foreach($res as $k=>$v){
			$res[$k]['content'] = str_replace(",",";&#10;",$v['content']);
			if($v['type'] == 0){			
				$page_info['field'][] = $res[$k];
			}
			if($v['type'] == 1){			
				$page_info['email'][] = $res[$k];
			}
		}

		//取出删除申请邮件配置
		$delete_config = Db::name('cost_delele_email_config')->select();
		foreach($delete_config as $k => $v){
			$temp['config'] = get_distinct_dep_user($v['id'],$v['dep_name']);
			$temp['title'] = $v['title'];
			$temp['id'] = $v['id'];
			$temp['name'] = $v['dep_name'];
			$page_info['user'][] = $temp;
		}

		//取出pe、te、pp提交删除申请的人员
		$pe_qa_user_str = get_distinct_dep_user(1,'Pe');
		$te_qa_user_str = get_distinct_dep_user(2,'Te');
		$pp_qa_user_str = get_distinct_dep_user(3,'Pp');
		$page_info['te_user_data'] = $te_qa_user_str;
		$page_info['pe_user_data'] = $pe_qa_user_str;
		$page_info['pp_user_data'] = $pp_qa_user_str;
		

		$this->assign('page_info',$page_info);
		return  $this->fetch();
	}
	public function edit(){
		$content = $_POST['content'];
		//$content = str_replace(PHP_EOL, '', $content);
		$content = str_replace(array("\r\n", "\r", "\n"),"",$content);
		$content = str_replace(array('；',';'),',',$content);
		//检验是否有重复数据
		if (count(cost_string_to_array($content)) != count(array_unique(cost_string_to_array($content)))) {
			echo setServerBackJson(0,'数据中有重复的值!');exit;
		}
		$res = db('cost_config')->where('id',$_POST['id'])->setField('content',$content);
		echo setServerBackJson(1,'保存成功!');exit;
	}
	public function changeDate(){
		set_time_limit(0);
		
		Db::query('truncate table sw_cost_cost');
		Db::query('truncate table sw_cost_log');
		
		$sql = 'select * from dbo.pp_cost';
		$sql=auto_charset($sql);
		$res = get_mssql_info('mms',$sql);
		$log =  date('Y-m-d H:i:s',time()).' '.get_user_nickname().'导入了数据';
		foreach($res as $k=>$v){
			$res[$k]['type_p'] = $v['type'];
			$type = explode('-',$v['type']);
			$res[$k]['type'] = $type['0'];
			
			$by58 = explode('(',$v['by58']);
			$res[$k]['by58'] = $by58['0'];
			
				if(isset($by58['1'])){
					$res[$k]['fab_name_r'] = '是';
				}else{
					$res[$k]['fab_name_r'] = '否';
				}
			
						
			$CP_factory = explode('(',$v['CP_factory']);
			$res[$k]['CP_factory'] = $CP_factory['0'];
		
				if(isset($CP_factory['1'])){
					$res[$k]['CP_factory_r'] = '是';
				}else{
					$res[$k]['CP_factory_r'] = '否';
				}
			
			
			$Assy_ab = explode('(',$v['Assy_ab']);
			$res[$k]['Assy_ab'] = $Assy_ab['0'];
		
				if(isset($Assy_ab['1'])){
					$res[$k]['Assy_ab_r'] = '是';
				}else{
					$res[$k]['Assy_ab_r'] = '否';
				}
			
			$F_T_Out = explode('(',$v['F_T_Out']);
			$res[$k]['F_T_Out'] = $F_T_Out['0'];
			
				if(isset($F_T_Out['1'])){
					$res[$k]['F_T_r'] = '是';
				}else{
					$res[$k]['F_T_r'] = '否';
				}
			
			$res[$k]['show_type'] = '1';
			$res[$k]['flow'] = '4';
			$res[$k]['o_id'] = $v['id'];
			
			cost_set_log($k+1,$log,'1');//写进log
			unset($res[$k]['id']);
			$a = db('cost_cost')->insert($res[$k]);
		}
		echo setServerBackJson(1,'导入成功！');exit;
	}


	//邮件配置功能
	public function add_email_config(){
		$insert_data['id'] = $_POST['id'];
		$insert_data['id_str'] = $_POST['id_str'];
		Db::name('cost_delele_email_config')->where('id',$insert_data['id'])->setField('id_str','');
		Db::name('cost_delele_email_config')->where('id',$insert_data['id'])->update($insert_data);
		echo setServerBackJson(1,"保存成功");

	}
}

