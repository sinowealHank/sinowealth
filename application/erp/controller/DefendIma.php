<?php
namespace app\erp\controller;
use think\Controller;
use app\index\controller\Admin;
use app\erp\model\DefendIma as ImaModel;
class DefendIma extends Admin{
    public $dbName = '';

    public function _initialize()
    {
        parent::_initialize();
        $this->dbName = config('site_name');
    }

    public function index()
    {
        $ImaModel = new ImaModel($this->dbName);
        //获得产品线
        $line = $ImaModel->getLine();
        //获得PM
        $Pm = $ImaModel->getPm();
        //事业群
        $BusinessGroup = $ImaModel->getBusinessGroup();
        //PKGType
        $PkgType = $ImaModel->getPkgType();
        return $this->fetch('',[
            'line' => $line,
            'pm'=> $Pm,
            'BusinessGroup' => $BusinessGroup,
            'pkgtype' => $PkgType
        ]);
    }



    /**
     *  获取数据
     */
    public function getData()
    {
        $data = input('post.','','trim');
        $where = $this->checkFieldData($data);
        $ImaModel = new ImaModel($this->dbName);
        $data = $ImaModel->getImaData($where);
        $qty = count($data);
        $json = '{"total":'.$qty.',"rows":'.json_encode($data).'}';
        echo $json;
    }

    /**  检查对应的字段值
     * @param array $data
     * @return array
     */
    private function checkFieldData($data = [])
    {
        $where = [];
        if(isset($data['order'])){
            $where['order'] = $data['order'];
        }
        if(isset($data['sort'])){
            $where['sort'] = $data['sort'];
        }
        //料号
        if(isset($data['prdno'])){
            $where['prdno'] = $data['prdno'];
        }
        //分群吗
        if(isset($data['ima06'])){
            $where['ima06'] = $data['ima06'];
        }
        //产品线
        if(isset($data['ima13'])){
            $where['ima13'] = $data['ima13'];
        }
        //群组料号
        if(isset($data['ima133'])){
            $where['ima133'] = $data['ima133'];
        }
        return $where;

    }


    //修改数据
    public function edit_data()
    {
        $data = input('post.');
        //修改数据
        $ImaModel = new ImaModel($this->dbName);
        $result =  $ImaModel->editData($data);
        if(!$result)
        {
            echo setServerBackJson(0,"回写失败");
        }else
        {
            $json = [
                'statusCode' => 1,
            ];
            return json_encode($json);
        }
    }
}