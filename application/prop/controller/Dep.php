<?php
namespace app\prop\controller;
use think\Db;
use think\Session;
use think\Request;
use app\index\controller\Admin;

class Dep extends Admin{
    public function index(){
        $reg_prop_num = trim(input('get.reg_prop_num'));
        $reg_prop_name = trim(input('get.reg_prop_name'));
        $product_model = trim(input('get.product_model'));

        //取出当前登录的id
        $login_id = Session::get('login_id');
        //取出当前的所属部门
//        $dep_id = Db::name('sys_user')->where('id',$login_id)->value('dep_id');
        $dep_id = get_cache_data('user_info',$login_id,'dep_id');

        if($reg_prop_num || $reg_prop_name || $product_model){
            $map['reg_prop_num'] = ['like','%'.trim($reg_prop_num).'%'];
            $map['reg_prop_name'] = ['like','%'.trim($reg_prop_name).'%'];
            $map['product_model'] = ['like','%'.trim($product_model).'%'];
            $prop_data = Db::name('prop')->where($map)->where('local_dep_id',$dep_id)->paginate(10,false,[
                'query' => Request::instance()->param(),
            ]);
            $page_info['reg_prop_num'] = $reg_prop_num;
            $page_info['reg_prop_name'] = $reg_prop_name;
            $page_info['product_model'] = $product_model;

            $page_info['page'] = $prop_data->render();
            $page_info['data'] = $prop_data;
            $this->assign('page_info',$page_info);
            return $this->fetch();

        }else{
            //取出用户确认过的验收单
            //取出属于当前部门下的资产
            $prop_data = Db::name('prop')->where('local_dep_id',$dep_id)->paginate(10,false,[
                'query' => Request::instance()->param(),
            ]);
            $page_info['page'] = $prop_data->render();
            $page_info['data'] = $prop_data;
            $this->assign('page_info',$page_info);
            return $this->fetch();
        }


    }
   //申请部门审核
    public function showpur(){
        $pur_name = trim(input('get.pur_name'));
        $user_name = trim(input('get.user_name'));
        //取出当前登录人的id
        $login_id = session('login_id');
        if($pur_name != '' || $user_name != ''){
            $map['pur_name'] = ['like','%'.$pur_name.'%'];
            $map['user_name'] = ['like','%'.$user_name.'%'];
            $result = Db::name('prop_pur')->where($map)->where('dep_manage_id',$login_id)->where('pur_process',2)->paginate(5,false,[
                'query' => Request::instance()->param(),
            ]);
            $page_info['pur_name'] = $pur_name;
            $page_info['user_name'] = $user_name;
            $page_info['page'] = $result->render();
            $page_info['data'] = $result ;
            $this->assign('page_info',$page_info);
            return $this->fetch();

        }else{
            $result = Db::name('prop_pur')->where('dep_manage_id',$login_id)->where('pur_process',2)->paginate(5,false,[
                'query' => Request::instance()->param(),
            ]);
            $page_info['page'] = $result->render();
            $page_info['data'] = $result ;
            $this->assign('page_info',$page_info);
            return $this->fetch();
        }



    }


    public  function checkpur(){
        $id = input('id');
        //取出当前采购单的公共信息
        $common_data = Db::name('prop_pur')->where('id',$id)->find();
        $id_str = $common_data['model_id_str'];
        $num = count(explode(',',$id_str));
        $page_info['common'] = $common_data;
        //取出it部门人员
        $id_arr = Db::name('sys_user')->field('id,nickname')->where('dep_id',16)->select();
        $it_str = '';
        foreach($id_arr as $k => $v){
            if($v['id'] == $common_data['accept_use_id']){
                $selected = 'selected="selected"';
            }else{
                $selected = '';
            }
            $it_str .= '<option value="'.$v['id'].'" '.$selected.' >'.$v['nickname'].'</option>';
        }
        $page_info['it'] = $it_str;
        //取出会签部门
        $dep_all = Db::name('sys_dep')->field('id,zh_name,pid as parent_id')->select();
        $dep_all = _reSort($dep_all);
        $dep_str = '';
        foreach($dep_all as $k => $v){
            $dep_str .= '<option value="'.$v['id'].'" >'.str_repeat('&nbsp;', 6*$v['level']).$v['zh_name'].'</option>';
        }
        $page_info['dep'] = $dep_str;
        $page_info['num'] = $num;
        //取出当前选择型号的信息
        $map['id'] = ['in',$id_str];
        $model_data = Db::name('prop_model')->where($map)->select();
        $brand_str = '';
        $model_str = '';
        foreach($model_data as $k1 => $v1){
            $brand_str .= '<tr >';
            $brand_str .= '<td></td>
                            <td><input disabled="disabled" type="text" name="brand_model[]" value="'.$v1['brand_model'].'"></td>
                            <td><input disabled="disabled" type="text" name="basic_info[]" value="'.$v1['basic_info'].'"></td>
                            <td><input disabled="disabled" type="text" name="refer_price[]" value="'.$v1['refer_price'].'"></td>
                            <td><input disabled="disabled" type="text" name="remark[]" value="'.$v1['remark'].'"></td>';
            $brand_str .= '</tr>';

            $model_str .= '<tr>';
            $model_str .= '<td><input disabled="disabled" type="text" name="model[]" value="'.$v1['model'].'"></td>
                            <td><input disabled="disabled"  type="text" name="bar_price_1[]" value="'.$v1['bar_price_1'].'"></td>
                            <td><input disabled="disabled" type="text" name="offer_price_1[]" value="'.$v1['offer_price_1'].'"></td>
                            <td><input disabled="disabled" type="text" name="offer_price_2[]" value="'.$v1['offer_price_2'].'"></td>
                            <td><input disabled="disabled" type="text" name="bar_price_2[]" value="'.$v1['bar_price_2'].'"></td>
                            <td><input disabled="disabled" type="text" name="offer_price_3[]" value="'.$v1['offer_price_3'].'"></td>
                            <td><input disabled="disabled" type="text" name="bar_price_3[]" value="'.$v1['bar_price_3'].'"></td>';
            $model_str .='</tr>';
        }
        $where['id'] = ['in',$id_str];
        $result = Db::name('prop_model')->where($where)->field('brand_model,id')->select();
        $str = '';
        foreach($result as $k => $v){
            $str .= '<option value="'.$v['id'].'">'.$v['brand_model'].'</option>';
        }
        $page_info['select'] = $str;
        $page_info['brand'] = $brand_str;
        $page_info['model'] = $model_str;
        $this->assign('page_info',$page_info);
        return $this->fetch();
    }

    public function check(){
        $id = session('login_id');
        $data = $_POST;
        //数据的过滤
        $insertData = array();
        foreach($data as $k => $v){
            $insertData[$k] = trim($v);
        }
        if(isset($insertData['dep_agree'])){
            //审核不同意
            $insertData['dep_agree'] = 0;
            unset($insertData['select_model_id']);
            $insertData['pur_process'] = 3;
            Db::name('prop_pur')->where('id',$insertData['id'])->update($insertData);

            //设置log记录
            $log_info = array();
            $log_info['pur_id'] = $insertData['id'];
            $log_info['user_id'] = session('login_id');
            $log_info['create_user'] = $_SESSION['think']['user_auth']['nickname'];
            $log_info['create_time'] = date("Y-m-d H:i:s",time());
            $log_info['log_info'] = $log_info['create_time'].'申请部门主管'.$log_info['create_user']."不同意采购";
            $log_info['log_type'] = 2;
            Db::name('prop_pur_log')->insert($log_info);

            echo setServerBackJson(1,"审核成功");exit;

        }
        if(empty($insertData['select_model_id'])){
            echo setServerBackJson(0,"型号选择不能为空");exit;
        };
        $insertData['pur_process'] = 4;
        $insertData['dep_manage_id'] = $id;

        //设置log记录
        $log_info = array();
        $log_info['pur_id'] = $insertData['id'];
        $log_info['user_id'] = session('login_id');
        $log_info['create_user'] = $_SESSION['think']['user_auth']['nickname'];
        $log_info['create_time'] = date("Y-m-d H:i:s",time());
        $log_info['log_info'] = $log_info['create_time'].'申请部门主管'.$log_info['create_user']."同意采购";
        $log_info['log_type'] = 1;
        Db::name('prop_pur_log')->insert($log_info);

        //确认数据已审核
        Db::name('prop_pur')->where('id',$insertData['id'])->update($insertData);
        echo setServerBackJson(1,"已审核成功");exit;

    }

    public function showaccept(){
        $login_id = session('login_id');
        $dep_id = Db::name('sys_user')->where('id',$login_id)->value('dep_id');
        //取出验收单
        $data = Db::name('prop_accept')->where('dep_id',$dep_id)->where('type',1)->where('accept_process',1)->paginate(5,false,[
            'query' => Request::instance()->param(),
        ]);
        $page_info['page'] = $data->render();
        $page_info['data'] = $data;
        $this->assign('page_info',$page_info);
        return $this->fetch();

    }

    public function checkaccept(){
        $id = input('id');
        $data = Db::name('prop_accept')->where('id',$id)->find();
        $page_info['data'] = $data;
        $this->assign('page_info',$page_info);
        return $this->fetch();
    }

    public function depsign(){
        $id = input('id');
        $data = Db::name('prop_accept')->where('id',$id)->field('pur_id,dep_id')->find();
        $mange_id = Db::name('sys_dep')->where('id',$data['dep_id'])->value('manage_user_id');
        $manage_name = Db::name('sys_user')->where('id',$mange_id)->value('nickname');
        //设置采购流程
        $pur_id = $data['pur_id'];
        $apply_id = Db::name('prop_pur')->where('id',$pur_id)->value('apply_id');
        Db::name('prop_apply')->where('id',$apply_id)->setField('prop_process',5);
        $update['dep_sign'] = $manage_name;
        $update['accept_process'] = 2;
        Db::name('prop_accept')->where('id',$id)->update($update);
        echo setServerBackJson(1,"确认成功");

    }

    public function apply(){
        $login_id = session('login_id');

        //取出当前部门的申请列表
        $data = Db::name('prop_apply')->alias('a')->join('sys_user b','a.apply_use_id=b.id')->field('a.*,b.nickname')->where('use_manage_dep_id',$login_id)->paginate(10,false,[
            'query' => Request::instance()->param(),
        ]);;
        $page_info['page'] = $data->render();
        $page_info['data'] = object_change_array($data)['data'];
        $this->assign('page_info',$page_info);
        return $this->fetch();
    }

    public function checkapply(){
        $id = input('id');
        $data = Db::name('prop_apply')->where('id',$id)->find();
        $page_info = $data;
        $this->assign('page_info',$data);
        return $this->fetch();
    }

    public function applycheck(){
        $id = input('post.id');
        $advice = input('post.advice');
        if(empty($advice)){
            //同意本部门申请资产
            Db::name('prop_apply')->where('id',$id)->setField('apply_process',2);
            echo setServerBackJson(1,"同意申请");exit;
        }else{

           //不同意
            $insert['advice'] = $advice;
            $insert['apply_id'] = $id;
            Db::name('prop_apply')->where('id',$id)->setField('apply_process',1);
            $result = Db::name('prop_advice')->insert($insert);
            if($result){
                echo setServerBackJson(1,"不同意申请");exit;
            }
        }
    }


    public function checkagain(){
        $id = input('id');
        $common_data = Db::name('prop_pur')->where('id',$id)->find();
        $id_str = $common_data['model_id_str'];
        $num = count(explode(',',$id_str));
        $page_info['common'] = $common_data;
        //取出it部门人员
        $id_arr = Db::name('sys_user')->field('id,nickname')->where('dep_id',16)->select();
        $it_str = '';
        foreach($id_arr as $k => $v){
            if($v['id'] == $common_data['accept_use_id']){
                $selected = 'selected="selected"';
            }else{
                $selected = '';
            }
            $it_str .= '<option value="'.$v['id'].'" '.$selected.' >'.$v['nickname'].'</option>';
        }
        $page_info['it'] = $it_str;
        //取出会签部门
        $dep_all = Db::name('sys_dep')->field('id,zh_name,pid as parent_id')->select();
        $dep_all = _reSort($dep_all);
        $dep_str = '';
        foreach($dep_all as $k => $v){
            $dep_str .= '<option value="'.$v['id'].'" >'.str_repeat('&nbsp;', 6*$v['level']).$v['zh_name'].'</option>';
        }
        $page_info['dep'] = $dep_str;
        $page_info['num'] = $num;
        //取出当前选择型号的信息
        $map['id'] = ['in',$id_str];
        $model_data = Db::name('prop_model')->where($map)->select();
        $brand_str = '';
        $model_str = '';
        foreach($model_data as $k1 => $v1){
            $brand_str .= '<tr >';
            $brand_str .= '<td></td>
                            <td><input disabled="disabled" type="text" name="brand_model[]" value="'.$v1['brand_model'].'"></td>
                            <td><input disabled="disabled" type="text" name="basic_info[]" value="'.$v1['basic_info'].'"></td>
                            <td><input disabled="disabled" type="text" name="refer_price[]" value="'.$v1['refer_price'].'"></td>
                            <td><input disabled="disabled" type="text" name="remark[]" value="'.$v1['remark'].'"></td>';
            $brand_str .= '</tr>';

            $model_str .= '<tr>';
            $model_str .= '<td><input disabled="disabled" type="text" name="model[]" value="'.$v1['model'].'"></td>
                            <td><input disabled="disabled"  type="text" name="bar_price_1[]" value="'.$v1['bar_price_1'].'"></td>
                            <td><input disabled="disabled" type="text" name="offer_price_1[]" value="'.$v1['offer_price_1'].'"></td>
                            <td><input disabled="disabled" type="text" name="offer_price_2[]" value="'.$v1['offer_price_2'].'"></td>
                            <td><input disabled="disabled" type="text" name="bar_price_2[]" value="'.$v1['bar_price_2'].'"></td>
                            <td><input disabled="disabled" type="text" name="offer_price_3[]" value="'.$v1['offer_price_3'].'"></td>
                            <td><input disabled="disabled" type="text" name="bar_price_3[]" value="'.$v1['bar_price_3'].'"></td>';
            $model_str .='</tr>';
        }
        $where['id'] = ['in',$id_str];
        $result = Db::name('prop_model')->where($where)->field('brand_model,id')->select();
        $str = '';
        foreach($result as $k => $v){
            if($v['id'] == $common_data['select_model_id']){
                $selected1 = 'selected="selected"';
            }else{
                $selected1 = '';
            }
            $str .= '<option value="'.$v['id'].'" '.$selected1.'>'.$v['brand_model'].'</option>';
        }
        //取出当前部门主管的id
//        $manage_dep_id = Db::name('sys_dep')->where('id',$common_data['manage_id'])->value('manage_user_id');
//        $manage_nickname = Db::name('sys_user')->where('id',$manage_dep_id)->value('nickname');
//        $manage_str = '<option value="'.$manage_dep_id.'">'.$manage_nickname.'</option>';
//        $page_info['manage_str'] = $manage_str;
        $page_info['select'] = $str;
        $page_info['brand'] = $brand_str;
        $page_info['model'] = $model_str;
        $this->assign('page_info',$page_info);
        return $this->fetch();

    }



}