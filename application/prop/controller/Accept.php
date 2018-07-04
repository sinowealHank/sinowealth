<?php
namespace app\prop\controller;
use think\Db;
use think\Session;
use think\Request;
use app\index\controller\Admin;

class Accept extends Admin{
    public function index(){
        $page_info = array();
        //取出验收表的记录
        $login_id = Session::get('login_id');
        //取出当前用户下的验收表单
        $result = Db::name('prop_accept')->where('user_id',$login_id)->where('accept_process',0)->paginate(10,false,[
            'query' => Request::instance()->param(),
        ]);
        $page_info['page'] = $result->render();
        $page_info['data'] = $result;
        $this->assign('page_info',$page_info);
        return $this->fetch();
    }

    public function check(){
        $id = input('id');
        $data = Db::name('prop_accept')->where('id',$id)->find();
        $page_info['data'] = $data;
//        //取出验收人
//        $accept_user = Db::name('sys_user')->where('id',$data['accept_user_id'])->value('nickname');
//        $accept_str = '<option>'.$accept_user.'</option>';
        //取出所在部门
//        $dep = Db::name('sys_dep')->where('id',$data['dep_id'])->value('zh_name');
//        $page_info['dep'] = '<option>'.$dep.'</option>';
//        $page_info['user'] = $accept_str;
        $this->assign('page_info',$page_info);
        return $this->fetch();

    }
    public function checkform(){
        $id = input('id');
        $accept_id = session('login_id');
        $nickname = Db::name('sys_user')->where('id',$accept_id)->value('nickname');
        $time = date("Y-m-d",time());
        $update['receive_name'] = $nickname;
        $update['receive_time'] = $time;
        $update['accept_process'] = 1;
        //设置验收流程
        Db::name('prop_accept')->where('id',$id)->update($update);
        //设置采购流程
        $pur_id = Db::name('prop_accept')->where('id',$id)->value('pur_id');
        $apply_id = Db::name('prop_pur')->where('id',$pur_id)->value('apply_id');
        Db::name('prop_apply')->where('id',$apply_id)->setField('prop_process',4);
        echo setServerBackJson(1,"验收成功");
    }




}