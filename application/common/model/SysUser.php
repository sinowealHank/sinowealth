<?php
namespace app\common\model;
use think\Model;

class SysUser extends Model{
    //���ݹ��Ż�ö�Ӧ����
    public function get_user_data($user_gh){
        $map['user_gh'] = $user_gh;
        return $this->where($map)->find();
    }

}