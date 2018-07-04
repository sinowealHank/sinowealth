<?php
namespace app\sys\model;
use think\Model;
use think\Validate;
class Privilege extends Model{
    public function getTree(){
        $role = new Privilege();
        $data = $role->select();
        return _reSort($data);
    }

    /*检测添加权限数据*/
    public function addPriData($data,$level){
        if($level == 0){
            $rule = [
                ['pri_name','require','权限名称必须填写'],
                ['module_name','require','模块名称必须填写'],
//                ['order_id','require','排序id必须填写']
            ];
            $validateData =[
                'pri_name' => trim($data['pri_name']),
                'module_name'=> trim($data['module_name'])
//                'order_id' => trim($data['order_id'])
            ];
            $validate = new Validate($rule);
            $result = $validate->check($validateData);
            if(!$result){
                echo setServerBackJson(0,$validate->getError());exit;
            }

        }else if($level== 1){
            /*验证规则*/
            $rule = [
                ['pri_name','require','权限名称必须填写'],
                ['module_name','require','模块名必须填写'],
                ['controller_name','require','控制器名必须填写'],
//                ['order_id','require','排序id必须填写']
            ];
            $validateData = [
                'pri_name' => trim($data['pri_name']),
                'module_name'=> trim($data['module_name']),
                'controller_name' => trim($data['controller_name']),
//                'order_id' => trim($data['order_id'])
            ];
            $validate = new Validate($rule);
            $result = $validate->check($validateData);
            if(!$result){
                echo setServerBackJson(0,$validate->getError());exit;
            }
        }else{
            /*验证规则*/
            $rule = [
                ['pri_name','require','权限名称必须填写'],
                ['module_name','require','模块名必须填写'],
                ['controller_name','require','控制器名必须填写'],
                ['action_name','require','方法名必须填写'],
//                ['order_id','require','排序id必须填写']
            ];
            $validateData = [
                'pri_name' => trim($data['pri_name']),
                'module_name'=> trim($data['module_name']),
                'controller_name' => trim($data['controller_name']),
                'action_name' => trim($data['action_name']),
//                'order_id' => trim($data['order_id'])
            ];
            $validate = new Validate($rule);
            $result = $validate->check($validateData);
            if(!$result){
                echo setServerBackJson(0,$validate->getError());exit;
            }
        }

    }
   /*修改权限数据*/
    public function editData($data,$id,$level){
        if($level == 0){
            $rule = [
                ['pri_name','require','权限名称必须填写'],
                ['module_name','require','模块名称必须填写'],

            ];
            $validateData =[
                'pri_name' => trim($data['pri_name']),
                'module_name'=> trim($data['module_name'])
            ];
            $validate = new Validate($rule);
            $result = $validate->check($validateData);
            if(!$result){
                echo setServerBackJson(0,$validate->getError());exit;
            }
        }else if($level == '1'){
            $rule = [
                ['pri_name','require','权限名称必须填写 '],
                ['module_name','require','模块名必须填写'],
                ['controller_name','require','控制器名必须填写'],
//                ['order_id','require','排序id必须填写']
            ];
            $validateData = [
                'pri_name' => trim($data['pri_name']),
                'module_name'=> trim($data['module_name']),
                'controller_name' => trim($data['controller_name']),
//                'order_id' => trim($data['order_id'])
            ];
            $validate = new Validate($rule);
            $result = $validate->check($validateData);
            if(!$result){
                echo setServerBackJson(0,$validate->getError());exit;
            }
        } else{
            $rule = [
                ['pri_name','require','权限名称必须填写 '],
                ['module_name','require','模块名必须填写'],
                ['controller_name','require','控制器名必须填写'],
                ['action_name','require','控制器名必须填写'],
//                ['order_id','require','排序id必须填写']
            ];
            $validateData = [
                'pri_name' => trim($data['pri_name']),
                'module_name'=> trim($data['module_name']),
                'controller_name' => trim($data['controller_name']),
                'action_name' => trim($data['action_name']),
//                'order_id' => trim($data['order_id'])
            ];
            $validate = new Validate($rule);
            $result = $validate->check($validateData);
            if(!$result){
                echo setServerBackJson(0,$validate->getError());exit;
            }
        }

    }







}