<?php
namespace app\erp\model;
use think\Model;

class ErpUserType extends Model{
    //增加人员数据
    public function save_data($data){
        //根据代号获得人员信息基础数据
        $user_data = model('SysUser')->get_user_data($data['user_gh']);
        $data['user_id'] = $user_data['id'];
        $data['user_name'] = $user_data['nickname'];
        if(isset($data['line'])){
            $line = $data['line'];
            $line_str = implode(',',$line);
            $data['prd_line'] = $line_str;
            //判断是那个维护群组
            unset($data['line']);
        }
        $data['user_type'] = $data['dep'];
        $data['user_gh'] = $data['user_gh_val'];
        unset($data['user_gh_val']);
        unset($data['dep']);;
        return $this->insert($data);

    }

    //修改数据
    public function update_data($data){

        $user_data = model('SysUser')->get_user_data($data['user_gh_val']);
        $data['user_id'] = $user_data['id'];
        $data['user_name'] = $user_data['nickname'];

        if(isset($data['line'])){
            $line = $data['line'];
            $line_str = implode(',',$line);
            $data['prd_line'] = $line_str;
            //判断是那个维护群组
            unset($data['line']);
        }else{
            $data['prd_line'] = '';
        }
        $data['user_type'] = $data['dep'];
        $data['user_gh'] = $data['user_gh_val'];
        unset($data['dep']);
        unset($data['user_gh_val']);
        return $this->where('id',$data['id'])->update($data);
    }



}