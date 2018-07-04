<?php
namespace app\sys\model;
use think\Model;
use think\Validate;
class Role extends Model{

    public function getTree(){
        $role = new Role();
        $data = $role->select();
        return  _reSort($data);
    }

    public function getChildren($id)
    {
        $role = new Role();
        $data = $role->select();
        return $this->_children($data, $id);
    }

    private function _children($data, $parent_id=0, $isClear=TRUE)
    {
        static $ret = array();
        if($isClear)
            $ret = array();
        foreach ($data as $k => $v)
        {
            if($v['parent_id'] == $parent_id)
            {
                $ret[] = $v['id'];
                $this->_children($data, $v['id'], FALSE);
            }
        }
        return $ret;
    }

    /*添加部门数据*/
    public function insertData($data){
        $rule = [
            ['role_name','require','角色名称不能为空'],
            ['c_group_name','require','角色中文名称不能为空'],

            ['parent_id','require','顶级角色不能为空']
        ];
        $validateData = [
            'role_name' => trim($data['role_name']),
            'c_group_name'=> trim($data['c_group_name']),

            'parent_id' => trim($data['parent_id'])
        ];
        $validate = new Validate($rule);
        $result = $validate->check($validateData);
        if(!$result){
            echo setServerBackJson(0,$validate->getError());exit;
        }

    }

    /*修改部门数据*/
    public function editData($data){
        $rule = [
            ['role_name','require','角色名称不能为空'],
            ['c_group_name','require','角色中文名称不能为空'],
            ['parent_id','require','上级部门不能为空']
        ];
        $validateData = [
            'role_name' => trim($data['role_name']),
            'c_group_name'=> trim($data['c_group_name']),
            'parent_id' => trim($data['parent_id'])
        ];
        $validate = new Validate($rule);
        $result = $validate->check($validateData);
        if(!$result){
            echo setServerBackJson(0,$validate->getError());exit;
        }
    }
}