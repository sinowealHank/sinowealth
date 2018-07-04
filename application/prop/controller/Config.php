<?php
namespace app\prop\controller;
use think\Controller;
use think\Db;
use think\Request;
use app\index\controller\Admin;

class Config extends Admin{
    public function index(){
        //获取资产分类的数据
        $page_info = array();
        $result  = Db::name('prop_type')->order('id desc')->select();
        $result = change_filed($result,'name','prop_type');
        $tree = make_tree($result);
        $tree = json_encode($tree);
        $page_info['tree'] = $tree;
        $this->assign('page_info',$page_info);
        return $this->fetch();
    }



    //现实添加资产分类的页面
    public function add(){
        $result = Db::name('prop_type')->select();
        $result = _reSort($result);
        $str = '';
        foreach($result as $k =>$v){
            $str .= '<option value="'.$v['id'].'">'.str_repeat('&nbsp;', 8*$v['level']).$v['prop_type'].'</option>';
        }
        $this->assign('str',$str);
        return $this->fetch();
    }


    //添加资产的分类
    public function addtype(){
        $propTypeData = input('post.');
        if($propTypeData['prop_type'] == ''){
            echo setServerBackJson(0,"请填写资产分类名称");exit;
        }
        //添加数据
        Db::name('prop_type')->insert($propTypeData);
        echo setServerBackJson(1,"添加资产分类成功");
    }
    //显示修改资产的分页
    public function edit(){
        $id = input('id');
        //取出当前资产分类的数据
        $prop_type = Db::name('prop_type')->where('id',$id)->find();
        $result = Db::name('prop_type')->select();
        $result = _reSort($result);
        $str = '';
        foreach($result as $k => $v){
            if($v['id'] == $prop_type['parent_id']){
                $selected = 'selected="selected"';
            }else{
                $selected = '';
            }
            $str .= '<option value="'.$v['id'].'" '.$selected.'>'.str_repeat('&nbsp;', 8*$v['level']).$v['prop_type'].'</option>';
        }
        $page_info['prop'] = $prop_type;
        $page_info['str'] = $str;
        $this->assign('page_info',$page_info);
        return $this->fetch();
    }
    public function edittype(){
       $data = $_POST;
        Db::name('prop_type')->where('id',$data['id'])->update($data);
        echo setServerBackJson(1,"资产分类修改成功");

    }


    public function appoint(){
        
        return $this->fetch();
    }



}