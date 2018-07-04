<?php
namespace app\erp\model;
use think\Model;
class ErpPrice extends Model{
    //通过id获得数据
    public function get_price_data($id){
        return $this->where('id',$id)->find();
    }




}