<?php
namespace app\erp\controller;
use think\Controller;

use app\erp\model\CostTable as CostTableModel;
use app\index\controller\Admin;
class CostTable extends Admin{
    public function index()
    {
        return $this->fetch();
    }


    public function getBomData()
    {
        $data = input('post.','','trim');
        $prdNo = $data['prdno'];
        //处理数据
        $costTable = new CostTableModel();
        $CostData = $costTable->getBomData($prdNo);
        if(!$CostData)
        {
            $CostData = [];
        }
        $qty = count($CostData);
        $json = '{"total":'.$qty.',"rows":'.json_encode($CostData).'}';
        echo $json;
    }







}