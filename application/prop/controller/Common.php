<?php
namespace app\prop\controller;
use think\Controller;
use think\Db;
use think\Request;
use app\index\controller\Admin;

class Common extends Admin{

    public function index(){
        $reg_prop_num = trim(input('get.reg_prop_num'));
        $reg_prop_name = trim(input('get.reg_prop_name'));
        $prop_user = trim(input('get.prop_user'));
        if($reg_prop_num || $prop_user || $reg_prop_name){
            $map['reg_prop_num'] = ['like','%'.trim($reg_prop_num).'%'];
            $map['reg_prop_name'] = ['like','%'.trim($reg_prop_name).'%'];
            $map['prop_user'] = ['like','%'.trim($prop_user).'%'];
            $allDep = Db::name('prop')->where($map)->where('prop_status',4)->whereOr('prop_status',2)->paginate(5,false,[
                'query' => Request::instance()->param(),
            ]);

            $page = $allDep->render();
            $page_info['alldep'] = $allDep;
            $this->assign('page',$page);
            $this->assign('page_info',$page_info);
            $this->assign("reg_prop_num",$reg_prop_num);
            $this->assign("reg_prop_name",$reg_prop_name);
            $this->assign('prop_user',$prop_user);
            return $this->fetch();
        }else{
        //取出公司和部门的公共财产
        $allDep = Db::name('prop')->paginate(5,false,[
            'query' => Request::instance()->param(),
        ]);
        $page = $allDep->render();
        $page_info['alldep'] = $allDep;
        $this->assign('page',$page);
        $this->assign('page_info',$page_info);
        return $this->fetch();
        }
    }


    public function receive(){
        $id = input('id');
        $data = Db::name('prop_accept')->where('id',$id)->find();
        $page_info['data'] = $data;
        $this->assign('page_info',$page_info);
        return $this->fetch();
    }

    public function receiveprop(){
        $id = input('id');
        $login_id = session('login_id');
        $name = Db::name('sys_user')->where('id',$login_id)->value('nickname');
        $update['receive_name'] = $name;
        $update['receive_time'] = date('Y-m-d',time());
        $update['dep_sign'] = $name;
        $update['accept_process'] = 2;
        Db::name('prop_accept')->where('id',$id)->update($update);
        //设置采购单流程
        $pur_id = Db::name('prop_accept')->where('id',$id)->value('pur_id');
        $apply_id = Db::name('prop_pur')->where('id',$pur_id)->value('apply_id');
        Db::name('prop_apply')->where('id',$apply_id)->setField('prop_process',5);
        echo setServerBackJson(1,"领用成功");

    }

    public function comlist(){
        //取出当前dep_id
        $id = session('login_id');

        $dep_id = Db::name('sys_user')->where('id',$id)->value('dep_id');

//        $data = Db::name('prop_accept')->where('accept_process',0)->where('dep_id',$dep_id)->where('type',2)->whereOr('type',3)->paginate(5,false,[
//            'query' => Request::instance()->param(),
//        ]);
//        var_dump(object_change_array($data)['data']);die;
        $data = Db::name('prop_accept')->alias('a')->join('prop_pur b','a.pur_id=b.id')->field('a.*,b.user_name')->where('accept_process',0)->where('dep_id',$dep_id)->where('type',2)->whereor('type',3)->paginate(5,false,[
            'query' => Request::instance()->param(),
        ]);

        $page_info['page'] = $data->render();
        $page_info['data'] = object_change_array($data)['data'];
        $this->assign('page_info',$page_info);
        return $this->fetch();
    }

    //it主管审核列表
    public function checkpur(){
          $pur_name = trim(input('get.pur_name'));
          $manage_id = input('get.manage_id');
          $user_name = trim(input('get.user_name'));

          if($pur_name != '' || $manage_id != ''|| $user_name != ''){
              if($manage_id == ''){
                  $map['pur_name'] = ['like','%'.$pur_name.'%'];
                  $map['user_name'] = ['like','%'.$user_name.'%'];
              }else{
                  $map['pur_name'] = ['like','%'.$pur_name.'%'];
                  $map['user_name'] = ['like','%'.$user_name.'%'];
                  $map['manage_id'] = ['eq',$manage_id];
              }

              $data = Db::name('prop_pur')->alias('a')->join('sys_user b','a.accept_use_id=b.id')->field('a.*,b.nickname')->where($map)->where('pur_process',0)->paginate(5,false,[
                  'query' => Request::instance()->param(),
              ]);
              $page_info['page'] = $data->render();
              $page_info['data'] = object_change_array($data)['data'];
              //取出所有部门信息
              $dep_all = Db::name('sys_dep')->field('id,en_name,pid as parent_id')->select();
              $dep_all = _reSort($dep_all);
              $dep_all_str = '';
              foreach($dep_all as $k => $v){
                  if($v['id'] == $manage_id){
                      $select = 'selected="selected"';
                  }else{
                      $select = '';
                  }
                  $dep_all_str .= '<option value="'.$v['id'].'" '.$select.'>'.str_repeat('&nbsp;', 6*$v['level']).$v['en_name'].'</option>';
              }
              $page_info['dep_info'] = $dep_all_str;
              $page_info['pur_name'] = $pur_name;
              $page_info['user_name'] = $user_name;
              $this->assign('page_info',$page_info);
              return $this->fetch();
          }else{
              //取出所有的it主管审核的记录
              $data = Db::name('prop_pur')->alias('a')->join('sys_user b','a.accept_use_id=b.id')->field('a.*,b.nickname')->where('pur_process',0)->paginate(5,false,[
                  'query' => Request::instance()->param(),
              ]);
              $page_info['page'] = $data->render();
              $page_info['data'] = object_change_array($data)['data'];
              //取出所有部门信息
              $dep_all = Db::name('sys_dep')->field('id,en_name,pid as parent_id')->select();
              $dep_all = _reSort($dep_all);
              $dep_all_str = '';
              foreach($dep_all as $k => $v){
                  $dep_all_str .= '<option value="'.$v['id'].'">'.str_repeat('&nbsp;', 6*$v['level']).$v['en_name'].'</option>';
              }
              $page_info['dep_info'] = $dep_all_str;
              $this->assign('page_info',$page_info);
              return $this->fetch();
          }
    }

    //展示it主管审核信息
    public function purcheck(){
        $id = input('id');
        //取出当前采购单的公共信息
        $common_data = Db::name('prop_pur')->where('id',$id)->find();
        $id_str = $common_data['model_id_str'];
        $num = count(explode(',',$id_str));
        $page_info['common'] = $common_data;
        //取出it部门人员
        $it_dep_id = config('IT_DEP_ID');
        $id_arr = Db::name('sys_user')->field('id,nickname')->where('dep_id',$it_dep_id)->select();
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
        $page_info['brand'] = $brand_str;
        $page_info['model'] = $model_str;
        $this->assign('page_info',$page_info);
        return $this->fetch();
    }

    public function checkaccept(){
        $data = $_POST;
        if(isset($data['it_agree'])){
//            $data['it_agree'] = 0;
            //取出当前it部门的id
            $it_dep_id = config('IT_DEP_ID');
//            $it_manage_id = Db::name('sys_dep')->where('id',$it_dep_id)->value('manage_user_id');
            $it_manage_id = get_cache_data('dep_info',$it_dep_id,'manage_user_id');
            $data['it_id'] = $it_manage_id;
            $insertData = array();
            foreach($data as $k => $v){
                $insertData[$k] = trim($v);
            }
            $insertData['pur_process'] = 1;
            //设置采购单信息
            Db::name('prop_pur')->where('id',$insertData['id'])->update($insertData);
            //设置log记录
            $log_info = array();
            $log_info['pur_id'] = $insertData['id'];
            $log_info['user_id'] = session('login_id');
            $log_info['create_user'] = $_SESSION['think']['user_auth']['nickname'];
            $log_info['create_time'] = date("Y-m-d H:i:s",time());
            $log_info['log_info'] = $log_info['create_time'].'IT部门主管'.$log_info['create_user']."不同意采购";
            $log_info['log_type'] = 2;
            Db::name('prop_pur_log')->insert($log_info);
            echo setServerBackJson(1,"审核成功");exit;
        }else{
            //取出当前it部门的id
            $it_dep_id = config('IT_DEP_ID');
            $it_manage_id = Db::name('sys_dep')->where('id',$it_dep_id)->value('manage_user_id');
            $data['it_id'] = $it_manage_id;
            $insertData = array();
            foreach($data as $k => $v){
                $insertData[$k] = trim($v);
            }
            //设置采购单的状态
            $insertData['it_agree'] = 1;
            $insertData['pur_process'] = 2;
            Db::name('prop_pur')->where('id',$insertData['id'])->update($insertData);
            //设置log记录
            $log_info = array();
            $log_info['pur_id'] = $insertData['id'];
            $log_info['user_id'] = session('login_id');
            $log_info['create_user'] = $_SESSION['think']['user_auth']['nickname'];
            $log_info['create_time'] = date("Y-m-d H:i:s",time());
            $log_info['log_info'] = $log_info['create_time'].'IT部门主管'.$log_info['create_user']."同意采购";
            $log_info['log_type'] = 1;
            Db::name('prop_pur_log')->insert($log_info);
            echo setServerBackJson(1,"审核成功");exit;
        }

    }

    public function sign(){

        return $this->fetch();
    }

    public function depsignlist(){
        //取出当前的登录的id
        $login_id = session('login_id');
        //取出统购长批示完以及申请部门审核后的采购单
        $pur_data = Db::name('prop_pur')->alias('a')->join('prop_apply b','a.apply_id=b.id')->field('a.*,b.require_time,b.apply_reason,b.type')
            ->where('sign_id',$login_id)->where('is_pur',0)->where('pur_process',4)->whereOr('pur_process',6)->paginate(5,false,[
            'query' => Request::instance()->param(),
        ]);

        $page_info['page'] = $pur_data->render();
        $page_info['data'] = $pur_data;
        $this->assign('page_info',$page_info);
        return $this->fetch();
    }

//    public function pursignlist(){
//        //取出当前的登录的id
//        $login_id = session('login_id');
//        //取出统购长批示完以及申请部门审核后的采购单
//        $pur_data = Db::name('prop_pur')->alias('a')->join('prop_apply b','a.apply_id=b.id')->field('a.*,b.require_time,b.apply_reason,b.type')
//            ->where('sign_id',$login_id)->where('is_pur',1)->where('pur_process',6)->paginate(5,false,[
//                'query' => Request::instance()->param(),
//            ]);
//
//        $page_info['page'] = $pur_data->render();
//        $page_info['data'] = $pur_data;
//        $this->assign('page_info',$page_info);
//        return $this->fetch();
//
//    }

    //添加签呈

    public function addsign(){
        $id = input('id');
        //取出当前采购单的公共信息
        $common_data = Db::name('prop_pur')->where('id',$id)->find();
        $id_str = $common_data['model_id_str'];
        $num = count(explode(',',$id_str));
        $page_info['common'] = $common_data;
        //取出it部门人员
        $it_dep_id = config('IT_DEP_ID');
        $id_arr = Db::name('sys_user')->field('id,nickname')->where('dep_id',$it_dep_id)->select();
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
            $model_str .= ' <td><input disabled="disabled"  type="text" name="model[]" value="'.$v1['model'].'"></td>
                            <td><input disabled="disabled" type="text" name="bar_price_1[]" value="'.$v1['bar_price_1'].'"></td>
                            <td><input disabled="disabled" type="text" name="offer_price_1[]" value="'.$v1['offer_price_1'].'"></td>
                            <td><input disabled="disabled" type="text" name="offer_price_2[]" value="'.$v1['offer_price_2'].'"></td>
                            <td><input disabled="disabled" type="text" name="bar_price_2[]" value="'.$v1['bar_price_2'].'"></td>
                            <td><input disabled="disabled" type="text" name="offer_price_3[]" value="'.$v1['offer_price_3'].'"></td>
                            <td><input disabled="disabled" type="text" name="bar_price_3[]" value="'.$v1['bar_price_3'].'"></td>';
            $model_str .='</tr>';
        }

        // 取出董事长的信息
        $boss_dep_id = config('BOSS_DEP_ID');
        $boss_data = Db::name("sys_dep")->where('id',$boss_dep_id)->find();
        $boss_name = $boss_data['zh_name'];
        $boss_id = $boss_data['manage_user_id'];

        //取出当前申请资产的所属上级部门
        $sign_id = $common_data['sign_id'];
        $manage_dep_id = Db::name('sys_dep')->where('manage_user_id',$sign_id)->value('pid');
        //二级主管信息
        $level_1 = Db::name('sys_dep')->where('id',$manage_dep_id)->find();
        $level_1_manage_id = $level_1['manage_user_id'];
        $level_1_name = $level_1['zh_name'];
        $level_1_parent_id = $level_1['pid'];

        //三级主管信息
        if($level_1_parent_id == 1){
            unset($level_1_parent_id);
            $sign_str = '<option value="'.$level_1_manage_id.'">'.$level_1_name.'</option>
                      <option value="'.$level_1_manage_id.','.$boss_id.'">'.$level_1_name.'->'.$boss_name.'</option>';
            $page_info['sign_str'] = $sign_str;
            $page_info['brand'] = $brand_str;
            $page_info['model'] = $model_str;
            $this->assign('page_info',$page_info);
            return $this->fetch();

        }else{
            $level_2 = Db::name('sys_dep')->where('id',$level_1_parent_id)->find();
            $level_2_manage_id = $level_2['manage_user_id'];
            $level_2_name = $level_2['zh_name'];
            $sign_str = '<option value="'.$level_1_manage_id.'">'.$level_1_name.'</option>
                      <option value="'.$level_1_manage_id.','.$level_2_manage_id.'">'.$level_1_name.'->'.$level_2_name.'</option>
                      <option value="'.$level_1_manage_id.','.$level_2_manage_id.','.$boss_id.'">'.$level_1_name.'->'.$level_2_name.'->'.$boss_name.'</option>';
            $page_info['sign_str'] = $sign_str;
            $page_info['brand'] = $brand_str;
            $page_info['model'] = $model_str;
            $this->assign('page_info',$page_info);
            return $this->fetch();
        }


    }

    //添加签呈相关信息
    public function addsigndata(){
        $data = $_POST;
        $pur_id = $data['id'];
        //取出当前一级主管的id
        $sign_id = Db::name('prop_pur')->where('id',$pur_id)->value('sign_id');

        $sign_pro = $data['sign_pro'];
        $sign_arr = explode(',',$sign_pro);
        $num = count($sign_arr);

        $insert = array();
        if($num == '1'){
            $insert['level1_manage_id'] = $sign_id;
            $insert['level2_manage_id'] = $sign_arr[0];
            $insert['pur_id'] = $data['id'];
            $insert['purport'] = trim($data['purport']);
            $insert['amout'] = trim($data['amout']);
        }else if($num == '2'){
            $insert['level1_manage_id'] = $sign_id;
            $insert['level2_manage_id'] = $sign_arr[0];
            $insert['level3_manage_id'] = $sign_arr[1];
            $insert['pur_id'] = $data['id'];
            $insert['purport'] = trim($data['purport']);
            $insert['amout'] = trim($data['amout']);
        }else if($num == '3'){
            $insert['level1_manage_id'] = $sign_id;
            $insert['level2_manage_id'] = $sign_arr[0];
            $insert['level3_manage_id'] = $sign_arr[1];
            $insert['level4_manage_id'] = $sign_arr[2];
            $insert['pur_id'] = $data['id'];
            $insert['purport'] = trim($data['purport']);
            $insert['amout'] = trim($data['amout']);

        }
        var_dump($insert);die;

    }






}
