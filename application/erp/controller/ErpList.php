<?php
namespace app\erp\controller;

set_time_limit(0);
ini_set("memory_limit","-1");

use think\Controller;
use app\index\controller\Admin;

class ErpList extends Admin{
	/**
	 * 案例追踪
	 */
	public function index() {
		return $this->fetch();		
	}

	//输出表格数据
	public function get_erp_data(){

			//构建表格数据
			$tb_arr=array();
			
			//考勤计算数据获取
			$arr=db('erp_list')->select();			
			$tb_arr['total']=count($arr);
			$tb_arr['rows']=array();
			
			foreach($arr as $key=>$val){
				if($val['pe_prd_list']==1){
					$val['pe_prd_list']="√";
				}else{
					$val['pe_prd_list']="";
				}
				
				if($val['pe_bom']==1){
					$val['pe_bom']="√";
				}else{
					$val['pe_bom']="";
				}
				
				if($val['pe_sup']==1){
					$val['pe_sup']="√";
				}else{
					$val['pe_sup']="";
				}
				
				if($val['pe_field']==1){
					$val['pe_field']="√";
				}else{
					$val['pe_field']="";
				}
				array_push($tb_arr['rows'],$val);
			}

			echo json_encode($tb_arr);
			
	}
	
	//显示编辑数据
	public function edit_row(){
		$page_info=array();
		$data=$this->param;
		
		$page_info=db('erp_list')->where('id='.$data['id'])->find();
		
		$this->assign('page_info',$page_info);
		return $this->fetch();
	}
	
	//编辑数据
	public function update_row(){
		$page_info=array();
		$data=$this->param;	
		
		return '1ssss';
	}

	
	
	
	
	
}
