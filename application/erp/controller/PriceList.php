<?php
namespace app\erp\controller;
use think\Controller;
use think\Db;
use think\Session;
use app\index\controller\Admin;
class PriceList extends admin{
    //显示pricelist页面
    public function index(){
        //判断登录人的身份、用户配置
        $login_id = Session::get('login_id');
        $auth_user_type = Db::name('ErpUserType')->where('user_id',$login_id)->value('user_type');
//        $dep_id = Db::name('Sys_user')->where('id',$login_id)->value('dep_id');
        $user_type = '';
        //PP部门
        if($auth_user_type == 1){
            $user_type = 1;
        //mcu事业部
        }else if($auth_user_type == 2){
            $user_type = 2;
        //SD
        }else if($auth_user_type == 4){
            $user_type = 3;
        }
        //同步ERP数据
//        model("PriceList")->get_price_list_data();

        return $this->fetch('',[
            'user_type' => $user_type
        ]);
    }
    //查询Pricelist的数据
    public function get_price_data(){
        //取出当前的PM人员设置
        $login_id = session('login_id');
        $user_type_data = Db::name('ErpUserType')->field('prd_line line,user_type')->where('user_id',$login_id)->find();
        $map = [];
        if($user_type_data['user_type'] == 2){
//            $line = explode(',',$user_type_data['line']);
            $map['ima131'] = ["in",$user_type_data['line']];
//            var_dump($map['ima131']);
        }
        //查询多少条数据
        $xmf03 =  isset($_POST['param']) ? $_POST['param'] : '';
        $ima131=  isset($_POST['line']) ? $_POST['line'] : '';

        $sort = isset($_POST['sort']) ? $_POST['sort'] : 'id';
        $order = isset($_POST['order']) ? $_POST['order'] : 'desc';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $rows = isset($_POST['rows']) ? intval($_POST['rows']) : 20;
        //查询条件
        $map['xmf03'] = ['like','%'.trim($xmf03).'%'];
        if($user_type_data['user_type']  != 2){
            $map['ima131'] = ['like','%'.trim($ima131).'%'];
        }
        $offset = ($page-1)*$rows;

        //取出审核表已回写ERP的数据
//        $check_data = Db::name('ErpCheckPriceList')->field('id,prdno,prd_rmb_price,prd_usd_price,prd_code_rmb_price,prd_code_rmb_price_vat,prd_code_usd_price')
//                     ->where('check_flow',2)->where('status',0)->select();
//        var_dump($map);
        $Pri_data = Db::name('ErpPrice')->where($map)->limit($offset,$rows)->order($sort,$order)->select();
//        echo Db::name('ErpPrice')->getLastsql();

//        foreach($Pri_data as $key => &$val){
//            foreach($check_data as $key1 => $val1){
//                if($val1['prdno'] === $val['xmf03']){
//                    $val['prd_rmb_price'] = $val1['prd_rmb_price'];
//                    $val['prd_usd_price'] = $val1['prd_usd_price'];
//                    $val['prd_code_rmb_price'] = $val1['prd_code_rmb_price'];
//                    $val['prd_code_rmb_price_vat'] = $val1['prd_code_rmb_price_vat'];
//                    $val['prd_code_usd_price'] = $val1['prd_code_usd_price'];
//                }
//            }
//        }
        $count =  Db::name('ErpPrice')->count();
        $qq  = $Pri_data;
        $qty = $count;
        $json = '{"total":'.$qty.',"rows":'.json_encode($qq).'}';
        echo $json;

    }

    //显示修改页面
//    public function edit_price($id){
//        //获取最新的pricelist数据同步到mysql中去(pp部门人员打开页面)
//        $login_id = Session::get('login_id');
//        $user_type_data = model('ErpUserType')->where('user_id',$login_id)->find()->toArray();
//        //取出当前的数据
//        $price_data = model('ErpPrice')->get_price_data($id);
//        return $this->fetch('',
//            [
//                'price_data'=> $price_data,
//                'user_type' => $user_type_data['user_type']
//            ]
//        );
//    }


    //存储修改的数据
    public function save_price(){
        $data = input('post.','','trim');

        if(isset($data['ima10'])){
            if(empty($data['ima10'])){
                echo setServerBackJson(0,'归属地不合法！');exit;
            }
            $product_site = ['SH','HK','XA'];
            if(!in_array($data['ima10'],$product_site)){
                echo setServerBackJson(0,'归属地不合法！');exit;
            }
        }

        $ima01 = $data['ima01'];
        $id = $data['id'];
        unset($data['id']);
        unset($data['ima01']);
        $field = array_keys($data)[0];
        if(!$field){
            exit;
        }
        //会写到erp中去
        $erp_data = $data;
        $erp_result = model('PriceList')->update_erp_data($erp_data,$ima01);
        //var_dump($erp_result);die;
        if($erp_result){
             //判断是否修改
            foreach($data as $k => $v){
                if($k === "xmf07"){
                    $prd_rmb_price = ($v/1.17);
                    $prd_rmb_price = number_format($prd_rmb_price,4);
                    $prd_usd_price = $prd_rmb_price/6.7;
                    $prd_usd_price = number_format($prd_usd_price,4);
                    //更新本地数据
                    $result = model('ErpPrice')->where('id',$id)->update($data);
                    if($result === false){
                        echo setServerBackJson(0,"MYSQL中数据更新错误");exit;
                    }
                    //更新数据
                    $update['prd_rmb_price'] = $prd_rmb_price;
                    $update['prd_usd_price'] = $prd_usd_price;
//                    $result =  Db::name("ErpCheckPriceList")->where("prdno",$ima01)->setField("prd_rmb_price",$prd_rmb_price);
                    $result =  Db::name("ErpPrice")->where("id",$id)->update($update);
//                    echo Db::name("ErpPrice")->getLastSql();die;
                    if(!$result){
                        echo setServerBackJson(0,"标准品RMB不含税更新异常");exit;
                    }
                }else if($k === "ta_xmf11"){
                    $prd_code_rmb_price = ($v/1.17);
                    $prd_code_rmb_price =  number_format($prd_code_rmb_price,4);
                    $prd_code_usd_price = $prd_code_rmb_price/6.7;
                    $prd_code_usd_price  = number_format($prd_code_usd_price,4);
                    //更新本地数据

                    $result = model('ErpPrice')->where('id',$id)->update($data);

                    if($result === false){
                        echo setServerBackJson(0,"MYSQL中数据更新错误");exit;
                    }
                    //更新数据
                    $update['prd_code_rmb_price'] = $prd_code_rmb_price;
                    $update['prd_code_usd_price'] = $prd_code_usd_price;
                    $result =  Db::name("ErpPrice")->where("id",$id)->update($update);
                    if(!$result){
                        echo setServerBackJson(0,"带CodeRMB标准价格(不含税)更新异常");exit;
                    }
                }else{
                    $result = model('ErpPrice')->where('id',$id)->update($data);
                    if($result === false){
                        echo setServerBackJson(0,"MYSQL中数据更新错误");exit;
                    }
                }
            }
            //日志信息的记录
             $comment = model('ImaFile')->get_filed_comment($field);
             if($field === 'imaud10'){
                 $log['type'] = 1;
             }
             $date = date("Y-m-d H:i:s",time());
             //写入日志信息。
             $log['pri_id'] = input('post.id');
             $log['user_name'] = Db::name('SysUser')->where('id',Session::get('login_id'))->value('nickname');
             $log['log'] = $date.' '.$log['user_name'].'将'.$comment.'改为<span style="color:red">'.$data[$field].'</span>';
             $log['create_time'] = time();
             $logResult = Db::name('ErpProPriceLog')->insert($log);
             if(!$logResult){
                 echo setServerBackJson(0,"日志写入错误!");exit;
             }
             echo setServerBackJson(1,"数据更新成功");exit;

        }else{
             echo setServerBackJson(0,"修改失败");
        }
    }

//    //批量修改预见库存
//    public function edit_batch($id){
//        $id_str = $id;
//        $count = strlen($id_str);
//        return $this->fetch('',[
//            'count' => $count,
//            'id_str'=> $id_str
//        ]);
//    }

    //修改预见库存，以及修改erp的预见库存
    public function save_batch(){
        $id_str = input('post.id','','trim');
        $imaud10 =  input('post.param','','trim');
        //先修改mysql的数据
        $edit_mysql_result = Db::name('ErpPrice')->where('id','in',$id_str)->setField('imaud10',$imaud10);
        //取出对应的产品
        $ima01 = Db::name('ErpPrice')->where('id','in',$id_str)->field('xmf03')->select();
        if($edit_mysql_result === false){
            echo setServerBackJson(0,"更新mysql中预见库存失败");exit;
        }
        foreach($ima01 as &$val){
            $val['imaud10'] = $imaud10;
        }
        //插入erp的数据库
        model('ImaFile')->update_erp($ima01);
        echo setServerBackJson(1,"ERP数据已成功修改");


    }
    //显示日志信息
    public function showlog($id){
        $log_result = Db::name('ErpProPriceLog')->where('type',1)->field('log')->order('create_time desc')->where('pri_id',$id)->select();
        $log = '';
        foreach($log_result as $v){
            $log .= $v['log'].'<br>';
        }
        echo $log;
    }














}