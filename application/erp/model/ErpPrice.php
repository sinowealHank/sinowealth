<?php
namespace app\erp\model;
use think\Model;
class ErpPrice extends Model{
    //ͨ��id�������
    public function get_price_data($id){
        return $this->where('id',$id)->find();
    }




}