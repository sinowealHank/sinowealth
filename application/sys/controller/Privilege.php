<?php
namespace app\sys\controller;
use think\Controller;
use think\Session;
use think\Db;
use think\Validate;
use app\index\controller\Admin;

//节点管理
class Privilege extends Admin{

    public function index(){
        //取出数据
        $result = db('privilege')->field('pri_name as name,id,parent_id')->order('order_id desc')->select();
        $result = _reSort($result);
        $result = make_tree($result);
//        var_dump($result);die;

        $result = json_encode($result);



        //获得当前用户的所有权限
        $pri_arr = get_session_privilege();
        //获得当前模块和控制
        $module_controller = get_current_url();

        $page_info['result'] = $result;
        $page_info['pri_data'] = $pri_arr;
        $page_info['module_controller'] = $module_controller;
        
        $this->assign('page_info',$page_info);
        return $this->fetch();
    }

    //显示添加的页面
    public function add(){
        $parentData = db('privilege')->order('order_id asc')->select();
        $parentData = _reSort($parentData);
        $str = '';
        foreach($parentData as $k => $v){
            $level = $v['level']+1;
            if($level == '3'){
                continue;
            }else{
                $str .= '<option value="'.$v['id'].'" level="'.$level.'" " module_name="'.$v['module_name'].'" controller_name="'.$v['controller_name'].'"> '
                     .str_repeat("&nbsp;",8*$v['level']).$v['pri_name'].'</option>';
            }

        }
        //显示添加样式
        $class = Db::name('class')->select();
        $class_str = '';
        foreach($class as $key => $val){
            $class_str .= '<option value="'.$val['id'].'">'.$val['class'].'</option>';
        }

        $this->assign('class',$class_str);
        $this->assign('str',$str);
        return $this->fetch('');
    }

    //显示修改的页面
    public function edit(){
        $id = input('id');
        $_level = input('level');


        //链接数据库
        $result = db('privilege')->where('id',$id)->find();

        //取出所有的权限
        $parentData = db('privilege')->order('order_id asc')->select();
        $parentData = _reSort($parentData);
        $str = '';
        foreach($parentData as $k => $v){
            $level = $v['level']+1;
            if($level == '3'){
                continue;
            }else{
                $str .= '<option value="'.$v['id'].'" level="'.$level.'" " module_name="'.$v['module_name'].'" controller_name="'.$v['controller_name'].'"> '
                    .str_repeat("&nbsp;",8*$v['level']).$v['pri_name'].'</option>';
            }

        }
        //显示选择的样式
        $classAll = Db::name('class')->select();
        $classname =  $result['class'];

        $classAll_str = '';
        foreach($classAll as $key => $val){
            if($val['class'] == $classname){
                $check = 'selected="selected"';
            }else{
                $check = '';
            }
            $classAll_str .= '<option value="'.$val['id'].'" '.$check.'>'.$val['class'].'</option>';
        }
        $page_info['class_str'] = $classAll_str;
        $page_info['str'] = $str;
        $page_info['level'] = $_level;
        $page_info['privilege'] = $result;
        $this->assign('page_info',$page_info);
        return $this->fetch('');
    }


    //添加权限数据
    public function addNode(){
        $node = $_POST;

        //只对顶级权限进行判断
        $ip_check_ip = '';
        if($node['parent_id'] == 0)
        {
            $ip_check_ip = $node['is_check_ip'];
        }
        unset($node['is_check_ip']);

        $node['module_name'] = trim($node['module_name']);
        $node['controller_name'] = trim($node['controller_name']);
        $node['action_name'] = trim($node['action_name']);
        $class_id = $node['class_style'];
        unset($node['class_style']);
        $class_name = db('class')->where('id',$class_id)->value('class');
        $node['class'] = $class_name;
        $level = trim($node['level']);
        unset($node['level']);
        //检测数据
        $privilege = new \app\sys\model\Privilege();
        $privilege->addPriData($node,$level);

        /*取出当前下面的父级下最大的order_id*/
        $order_id = db('privilege')->where('parent_id',$node['parent_id'])->max('order_id');
        if(empty($node['order_id'])){
            $node['order_id'] = $order_id+1;
        }
        /*添加数据到数据库中*/
        if($node['parent_id'] == 0)
        {
            $node['is_check_ip'] = $ip_check_ip;
        }

        $result = db('privilege')->insert($node);
        if($result !== false){
            echo setServerBackJson(1,"添加成功");
        }else{
            echo setServerBackJson(0,"添加失败");
        }
    }



    //获得修改权限的数据
    public function ajaxNodeedit(){
        $id = trim($_POST['id']);
        $data = $this->getChildren($id);
        $privilege = new \app\sys\model\Privilege();
        $parent = $privilege->getTree();
        $parentData['parent'] = $parent;
        //取出父级id
        if(empty($id)){
            echo setServerBackJson(0,"没有节点id");exit;
        }
        //链接数据库
        $result = db('privilege')->where('id',$id)->find();
        $Data['cid'] = $data;
        $result = array_merge($result,$Data);
        $result = array_merge($result,$parentData);
        echo json_encode($result);
    }


    //获得当前节点下面的子节点
    public function getChildren($id)
    {
        $data = db('privilege')->select();
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


    //删除节点权限数据
    public function deleteajaxNode(){
        $id = input('id');
        if(empty($id)){
            echo setServerBackJson(0,"没有节点id");exit;
        }
        $result = $this->getChildren($id);
        if($result){
            echo setServerBackJson(0,"当前节点下有子类无法删除","","",1);exit;
        }
        
        db('privilege')->where('id',$id)->delete();
        echo setServerBackJson(1,"删除成功","","closeDialog");
    }


    //提交表单修改权限数据
    public function editPriData(){
        $post = input('post.');
        $editData = $post;
        $editData['module_name'] = trim($editData['module_name']);
        $editData['controller_name'] = trim($editData['controller_name']);
        $editData['action_name'] = trim($editData['action_name']);
        //样式数据的处理

        $className = Db::name('class')->where('id',$editData['class_style'])->value('class');
        $editData['class'] = $className;
        unset($editData['class_style']);

         if(!isset($editData['new_open'])){
             $editData['new_open'] = 0;
         }

        $id = $editData['id'];
        $level = $editData['level'];
        unset($editData['level']);
        unset($editData['id']);
        //校验修改的数据
        $privilege = new \app\sys\model\Privilege();
        $privilege->editData($editData,$id,$level);

        //判断是否为一级权限
        if($post['parent_id'] == 0)
        {
            $editData['is_check_ip'] = $post['is_check_ip'];
        }
        //修改添加的数据
        db('privilege')->where('id',$id)->update($editData);
        echo setServerBackJson(1,"修改成功","","closeDialog");
    }

    //检查权限名称是否重复
    public function checkPriname(){
        $post = input('post.data');
        /*链接数据库*/
        $result = db('privilege')->where('pri_name',$post)->find();
        if($result){
            echo json_encode("权限名称重复，请重新输入");
        }
    }

}