<?php
namespace app\prop\model;
use think\Model;
use think\Validate;
class Prop extends Model{
    //添加时检测数据
    public function insertPropData($data){

        $rule = [
            ['reg_prop_num','require','资产编号不能为空'],
            ['reg_prop_name','require','资产名称不能为空'],
            ['product_model','require','规格型号不能为空'],
            ['prop_user','require','使用人不能为空'],
            ['propuser','require','申请人不能为空'],
            ['local_dep_id','require','所在部门不能为空'],
            ['respon_dep_id','require','负责部门不能为空'],
            ['buy_time','require','购买时间不能为空']

        ];

        $validateData = [
            'reg_prop_num' => trim($data['reg_prop_num']),
            'reg_prop_name'=> trim($data['reg_prop_name']),
            'product_model'=> trim($data['product_model']),
            'prop_user' => trim($data['prop_user']),
            'propuser' => trim($data['propuser']),
            'local_dep_id' => trim($data['local_dep_id']),
            'respon_dep_id' => trim($data['respon_dep_id']),
            'buy_time'=> trim($data['buy_time']),

        ];
        $validate = new Validate($rule);
        $result = $validate->check($validateData);
        if(!$result){
            echo setServerBackJson(0,$validate->getError());exit;
        }
    }

    public function editPropData($data){

        $rule = [
            ['reg_prop_num','require','资产编号不能为空'],
            ['reg_prop_name','require','资产名称不能为空'],
            ['product_model','require','规格型号不能为空'],
            ['local_dep_id','require','所在部门不能为空'],
            ['respon_dep_id','require','负责部门不能为空'],
        ];

        $validateData = [
            'reg_prop_num' => trim($data['reg_prop_num']),
            'reg_prop_name'=> trim($data['reg_prop_name']),
            'product_model'=> trim($data['product_model']),
            'local_dep_id' => trim($data['local_dep_id']),
            'respon_dep_id' => trim($data['respon_dep_id']),
        ];
        $validate = new Validate($rule);
        $result = $validate->check($validateData);
        if(!$result){
            echo setServerBackJson(0,$validate->getError());exit;
        }

    }




}