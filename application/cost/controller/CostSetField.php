<?php
namespace app\cost\controller;

use think\Controller;
use app\index\controller\Admin;

class CostSetField extends Admin{
	
	public function index(){
		return  $this->fetch();
	}
	//costTable表格
	public function fieldDate(){
		$where = 'field not in('.cost_config(3).')';
		$field=db('cost_field')->where($where)->order('order','asc')->select();
	
		echo json_encode($field);
		
	}
	public function editShow(){
		$rowData = $_POST['rowData'];
		$id = $rowData['id'];
		unset($rowData['id']);
		$result = db('cost_field')->where('id',$id)->update($rowData);
		echo '1';	
	}
}

