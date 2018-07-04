<?php
namespace app\erp\model;
use think\Model;
use think\Session;

/** CostTable相关数据
 * Class ErpCtFac
 * @package app\erp\model
 */
class ErpCtFac extends  Model
{
    /** 保存数据
     * @return bool
     */
    public function save_data($map,$data)
    {

        $result = $this->where($map)->update($data);
        if($result !== false)
        {
            return true;
        }
        return false;

    }






}