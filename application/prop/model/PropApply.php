<?php
namespace app\prop\model;
use think\Model;
use think\Validate;

class PropApply extends Model{

    public function insertdata($data){
        $rule = [
            ['use_prop_name','require','使用人名称不能为空'],
            ['use_dep_id','require','负责部门不能为空'],
            ['require_time','require','需求时间不能为空'],
            ['apply_thing','require','申请物品不能为空'],
            ['thing_num','require','物品数量不能为空'],
            ['apply_reason','require','申请理由不能为空'],
        ];

        $validateData = [
            'use_prop_name' => trim($data['use_prop_name']),
            'use_dep_id'=> trim($data['use_dep_id']),
            'require_time'=> trim($data['require_time']),
            'apply_thing' => trim($data['apply_thing']),
            'thing_num' => trim($data['thing_num']),
            'apply_reason' => trim($data['apply_reason']),
        ];
        $validate = new Validate($rule);
        $result = $validate->check($validateData);
        if(!$result){
            echo setServerBackJson(0,$validate->getError());exit;
        }

    }




}