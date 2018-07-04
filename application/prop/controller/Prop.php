<?php
namespace app\prop\controller;
use think\Controller;
use think\Db;
use think\Model;
use app\index\controller\Admin;

class Prop extends Admin{


    public function index(){
        $page_info['title'] = "资产管理总表";
        //相关部门信息
//        $depAll = Db::name('sys_dep')->field('id,zh_name,pid as parent_id')->select();
        $depAll = config('dep_info');
        $depAll = change_filed($depAll,'parent_id','pid');

        $depAll = _reSort($depAll);
        $dep = '';
        foreach( $depAll as $k => $v){
            $dep .= '<option value="'.$v['id'].'" > '
                .str_repeat("&nbsp;",4*$v['level']).$v['zh_name'].'</option>';
        }
        $page_info['user_dep'] = $dep;
        $page_info['dep'] = $dep;
        $this->assign('page_info',$page_info);
        return $this->fetch();
    }



   public function prop_list(){
       $arr = array();
       $offset = 0;
       $limit = 20;
       $order = 'desc';
       if(isset($_POST['order'])){
           $order = $_POST['order'];
       }

       if(isset($_POST['data'])){

            $data = $_POST['data'];
            foreach($data as $k => $v){
                 $insert[$v['name']] = $v['value'];
                 $arr[] = $insert;
            }
            $arr = end($arr);
            $str = '';
            foreach($arr as $val){
                $str .= trim($val);
            }
//            var_dump($arr);die;

           //当所有查询条件为空时
//            if($str == ''){
////                var_dump($_POST);die;
//
////                var_dump($_POST['page']);die;
//                if ($_POST['page'] > 1){
//                    var_dump('1223');
//                    $page = $_POST['page'];
//                    $offset = ($page - 1)*20 ;
//                }
//
//
//                $result = Db::name('prop')->limit('' . $offset . ',' . $limit . '')->order('id '.$order.'.')->select();
//                var_dump('12');die;
//                $dg['rows'] = $result;
//                $total = Db::name('prop')->select();
//                $dg['total'] = count($total);
//                echo json_encode($dg);exit;
//            }

           //获得使用部门的名称
            if(!$arr['use_dep']){
                $use_dep = '';
            }else{
                $use_dep = $arr['use_dep'];
            }
            //获得所在部门的名称
            if($arr['dep'] == ''){
                $dep = '';
            }else{
                  $dep = $arr['dep'];
            }
            //获得负责部门的名称
            if(!$arr['respon_dep']){
                $respon_dep = '';
            }else{
                $respon_dep = $arr['respon_dep'];
            }

            $map['reg_prop_num'] = ['like','%'.trim($arr['reg_prop_num']).'%'];
            $map['reg_prop_name'] = ['like','%'.trim($arr['reg_prop_name']).'%'];
            $map['product_model'] = ['like','%'.trim($arr['product_model']).'%'];
            $map['use_dep'] = ['like','%'.trim($use_dep).'%'];
            if($dep != ''){

                $map['local_dep_id'] = ['eq',$dep];
            }

            if($respon_dep != ''){
                $map['respon_dep_id'] = ['eq',$respon_dep];
            }

            $map['prop_user'] = ['like','%'.trim($arr['prop_user']).'%'];
            $map['propuser'] = ['like','%'.trim($arr['propuser']).'%'];
            if($_POST['page'] > 1){
               $page = $_POST['page'];
               $offset = ($page-1)*20 ;
            }

           if(trim($arr['buy_time']) != ''){
//               var_dump(trim($arr['buy_time']));die;
               $result = Db::name('prop')->where($map)->whereTime('buy_time','>=',trim($arr['buy_time']))->limit('' . $offset . ',' . $limit . '')->select();
               $total = Db::name('prop')->where($map)->select();
               $dg['rows'] = $result;
               $dg['total'] = count($total);
               echo json_encode($dg);exit;
           }else{
               $result = Db::name('prop')->where($map)->limit('' . $offset . ',' . $limit . '')->order('id '.$order.'')->select();
           }
            $total = Db::name('prop')->where($map)->select();
            $dg['rows'] = $result;
            $dg['total'] = count($total);
            echo json_encode($dg);exit;
       }
       if ($_POST['page'] > 1){
                $page = $_POST['page'];
                $offset = ($page - 1)*20 ;
       }
       $result = Db::name('prop')->limit('' . $offset . ',' . $limit . '')->order('id '.$order.'')->select();
       $dg['rows'] = $result;
       $total = Db::name('prop')->select();
       $dg['total'] = count($total);
       echo json_encode($dg);
   }

    //显示添加页面的信息
    public function add(){
        //获取公司部门信息
//        $dep_arr = Db::name('sys_dep')->field('id,en_name,zh_name,pid as parent_id')->select();
        $dep_arr = config('dep_info');
        $dep_arr = change_filed($dep_arr,'parent_id','pid');
        $dep_arr = _reSort($dep_arr);
        $str = '';
        foreach($dep_arr as $k => $v){
            if($v['id'] == config('STOCK_DEP_ID')){
                $str .= '<option value="1" style="color: red;">'.str_repeat("&nbsp;",6*$v['level']).'库存'.'</option>';
            }else{
                $str .= '<option value="'.$v['id'].'" > '.str_repeat("&nbsp;",6*$v['level']).$v['zh_name'].'</option>';
            }

        }
        $page_info['str'] = $str;
//        //获取资产的分类信息
//        $prop_data = Db::name('prop_type')->select();
//        $prop_data = _reSort($prop_data);
//        $prop_type_str = '';
//        foreach($prop_data as $key => $val){
//            $prop_type_str .= '<option value="'.$val['id'].'" > '
//                .str_repeat("&nbsp;",6*$val['level']).$val['prop_type'].'</option>';
//        }
        //获取资产的状态
        $prop_status_str = get_prop_status();
        //获取所有申请人

        $page_info['prop_status_str'] = $prop_status_str;
//        $page_info['prop_type'] = $prop_type_str;
        $this->assign('page_info',$page_info);
        return $this->fetch();
    }

    //添加资产管理
    public function addprop(){
        $data = $_POST;

        //实例化model类，校验数据
        $prop = new \app\prop\model\Prop();
        $prop->insertPropData($data);
        if($data['local_dep_id'] == 0){
            echo setServerBackJson(0,"请选择所在部门");exit;
        }
        if($data['respon_dep_id'] == 1){
            echo setServerBackJson(0,"请选择负责部门");exit;
        }

        //查找对应的所在部门
//        $dep = Db::name('sys_dep')->where('id',$data['local_dep_id'])->field('zh_name')->find();
//        $data['dep'] = $dep['zh_name'];
        $data['dep'] = get_cache_data('dep_info',$data['local_dep_id'],'en_name');
        //查找对应的负责部门
//        $respon_dep = Db::name('sys_dep')->where('id',$data['respon_dep_id'])->field('zh_name')->find();
//        $data['respon_dep'] = $respon_dep['zh_name'];
        $data['respon_dep'] = get_cache_data('dep_info',$data['respon_dep_id'],'en_name');
       //查找对应的申请人、申请人id
//        $apply_name = Db::name('sys_user')->where('id',$data['propuser'])->field('nickname')->find();
        $apply_name = get_cache_data('user_info',$data['propuser'],'nickname');
        $data['apply_id'] = $data['propuser'];
        $data['propuser'] = $apply_name;
//        $data['propuser'] = $apply_name['nickname'];

       //查找是否有对应的使用人
        if(is_numeric($data['prop_user'])){
//            $user_name = Db::name('sys_user')->where('id',$data['prop_user'])->field('nickname')->find();

            $data['user_id'] = $data['prop_user'];
//            $data['prop_user'] = $user_name['nickname'];
            $data['prop_user'] = get_cache_data('user_info',$data['prop_user'],'nickname');
        }
        if(isset($data['is_newbie'])){
            $data['is_newbie'] = 1;
        }

        //是否为公共对象
        if(isset($data['is_common'])){
            $data['is_common'] = 1;
        }


        //对数据的过滤
        $insert_data = array();
        foreach($data as $k => $v){
            $insert_data[$k] = trim($v);
        }

        //插入数据库中
        $result = Db::name('prop')->insert($insert_data);
        if($result){
            echo setServerBackJson(1,"添加资产成功!");exit;
        }else{
            echo setServerBackJson(0,"添加资产失败!");exit;
        }


    }

    public function edit(){

        $id = input('id');
        //取出资产的相关信息
        $prop_data = Db::name('prop')->where('id',$id)->find();
//        var_dump($prop_data);die;

        //取出所在部门的id
//        $dep_id = Db::name('sys_dep')->where('en_name',$prop_data['dep'])->field('id')->find();
//        $dep_id = get_cache_data('dep_info',$prop_data['dep'],'id');
//        $dep_id  = $dep_id['id'];
        $dep_id = $prop_data['local_dep_id'];

        //取出负责部门的id
//        $respon_dep_id = Db::name('sys_dep')->where('en_name',$prop_data['respon_dep'])->field('id')->find();
//        $respon_dep_id = $respon_dep_id['id'];

        $respon_dep_id = $prop_data['respon_dep_id'];

        //如果当前为人，取出对应的部门的所有人信息
        $user_id = $prop_data['user_id'];
        if($user_id){
            //取出使用人的部门id
            $user_dep_id = Db::name('sys_user')->where('id',$user_id)->value('dep_id');
            //取出当前部门下的所有的人
            $user_all = Db::name('sys_user')->where('dep_id',$user_dep_id)->select();
            $user_str =  '';
            $user_str .= '<select name="user_id" style="width: 247px;height: 27px;">';
            foreach($user_all as $k => $v){
                if($user_id == $v['id']){
                    $selected_user = 'selected="selected"';
                }else{
                    $selected_user = '';
                }
                $user_str .= '<option value="'.$v['id'].'" '.$selected_user.'>'.$v['nickname'].'</option>';
            }
            $user_str .= '<select>';
        }else{
            $user_str = '<input style="width:240px;" type="text" name="prop_user" value="'.$prop_data['prop_user'].'">';
        }

        //取出所部门组织架构
//        $depAll = Db::name('sys_dep')->field('id,en_name,pid as parent_id')->select();
//        $depAll = _reSort($depAll);
        $depAll = config('dep_info');
        $depAll = change_filed($depAll,'parent_id','pid');
        $depAll = _reSort($depAll);
        $dep_str = '';

        foreach($depAll as $k => $v){

            if($v['id'] == $dep_id){
                $selected = 'selected="selected"';
            }else{
                $selected = '';
            }
            if($v['id'] == config('STOCK_DEP_ID')){
                $dep_str .= '<option value="1" '.$selected.'>'.str_repeat("&nbsp;",6*$v['level']).'库存'.'</option>';
            }else{
                $dep_str .= '<option value="'.$v['id'].'" '.$selected.' > '
                    .str_repeat("&nbsp;",6*$v['level']).$v['en_name'].'</option>';
            }

        }


        //取出负责部门的组织架构
        $respon_str = '';
        foreach($depAll as $k1 => $v1){
            if($v1['id'] == $respon_dep_id){
                $selected1 = 'selected="selected"';
            }else{
                $selected1 = '';
            }
            $respon_str .= '<option value="'.$v1['id'].'" '.$selected1.' > '
                .str_repeat("&nbsp;",6*$v1['level']).$v1['en_name'].'</option>';
        }

        //资产的申请人
        $apply_id = $prop_data['apply_id'];
        $local_dep_id = $prop_data['local_dep_id'];
        $user_data = Db::name('sys_user')->where('dep_id',$local_dep_id)->select();
        $user_data_str = '';
        foreach($user_data as $k4 => $v4){
            if($apply_id == $v4['id']){
                $selected = 'selected="selected"';
            }else{
                $selected = '';
            }
            $user_data_str .= '<option value="'.$v4['id'].'" '.$selected.'>'.$v4['nickname'].'</option>';
        }

        //资产所属类型
        $prop_type_data = Db::name('prop_type')->select();
        $prop_type_data = _reSort($prop_type_data);
        $prop_type_str = '';
        foreach($prop_type_data as $k3 => $v3){
            if($prop_data['prop_type'] == $v3['id']){
                $selected2 = 'selected="selected"';
            }else{
                $selected2 = '';
            }
            $prop_type_str .=  '<option value="'.$v3['id'].'" '.$selected2.' > '
                .str_repeat("&nbsp;",6*$v3['level']).$v3['prop_type'].'</option>';
        }
//        $page_info['prop_status_str'] = $prop_status_str;
        $page_info['user_data_str'] = $user_data_str;
        $page_info['user_str'] = $user_str;

        $page_info['prop_type_str'] = $prop_type_str;
        $page_info['respon_dep_str'] = $respon_str;
        $page_info['dep_str'] = $dep_str;
        $page_info['prop_data'] = $prop_data;
        $this->assign('page_info',$page_info);
        return $this->fetch();
    }


    public function editprop(){
        $editPropData = $_POST;

        if( $editPropData['local_dep_id'] == 1){
            echo setServerBackJson(0,"请选择相应的部门");exit;
        }
        if( $editPropData['respon_dep_id'] == 1){
            echo setServerBackJson(0,"请选择相应的部门");exit;
        }



        //查询所在部门的名称
//        $dep = Db::name('sys_dep')->where('id', $editPropData['local_dep_id'])->field('en_name')->find();
//        var_dump(config('dep_info')[$editPropData['local_dep_id']]['en_name']);die;
        $dep = get_cache_data('dep_info',$editPropData['local_dep_id'],'en_name');
//        var_dump($dep);die;
//        $editPropData['dep'] =  $dep['en_name'];
        $editPropData['dep'] = $dep;

        //查询负责部门的名称
//        $respon_dep = Db::name('sys_dep')->where('id', $editPropData['respon_dep_id'])->field('en_name')->find();
//        $editPropData['respon_dep'] = $respon_dep['en_name'];
        $editPropData['respon_dep'] = get_cache_data('dep_info',$editPropData['respon_dep_id'],'en_name');

        //实例化model类，校验数据
        $prop = new \app\prop\model\Prop();
        $prop->editPropData($editPropData);




        //修改是否为新人
        if(isset($editPropData['is_newbie'])){
            $editPropData['is_newbie'] = 1;
        }
        if(isset($editPropData['is_common'])){
            $editPropData['is_common'] = 1;
        }
//        var_dump($editPropData);die;
        //判断是否存在系统的用户、
        if(isset($editPropData['user_id'])){
            //查找对应的名字
//            $editPropData['prop_user'] = Db::name('sys_user')->where('id',$editPropData['user_id'])->value('nickname');
            $editPropData['prop_user'] = get_cache_data('user_info',$editPropData['user_id'],'nickname');

        }

        //查找对应的申请人
//        $editPropData['propuser'] = Db::name('sys_user')->where('id',$editPropData['apply_id'])->value('nickname');
        $editPropData['propuser'] = get_cache_data('user_info',$editPropData['apply_id'],'nickname');


        //过滤数据
        $update_data = array();
        foreach($editPropData as $k => $v){
            $update_data[$k] = trim($v);
        }

        //修改数据
        Db::name('prop')->where('id',$update_data['id'])->update($update_data);
        echo setServerBackJson(1,"修改成功");
    }



    public function checkPropName(){
        $name = $_POST['data'];
        //链接数据库进行查询
        $result = Db::name('prop')->where('reg_prop_num',$name)->find();
        if($result){
            echo setServerBackJson(0,"资产编号重复！");exit;
        }
    }

    public function deleteprop(){
        $id = input('post.id');
         //删除数据
        Db::name('prop')->where('id',trim($id))->delete();
        echo setServerBackJson(1,"删除成功");
    }

   //增加页面中获得部门的下员工
    public function getapplyuser(){
         $dep_id = input('post.id');
         //取出属于当前部门下的员工
         $user_id_data = Db::name('sys_user')->field('id,nickname')->where('dep_id',$dep_id)->select();
         $str = '';
         foreach($user_id_data as $k => $v){
             $str .=  '<option value="'.$v['id'].'">'.$v['nickname'].'</option>';
         }
         return $str;
    }

    //修改页面中获得部门下运功
    public function getedituser(){
        $id = input('post.id');

        //取出库房的部门的id
        $st_id = config('STOCK_DEP_ID');

        $user_id_data = Db::name('sys_user')->field('id,nickname')->where('dep_id',$id)->select();
        //取出属于当前部门下的员工
        $st_id = config('STOCK_DEP_ID');
        if($st_id == $id){
            $str = '<input type="text" name="prop_user" style="width: 240px;">';
        }else{

            $str = '<select name="user_id" style="width: 247px;height: 27px;" class="select_edit">';
            foreach($user_id_data as $k => $v){
                $str .=  '<option value="'.$v['id'].'">'.$v['nickname'].'</option>';
            }
            $str .= '</select><span style="color: red">*</span>';
        }


        $str_apply = '<select name="apply_id" style="width: 247px;height: 27px;" class="select_edit">';
        foreach($user_id_data as $k1 => $v1){
            $str_apply .= '<option value="'.$v1['id'].'">'.$v1['nickname'].'</option>';
        }
        $str_apply .= '</select>';

        $return['prop_user'] = $str;
        $return['prop_apply'] = $str_apply;
        return $return;
//        return $str;
    }

    //获得申请部门下的申请人
    public function get_apply_user(){
        $id = input('post.id');
        //取出库房的部门的id
        $st_id = config('STOCK_DEP_ID');
        if($id == $st_id){
            $str = '<input type="text" name="prop_user" style="width: 240px;">';
            return $str;
        }
        //取出属于当前部门下的员工
        $user_id_data = Db::name('sys_user')->field('id,nickname')->where('dep_id',$id)->select();
        $str = '<select name="apply_id" style="width: 240px;">';
        foreach($user_id_data as $k => $v){
            $str .=  '<option value="'.$v['id'].'">'.$v['nickname'].'</option>';
        }
        $str .= '</select><span style="color: red">*</span>';
        return $str;
    }



    public function addexcel(){
        return $this->fetch();
    }

    public function leadexcel(){

        if(!isset($_FILES['file'])){
            echo setServerBackJson(0,"请上传excel表格");exit;
        }

        $file = $_FILES['file'];
        //单个文件上传
        $fileType = explode('.',$file['name']);
        //文件格式
        $file_format = end($fileType);
        $upload_type = array(
            '0'=> 'xls',
            '1'=> 'xlsx'
        );

        if(!in_array($file_format,$upload_type)){
            echo setServerBackJson(0,"请重新上传对应的格式文件！");exit;
        }

        //文件上传的目录
        $project_dir = config('PROJECT_DIR');


        $filePath = config('UPLOAD_FILE_URL').DS.'excel'.DS.'prop'.DS;
        
        if(!is_dir($filePath)){
            mkdir($filePath,777,true);
        }

        //文件上传的完整路径
        $new_filename = 'prop';
        $new_filename = iconv("UTF-8", "GB2312", $new_filename);
        $filename_path = $filePath."/".$new_filename.".".$file_format;
        move_uploaded_file($file['tmp_name'],$filename_path);

        //获取上传excel表格的内容
        \think\Loader::import('.PHPExcel.PHPExcel');

        if($file_format == 'xlsx'){
            $file_type = 'Excel2007' ;
        }else{
            $file_type = 'Excel5' ;
        }

        $objReader = \PHPExcel_IOFactory::createReader($file_type);
        $file_url = $filename_path;
        $objPHPExcel=$objReader->load($file_url);
        $sheet = $objPHPExcel->getSheet(0);
        $row_count = $sheet->getHighestRow();
        $col_max = $sheet->getHighestColumn();
        $col_count=\PHPExcel_Cell::columnIndexFromString($col_max);

        //从第二行开始读取数据
        for($j=2;$j<=$row_count;$j++){
            for($k=0;$k<$col_count;$k++){
                //拼接单元格坐标
                $cell_val=\PHPExcel_Cell::stringFromColumnIndex($k).$j;
                $return_arr[$j][$k]=auto_charset($objPHPExcel->getActiveSheet()->getCell($cell_val)->getValue(),'utf-8');
            }
        }

        $insert_arr = array();
        foreach($return_arr as $k => $v){
            foreach($v as $k1 => $v1){
                if(is_object($v1)) {
                    $v1= (string)$v1;
                }
                $tmp[$k1] = $v1;
            }
            //对数据的处理
            $tmp[3] = str_ireplace(".","-",$tmp[3]);
            $insert_data['reg_prop_num'] = $tmp[0];
            $insert_data['reg_prop_name'] = $tmp[1];
            $insert_data['product_model'] = $tmp[2];
            $insert_data['start_use_time'] = $tmp[3];
            $insert_data['use_dep'] = $tmp[4];
            $insert_data['prop_user'] = $tmp[5];
            $t_user_id=get_userid($tmp[5]);
            if(strlen($t_user_id)==0){
            	$t_user_id=0;
            }
            $insert_data['user_id'] = $t_user_id;
            
            if($tmp[6] == 'ST'){
                $insert_data['dep'] = "中颖电子";
            }else{
                $insert_data['dep'] = $tmp[6];
            }

            $insert_data['local_dep_id'] = get_dep_id($tmp[6]);
            $insert_data['prop_remark'] = $tmp[7];
            $insert_data['respon_dep'] = $tmp[8];
            $insert_data['respon_dep_id'] = get_dep_id($tmp[8]);
            $insert_data['logoff_time'] = $tmp[9];
            $insert_data['orginal_val'] = $tmp[10];
            $insert_data['net_val'] = $tmp[11];
            if($insert_data['user_id'] == ''){
                $insert_data['is_common'] = 1;
            }else{
                $insert_data['is_common'] = 0;
            }
            $insert_arr[] = $insert_data;

        }

       //插入数据库
        $result = Db::name('prop')->insertAll($insert_arr);
        if($result){
            echo setServerBackJson(1,"导入成功");exit;
        }else{
            echo setServerBackJson(0,"导入失败");exit;
        }

    }





}