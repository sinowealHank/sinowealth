<?php
namespace app\prop\model;
use think\Model;
use think\Validate;

class PropAccept extends Model{
    //校验数据
    public function check_accept_data($data){
        $rule = [
            ['instr_name','require','仪器名称不能为空'],
            ['equip_num','require','设备编号不能为空'],
            ['brand_model','require','品牌型号不能为空'],
            ['product_num','require','产品序列号不能为空'],
            ['supplier','require','供货商不能为空'],
            ['buy_time','require','购买时间不能为空'],
        ];

        $validateData = [
            'instr_name' => trim($data['instr_name']),
            'equip_num'=> trim($data['equip_num']),
            'brand_model'=> trim($data['brand_model']),
            'product_num' => trim($data['product_num']),
            'supplier' => trim($data['supplier']),
            'buy_time' => trim($data['buy_time']),
        ];

        $validate = new Validate($rule);
        $result = $validate->check($validateData);
        if(!$result){
            echo setServerBackJson(0,$validate->getError());exit;
        }
    }




}