<?php
namespace app\erp\model;
use think\Exception;
use think\Model;
class DefendIma extends Basic{

    private $keyStr = 'imaicd';
    /** 数据库名字
     * @var|string
     */
    protected $dbName = '';
    /** oracle数据对象
     * @var obj
     */
    protected $obj = null;

    /**  初始化对象
     * @param array|object $db
     */
    public function __construct($db)
    {
        $this->dbName = $db;
        $this->obj = new Oracle();
    }

    /** 查询数据库数据
     * @return array
     */
    public function getImaData($where)
    {

        if(!empty($where['prdno']))
        {
            $prdno_str = "and ima01 like '%{$where['prdno']}%'";
        }else{
            $prdno_str = '';
        }
        if(!empty($where['ima06'])){
            $ima06_str = "and ima06 = '{$where['ima06']}'";
        }else{
            $ima06_str = '';
        }
        if(!empty($where['ima133'])){
            $ima13_str = "and ima133 like '%{$where['ima133']}%'";
        }else{
            $ima13_str = '';
        }
        if(!empty($where['order']) && !empty($where['sort']))
        {

            $order_str = " order by {$where['sort']} {$where['order']}";
        }else{
            $order_str = '';
        }

         $sql = "select ima01,ima02,ima06,ima09,ima10,ima131,ima133,ima908,ima906,ima907,ima25,imaicd01,imaicd14,imaicd18,imaud07 from ".$this->dbName.".ima_file,
         ".$this->dbName.".imaicd_file where ima01 = imaicd00 and rownum < 51 ".$prdno_str.$ima06_str.$ima13_str.$order_str;
//        var_dump($sql);die;
        $data = $this->obj->getOracleData($sql);
        $data = $this->dealWithData($data);
        //处理数据
        if(empty($data))
        {
            return [];
        }else{
            return $data;
        }
    }

    /**   处理返回的对应的数据
     * @param $data
     * @return mixed
     */
    private function dealWithData($data)
    {
        foreach($data as $k => &$v)
        {
            if($v['ima906'] == 1)
            {
                $v['ima906'] = '单一单位';
            }elseif($v['ima906'] == 2)
            {
                $v['ima906'] = '母子单位';
            }elseif($v['ima906'] == 3)
            {
                $v['ima906'] = '参考单位';
            }else{
                $v['ima906'] = '无单位';
            }
        }
        return $data;
    }

    //获得产品线数据
    public function getLine()
    {
        $time = date('Y',time());
        $sql = "select distinct tc_prob06 from {$this->dbName}.tc_prob_file where tc_prob01=".$time;
        $lines = $this->obj->getOracleData($sql);
        $data = [];
        foreach($lines as $line)
        {
            $temp['value'] = $line['tc_prob06'];
            $temp['name'] = $line['tc_prob06'];
            $data[] = $temp;
        }
        return json_encode($data);
    }
   //获得PM
    public function getPm()
    {
        $time = date('Y',time());
        $sql = "select distinct tc_prob05 from {$this->dbName}.tc_prob_file where tc_prob01=".$time;
        $lines = $this->obj->getOracleData($sql);
        $data = [];
        foreach($lines as $line)
        {
            $temp['value'] = $line['tc_prob05'];
            $temp['name'] = $line['tc_prob05'];
            $data[] = $temp;
        }
        return json_encode($data);
    }

    //获得事业群
    public function getBusinessGroup()
    {
        $time = date('Y',time());
        $sql = "select distinct tc_prob04 from {$this->dbName}.tc_prob_file where tc_prob01=".$time;
        $lines = $this->obj->getOracleData($sql);
        $data = [];
        foreach($lines as $line)
        {
            $temp['value'] = $line['tc_prob04'];
            $temp['name'] = $line['tc_prob04'];
            $data[] = $temp;
        }
        return json_encode($data);
    }

    //获得PkgType
    public function getPkgType()
    {
        $sql = "select icd01 from data.icd_file where icd02='O'";
        $lines = $this->obj->getOracleData($sql);
        $data = [];
        foreach($lines as $line)
        {
            $temp['value'] = $line['icd01'];
            $temp['name'] = $line['icd01'];
            $data[] = $temp;
        }
        $data[] = [
            'value'=> ' ',
            'name' => '空'
        ];
        return json_encode($data);
    }





    /**
     *  修改数据
     */
    public function editData($data)
    {
        //取出唯一key值
        $keys = $data['key'];
        unset($data['key']);
        //判断是存在哪个表
        foreach($data as $key => $val)
        {
            //判断是ima、imaicd表中的字段
            $result = strpos($key,$this->keyStr);
            if($result !==  false){
                return  $this->editImaData($key,$val,$keys);
            }else{
                return  $this->editImaIcdData($key,$val,$keys);
            }
        }
    }

    // Imaicd表
    private function editImaData($key,$val,$keys)
    {
        $sql = "update {$this->dbName}.imaicd_file set {$key} ='{$val}' where imaicd00 = '{$keys}'";
        $result = exec_oracle($sql);
        if($result == false)
        {
            return false;
        }else{
            return true;
        }
    }

    // Ima表
    private function editImaIcdData($key,$val,$keys)
    {
        $sql = "update {$this->dbName}.ima_file set {$key} ='{$val}' where ima01 = '{$keys}'";
        $result = exec_oracle($sql);
        if($result == false)
        {
            return false;
        }else{
            return true;
        }
    }







}