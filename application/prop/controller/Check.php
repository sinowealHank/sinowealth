<?php
namespace app\prop\controller;
use think\Db;
use think\Session;
use think\Request;
use app\index\controller\Admin;

class Check extends Admin{
    public function index(){
        //取出当前验收人的验收信息
        $login_id = session('login_id');
        $check_data = Db::name('prop_pur')->alias('a')->join('prop_apply b','a.apply_id=b.id')->field('a.*,b.type')->where('accept_use_id',$login_id)->where('pur_process',4)->paginate(10,false,[
            'query' => Request::instance()->param(),
        ]);
        $page_info['page'] = $check_data->render();
        $page_info['data'] = object_change_array($check_data)['data'] ;
        $this->assign('page_info',$page_info);
        return $this->fetch();

    }

    public function add(){
        //获得采购单的id
        $id = input('id');
        //判断是否为公共财产
//        $data = Db::name('prop_pur')->where('id',$id)->field('is_common,user_name,pur_name,apply_id')->find();
        $data = Db::name('prop_pur')->alias('a')->join('prop_apply b','a.apply_id=b.id')->field('a.user_name,a.user_name,a.pur_name,a.apply_id,b.type,b.is_new')->where('a.id',$id)->find();
        $data = object_change_array($data);
        $is_new = $data['is_new'];
        $type = $data['type'];
        $use_name = $data['user_name'];
        if($type == 2 || $type == 3){
            $use_name_str = '<input disabled="disabled" type="text" value="'.$use_name.'">';
            $page_info['use_name'] = $use_name_str;
        }else if($is_new == 1){
            $use_name_str = '<input disabled="disabled" type="text" value="'.$use_name.'">';
            $page_info['use_name'] = $use_name_str;
        }else{
            $use_name_str = '<input disabled="disabled" type="text" value="'.$use_name.'">';
            $page_info['use_name'] = $use_name_str;
        }
        //取出所有的部门
        $dep_id = Db::name('prop_apply')->where('id',$data['apply_id'])->value('use_dep_id');
        $dep = Db::name('sys_dep')->field('id,pid as parent_id,zh_name')->select();
        $dep = _reSort($dep);
        $dep_str = '';
        foreach($dep as $k => $v){
            if($dep_id == $v['id']){
                $selected = 'selected="selected"';
            }else{
                $selected = '';
            }
            $dep_str .= '<option value="'.$v['id'].'" '.$selected.'>'.str_repeat('&nbsp;', 6*$v['level']).$v['zh_name'].'</option>';
        }
        $page_info['pur_id'] = $id;
        $page_info['type'] = $type;
        $page_info['dep'] = $dep_str;
        $this->assign('page_info',$page_info);
        return $this->fetch();

    }

    public function addaccept(){
        $data = $_POST;
        $id = $data['id'];
        //进行数据的校验
        $prop = new \app\prop\model\PropAccept();
        $prop->check_accept_data($data);

        //数据的过滤
        $insertdata = array();
        foreach($data as $k => $v){
            $insertdata[$k] = trim($v);
        }
        if(isset($insertdata['is_pur'])){
            $insertdata['is_pur'] = 1;
        }else{
            $insertdata['is_pur'] = 0;
        }
        //添加所在部门、所在用户id
        $apply_id = Db::name('prop_pur')->where('id',$id)->value('apply_id');
        $arr = Db::name('prop_apply')->where('id',$apply_id)->field('user_id,use_dep_id,type,is_new')->find();
        $insertdata['user_id'] = $arr['user_id'];
        $insertdata['dep_id'] = $arr['use_dep_id'];
        $insertdata['type'] = $arr['type'];
        $insertdata['is_new'] = $arr['is_new'];
        $insertdata['pur_id'] = $insertdata['id'];
        unset($insertdata['id']);

        //改变采购列表的状态
        Db::name('prop_pur')->where('id',$id)->setField('pur_process','2');
        //改变采购列表中的流程
        $apply_id = Db::name('prop_pur')->where('id',$id)->value('apply_id');
        Db::name('prop_apply')->where('id',$apply_id)->setField('prop_process',3);
        //添加到记录到验收表中
        $result = Db::name('prop_accept')->insert($insertdata);
        if($result){
            echo setServerBackJson(1,"验收表单添加成功");exit;
        }else{
            echo setServerBackJson(0,"验收表单添加失败");exit;
        }

    }
}