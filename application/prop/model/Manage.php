<?php
namespace app\prop\model;
use think\Model;
use think\Validate;

class Manage extends Model{
    public function check($data){
//        var_dump($data);die;
        $rule = [
            ['brand_model','require','品牌型号不能为空'],
            ['basic_info','require','基本配置信息不能为空'],
            ['refer_price','require','参考价格不能为空'],
            ['remark','require','备注不能为空'],
            ['model','require','型号不能为空'],
            ['supplier_1','require','供应商1不能为空'],
            ['supplier_2','require','供应商2不能为空'],
            ['supplier_3','require','供应商3不能为空'],
            ['offer_price_1','require','供应商1报价不能为空'],
            ['bar_price_1','require','供应商议价清单报价1不能为空'],
            ['offer_price_2','require','供应商2报价不能为空'],
            ['bar_price_2','require','供应商议价清单报价2不能为空'],
            ['offer_price_3','require','供应商3报价不能为空'],
            ['bar_price_3','require','供应商议价清单报价3不能为空'],
            ['accept_use_id','require','验收人不能为空'],
            ['pur_advice','require','采购建议不能为空'],
//            ['cheng','require','呈不能为空'],
//            ['instruct','require','批示不能为空'],
//            ['sign_dep_id','require','会签部门不能为空'],
//            ['purport','require','主旨不能为空']
        ];
        $validateData = [
            'brand_model' => trim($data['brand_model']),
            'basic_info'=> trim($data['basic_info']),
            'refer_price'=> trim($data['refer_price']),
            'remark' => trim($data['remark']),
            'model' => trim($data['model']),
            'supplier_1' => trim($data['supplier_1']),
            'supplier_2' => trim($data['supplier_2']),
            'supplier_3' => trim($data['supplier_3']),
            'offer_price_1'=> trim($data['offer_price_1']),
            'bar_price_1'=> trim($data['bar_price_1']),
            'offer_price_2'=> trim($data['offer_price_2']),
            'bar_price_2'=> trim($data['bar_price_2']),
            'offer_price_3'=> trim($data['offer_price_3']),
            'bar_price_3'=> trim($data['bar_price_3']),
            'accept_use_id'=> trim($data['accept_use_id']),
            'pur_advice'=> trim($data['pur_advice']),
//            'cheng'=> trim($data['cheng']),
//            'instruct'=> trim($data['instruct']),
//            'sign_dep_id'=> trim($data['sign_dep_id']),
//            'purport'=> trim($data['purport'])
        ];
        $validate = new Validate($rule);
        $result = $validate->check($validateData);
        if(!$result){
            echo setServerBackJson(0,$validate->getError());exit;
        }

    }



}