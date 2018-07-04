<?php
namespace app\erp\controller;

use think\Controller;
use app\index\controller\Admin;
use think\Db;


class CostTb extends Admin
{
	/**
	 * CostTb
	 */
	public function index() {
		
		
		return $this->fetch();
	}

	//输出申请单表格数据
	public function get_cost_data(){
		$key="sh69p42";
		
		//获取PRD_NO数量
		$sql="select imaicd00 from T_SHSINO.imaicd_file where imaicd00 = '".$key."' and imaicd04=4";
		
		$cost_arr=model('Oracle')->getOracleData($sql);
		
		pr($cost_arr);
		//完善此Prd_no BOM信息
		
		//
		
	}
	
}
