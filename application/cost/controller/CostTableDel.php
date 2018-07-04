<?php
namespace app\cost\controller;

use think\Controller;
use app\index\controller\Admin;
use think\Db;

class CostTableDel extends Admin{
    public function index(){
        //取出当前列表中的字段
        $field = cost_config(3);
        $where = 'field not in('.cost_config(3).')';
        $else_filed = Db::name('cost_field')->where($where)->field('id,field,field_show')->select();
        $page_info['field'] = $else_filed;

        $lines = Db::name('cost_config')->where('name','line')->column('content');
        $page_info['line'] = cost_string_to_array($lines['0']);

        $this->assign('page_info',$page_info);
        return $this->fetch();
    }

    //请求数据
    public function costData(){
        $sort = isset($_POST['sort']) ? $_POST['sort'] : 'id';
        $order = isset($_POST['order']) ? $_POST['order'] : 'desc';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $rows = isset($_POST['rows']) ? intval($_POST['rows']) : 10;
        $search = $_POST;

        if(isset($_POST['data'])){
            $line = $search['data'][0]['value'];
            $type = trim($search['data'][1]['value']);
            $prono = trim($search['data'][2]['value']);
            if(!empty($line) && $line != 1){
                $map['line'] = $line;
            }
            // type的查询
            if(!empty($type)){
                $map['type'] = ['like','%'.$type.'%'];
            }
            //pro no的查询
            if(!empty($prono)){
                $map['prdno'] = ['like','%'.$prono.'%'];
            }
        }

        $offset = ($page-1)*$rows;
        $map['show_type'] = '0';
        $qq = db('cost_cost')->where($map)->limit($offset,$rows)->order($sort,$order)->select();
        $qty = db('cost_cost')->where($map)->count();
        $json='{"total":'.$qty.',"rows":'.json_encode($qq).'}';
        echo $json;
    }


    //双击进行操作
    public function action(){
        $id = input('id');
        $page_info['id'] = $id;
        $this->assign('page_info',$page_info);
        return $this->fetch();
    }

   //真实删除数据
    public function deletecost(){
        $id = $_POST['id'];
        Db::name('cost_cost')->where('id',$id)->delete();
        //日志记录
        $log = date('Y-m-d H:i:s',time()).' '.power_show(cost_get_authorily(),1).'部门'.get_user_nickname().'删除真实数据';
        cost_set_log($id,$log,'1');
        echo setServerBackJson(1,"删除成功");exit;

    }










}