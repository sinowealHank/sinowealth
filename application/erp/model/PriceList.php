<?php
namespace app\erp\model;
use think\Model;

class PriceList extends Basic{
    //获取Pircelist数据
    protected $table = 'sino.xmf_file';
    private $site = '';
    private $site1 = '';

    public function initialize(){
        $this->site = config('site_name');
        $this->site1 = config('site_name1');
    }


    //取出pricelist数据
    public function get_erp_priceList(){
        
        if($this->site == 'sino'){
            $sql = "SELECT ima01 as prdno,ima021 as pkg,tc_prob06 as description,tc_prob08 as pm
                    FROM ".$this->site.".ima_file p,".$this->site.".tc_prob_file f  where ima06='S_FT' and p.ima09=f.tc_prob06 and ima01 not in (select xmf03 from ".$this->site.".xmf_file)
                    and ima01 = ima133";
        }else{
            $sql = "SELECT ima01 as prdno,ima021 as pkg,tc_prob06 as description,tc_prob08 as pm
                    FROM ".$this->site.".ima_file p,".$this->site.".tc_prob_file f  where ima06='4_FT' and p.ima09=f.tc_prob06 and ima01 not in (select xmf03 from ".$this->site.".xmf_file)
                    and ima01 = ima133";
        }
        $result_arr = model('Oracle')->getOracleData($sql);

        
        //把键值变为小写
        $erp_arr = [];
        foreach($result_arr as $val){
            $erp_arr[] = array_change_key_case($val,CASE_LOWER);
        }



        //取出产品信息
        $check_data = [];
        foreach($erp_arr as $key => &$val){
            $check_data[]['prdno'] = $val['prdno'];
        }
        //查找mysql中的数据
         $data = model('ErpCheckPriceList')->field('prdno')->where('check_flow',1)->where('status',1)->select();
         $mysql_data = [];
         foreach($data as $val){
             $mysql_data[] = $val->toArray();
         }

        //获取最新的pricelist数据
        $update_data = [];
        foreach($check_data as $k){
             if(!in_array($k,$mysql_data)){
                 foreach($erp_arr as $k1){
                     if($k['prdno'] == $k1['prdno']){
                         $update_data[] = $k1;
                     }
                 }
             }
        }

        //更新到mysql中去进行处理
        if(!empty($update_data)){
            try {
                model('ErpCheckPriceList')->insertAll($update_data);
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
    }

    public function insert_erp_price($data){
        $date = date("Y-m-d",time());
        $date = "'".$date."'";
        //插入rmb的记录
        $sql1 = "insert into ".$this->site.".xmf_file(xmf01,xmf03,ta_xmf09,Ta_xmf01,Ta_xmf02,Ta_xmf03,Ta_xmf08,xmf02,xmf07,Ta_xmf11,Ta_xmf06,Ta_xmf07,Ta_xmf04,Ta_xmf05,xmf05,xmf04)
                values ('SINO',"."'".$data['prdno']."'".","."'".$data['mk']."'".","."'".$data['lt']."'".","."'".$data['moq']."'".","."'".$data['pack']."'".","."'".$data['refund_qty']."'"."
                ,'RMB',"."'".$data['prd_rmb_price_vat']."'".","."'".$data['prd_code_rmb_price_vat']."'".","."'".$data['d_agent_sales']."'".","."'".$data['x_agent_sales']."'".",
               "."'".$data['d_market_sales']."'".","."'".$data['x_market_sales']."'".",to_date($date,'yyyy-mm-dd'),'EA')";
//        var_dump($sql1);die;
        $result1 = exec_oracle($sql1);
        if($result1 == false){
            echo setServerBackJson(0,"回写ERP数据失败");exit;
        }
//        //更新ima_file信息
        $ima_sql = "update ".$this->site.".ima_file set ima10 ='".$data['ima10']."',imaud07 = '".$data['imaud07']."'";
        $ima_result =  exec_oracle($ima_sql);
        if($result1 == false){
            echo setServerBackJson(0,"回写ERP数据失败");exit;
        }
////
//        //处理是否插入外销
        if($data['prd_code_rmb_price_vat'] === "0.0000"){
            $data['prd_code_rmb_price_vat'] = "";
        }
        if($data['prd_code_rmb_price_vat']){
            //插入usd的记录
            $sql2 = "insert into ".$this->site1.".xmf_file(xmf01,xmf03,ta_xmf09,Ta_xmf01,Ta_xmf02,Ta_xmf03,Ta_xmf08,xmf02,xmf07,Ta_xmf11,Ta_xmf06,Ta_xmf07,Ta_xmf04,Ta_xmf05,xmf05,xmf04)
                     values ('SINO',"."'".$data['prdno']."'".","."'".$data['mk']."'".","."'".$data['lt']."'".","."'".$data['moq']."'".","."'".$data['pack']."'".","."'".$data['refund_qty']."'"."
                    ,'USD',"."'".$data['prd_usd_price']."'".","."'".$data['prd_code_usd_price']."'".","."'".$data['d_agent_sales']."'".","."'".$data['x_agent_sales']."'".",
                   "."'".$data['d_market_sales']."'".","."'".$data['x_market_sales']."'".",to_date($date,'yyyy-mm-dd'),'EA')";
            $result2 = exec_oracle($sql2);
            if($result2 == false){
                echo setServerBackJson(0,"回写ERP数据失败");exit;
            }
        }
        //写入维护表中
        $pro_data['ima131'] = $data['description'];
        $pro_data['imaud07'] = $data['imaud07'];
        $pro_data['prd_rmb_price'] = $data['prd_rmb_price'];
        $pro_data['prd_usd_price'] = $data['prd_usd_price'];
        $pro_data['ima10'] = $data['ima10'];
        $pro_data['prd_code_rmb_price'] = $data['prd_code_rmb_price'];
        $pro_data['prd_code_usd_price'] = $data['prd_code_usd_price'];
        $pro_data['xmf03'] = $data['prdno'];
        $pro_data['xmf07'] = $data['prd_rmb_price_vat'];
        $pro_data['ta_xmf11'] = $data['prd_code_rmb_price_vat'];
        $pro_data['ta_xmf01'] = $data['lt'];
        $pro_data['ta_xmf02'] = $data['moq'];
        $pro_data['ta_xmf03'] = $data['pack'];
        $pro_data['ta_xmf08'] = $data['refund_qty'];
        $pro_data['ta_xmf04'] = $data['d_market_sales'];
        $pro_data['ta_xmf05'] = $data['x_market_sales'];
        $pro_data['ta_xmf06'] = $data['d_agent_sales'];
        $pro_data['ta_xmf07'] = $data['x_agent_sales'];
        unset($pro_data['id']);
        //插入维护表
        $result = model('ErpPrice')->insert($pro_data);
        if($result == false){
            echo setServerBackJson(0,"维护表插入失败");exit;
        }

        //改变当前的mysql中的数据情况
         model('ErpCheckPriceList')->where('id',$data['id'])->setField('status',0);
         echo setServerBackJson(1,'回写ERP成功');exit;
    }

    /**
     * 同步oracle数据到mysql中
     * @return array
     */
//imaud10
//    public function get_price_list_data(){
//
//        $sql = "select a.xmf01,a.xmf02,a.xmf03,a.xmf04,a.xmf05,a.xmf06,a.xmf07,a.xmf08,a.ta_xmf01,a.ta_xmf02,a.ta_xmf03,a.ta_xmf04,a.ta_xmf05,
//                a.ta_xmf06,a.ta_xmf07,a.ta_xmf08,a.ta_xmf11,b.Imaud07,b.ima27,b.imaud10,b.ima131,b.ima10,b.ima11 from
//                ".$this->site.".xmf_file a,".$this->site.".ima_file b where a.xmf03=b.ima01";
//        $erp_data = model('Oracle')->getOracleData($sql);
//
//        //取出产品数据
//        $check_data = [];
//        foreach($erp_data as $val){
//            $temp['xmf03'] = $val['xmf03'];
////            $temp['imaud10'] = $val['imaud10'];
////            $check_data[]['xmf03'] = $val['xmf03'];
////            $check_data[]['imaud10'] = $val['imaud10'];
//            $check_data[] = $temp;
//
//        }
//
////        var_dump($check_data);die;
//
//        //查找mysql中的数据
//        $data = model('ErpPrice')->field('xmf03')->select();
////        $data = model('ErpPrice')->field('id,xmf03,imaud10')->select();
////        if(!empty($data)){
////
////        }
//        $mysql_data = [];
//        foreach($data as $v){
//            $mysql_data[] = $v->toArray();
//        }
////        var_dump($mysql_data);die;
//
//        //获取最新的pricelist数据
//        $update_data = [];
//        foreach($check_data as $k){
//            if(!in_array($k,$mysql_data)){
//                foreach($erp_data as $k1){
//                    if($k['xmf03'] == $k1['xmf03']){
//                        $update_data[] = $k1;
//                    }
//                }
//            }
//        }
//
//        //更新到mysql中去进行处理
//        if(!empty($update_data)){
//            try {
//                model('ErpPrice')->insertAll($update_data);
//            } catch (\Exception $e) {
//                throw new \Exception($e->getMessage());
//            }
//        }
//
//    }



    public function get_price_list_data(){
//        $sql = "select a.xmf01,a.xmf02,a.xmf03,a.xmf04,a.xmf05,a.xmf06,a.xmf07,a.xmf08,a.ta_xmf01,a.ta_xmf02,a.ta_xmf03,a.ta_xmf04,a.ta_xmf05,
//                a.ta_xmf06,a.ta_xmf07,a.ta_xmf08,a.ta_xmf11,b.Imaud07,b.ima27,b.imaud10,b.ima131,b.ima10,b.ima11 from
//                ".$this->site.".xmf_file a,".$this->site.".ima_file b where a.xmf03=b.ima01";

        $sql = "select a.xmf01,a.xmf02,a.xmf03,a.xmf04,a.xmf05,a.xmf06,a.xmf07,a.xmf08,a.ta_xmf01,a.ta_xmf02,a.ta_xmf03,a.ta_xmf04,a.ta_xmf05,
                a.ta_xmf06,a.ta_xmf07,a.ta_xmf08,a.ta_xmf11,b.Imaud07,b.ima27,b.imaud10,b.ima131,b.ima10,b.ima11,rtrim(to_char(c.xmf07,'fm9999990.9999'),'.') as c_xmf07,rtrim(to_char(c.ta_xmf11,'fm9999990.9999'),'.') as c_ta_xmf11 from
                t_shsino.xmf_file a,t_shsino.ima_file b where a.xmf03=b.ima01";
//        var_dump($sql);die;
        $erp_data = model('Oracle')->getOracleData($sql);
//        var_dump($erp_data);die;

        //取出产品数据
        $check_data = [];
        foreach($erp_data as $val){
            $temp['xmf03'] = $val['xmf03'];
            $temp['imaud10'] = $val['imaud10'];
            $check_data[] = $temp;
        }

        //查找mysql中的数据
        $data = model('ErpPrice')->field('xmf03,imaud10')->select();
        $mysql_data = [];
        foreach($data as $v){
            $mysql_data[] = $v->toArray();
        }

        //获取产品数据
        $prdno_data = [];
        foreach($mysql_data as $val_sql_data){
            $prdno_data[]['xmf03'] = $val_sql_data['xmf03'];
        }


        //获取最新的pricelist数据
        $update_data = [];
        foreach($check_data as $k){
            if(!in_array($k,$mysql_data)){
                foreach($erp_data as $k1){
                    if($k['xmf03'] == $k1['xmf03']){
                        $update_data[] = $k1;
                    }
                }
            }
        }

        if(!empty($data)){
            //处理更新的数据
            foreach($update_data as $k2 => $v2){
                //判断是否是更新和插入数据库
                $arr['xmf03'] = $v2['xmf03'];
                if(in_array($arr,$prdno_data)){
                    //更新原有数据
                    try{
                        model('ErpPrice')->where('xmf03',$v2['xmf03'])->setField('imaud10',$v2['imaud10']);
                    }catch (\Exception $e){
                        throw new \Exception($e->getMessage());
                    }
                }else{
                    //增加新的数据
                    try{
                        model('ErpPrice')->insert($v2);

                    }catch (\Exception $e){
                        throw new \Exception($e->getMessage());
                    }

                }
            }
        }else{
            //更新到mysql中去进行处理
        if(!empty($update_data)){
            try {
               model('ErpPrice')->insertAll($update_data);
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }

        }

    }






    //行内编辑修改ERP数据
    public function update_erp_data($data,$ima01){
//        var_dump(each($data));
        //取出修改定价或者带codeRMB单价

        if($data['ima10'] === 'SH'){
           $database = $this->site;
        }else{
           $database = $this->site1;
        }

        foreach($data as $k => $v){
             if(strpos($k,'ima') !== false){
                 $sql = "update ".$database.".ima_file set ".$k."="."'".$v."' where ima01 ='".$ima01."'";
                 $result = exec_oracle($sql);
             } else{
                 $sql = "update ".$database.".xmf_file set ".$k."="."'".$v."' where xmf03 ='".$ima01."'";
                 if($k === "xmf07"){
                     //修改外销的价格
                     $v1 = number_format(($v/1.17)/6.7,4);
                     $sql1 ="update ".$this->site1.".xmf_file set ".$k."="."'".$v1."' where xmf03 ='".$ima01."'";
                     $result1 = exec_oracle($sql1);
                     if(!$result1){
                         echo setServerBackJson(0,"回写外销失败！");exit;
                     }
                 }else if($k === 'ta_xmf11'){
                     $v1 = number_format(($v/1.17)/6.7,4);
                     $sql1 ="update ".$this->site1.".xmf_file set ".$k."="."'".$v1."' where xmf03 ='".$ima01."'";
                     $result1 = exec_oracle($sql1);
                     if(!$result1){
                         echo setServerBackJson(0,"回写外销失败！");exit;
                     }
                 }
                 $result = exec_oracle($sql);
             }
            //为0的状态
            if($result == 0){
                echo setServerBackJson(0,"对应库没有产品信息！");exit;
            }

            if(!$result){
                echo setServerBackJson(0,"回写ERP失败！");exit;
            }
            return $result;
      }
    }


    //写入数据库
    public function insert_oracel_data($sql){
        $config_oracle_info = $this->connection;
        $conn = oci_connect($config_oracle_info['username'],$config_oracle_info['password'],$config_oracle_info['hostname']."/".$config_oracle_info['database']);
        if (!$conn) {
            $e = oci_error();
            print htmlentities($e['message']);
            exit;
        }

        $ora_test = oci_parse($conn,$sql); //编译sql语句
        try{
            oci_execute($ora_test); //执行
        }catch (\Exception $e){
            throw new \Exception($e->getMessage());
        }
    }













}