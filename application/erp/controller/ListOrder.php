<?php
namespace app\erp\controller;
use think\Controller;
use think\Db;
use think\Session;

use app\index\controller\Admin;
class ListOrder extends admin{


    public function index(){

        $filed_data = Db::name('list_filed')->select();

        return $this->fetch('',[
            'field_data'=>$filed_data
        ]);
    }

    //获取数据datagird数据
    public function get_order_data(){
        $result = model('ListOrder')->get_data();
        $Pri_data = [];
        foreach($result as $val){
            $Pri_data[] = $val->toArray();
        }
        $count =  Db::name('ListOrder')->count();
        $qq  = $Pri_data;
        $qty = $count;
        $json = '{"total":'.$qty.',"rows":'.json_encode($qq).'}';
        echo $json;
    }

    //修改订单数据
    public function edit_order($id){
        $order_id = $id;
        $login_id = session('login_id');
        //判断对应的部门
        $dep_id = get_cache_data('user_info',$login_id,'dep_id');
        //获取对应的人的字段
        $user_type = model("ListOrder")->get_dep_author_field($dep_id);
        //取出对应的字段
        $field_arr = Db::name('ListFiled')->where('user_type',$user_type)->select();
        $map = [];
        foreach($field_arr as $k => $val){
            $map[] = $val['filed'];
        }
        $map_str = implode(',',$map);
        //取出对应的字段的值
        $order_data = Db::name('ListOrder')->field($map_str)->where('id',$order_id)->find();
        //构建对应的数组
        foreach($field_arr as $k1 => &$val1){
            $val1['value'] = $order_data[$val1['filed']];
        }
        $remake_data = Db::name('ListOrder')->where('id',$order_id)->find();
        $remake = '';
        //FD部门
        if($dep_id == 3){
            $remake = $remake_data['fd_remake'];
        //SD部门
        }else if($dep_id == 10){
            $remake = $remake_data['sd_remake'];
        //PP部门
        }else if($dep_id == 13){
            $remake = $remake_data['pp_remake'];
        //PE部门
        }else if($dep_id == 12){
            $remake = $remake_data['pe_remake'];
        }

        if($login_id == 1){
            //取出对应的部门的备注信息
            $remake_data_arr = Db::name('ListOrder')->field('pe_remake,pp_remake,sd_remake,fd_remake')->where('id',$id)->find();
            $remake_arr['pe_remake'] = $remake_data_arr['pe_remake'];
            $remake_arr['pp_remake'] = $remake_data_arr['pp_remake'];
            $remake_arr['sd_remake'] = $remake_data_arr['sd_remake'];
            $remake_arr['fd_remake'] = $remake_data_arr['fd_remake'];
            $this->assign('remake_data',$remake_arr);

        }
        return $this->fetch('',[
            'field'=> $field_arr,
            'id' => $order_id,
            'remake'=> $remake,
        ]);
    }

    public function save_order(){
         $data = input('post.','','trim');
         //增加备注信息
        $login_id = session('login_id');
        $dep_id = get_cache_data('user_info',$login_id,'dep_id');
        //FD部门
        if($dep_id == 3){
            $data['fd_remake'] = $data['remake'];
        //SD部门
        }else if($dep_id == 10){
            $data['sd_remake'] = $data['remake'];
        //PP部门
        }else if($dep_id == 13){
            $data['pp_remake'] = $data['remake'];
        //PE部门
        }else if($dep_id == 12){
            $data['pe_remake'] = $data['remake'];
        }
        unset($data['remake']);

         //插入到数据
         $result = model('ListOrder')->where('id',$data['id'])->update($data);
         if($result !== false){
             echo setServerBackJson(1,"编辑成功");
         }else{
             echo setServerBackJson(0,'编辑失败');
         }

    }

}