<?php
namespace app\sys\controller;

use think\Controller;
use app\index\controller\Admin;

class Group extends Admin{
	
	function index(){		
		
		return $this->fetch();
	}
	
}