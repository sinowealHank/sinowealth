<?php
namespace app\sys\controller;
use think\Controller;
use think\Session;
use think\Db;

use app\index\controller\Admin;
class CollectUrl extends Admin{
    public function index(){
        //获得当前用户的权限数据
        $id = session('login_id');
        $admin_id = config('ADMIN_ID');
        if($id == $admin_id){
            //取出管理员的全部权限
            $pri_all = Db::query("select id,pri_name as name,module_name,controller_name,action_name,parent_id from sw_privilege where is_show = 1");
            //取出管理员的收藏菜单
            $navResult = db('user_collect_url')->where('user_id',$id)->find();
            $privileg_str = $navResult['pri_str'];
            $privileg_str = explode(',',$privileg_str);
            foreach ($privileg_str as $key => $val){
                $temp['pri_id'] = $val;
                $nav_arr[] = $temp;
            }

            $pri_all = _reSort($pri_all);
            $pri_all = make_tree($pri_all);
            $tree = make_Nav($pri_all,$nav_arr,'pri_id');

            $this->assign('tree',$tree);
            return $this->fetch();
        }else{
            //获得当前用户的角色id
            $role_id = db('user_role')->where('user_id',$id)->value('role_id');
            //获得当前用户的权限
            $priArr = db('role_privilege')->where('role_id',$role_id)->select();
            //获得当前用户收藏菜单栏
            $navResult = db('user_collect_url')->where('user_id',$id)->find();
            $privileg_str = $navResult['pri_str'];
            $privileg_str = explode(',',$privileg_str);
            foreach ($privileg_str as $key => $val){
                $temp['pri_id'] = $val;
                $nav_arr[] = $temp;
            }
            foreach ($priArr as $k => &$v){
                unset($v['role_id']);
                $pri_arr[] = $v['pri_id'];
            }
            $pri_str = implode(',',$pri_arr);
            if(empty($pri_str)){
                $pri_str = 0;
            }
            $sql = "select id,pri_name as name,module_name,controller_name,action_name,parent_id from sw_privilege where id in ({$pri_str}) and is_show = 1 ";
            $pri_result = Db::query($sql);
            $pri_result = _reSort($pri_result);
            $pri_result = make_tree($pri_result);
            $tree = make_Nav($pri_result,$nav_arr,'pri_id');
            $this->assign('tree',$tree);
            return $this->fetch();
        }

    }

    public function addnav(){
        $id_str = input('idData');
        $user_id = session('login_id');
        if(empty($id_str)){
            db('user_collect_url')->where('user_id',$user_id)->delete();
            echo setServerBackJson(1,"保存菜单栏成功","","",1);exit;
        }
        //获取用户的id
        $nav = array();
        $nav['user_id'] = $user_id;
        $nav['pri_str'] = $id_str;
        /*保存前删除之前的权限*/
        db('user_collect_url')->where('user_id',$user_id)->delete();
        db('user_collect_url')->insert($nav);
        echo setServerBackJson(1,"保存菜单栏成功","","",1);
    }

}