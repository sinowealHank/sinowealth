<?php
namespace app\user\controller;

use think\Controller;
use app\index\controller\Admin;

class Index extends Admin
{
	public function index()
	{
		return  $this->fetch();
	}
	
	public function main(){
		return  $this->fetch();
	}
}