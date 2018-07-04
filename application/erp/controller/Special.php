<?php
namespace app\erp\controller;
use think\Controller;
use think\Db;
use think\Session;
use app\index\controller\Admin;
use think\Validate;
class Special extends Admin{
    public function index(){
        return $this->fetch();
    }

    /**
     * 获取特殊正义数据
     * 2018/2/13 周仁杰修改
     */
    public function get_icl_data(){
        //查询多少条数据
        $icl01 =  isset($_POST['param']) ? $_POST['param'] : '';
        $name =  isset($_POST['name']) ? $_POST['name'] : '';
        $icl05 =  isset($_POST['icl05']) ? $_POST['icl05'] : '';
        $sort = isset($_POST['sort']) ? $_POST['sort'] : 'id';
        $order = isset($_POST['order']) ? $_POST['order'] : 'desc';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $rows = isset($_POST['rows']) ? intval($_POST['rows']) : 20;
        //查询条件
        $map['icl01'] = ['like','%'.trim($icl01).'%'];
        $map['icl05'] = ['like','%'.trim($icl05).'%'];
        //检测是否有汉字
        if (preg_match("/[\x7f-\xff]/", $name))
        {
            $map['name'] = ['like','%'.trim($name).'%'];
        }else{
            $map['ta_icl01'] = ['like','%'.trim($name).'%'];
        }

        $offset = ($page-1)*$rows;
        $Pri_data = [];
        $result = Model('ErpIcl')->where($map)->limit($offset,$rows)->order($sort,$order)->select();
        foreach($result as $val)
        {
            $Pri_data[] = $val->toArray();
        }
        //数量总数
        $count =  Db::name('ErpIcl')->count();
        $qq  = $Pri_data;
        $qty = $count;
        $json = '{"total":'.$qty.',"rows":'.json_encode($qq).'}';
        echo $json;

    }

    //增加特殊正义
    public function add_icl(){
        //获得产品的名称，在FT段。
//        $sql =  "select ima01 from sino.ima_file where ima06 = 'S_FT'";
//        $prdno = model('Oracle')->getOracleData($sql);
        //拼接产品名的信息
//        $prdno_str = '';
//        foreach($prdno as $k => $v){
//            $prdno_str .= '<option value="'.$v['ima01'].'">'.$v['ima01'].'</option>';
//        }
        //获取客户编号
        $sql1 = 'select occ01,occ02 from data.occ_file';
        $agent = model('Oracle')->getOracleData($sql1);
        $agent_str = '';
        foreach($agent as $k1 => $v1)
        {
            $agent_str .= '<option value="'.$v1['occ01'].'">'.$v1['occ01'].'---'.$v1['occ02'].'</option>';
        }
        return $this->fetch('',[
//            'prdno' => $prdno_str,
            'agent' => $agent_str
        ]);
    }

    //添加特殊正义数据
    public function save_icl()
    {
        //表单验证
        $data = request()->param();
        unset($data['icl_01']);
        unset($data['ta_icl_01']);
        $result = $this->validate($data,[
            'icl01' => 'require',
            'icl05' => 'require',
            'ta_icl01' => 'require'
        ],[
            'icl01.require' => '产品名必须选择',
            'icl05.require' => '特殊正印必须选择',
            'ta_icl01.require' => '客户简称必须填写',
        ]
        );
        if(true !== $result)
        {
            echo setServerBackJson(0,$result);exit;
        }
        //处理数据
        $result =  model('ErpIcl')->insert_icl($data);
        if($result !== false)
        {
            echo setServerBackJson(1,'添加成功');
        }else{
            echo setServerBackJson(0,'添加失败');
        }


    }
    //修改页面展示
    public function edit_icl($id,$key)
    {
       $key_arr = explode(',',$key);
        //产品名
        $icl01 = $key_arr[0];
        //特殊正义
        $icl05 = $key_arr[2];
        //客户简称
        $ta_icl01 = $key_arr[3];
        //获得产品的名称，在FT段。
        $sql =  "select ima01 from sino.ima_file where ima06 = 'S_FT'";
        $prdno = model('Oracle')->getOracleData($sql);
        //拼接产品名的信息
        $prdno_str = '';
        foreach($prdno as $k => $v)
        {
            if($v['ima01'] === $icl01)
            {
                $select_1 = 'selected="selected"';
            }else
            {
                $select_1 = '';
            }
            $prdno_str .= '<option '.$select_1.' value="'.$v['ima01'].'">'.$v['ima01'].'</option>';
        }
        //获取客户编号
        $sql1 = 'select occ01,occ02 from data.occ_file';
        $agent = model('Oracle')->getOracleData($sql1);
        $agent_str = '';
        foreach($agent as $k1 => $v1)
        {
            if($v1['occ01'] === $ta_icl01){
                $select_2 = 'selected="selected"';
            }else{
                $select_2 = '';
            }
            $agent_str .= '<option '.$select_2.' value="'.$v1['occ01'].'">'.$v1['occ01'].'---'.$v1['occ02'].'</option>';
        }

        return $this->fetch('',[
            'id' => $id,
            'key' => $key,
            'icl01' => $icl01,
            'occ01' => $ta_icl01,
            'prdno' => $prdno_str,
            'agent' => $agent_str,
            'icl05' => $icl05
        ]);
    }
    //修改数据行为
    public function update_icl()
    {
        $data = input('post.','','trim');
        unset($data['ta_icl02']);
        $result = $this->validate($data,[
            'icl05' => 'require',
            'ta_icl01' => 'require'
        ],[
                'icl05.require' => '特殊正印必须填写',
                'ta_icl01.require' => '客户简称必须填写',
            ]
        );
        if(true !== $result)
        {
            echo setServerBackJson(0,$result);exit;
        }

        //判断数据是否修改
        $data_arr = explode(',',$data['key']);
        if($data['icl05'] === $data_arr[2] && $data['ta_icl01'] === $data_arr[3]){
            echo setServerBackJson(0,'当前数据没有修改！');exit;
        }
        //处理ERP数据，以及mysql中数据

        $return = model('ErpIcl')->update_icl($data);
        if($return)
        {
            echo setServerBackJson(1,'特殊正印会写成功！');
        }else
        {
            echo setServerBackJson(0,'会写失败！');
        }


    }



    //查找对应的产品
    public function search_prd(){
        $data = input('post.param','','trim');
        if(empty($data))
        {
            return false;
        }
        $data = strtoupper($data);
         //查询oracle产品数据库
        $sql =  "select ima01 from sino.ima_file where ima06 = 'S_FT' and ima01 like '%".$data."%' and ROWNUM<= 10";
        $prdno = model('Oracle')->getOracleData($sql);
        $prdno_str = '';
        $i = 0;
        foreach($prdno as $k => $v)
        {
            $prdno_str .= '<span style="margin-left:10px;width: 180px;height: 20px;display: inline-block;float: left;text-align: left;"><input  value="'.$v['ima01'].'" type="radio" name="icl01">'.$v['ima01'].'</span>';
            $i++;
            if(is_integer($i/3))
            {
                $prdno_str .='<br>';
            }
        }
        return $prdno_str;

    }


    //查找对应的客户简称
    public function search_agent()
    {
        $data = input('post.param','','trim');
        if(empty($data)){
            return false;
        }
        if (preg_match("/[\x7f-\xff]/", $data))
        {
            $sql1 = "select occ01,occ02 from data.occ_file where occ02 like '%".$data."%' and ROWNUM<=10";
        }else
        {
            $data = strtoupper($data);
            $sql1 = "select occ01,occ02 from data.occ_file where occ01 like '%".$data."%' and ROWNUM<=10";
        }

        $agent = model('Oracle')->getOracleData($sql1);
        $agent_str = '';
        $i = 0;
        foreach($agent as $k => $v)
        {
            $agent_str .= '<span style="margin-left:10px;width: 180px;height: 20px;display: inline-block;float: left;text-align: left;"><input value="'.$v['occ01'].'" type="radio" name="ta_icl01">'.$v['occ01'].'--'.$v['occ02'].'</span>';
            $i++;
            if(is_integer($i/3))
            {
                $agent_str .='<br>';
            }
        }
        return $agent_str;
    }
//
//    //执行插入方法
//    public function update_icl_data(){
//        $data = Db::name('Icl')->field("icl01,icl05,ta_icl01")->select();
//        //循环插入操作l
//        foreach($data as $k => $v){
////            var_dump($v);die;
//            //调用写入方法
//            model('ErpIcl')->insert_icl($v);
//        }
//    }
//
//
//    public function update_name()
//    {
//        $data = Db::name('ErpIcl')->field('id,ta_icl01,name')->select();
//        $data_name = Db::name('Name')->select();
//
//        foreach($data as $key => $val)
//        {
//
//            foreach($data_name as $key1 => $val1)
//            {
//                if($val['ta_icl01'] === trim($val1['js'])){
//                    Db::name('ErpIcl')->where('id',$val['id'])->setField('name',trim($val1['name']));
//                }
//            }
//
//        }
//        var_dump($data);die;
//    }

//      public function get_data_prd(){
//          $sql = "select ima01 from data.ima_file";
//          $data = model('Oracle')->getOracleData($sql);
//          //取出特殊正义的栏位
//          $sql1 = "select distinct icl01 from sw_erp_icl";
//          $arr = Db::query($sql1);
//
//
//          $temp = [];
//          //找出不在上述产品线的产品
//          foreach($arr as $k => $v)
//          {
//              if(in_array($v,$data))
//              {
//                  $temp[] = $v;
//              }
//          }
//
//          var_dump($temp);die;
//
////          var_dump($data);die;
//
//
//      }











}