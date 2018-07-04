<?php
namespace app\prop\controller;
use think\Controller;
use think\Db;
use think\Request;
use app\index\controller\Admin;

class Purchase extends Admin{
    public function index(){
        $login_id = session('login_id');
        //取出统购长批示的采购单
        $data = Db::name('prop_pur')->alias('a')->join('sys_dep b','a.manage_id=b.id')->join('sys_user c','a.apply_use_id=c.id')
            ->field('a.*,b.zh_name,c.nickname')->where('a.purchase_id',$login_id)->where('a.is_pur',1)->where('a.pur_process',4)->paginate(10,false,[
            'query' => Request::instance()->param(),
        ]);
        $page_info['page'] = $data->render();
        $page_info['data'] = $data ;
        $this->assign('page_info',$page_info);
        return $this->fetch();
    }

    public function showpur(){
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
        $model_id = $common_data['select_model_id'];
        foreach($result as $k => $v){
            if($v['id'] == $model_id){
                $selected = 'selected="selected"';
            }else{
                $selected = '';
            }
            $str .= '<option value="'.$v['id'].'" '.$selected.'>'.$v['brand_model'].'</option>';
        }
        $page_info['select'] = $str;
        $page_info['brand'] = $brand_str;
        $page_info['model'] = $model_str;
        $this->assign('page_info',$page_info);
        return $this->fetch();
    }

    public function check(){
        $id = session('login_id');
        $data = input('post.');
        $insertData = array();
        foreach($data as $k => $v){
            $insertData[$k] = trim($v);
        }
        if(isset($insertData['purchase_agree'])){
            $insertData['purchase_agree'] = 0;
            $insertData['pur_process'] = 5;
            Db::name('prop_pur')->where('id',$insertData['id'])->update($insertData);

            //设置log记录
            $log_info = array();
            $log_info['pur_id'] = $insertData['id'];
            $log_info['user_id'] = session('login_id');
            $log_info['create_user'] = $_SESSION['think']['user_auth']['nickname'];
            $log_info['create_time'] = date("Y-m-d H:i:s",time());
            $log_info['log_info'] = $log_info['create_time'].'统购长主管'.$log_info['create_user']."不同意采购";
            $log_info['log_type'] = 1;
            echo setServerBackJson(1,"审核成功");exit;
        }
        $insertData['purchase_id'] = $id;
        $insertData['purchase_agree'] = 1;
        $insertData['pur_process'] = 6;
        Db::name('prop_pur')->where('id',$insertData['id'])->update($insertData);

        //设置log记录
        $log_info = array();
        $log_info['pur_id'] = $insertData['id'];
        $log_info['user_id'] = session('login_id');
        $log_info['create_user'] = $_SESSION['think']['user_auth']['nickname'];
        $log_info['create_time'] = date("Y-m-d H:i:s",time());
        $log_info['log_info'] = $log_info['create_time'].'统购长主管'.$log_info['create_user']."同意采购";
        $log_info['log_type'] = 1;
        echo setServerBackJson(1,"审核成功");exit;

    }




}