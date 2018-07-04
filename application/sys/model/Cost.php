<?php
namespace app\sys\model;
use think\Model;
use think\Validate;

class Cost extends Model{

    //校验添加记录的数据
    public function check_insertData($data){
        $rule = [
            ['vou_num','require','凭证号不能为空'],
            ['dep_id','require','部门名称不能为空'],
            ['dep_num','require','部门号不能为空'],
            ['user_job_num','require','员工工号不能为空'],
            ['type_num','require','类型号不能为空'],
            ['user_id','require','员工名称不能为空'],
            ['free_type_select','require','费用类型名称不可为空'],
            ['remark','require','备注不能为空'],
            ['cost_amout','require','费用额不能为空']
        ];

        $validateData = [
            'vou_num' => $data['vou_num'] ,
            'dep_id'=>  $data['dep_id'] ,
            'dep_num'=>  $data['dep_num'],
            'user_job_num' => $data['user_job_num'],
            'type_num' => $data['type_num'],
            'user_id' => $data['user_id'],
            'free_type_select' => $data['free_type_select'],
            'remark' => $data['remark'],
            'cost_amout'=> $data['cost_amout']
        ];

        $validate = new Validate($rule);
        $result = $validate->check($validateData);
        if(!$result){
            echo setServerBackJson(0,$validate->getError());exit;
        }

    }


    public function check_editData($data){
        $rule = [
            ['vou_num','require','凭证号不能为空'],
            ['dep_id','require','部门名称不能为空'],
            ['dep_num','require','部门号不能为空'],
            ['user_job_num','require','员工工号不能为空'],
            ['type_num','require','类型号不能为空'],
            ['user_id','require','员工名称不能为空'],
            ['free_type_select','require','费用类型名称不可为空'],
            ['remark','require','备注不能为空'],
            ['cost_amout','require','费用额不能为空']
        ];

        $validateData = [
            'vou_num' => $data['vou_num'] ,
            'dep_id'=>  $data['dep_id'] ,
            'dep_num'=>  $data['dep_num'],
            'user_job_num' => $data['user_job_num'],
            'type_num' => $data['type_num'],
            'user_id' => $data['user_id'],
            'free_type_select' => $data['free_type_select'],
            'remark' => $data['remark'],
            'cost_amout'=> $data['cost_amout']
        ];

        $validate = new Validate($rule);
        $result = $validate->check($validateData);
        if(!$result){
            echo setServerBackJson(0,$validate->getError());exit;
        }

    }


}