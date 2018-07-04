<?php
namespace app\erp\controller;

use think\Controller;
use app\index\controller\Admin;
use think\Db;
use think\db\Query;
//用户首页详情
class Wtq extends Admin{
	public function index(){
		echo ' <form action="http://192.9.231.37:8086/NaNaWeb/GP/Authentication"><input name="txtUserId" value="administrator"><input name="txtPassword" value="1234"><input name="hdnMethod" value="login"><input name="hdnLogoutForMultiLogin" value="true"><button>ok</button></form>';
	}
}
