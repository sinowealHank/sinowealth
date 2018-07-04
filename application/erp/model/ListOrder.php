<?php
namespace app\erp\model;
use think\Model;
use think\Session;
class ListOrder extends model{

    protected static function getPeBomAttr($value){
        if($value == 0){
            return  "";
        }else{
            return  "√";
        }
    }

    protected static function getPePrdListAttr($value){
        if($value == 0){
            return  "";
        }else{
            return  "√";
        }
    }

    protected static function getPeSupAttr($value){
        if($value == 0){
            return  "";
        }else{
            return  "√";
        }
    }


    protected static function getPeFieldAttr($value){
        if($value == 0){
            return  "";
        }else{
            return  "√";
        }
    }

    protected static function getPmBPriceAttr($value){
        if($value == 0){
            return  "";
        }else{
            return  "√";
        }
    }

    protected static function getPmStockAttr($value){
        if($value == 0){
            return  "";
        }else{
            return  "√";
        }
    }

    protected static function getSdSalesCheckAttr($value){
        if($value == 0){
            return  "";
        }else{
            return  "√";
        }
    }

    protected static function getSdCustOverStatusAttr($value){
        if($value == 0){
            return  "";
        }else{
            return  "√";
        }
    }

    protected static function getPpWfPriceAttr($value){
        if($value == 0){
            return  "";
        }else{
            return  "√";
        }
    }

    protected static function getPpPkgPNumAttr($value){
        if($value == 0){
            return  "";
        }else{
            return  "√";
        }
    }

    protected static function getPpFtPNumAttr($value){
        if($value == 0){
            return  "";
        }else{
            return  "√";
        }
    }

    protected static function getPpFtApAttr($value){
        if($value == 0){
            return  "";
        }else{
            return  "√";
        }
    }

    protected static function getPpWfFAttr($value){
        if($value == 0){
            return  "";
        }else{
            return  "√";
        }
    }


    protected static function getPpCpFAttr($value){
        if($value == 0){
            return  "";
        }else{
            return  "√";
        }
    }

    protected static function getPpFtFAttr($value){
        if($value == 0){
            return  "";
        }else{
            return  "√";
        }
    }

    protected static function getSdOrderFAttr($value){
        if($value == 0){
            return  "";
        }else{
            return  "√";
        }
    }


    public function get_data(){
       return  $this->select();
    }


    public function get_dep_author_field($dep_id){
        //FD部门
        if($dep_id == 3){
            return 5;
        //SD部门
        }else if($dep_id == 10){
            return 3;
        //PP部门
        }else if($dep_id == 13){
            return 4;
        //PE部门
        }else if($dep_id == 12){
            return 1;
        }
    }


}