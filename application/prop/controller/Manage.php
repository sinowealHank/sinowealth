<?php
namespace app\prop\controller;
use app\common\validate\Config;
use think\Controller;
use think\Db;
use think\Model;
use think\Session;
use think\Request;
use app\index\controller\Admin;

class Manage extends Admin{
    public function index(){


        $page_info = array();
//        //取出验收表单的记录
//        $sql = 'select a.*,b.nickname,c.zh_name from sw_prop_accept as a left join sw_sys_user as b on a.accept_user_id = b.id left join sw_sys_dep as c on a.use_dep_id = c.id ';
//        $accept_data = Db::query($sql);
//        $page_info['data'] = $accept_data;

        //取出申请资产的列表
        $data = Db::name('prop_apply')->alias('a')->field('a.*,b.zh_name')->join('sys_dep b','a.use_dep_id=b.id')->join('sys_user c','a.apply_use_id=c.id')
            ->field('a.*,b.zh_name,c.nickname')->paginate(10,false,[
            'query' => Request::instance()->param(),
        ]);
        $page_info['page'] = $data->render();
        $data = object_change_array($data)['data'];
        $page_info['apply'] = $data;
        $this->assign('page_info',$page_info);
        return $this->fetch();
    }

    public function acceptForm(){
        //取出所有的部门
        $dep = Db::name('sys_dep')->field('id,pid as parent_id,zh_name')->select();
        $dep = _reSort($dep);
        $dep_str = '';
        foreach($dep as $k => $v){
            $dep_str .= '<option value="'.$v['id'].'">'.str_repeat('&nbsp;', 6*$v['level']).$v['zh_name'].'</option>';
        }
        $page_info['dep'] = $dep_str;
        $this->assign('page_info',$page_info);
        return $this->fetch();
    }

    public function getdepdata(){
        $id = trim($_POST['id']);
        //取出当前部门下的所有人
        $user_data = Db::name('sys_user')->where('dep_id',$id)->field('id,nickname')->select();
        $user_str = '';
        foreach($user_data as $k => $v){
            $user_str .= '<option value="'.$v['id'].'">'.$v['nickname'].'</option>';
        }

        return $user_str;
    }

    public function addaccept(){

        $data = $_POST;
         //进行数据的校验
        $prop = new \app\prop\model\PropAccept();
        $prop->check_accept_data($data);

        //数据的过滤
        $insertdata = array();
        foreach($data as $k => $v){
            $insertdata[$k] = trim($v);
        }

        //插入数据库中进行处理
        $insertdata['accept_process'] = 1;
        //添加到记录到验收表中
        $result = Db::name('prop_accept')->insert($insertdata);
        if($result){
            echo setServerBackJson(1,"验收表单添加成功");exit;
        }else{
            echo setServerBackJson(0,"验收表单添加失败");exit;
        }

    }

    //确认表单信息，以及修改表单信息
    public function editaccept(){
        $id = input('id');
        //取出当前验收表单的信息
        $result = Db::name('prop_accept')->where('id',$id)->find();
        //取出所在部门的信息
        $dep_id = $result['dep_id'];
        $dep_data = Db::name('sys_dep')->field('id,pid as parent_id,zh_name')->select();
        $dep_data = _reSort($dep_data);
        $dep_str = '';
        foreach($dep_data as $k => $v){
            if($dep_id == $v['id']){
                $selected = 'selected="selected"';
            }else{
                $selected = '';
            }
            $dep_str .= '<option value="'.$v['id'].'" '.$selected.'>'.str_repeat('&nbsp;', 6*$v['level']).$v['zh_name'].'</option>';
        }
        //取出验收人
        $user = Db::name('sys_user')->field('id,nickname')->where('dep_id',$dep_id)->select();
        $user_str = '';
        foreach($user as $k => $v){
            if($result['accept_user_id'] == $v['id']){
                $selected1 = 'selected="selected"';
            }else{
                $selected1 = '';
            }
            $user_str .= '<option value="'.$v['id'].'" '.$selected1.'>'.$v['nickname'].'</option>';
        }
        $page_info['dep'] = $dep_str;
        $page_info['user'] = $user_str;
        $page_info['data'] = $result;
        $this->assign('page_info',$page_info);
        return $this->fetch();

    }

    public function editacceptdata(){
        $data = $_POST;
        //取出当前验收单的信息
        $result = Db::name('prop_accept')->where('id',$data['id'])->find();
        if($result['accept_process'] == 1){
            echo setServerBackJson(0,"已经确认过表单信息无法修改!");exit;
        }
        //校验数据
        $prop = new \app\prop\model\PropAccept();
        $prop->check_accept_data($data);
        //数据过滤
        $editdata = array();
        foreach($data as $k => $v){
            $editdata[$k] = trim($v);
        }
        //数据的修改
        Db::name('prop_accept')->where('id',$editdata['id'])->update($editdata);
        echo setServerBackJson(1,"修改信息成功");exit;

    }

    public function delaccept(){
        $id = input('id');
        Db::name('prop_accept')->where('id',$id)->setField('accept_process',1);
        echo setServerBackJson(1,"确认完成");
    }

    public function addpur(){

        $id = input('id');
        //取出当前使用者名字、使用人id、申请人id、部门id、申请物品、数量
        $result = Db::name('prop_apply')->where('id',$id)->find();
        $user_name = $result['use_prop_name'];
        $user_id = $result['user_id'];
        $apply_use_id = $result['apply_use_id'];
        $dep_id = $result['use_dep_id'];
        $apply_thing = $result['apply_thing'];
        $thing_num = $result['thing_num'];
        //取出部门信息
//        $dep = Db::name('sys_dep')->field('id,pid as parent_id,zh_name')->select();
//        $dep = _reSort($dep);
        $dep = config('dep_info');
        $dep = change_filed($dep,'parent_id','pid');
        $dep = _reSort($dep);
        $str = '';
        foreach($dep as $k => $v){
            $str .= '<option value="'.$v['id'].'" >'.str_repeat('&nbsp;', 6*$v['level']).$v['zh_name'].'</option>';
        }

        //取出it部门的所有人员
        $it_people = Db::name('sys_user')->field('id,nickname')->where('dep_id',16)->select();
        $it_str = '';
        foreach($it_people as $k1 => $v1){
            $it_str .= '<option value="'.$v1['id'].'" >'.$v1['nickname'].'</option>';
        }
        //取出签呈的部门信息
//        $sql = "select a.id,a.pid as parent_id,a.zh_name,b.nickname,b.id as user_id from sw_sys_dep a left join sw_sys_user b on a.manage_user_id=b.id";
//        $dep_all = Db::query($sql);
//        $dep_all = _reSort($dep_all);
//        $dep_all_str = '';
//        foreach($dep_all as $k => $v){
//            $dep_all_str .= '<option value="'.$v['user_id'].'">'.str_repeat('&nbsp;', 6*$v['level']).$v['zh_name'].'---('.$v['nickname'].')'.'</option>';
//        }
//        $page_info['dep_all_str'] = $dep_all_str;




        $page_info['apply_id'] = $id;
        $page_info['apply_thing'] = $apply_thing;
        $page_info['thing_num'] = $thing_num;
        $page_info['dep_id'] = $dep_id;
        $page_info['it'] = $it_str;
        $page_info['user_name'] = $user_name;
        $page_info['user_id'] = $user_id;
        $page_info['apply_use_id'] =$apply_use_id;
        $page_info['dep'] = $str;
        $this->assign('page_info',$page_info);
        return $this->fetch();
    }


    public function addpurform(){

        $login_id = session('login_id');
        $data = $_POST;
//        var_dump($data);die;
        $num = count($data['brand_model']);
        $common_data['pur_advice'] = trim($data['pur_advice']);
//        $common_data['pur_user_manage'] = trim($data['pur_user_manage']);
//        $common_data['cheng'] = trim($data['cheng']);
//        $common_data['instruct'] = trim($data['instruct']);
//        $common_data['purport'] = trim($data['purport']);
        $common_data['supplier_1'] = trim($data['supplier_1']);
        $common_data['supplier_2'] = trim($data['supplier_2']);
        $common_data['supplier_3'] = trim($data['supplier_3']);
        $common_data['user_name'] = trim($data['user_name']);
        $common_data['user_id'] =  trim($data['user_id']);
        $common_data['apply_use_id'] = trim($data['apply_use_id']);
        $common_data['accept_use_id'] = trim($data['accept_use_id']);
        $common_data['manage_id'] = trim($data['manage_id']);
        $common_data['pur_name'] = trim($data['pur_name']);
        $common_data['apply_id'] = trim($data['apply_id']);
        $common_data['sign_id'] = $data['sign_id'];


//        var_dump($common_data);die;
        //设置申请列表的采购流程
        Db::name('prop_apply')->where('id',$common_data['apply_id'])->setField('prop_process',1);
        if($common_data['accept_use_id'] == $login_id){
            echo setServerBackJson(0,"采购人与验收人不能相同，请重新选择");exit;
        }
        $insert = array();
        $id_arr = array();
        //循环构建数据
        for($i=0;$i<$num;$i++){
            //取出型号的相应信息
            $insert[$i]['brand_model'] = trim($data['brand_model'][$i]);
            $insert[$i]['basic_info'] = trim($data['basic_info'][$i]);
            $insert[$i]['refer_price'] = trim($data['refer_price'][$i]);
            $insert[$i]['remark'] = trim($data['remark'][$i]);
            //取出供应商的信息
            $insert[$i]['model'] = trim($data['model'][$i]);
            $insert[$i]['offer_price_1'] = trim($data['offer_price_1'][$i]);
            $insert[$i]['bar_price_1'] = trim($data['bar_price_1'][$i]);
            $insert[$i]['offer_price_2'] = trim($data['offer_price_2'][$i]);
            $insert[$i]['bar_price_2'] = trim($data['bar_price_2'][$i]);
            $insert[$i]['offer_price_3'] = trim($data['offer_price_3'][$i]);
            $insert[$i]['bar_price_3'] = trim($data['bar_price_3'][$i]);
            $checkData[$i] = array_merge($insert[$i],$common_data);

            //循环校验数据
            $pur_data = new \app\prop\model\Manage();
            $pur_data->check($checkData[$i]);
            //插入到型号表中
            $id = Db::name('prop_model')->insertGetId($insert[$i]);
            $id_arr[$i] = $id;


        }

        $id_str = implode(',',$id_arr);
        $common_data['model_id_str'] = $id_str;

        if(isset($data['is_pur'])){
            $common_data['is_pur'] = 1;
            //it主管的id
            $id_dep = config('IT_DEP_ID');
//            $it_manage_id = Db::name('sys_dep')->where('id',$id_dep)->value('manage_user_id');
            $it_manage_id = get_cache_data('dep_info',$id_dep,'manage_user_id');
            $common_data['it_id'] = $it_manage_id;
            //取出申请部门的主管id

            $dep_id = trim($data['apply_manage_id']);
//            $dep_manage_id = Db::name('sys_dep')->where('id',$dep_id)->value('manage_user_id');
            $dep_manage_id = get_cache_data('dep_info',$dep_id,'manage_user_id');
            $common_data['dep_manage_id'] = $dep_manage_id;

            //统购长的id
            $purchase_id = config('TONG_GOU_MANAGE_ID');
//            $purchase_manage_id = Db::name('sys_dep')->where('id',$purchase_id)->value('manage_user_id');
            $purchase_manage_id = get_cache_data('dep_info',$purchase_id,'manage_user_id');
            $common_data['purchase_id'] = $purchase_manage_id;
        }else{
            $common_data['is_pur'] = 0;
            //it主管的id
            $id_dep = config('IT_DEP_ID');
//            $it_manage_id = Db::name('sys_dep')->where('id',$id_dep)->value('manage_user_id');
            $it_manage_id = get_cache_data('dep_info',$id_dep,'manage_user_id');
            $common_data['it_id'] = $it_manage_id;
            //取出申请部门的主管id
            $dep_id = $data['apply_manage_id'];
//            $dep_manage_id = Db::name('sys_dep')->where('id',$dep_id)->value('manage_user_id');
            $dep_manage_id  = get_cache_data('dep_info',$dep_id,'manage_user_id');
            $common_data['dep_manage_id'] = $dep_manage_id;
        }

        //插入数据库中
        $result = Db::name('prop_pur')->insertGetId($common_data);

        //添加log信息,获取申请单id,采购id
        $log_info = array();
        $log_info['pur_id'] = $result;
        $log_info['user_id'] = session('login_id');
        $log_info['create_user'] = $_SESSION['think']['user_auth']['nickname'];
        $log_info['create_time'] = date("Y-m-d H:i:s",time());
        $log_info['log_info'] = $log_info['create_time'].'采购人'.$log_info['create_user']."添加了采购单";
        Db::name('prop_pur_log')->insert($log_info);


        //设置审批流程
        if($result){
            echo setServerBackJson(1,"添加采购单成功");exit;
        }else{
            echo setServerBackJson(0,"添加失败");exit;
        }

       //取出共同的信息

    }

    //展示采购单列表是否审核
    public function show(){
        //取出所有的采购单
        $pur_data = Db::name('prop_pur')->alias('a')->join('sys_user b', 'a.apply_use_id=b.id')->field('a.*,b.nickname')->paginate(10,false,[
            'query' => Request::instance()->param(),
        ]);
        $page_info['page'] = $pur_data->render();
        $page_info['data'] = $pur_data;
        $this->assign('page_info',$page_info);
        return $this->fetch();
    }

    public function editpur(){
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
//        $dep_all = Db::name('sys_dep')->field('id,zh_name,pid as parent_id')->select();
//        $dep_all = _reSort($dep_all);
//        $dep_str = '';
//        foreach($dep_all as $k => $v){
//            $dep_str .= '<option value="'.$v['id'].'" >'.str_repeat('&nbsp;', 6*$v['level']).$v['zh_name'].'</option>';
//        }
//        $page_info['dep'] = $dep_str;
//        $page_info['num'] = $num;
        //取出当前选择型号的信息
        $map['id'] = ['in',$id_str];
        $model_data = Db::name('prop_model')->where($map)->select();
        $brand_str = '';
        $model_str = '';
        foreach($model_data as $k1 => $v1){
            $brand_str .= '<tr >';
            $brand_str .= '<td></td>
                            <td><input  type="text" name="brand_model[]" value="'.$v1['brand_model'].'"></td>
                            <td><input  type="text" name="basic_info[]" value="'.$v1['basic_info'].'"></td>
                            <td><input  type="text" name="refer_price[]" value="'.$v1['refer_price'].'"></td>
                            <td><input  type="text" name="remark[]" value="'.$v1['remark'].'"></td>';
            $brand_str .= '</tr>';

            $model_str .= '<tr>';
            $model_str .= ' <td><input type="text" name="model[]" value="'.$v1['model'].'"></td>
                            <td><input type="text" name="bar_price_1[]" value="'.$v1['bar_price_1'].'"></td>
                            <td><input type="text" name="offer_price_1[]" value="'.$v1['offer_price_1'].'"></td>
                            <td><input type="text" name="offer_price_2[]" value="'.$v1['offer_price_2'].'"></td>
                            <td><input type="text" name="bar_price_2[]" value="'.$v1['bar_price_2'].'"></td>
                            <td><input type="text" name="offer_price_3[]" value="'.$v1['offer_price_3'].'"></td>
                            <td><input type="text" name="bar_price_3[]" value="'.$v1['bar_price_3'].'"></td>';
            $model_str .='</tr>';
        }
        $where['id'] = ['in',$id_str];
        $result = Db::name('prop_model')->where($where)->field('brand_model,id')->select();
        $str = '';
        foreach($result as $k => $v){
            $str .= '<option value="'.$v['id'].'">'.$v['brand_model'].'</option>';
        }
        //设置申请部门

//        $dep_data = Db::name('sys_dep')->field('id,pid as parent_id,zh_name')->select();
//        $dep_data = _reSort($dep_data);

        $dep_data = config('dep_info');
        $dep_data = object_change_array($dep_data,'parent_id','pid');
        $dep_data = _reSort($dep_data);
        $dep_id = Db::name('sys_dep')->where('manage_user_id',$common_data['dep_manage_id'])->value('id');
        $dep_data_str = '';
        foreach($dep_data as $k => $v){
            if($dep_id == $v['id']){
                $select = 'selected="selected"';
            }else{
                $select = '';
            }
            $dep_data_str .= '<option value="'.$v['id'].'" '.$select.'>'.str_repeat('&nbsp;', 6*$v['level']).$v['zh_name'].'</option>';
        }

        //设置签呈
        $sql = "select a.id,a.pid as parent_id,a.zh_name,b.nickname,b.id as user_id from sw_sys_dep a left join sw_sys_user b on a.manage_user_id=b.id";
        $dep_all_sign = Db::query($sql);
        $dep_all_sign = _reSort($dep_all_sign);
        $dep_all_sign_str = '';
        foreach($dep_all_sign as $k => $v){
            if($common_data['sign_id'] == $v['user_id']){
                $select_sign = 'selected="selected"';
            }else{
                $select_sign = '';
            }

            $dep_all_sign_str .= '<option value="'.$v['user_id'].'" '.$select_sign.'>'.str_repeat('&nbsp;', 6*$v['level']).$v['zh_name'].'---('.$v['nickname'].')'.'</option>';
        }
        $page_info['dep_all_sign'] = $dep_all_sign_str;
        $page_info['dep_data_str'] = $dep_data_str;
        $page_info['select'] = $str;
        $page_info['brand'] = $brand_str;
        $page_info['model'] = $model_str;
        $this->assign('page_info',$page_info);
        return $this->fetch();
    }

    public function editpurdata(){
        //取出公共的信息
        $data = $_POST;

        $common_data = array();
        if(isset($data['is_pur'])){
            $common_data['is_pur'] = 1;
        }
        $common_data['id'] = $data['id'];
        $common_data['pur_name'] = trim($data['pur_name']);
        $common_data['supplier_1'] = trim($data['supplier_1']);
        $common_data['supplier_2'] = trim($data['supplier_2']);
        $common_data['supplier_3'] = trim($data['supplier_3']);
        $common_data['accept_use_id'] = trim($data['accept_use_id']);
        $common_data['pur_advice'] = trim($data['pur_advice']);
        $common_data['sign_id'] = $data['sign_id'];
         //更新选择申请部门主管的id
//        $manage_user_id = Db::name('sys_dep')->where('id',$data['dep_manage_id'])->value('manage_user_id');
        $manage_user_id = get_cache_data('dep_info',$data['dep_manage_id'],'manage_user_id');
        $common_data['dep_manage_id'] = $manage_user_id;

        //取出公共信息关联的id
        $model_id_str = Db::name('prop_pur')->where('id',$common_data['id'])->value('model_id_str');
        //构建数组
        $insert = array();
        $num = count($data['brand_model']);
        for($i=0;$i<$num;$i++){
            //取出型号的相应信息
            $insert[$i]['brand_model'] = trim($data['brand_model'][$i]);
            $insert[$i]['basic_info'] = trim($data['basic_info'][$i]);
            $insert[$i]['refer_price'] = trim($data['refer_price'][$i]);
            $insert[$i]['remark'] = trim($data['remark'][$i]);
            //取出供应商的信息
            $insert[$i]['model'] = trim($data['model'][$i]);
            $insert[$i]['offer_price_1'] = trim($data['offer_price_1'][$i]);
            $insert[$i]['bar_price_1'] = trim($data['bar_price_1'][$i]);
            $insert[$i]['offer_price_2'] = trim($data['offer_price_2'][$i]);
            $insert[$i]['bar_price_2'] = trim($data['bar_price_2'][$i]);
            $insert[$i]['offer_price_3'] = trim($data['offer_price_3'][$i]);
            $insert[$i]['bar_price_3'] = trim($data['bar_price_3'][$i]);
            if($insert[$i]['brand_model'] == ''){
                echo setServerBackJson(0,"品牌型号不能为空");exit;
            }
            //更新对应的数据
            $map['id'] = ['in',$model_id_str];
            Db::name('prop_model')->where($map)->update($insert[$i]);
        }
        //清空意见
        $common_data['it_agree'] = 1;
        $common_data['dep_agree'] = 1;
        $common_data['purchase_agree'] = 1;
        $common_data['it_dep_advice'] = '';
        $common_data['dep_advice'] = '';
        $common_data['purchase_advice'] = '';
        //更新公共的信息
        $common_data['pur_process'] = 0;
        Db::name('prop_pur')->where('id',$common_data['id'])->update($common_data);
        //设置log记录
        $log_info = array();
        $log_info['pur_id'] = $common_data['id'];
        $log_info['user_id'] = session('login_id');
        $log_info['create_user'] = $_SESSION['think']['user_auth']['nickname'];
        $log_info['create_time'] = date("Y-m-d H:i:s",time());
        $log_info['log_info'] = $log_info['create_time'].$log_info['create_user']."重新编辑采购单信息";
        $log_info['log_type'] = 0;
        Db::name('prop_pur_log')->insert($log_info);

        echo setServerBackJson(1,"修改成功");exit;

    }

    public function get_sign_user(){
        $dep_id = input('post.id');
        //取出当前部门下所有的人员
        $dep_all_user = Db::name('sys_user')->where('dep_id',$dep_id)->select();
        $dep_all_user_str = '';
        foreach($dep_all_user as $k => $v){
            $dep_all_user_str .= '<option value="'.$v['id'].'">'.$v['nickname'].'</option>';
        }
        return $dep_all_user_str;

    }




}