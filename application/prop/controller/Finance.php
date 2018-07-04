<?php
namespace app\prop\controller;
use think\Controller;
use think\Db;
use think\Request;
use app\index\controller\Admin;

class Finance extends Admin{
    public function index(){
        $page_info = array();
        $reg_prop_num = input('get.reg_prop_num');
        $prop_user = input('get.prop_user');
        if(trim($reg_prop_num) || trim($prop_user) ){
            $map['reg_prop_num'] = ['like','%'.trim($reg_prop_num).'%'];
            $map['prop_user'] = ['like','%'.trim($prop_user).'%'];
            //取出财务需要审核的记录
            $finance_check = Db::name('prop')->where($map)->where('finan_check',0)->paginate(5,false,[
                'query' => Request::instance()->param(),
            ]);
            $page_info['reg_prop_num'] = $reg_prop_num;
            $page_info['prop_user'] = $prop_user;
            $page_info['page'] = $finance_check->render();
            $page_info['finance'] = $finance_check;
            $this->assign('page_info',$page_info);
            return $this->fetch();
        } else{
            //取出财务需要审核的记录
            $finance_check = Db::name('prop')->where('finan_check',0)->paginate(5,false,[
                'query' => Request::instance()->param(),
            ]);
            $page_info['page'] = $finance_check->render();
            $page_info['finance'] = $finance_check;
            $this->assign('page_info',$page_info);
            return $this->fetch();
        }
    }

    public function edit(){
        $id = input('id');
        //取出当前资产的相关信息
        $prop_data = Db::name('prop')->where('id',$id)->find();
        //取出所在部门的id
        $dep_id = Db::name('sys_dep')->where('zh_name',$prop_data['dep'])->field('id')->find();
        $dep_id = $dep_id['id'];
        //取出负责部门的id
        $respon_dep = Db::name('sys_dep')->where('zh_name',$prop_data['respon_dep'])->field('id')->find();
        $respon_dep_id = $respon_dep['id'];
        //取出使用部门的id
        $use_dep_id = Db::name('sys_dep')->where('zh_name',$prop_data['use_dep'])->field('id')->find();
        $use_dep_id = $use_dep_id['id'];

        //取出所在部门部门的信息
        $dep_arr = Db::name('sys_dep')->field('id,en_name,zh_name,pid as parent_id')->select();
        $dep_arr = _reSort($dep_arr);
        $str = '';
        foreach($dep_arr as $k => $v){
            if($dep_id == $v['id']){
                $selected = 'selected="selected"';
            }else{
                $selected = '';
            }

            $str .= '<option value="'.$v['id'].'" '.$selected.' > '
                .str_repeat("&nbsp;",6*$v['level']).$v['zh_name'].'</option>';
        }
        //取出负责部门的信息
        $respon_dep_str = '';
        foreach($dep_arr as $k1 => $v1){
            if($respon_dep_id == $v1['id']){
                $selected1 = 'selected="selected"';
            }else{
                $selected1 = '';
            }
            $respon_dep_str .= '<option value="'.$v1['id'].'" '.$selected1.' > '
                .str_repeat("&nbsp;",6*$v1['level']).$v1['zh_name'].'</option>';
        }
        //取出使用部门的信息
//        $use_dep_str = '';
//        foreach($dep_arr as $k2 => $v2){
//            if($v2['id'] ==  $use_dep_id){
//                $selected2 = 'selected="selected"';
//            }else{
//                $selected2 = '';
//            }
//            $use_dep_str .= '<option value="'.$v2['id'].'"  '.$selected2.'> '
//                .str_repeat("&nbsp;",6*$v2['level']).$v2['zh_name'].'</option>';
//        }

        $prop_type = Db::name('prop_type')->select();
        $prop_type = _reSort($prop_type);
        $prop_type_str = '';
        foreach($prop_type as $key => $val){
            if($val['id'] == $prop_data['prop_type']){
                $selected_type = 'selected="selected"';
            }else{
                $selected_type = '';
            }
            $prop_type_str .= '<option value="'.$val['id'].'" '.$selected_type.'> '
                .str_repeat("&nbsp;",6*$val['level']).$val['prop_type'].'</option>';
        }
        $page_info['prop_type_str'] = $prop_type_str;

        $page_info['prop_data'] = $prop_data;
//        $page_info['use_dep'] = $use_dep_str;
        $page_info['respon_dep'] = $respon_dep_str;
        $page_info['dep'] = $str;
        $this->assign('page_info',$page_info);
        return $this->fetch();
    }

    public function review(){
        $review_data = $_POST;

        if($review_data['orginal_val'] == ''){
            echo setServerBackJson(0,"原值不能为空");exit;
        }
        if($review_data['net_val'] == ''){
            echo setServerBackJson(0,"净值不能为空");exit;
        }
        if($review_data['use_dep'] == ''){
            echo setServerBackJson(0,"使用部门不能为空");exit;
        }

        //取出使用部门的信息
        $review_data['finan_check'] = 1;
        //审核数据内容
        Db::name('prop')->where('id',$review_data['id'])->update($review_data);

        echo setServerBackJson(1,"审核成功");
    }
    //查看已审核列表
    public function checked(){
        $page_info = array();
        $reg_prop_num = input('get.reg_prop_num');
        $prop_user = input('get.prop_user');
        $propuser = input('get.propuser');
        if(trim($reg_prop_num) || trim($prop_user) || trim($prop_user)){
            $map['reg_prop_num'] = ['like','%'.trim($reg_prop_num).'%'];
            $map['prop_user'] = ['like','%'.trim($prop_user).'%'];
            $map['propuser'] = ['like','%'.trim($propuser).'%'];
            //取出财务需要审核的记录
            $finance_check = Db::name('prop')->where($map)->where('finan_check',1)->paginate(5,false,[
                'query' => Request::instance()->param(),
            ]);
            $page_info['reg_prop_num'] = $reg_prop_num;
            $page_info['prop_user'] = $prop_user;
            $page_info['page'] = $finance_check->render();
            $page_info['finance'] = $finance_check;
            $this->assign('page_info',$page_info);
            return $this->fetch();
        } else{
            //取出财务需要审核的记录
            $finance_check = Db::name('prop')->whereOr('finan_check',1)->paginate(5,false,[
                'query' => Request::instance()->param(),
            ]);
            $page_info['page'] = $finance_check->render();
            $page_info['finance'] = $finance_check;
            $this->assign('page_info',$page_info);
            return $this->fetch();
        }
    }

    public function show(){



    }





}