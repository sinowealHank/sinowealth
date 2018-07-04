<?php
namespace app\prop\controller;
use think\Db;
use think\Session;
use think\Request;
use app\index\controller\Admin;

class Apply extends Admin{
    public function index(){
        $reg_prop_num = trim(input('get.reg_prop_num'));
        $reg_prop_name = trim(input('get.reg_prop_name'));
        $product_model = trim(input('get.product_model'));
        $page_info = array();
        //取出当前用户的用户名
        $login_id = Session::get('login_id');

        if($reg_prop_num || $reg_prop_name || $product_model){
            $map['reg_prop_num'] = ['like','%'.trim($reg_prop_num).'%'];
            $map['reg_prop_name'] = ['like','%'.trim($reg_prop_name).'%'];
            $map['product_model'] = ['like','%'.trim($product_model).'%'];
            $personal_prop = Db::name('prop')->where($map)->where('user_id',$login_id)->paginate(10,false,[
                'query' => Request::instance()->param()]);
            $page_info['page'] = $personal_prop->render();
            $page_info['reg_prop_num'] = $reg_prop_num;
            $page_info['reg_prop_name'] = $reg_prop_name;
            $page_info['product_model'] = $product_model;
            $page_info['per_prop'] = $personal_prop;
            $this->assign('page_info',$page_info);
            return $this->fetch();
        }else{

            //取出资产列表的个人所属资产
            $personal_prop = Db::name('prop')->where('user_id',$login_id)->paginate(10,false,[
                'query' => Request::instance()->param()]);
            $page_info['page'] = $personal_prop->render();

            $page_info['per_prop'] = $personal_prop;
            $this->assign('page_info',$page_info);
            return $this->fetch();
        }

    }

    //展示申请表单的页面
    public function add(){
        $id = session('login_id');
//        $dep_id = Db::name('sys_user')->where('id',$id)->value('dep_id');
        $dep_id = get_cache_data('user_info',$id,'dep_id');
        //取出所有部门
//        $dep_all = Db::name('sys_dep')->field('id,pid as parent_id,zh_name')->select();
        $dep_all = change_filed(config('dep_info'),'parent_id','pid');
        $dep_all = _reSort($dep_all);
        $dep_str = '';
        foreach($dep_all as $k => $v){
            if($dep_id == $v['id']){
                $selected = 'selected="selected"';
            }else{
                $selected = '';
            }
            $dep_str .= '<option value="'.$v['id'].'"'.$selected.'>'.str_repeat('&nbsp;', 6*$v['level']).$v['zh_name'].'</option>';
        }
        $page_info['dep'] = $dep_str;
        $this->assign('page_info',$page_info);
        return $this->fetch();
    }

    public function applyprop(){

        $data = $_POST;
        //对数据的过滤
        $insert_data = array();
        foreach($data as $k => $v){
            $insert_data[$k] = trim($v);
        }
        if(isset($insert_data['user_id'])){
//             var_dump(get_cache_data('user_info',$insert_data['user_id'],'nickname'));die;
            //取出当前使用人的用户名
//            $insert_data['use_prop_name'] = Db::name('sys_user')->where('id',$insert_data['user_id'])->value('nickname');
            $insert_data['use_prop_name'] = get_cache_data('user_info',$insert_data['user_id'],'nickname');
        }
        //申请人的id
        $insert_data['apply_use_id'] = session('login_id');
        //对数据的检测
        $Apply = new \app\prop\model\PropApply();
        $Apply->insertdata($insert_data);

        //是否公用
        if(isset($insert_data['is_common'])){
            $insert_data['is_common'] = 1;
        }
        //是否新人
        if(isset($insert_data['is_new'])){
            $insert_data['is_new'] = 1;
        }

        //取出负责部门主管的id
//        $insert_data['use_manage_dep_id'] = Db::name('sys_dep')->where('id',$insert_data['use_dep_id'])->value('manage_user_id');
        $insert_data['use_manage_dep_id'] = get_cache_data('dep_info',$insert_data['use_dep_id'],'manage_user_id');

        //添加申请资产
        $result = Db::name('prop_apply')->insert($insert_data);
        if($result){
            echo setServerBackJson(1,"添加成功");exit;
        }else{
            echo setServerBackJson(0,"添加失败");exit;
        }

    }

   public function applylist(){
       $id = session('login_id');

       //取出申请表单中的记录
       $apply_data = Db::name('prop_apply')->alias('a')->join('sys_user b','a.apply_use_id=b.id')->field('a.*,b.nickname')->where('a.apply_use_id',$id)->paginate(5,false,[
           'query' => Request::instance()->param(),
       ]);

       $page_info['page'] = $apply_data->render();
       $page_info['data'] = $apply_data;
       $this->assign('page_info',$page_info);
       return $this->fetch();
   }

    public function getuser(){
        $dep_id = input('post.id');
        //取出部门下所有的人员
        $user_data = Db::name('sys_user')->field('id,nickname')->where('dep_id',$dep_id)->select();
        $use_str = '';
        foreach($user_data as $k => $v){
            $use_str .= '<option value="'.$v['id'].'">'.$v['nickname'].'</option>
             <span style="color:red;top: -5px;position:relative;">*</span>';
        }
        return $use_str;
    }
    //提交辞呈
    public function sign(){
        $id = input('id');
        //取出当前采购单的公共信息
        $common_data = Db::name('prop_pur')->where('id',$id)->find();

        $id_str = $common_data['model_id_str'];
        $num = count(explode(',',$id_str));
        $page_info['common'] = $common_data;
        //取出it部门人员
        $id_dep_id = config('IT_DEP_ID');
        $id_arr = Db::name('sys_user')->field('id,nickname')->where('dep_id',$id_dep_id)->select();
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

//    public function addsign(){
//        $data = input('post.');
//        $insertData = array();
//        foreach($data as $k => $v){
//            $insertData[$k] = $v;
//        }
//        if($insertData['purport'] == ''){
//            echo setServerBackJson(0,"主旨不能为空");exit;
//        }
//        if($insertData['amount'] == ''){
//            echo setServerBackJson(0,"金额不能为空");exit;
//        }
//        $insertData['pur_process'] = 7;
//        //申请人添加签呈
//        Db::name('prop_pur')->where('id',$insertData['id'])->update($insertData);
//        echo setServerBackJson(1,"已添加辞呈");exit;
//
//
//    }
    //添加签呈
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
        //取出当前人的签呈id
        $signId = $common_data['sign_id'];
        $local_sign_dep_id = Db::name('sys_user')->where('id',$signId)->value('dep_id');
        $sign_manage_id = Db::name('sys_dep')->where('id',$local_sign_dep_id)->value('manage_user_id');




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
            $it_manage_id = Db::name('sys_dep')->where('id',$id_dep)->value('manage_user_id');
            $common_data['it_id'] = $it_manage_id;
            //取出申请部门的主管id
            $dep_id = trim($data['apply_manage_id']);
            $dep_manage_id = Db::name('sys_dep')->where('id',$dep_id)->value('manage_user_id');
            $common_data['dep_manage_id'] = $dep_manage_id;
            //统购长的id
            $purchase_id = config('TONG_GOU_MANAGE_ID');
            $purchase_manage_id = Db::name('sys_dep')->where('id',$purchase_id)->value('manage_user_id');
            $common_data['purchase_id'] = $purchase_manage_id;
        }else{
            $common_data['is_pur'] = 0;
            //it主管的id
            $id_dep = config('IT_DEP_ID');
            $it_manage_id = Db::name('sys_dep')->where('id',$id_dep)->value('manage_user_id');
            $common_data['it_id'] = $it_manage_id;
            //取出申请部门的主管id
            $dep_id = trim($data['apply_manage_id']);
            $dep_manage_id = Db::name('sys_dep')->where('id',$dep_id)->value('manage_user_id');
            $common_data['dep_manage_id'] = $dep_manage_id;
        }

        //插入数据库中
        $result = Db::name('prop_pur')->insert($common_data);
        //设置审批流程
        if($result){
            echo setServerBackJson(1,"添加验收单成功");exit;
        }else{
            echo setServerBackJson(0,"添加失败");exit;
        }

        //取出共同的信息

    }
}