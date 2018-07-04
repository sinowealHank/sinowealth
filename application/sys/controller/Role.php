<?php
namespace app\sys\controller;
use think\Controller;
use think\Session;
use think\Db;
use think\Model;
use app\index\controller\Admin;

//用户角色管理
class Role extends Admin{
    public function index(){
        //链接数据库,准备角色的数据
        $result  = db('role')->order('id desc')->select();
        //把显示的名字改为layui对应name格式
        $result = change_filed($result,'name','c_group_name','role_name');
        $tree = make_tree($result);
        $tree = json_encode($tree);

        //取出当前的用户权限
        $pri_data = get_session_privilege();
        //取出当前的模块名_控制器名称
        $module_controller = get_current_url();

        $page_info['pri_data'] = $pri_data;
        $page_info['module_controller'] = $module_controller;
        $page_info['data'] = $tree;

        $this->assign('page_info',$page_info);
        return $this->fetch();
    }

    //显示添加页面
    public function add(){
        $role = new \app\sys\model\Role();
        $data = $role->getTree();
        $data = object_change_array($data);
        $role = '';
        foreach($data as $k =>$v){
            $role .= '<option value="'.$v['id'].'">'.str_repeat('&nbsp;', 8*$v['level']).$v['c_group_name'].'</option>';
        }
        $this->assign('role',$role);
        return $this->fetch();
    }

    //显示修改角色的页面
    public function edit(){
        $id = input('id');
        //取出所有角色信息
        $role = new \app\sys\model\Role();
        $data = $role->getTree();
        $data = object_change_array($data);

        //获取当前角色信息
        $roleData = db('role')->where('id',$id)->find();
        $role = '';
        foreach($data as $k =>$v){
            $role .= '<option value="'.$v['id'].'"  >'.str_repeat('&nbsp;', 8*$v['level']).$v['c_group_name'].'</option>';
        }
        //获得当前模块、控制器
        $module_controller = get_current_url();
        //获得当前用户的权限
        $pri = get_session_privilege();

        $page_info['pri_data'] = $pri;
        $page_info['module_controller'] = $module_controller;
        $page_info['role'] = $role;
        $page_info['role_data'] = $roleData;
        $this->assign('page_info',$page_info);
        return $this->fetch();
    }


    //添加角色数据
    public function addRole(){
        $RoleData = input('post.');

        /*存在复制权限*/
        if(isset($RoleData['role_id'])){
            $role_id = $RoleData['role_id'];
            unset($RoleData['role_id']);
            unset($RoleData['confirm']);
        }


        /*实例化model类,校验添加的数据*/
        $role = new \app\sys\model\Role();
        $role->insertData($RoleData);
        $result = Db::name('role')->insertGetId($RoleData);
        /*取出复制角色的所有权限*/
        if(isset($role_id)){
            $pri_id  = db('role_privilege')->where('role_id',$role_id)->field('pri_id')->select();
            foreach($pri_id as $k=>$v){
                $insert['role_id'] =  $result;
                $insert['pri_id'] = $v['pri_id'];
                $save[] = $insert;
            }
            db('role_privilege')->insertAll($save);
        }
        if($result !== false){
            echo setServerBackJson(1,'添加成功',"","closeDialog");
        }else{
            echo setServerBackJson(0,"添加失败");
        }
    }


    //删除角色数据
    public function deleteRole(){
        $id = input('id');
        $result = $this->getChildren($id);

        if($result){
            echo setServerBackJson(0,"当前节点下有子类","","",1);exit;
        }
        $has = db('user_role')->where('role_id',$id)->select();
        if($has){
           echo setServerBackJson(0,"有用户属于这个角色无法删除");exit;
        }
        //删除角色对应的权限
        db('role_privilege')->where('role_id',$id)->delete();
        //删除角色
        db('role')->where('id',$id)->delete();
        echo setServerBackJson(1,"角色删除成功","1","closeDialog");

    }
    //获得当前节点下的子类
    public function getChildren($id)
    {
        $data = db('role')->select();
        return $this->_children($data, $id);
    }

	//分拆子节点层级	
    private function _children($data, $parent_id=0, $isClear=TRUE)
    {
        static $ret = array();
        if($isClear)
            $ret = array();
        foreach ($data as $k => $v)
        {
            if($v['parent_id'] == $parent_id)
            {
                $ret[] = $v['id'];
                $this->_children($data, $v['id'], FALSE);
            }
        }
        return $ret;
    }

    //修改角色数据
    public function roleEdit(){
        $post = input('post.');
        if(empty($post)){
            echo setServerBackJson(0,"修改数据未传入");exit;
        }
        $editData = array();
        $id = $post['id'];
        $editData['role_name'] = trim($post['r_name']);
        $editData['c_group_name'] = trim($post['c_g_name']);
        $editData['parent_id'] = trim($post['p_id']);
        //实例化model类*,校验数据
        $role = new \app\sys\model\Role();
        $role->editData($editData);
        //*把修改的数据添加到数据库
        db('role')->where('id',$id)->update($editData);
        echo setServerBackJson(1,"修改成功","","closeDialog");
    }

    //检查用户名是否重复
    public function checkRoleName(){
       $role_name = $_POST['data'];
        //检查部门名称是否重复
        $result = db('role')->where('role_name',$role_name)->find();
        if($result){
            echo json_encode("角色名称重复，请重新输入");
        }
    }





}