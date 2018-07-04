<?php
namespace app\sys\controller;

use think\Controller;
use app\index\controller\Admin;

class OrgTree extends Admin
{
    public function index()
    { 
        return  $this->fetch();
    }

    public function getorg(){    	
    	$str="{'status':'OK','data':{'1':{'name':'Asia','type':'folder','additionalParameters':{'id':'1','children':true}},'2':{'name':'Africa','type':'folder','additionalParameters':{'id':'2','children':true}},'3':{'name':'North America','type':'item','additionalParameters':{'id':'3'}},'4':{'name':'South America','type':'item','additionalParameters':{'id':'4'}},'5':{'name':'Antarctica','type':'item','additionalParameters':{'id':'5'}},'6':{'name':'Europe','type':'item','additionalParameters':{'id':'6'}},'7':{'name':'Australia','type':'item','additionalParameters':{'id':'7'}}}}";
    	echo $str;
    }
}
