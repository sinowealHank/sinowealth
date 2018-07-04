<?php
namespace app\common\model;
use think\Model;

class SysUser extends Model{
    //根据工号获得对应数据
    public function get_user_data($user_gh){
        $map['user_gh'] = $user_gh;
        return $this->where($map)->find();
    }

}