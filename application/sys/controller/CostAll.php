<?php
namespace app\sys\controller;

use think\Controller;
use app\index\controller\Admin;
use think\Db;
use think\Request;

class CostAll extends Admin{
    public function index(){

        $data = input('get.vou_data/a');
        $vou_data_1 = trim($data[0]);
        $vou_data_2 = trim($data[1]);

        if($vou_data_1 || $vou_data_2 ){
            if($vou_data_2 == ''){
                $result = Db::name('sys_cost')->where('is_expend',1)->where('vou_data','EGT',$vou_data_1)->paginate(20,false,[
                    'query' => Request::instance()->param(),
                ]);
                $page_info['vou_data1'] = $vou_data_1;
                $page_info['vou_data2'] = $vou_data_2;
                $page_info['page'] = $result->render();
                $page_info['data'] = $result;
                $this->assign('page_info',$page_info);
                return $this->fetch();exit;
            }
            //取出总表所有数据
            $result = Db::name('sys_cost')->where('is_expend',1)->where('vou_data','EGT',$vou_data_1)->where('vou_data','ELT',$vou_data_2)->paginate(20,false,[
                'query' => Request::instance()->param(),
            ]);
            $page_info['vou_data1'] = $vou_data_1;
            $page_info['vou_data2'] = $vou_data_2;
            $page_info['page'] = $result->render();
            $page_info['data'] = $result;
            $this->assign('page_info',$page_info);
            return $this->fetch();
        }else{

            //取出总表所有数据
            $result = Db::name('sys_cost')->where('is_expend',1)->paginate(10,false,[
                'query' => Request::instance()->param(),

            ]);;
            $page_info['page'] = $result->render();
            $page_info['data'] = $result;
            $this->assign('page_info',$page_info);
            return $this->fetch();
        }



    }


    //显示添加报销费用的页面
    public function add(){
//        send_email('renjie.zhou@sinowealth.com','功能测试','本月报销已结款');

        //取出所有的部门信息
        $dep_all = config('dep_info');
        $dep_all = change_filed($dep_all,'parent_id','pid');
        $dep_all = _reSort($dep_all);
        $dep_str = '';
        foreach($dep_all as $k => $v){
            $dep_str .= '<option value="'.$v['id'].'">'.str_repeat('&nbsp;', 6*$v['level']).$v['en_name'].'</option>';
        }
        $page_info['dep'] = $dep_str;

        //取出费用类型名称
        $cost_type = Db::name('sys_cost_type')->select();
        $cost_type_str = '';
        foreach($cost_type as $k1 => $v1){
            $cost_type_str .= '<option value="'.$v1['id'].'">'.$v1['free_type_select'].'</option>';
        }
        $page_info['cost_type'] = $cost_type_str;
        $this->assign('page_info',$page_info);
        return $this->fetch();
    }

    //添加费用报销记录
    public function addcost(){
        $data = $_POST;

        //对数据的过滤
        $insertdata = array();
        foreach($data as $k => $v){
            $insertdata[$k] = trim($v);
        }


        //凭证日期、生成日期
        $insertdata['vou_data'] = date('Y-m-d',time());
        $insertdata['create_time'] = date('Y-m-d',time());

        //得到员工的名字
        $insertdata['user_name'] = get_cache_data('user_info',$insertdata['user_id'],'nickname');

        //判断是否已经报销
        if(isset($insertdata['is_expend'])){
            //数据的校验
            $Apply = new \app\sys\model\Cost();
            $Apply->check_insertData($insertdata);

            $insertdata['is_expend'] = 1;
            //发送邮件,取出当前员工的邮件地址
            $email = get_cache_data('user_info',$insertdata['user_id'],'email');
            $title = '报销发放总金额通知';
            $message = '本月报销请款，总额为：'.$insertdata['cost_amout'].'元，已作业完成。将于三个工作日内完成支付，请查收.';
            send_email($email,$title,$message);

            $result = Db::name('sys_cost')->insert($insertdata);
            if($result){
                echo setServerBackJson(1,"添加数据成功");
            }else{
                echo setServerBackJson(0,"添加数据失败");
            }
        }else{
            //数据的校验
            $Apply = new \app\sys\model\Cost();
            $Apply->check_insertData($insertdata);

            $insertdata['is_expend'] = 0;
            //添加数据
            $result = Db::name('sys_cost')->insert($insertdata);
            if($result){
                echo setServerBackJson(1,"添加数据成功");
            }else{
                echo setServerBackJson(0,"添加数据失败");
            }
        }




    }

    //获得当前部门下的员工
    public function get_user(){
        $id = input('post.id');
        //获得当前部门下的员工
        $user_all = Db::name('sys_user')->where('dep_id',$id)->select();
        $user_all_str = '<option>请选择下拉框的内容</option>';
        foreach($user_all as $k => $v){
            $user_all_str .= '<option value="'.$v['id'].'" user_job_name="'.$v['user_gh'].'">'.$v['nickname'].'</option>';
        }
       return $user_all_str;

    }


    //未结案列表
    public function uncost(){
          $vou_data_1 = input('get.vou_data1');
          $vou_data_2 = input('get.vou_data2');
          $dep_id = input('get.dep_id');
          $sort = input('get.sort');
          if(!$sort){
              $sort = 'desc';
          }

        if($vou_data_1 || $vou_data_2 || $dep_id ){
            $map['dep_id'] = $dep_id;
            if($dep_id ==  ''){
                unset($map['dep_id']);
            }
            if($vou_data_2 == ''){
                $result = Db::name('sys_cost')->order('id '.$sort)->where($map)->where('is_expend',0)->where('vou_data','EGT',$vou_data_1)->paginate(20,1,[
                    'query' => Request::instance()->param(),
                ]);
                //取出部门的全部信息
                $dep_info = config('dep_info');
                $dep_info = change_filed($dep_info,'parent_id','pid');
                $dep_info = _reSort($dep_info);
                $dep_info_str = '';
                foreach($dep_info as $k => $v){
                    if($v['id'] == $dep_id){
                        $select = 'selected="selected"';
                    }else{
                        $select = '';
                    }
                    $dep_info_str .= '<option value="'.$v['id'].'" '.$select.'>'.str_repeat('&nbsp;', 6*$v['level']).$v['zh_name'].'</option>';
                }
                $page_info['sort'] = $sort;
                $page_info['dep_str'] = $dep_info_str;
                $page_info['vou_data1'] = $vou_data_1;
                $page_info['vou_data2'] = $vou_data_2;
                $page_info['page'] = $result->render();
                $page_info['data'] = $result;
                $this->assign('page_info',$page_info);
                return $this->fetch();exit;
            }
            //取出总表所有数据
            $result = Db::name('sys_cost')->order('id '.$sort)->where($map)->where('is_expend',0)->where('vou_data','EGT',$vou_data_1)->where('vou_data','ELT',$vou_data_2)->paginate(20,false,[
                'query' => Request::instance()->param(),
            ]);

            //取出部门的全部信息
            $dep_info = config('dep_info');
            $dep_info = change_filed($dep_info,'parent_id','pid');
            $dep_info = _reSort($dep_info);
            $dep_info_str = '';
            foreach($dep_info as $k => $v){
                if($v['id'] == $dep_id){
                    $select = 'selected="selected"';
                }else{
                    $select = '';
                }

                $dep_info_str .= '<option value="'.$v['id'].'" '.$select.'>'.str_repeat('&nbsp;', 6*$v['level']).$v['zh_name'].'</option>';
            }
            $page_info['sort'] = $sort;
            $page_info['dep_str'] = $dep_info_str;
            $page_info['vou_data1'] = $vou_data_1;
            $page_info['vou_data2'] = $vou_data_2;
            $page_info['page'] = $result->render();
            $page_info['data'] = $result;
            $this->assign('page_info',$page_info);
            return $this->fetch();
        }else{

            //取出总表所有数据
            $result = Db::name('sys_cost')->order('id '.$sort)->where('is_expend',0)->paginate(4,false,[
                'query' => Request::instance()->param(),
            ]);

            //取出部门的全部信息
            $dep_info = config('dep_info');
            $dep_info = change_filed($dep_info,'parent_id','pid');
            $dep_info = _reSort($dep_info);
            $dep_info_str = '';
            foreach($dep_info as $k => $v){
                $dep_info_str .= '<option value="'.$v['id'].'">'.str_repeat('&nbsp;', 6*$v['level']).$v['zh_name'].'</option>';
            }

            $page_info['sort'] = $sort;
            $page_info['dep_str'] = $dep_info_str;
            $page_info['page'] = $result->render();
            $page_info['data'] = $result;
            $this->assign('page_info',$page_info);
            return $this->fetch();
        }
    }

    //显示未结案表单的信息
    public function edit(){
        $id = input('id');
        //取出当前的未结案的信息
        $data = Db::name('sys_cost')->where('id',$id)->find();

        //取出当前的部门名称
        $dep_all = config('dep_info');
        $dep_all = change_filed($dep_all,'parent_id','pid');
        $dep_all = _reSort($dep_all);
        $dep_all_str = '';
        foreach($dep_all as $k => $v){
            if($v['id'] == $data['dep_id']){
                $select_dep = 'selected="selected"';
            }else{
                $select_dep = '';
            }
            $dep_all_str .= '<option value="'.$v['id'].'" '.$select_dep.'>'.str_repeat('&nbsp;', 6*$v['level']).$v['en_name'].'</option>';
        }

        //取出当前的部门下的员工
        $user_all = Db::name('sys_user')->where('dep_id',$data['dep_id'])->select();
        $user_all_str = '';
        foreach($user_all as $k1 => $v1){
            if($v1['id'] == $data['user_id']){
                $select_user = 'selected="selected"';
            }else{
                $select_user = '';
            }
            $user_all_str .= '<option value="'.$v1['id'].'" '.$select_user.' user_job_name="'.$v1['user_gh'].'">'.$v1['nickname'].'</option>';
        }

        //取出当前下的费用类型名称
        $cost_type = Db::name('sys_cost_type')->select();
        $cost_type_str =  '';
        foreach($cost_type as $k2 => $v2){
            if($v2['id'] == $data['free_type_select']){
                $select_type = 'selected="selected"';
            }else{
                $select_type = '';
            }
            $cost_type_str .= '<option value="'.$v2['id'].'" '.$select_type.'>'.$v2['free_type_select'].'</option>';
        }
        $page_info['cost_type'] = $cost_type_str;
        $page_info['user'] = $user_all_str;
        $page_info['dep'] = $dep_all_str;
        $page_info['data'] = $data;
        $this->assign('page_info',$page_info);
        return $this->fetch();
    }

    //修改信息及确认报销结案
    public function editcost(){
        $data = $_POST;
        //数据的过滤
        $edit_data = array();
        foreach($data as $k => $v){
            $edit_data[$k] = trim($v);
        }

        if(isset($edit_data['is_expend'])){
            $edit_data['is_expend'] = 1;

            //数据的校验
            $Apply = new \app\sys\model\Cost();
            $Apply->check_editData($edit_data);

            Db::name('sys_cost')->where('id',$edit_data['id'])->update($edit_data);
            echo setServerBackJson(1,"报销结案成功",1);
        }else{
            //数据的校验
            $Apply = new \app\sys\model\Cost();
            $Apply->check_insertData($edit_data);

            Db::name('sys_cost')->where('id',$edit_data['id'])->update($edit_data);
            echo setServerBackJson(1,"修改成功",1);
        }



    }

    //批量结案
    public function batchclose(){
        $data = $_POST;
        //找出当前所有的未结案的数据
        $cost_user_data = Db::name('sys_cost')->where('is_expend',0)->field('user_id,cost_amout')->select();
        foreach($cost_user_data as $val){
            $user_id = $val['user_id'];
            $email = get_cache_data('user_info',$user_id,'email');
            $title = '报销发放总金额通知';
            $message = '本月报销请款，总额为：'.$val['cost_amout'].'元，已作业完成。将于三个工作日内完成支付，请查收.';
            send_email($email,$title,$message);
        }
        //设置报销数据
        Db::name('sys_cost')->where('is_expend',0)->setField('is_expend',1);
        echo setServerBackJson(1,"批量结案成功",1);exit;

//        if($vou_data_2 == '' &&  $vou_data_1 == ''){
//            echo setServerBackJson(0,"请选择对应的凭证日期");exit;
//        }
//        if($vou_data_2 == ''){
//            //找出对应的员工的id
//            $cost_user_data = Db::name('sys_cost')->field('user_id,cost_amout')->where('is_expend',0)->where('vou_data','EGT',$vou_data_1)->select();
//            Db::name('sys_cost')->where('is_expend',0)->where('vou_data','EGT',$vou_data_1)->setField('is_expend','1');
//            //循环操作发邮件
//            foreach($cost_user_data as $val){
//                $user_id = $val['user_id'];
//                $email = get_cache_data('user_info',$user_id,'email');
//                $title = '报销发放总金额通知';
//                $message = '本月报销请款，总额为：'.$val['cost_amout'].'元，已作业完成。将于三个工作日内完成支付，请查收.';
//                send_email($email,$title,$message);
//            }
//
//            echo setServerBackJson(1,"批量结案成功",1);exit;
//        }
//        $cost_data = Db::name('sys_cost')->field('user_id,cost_amout')->where('is_expend',0)->where('vou_data','EGT',$vou_data_1)->where('vou_data','ELT',$vou_data_2)->select();
//        Db::name('sys_cost')->where('is_expend',0)->where('vou_data','EGT',$vou_data_1)->where('vou_data','ELT',$vou_data_2)->setField('is_expend','1');
//        //循环操作发邮件
//        foreach($cost_data as $val1){
//            $email1 = get_cache_data('user_info',$val1['user_id'],'email');
//            $title1 = '报销发放总金额通知';
//            $message1 = '本月报销请款，总额为：'.$val1['cost_amout'].'元，已作业完成。将于三个工作日内完成支付，请查收.';
//            send_email($email1,$title1,$message1);
//        }
//
//        echo setServerBackJson(1,"批量结案成功",1);exit;
    }

    //显示费用报销的类型
    public function addCostType(){
        return $this->fetch();
    }

    //添加费用报销功能
    public function addtype(){
        $free_type_select = trim($_POST['free_type_select']);
        if($free_type_select == ''){
            echo setServerBackJson(0,"费用报销名称不能为空");exit;
        }
        $insert['free_type_select'] = $free_type_select;
        //添加数据
        $result = Db::name('sys_cost_type')->insert($insert);
        if($result){
            echo setServerBackJson(1,"添加数据成功");exit;
        }else{
            echo setServerBackJson(0,"添加数据失败");exit;
        }
    }

    public function ajax_page(){
         $page = $_POST['page'];
         $result = Db::name('sys_cost')->where('is_expend',1)->limit($page.','.'2')->select();


         $page_str = '';
         foreach($result as $k => $v){
             $page_str .= '<tr>
                             <td>'.$v['id'].'</td>
                             <td>'.$v['vou_num'].'</td>
                             <td>'.$v['vou_data'].'</td>
                             <td>'.$v['dep_num'].' </td>
                             <td>'.get_cache_data('dep_info',$v['dep_id'],"en_name").'</td>
                             <td>'.$v['user_job_num'].'</td>
                             <td>'.get_cache_data('user_info',$v['user_id'],'nickname').'</td>
                             <td>'.$v['type_num'].' </td>
                             <td> '.get_cost_type($v['free_type_select']).'</td>
                             <td>'.$v['cost_amout'].'</td>
                             <td>'.$v['remark'].' </td>
                             <td>'.$v['create_time'].'</td> </tr>';
         }
        $page  = $page_str;
        return $page;
    }









}