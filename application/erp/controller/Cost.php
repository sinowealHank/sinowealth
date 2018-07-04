<?php
namespace app\erp\controller;
use think\Controller;
use think\Db;
use think\Session;

use app\index\controller\Admin;
class Cost extends Admin{
    public function index(){
//        $sql = "select distinct(ima01) as prd_name from data.ima_file where ima06 = '0_BODY'";
//        $body = model('Oracle')->getOracleData($sql);

        $b_data = ['B_SS323','B_SS388','B_SS370','B_SS413','B_SS396','B_SS406'];
        $body = [];
        foreach($b_data as $v_b_data)
        {
            $body[]['prd_name'] = $v_b_data;
        }

//        var_dump($body);die;
        //BODY层
        foreach($body as &$val)
        {
            $val['level'] = 1;
            $val['children'] = [];
            $val['name'] = $val['prd_name'];
            unset($val['prd_name']);
        }
        //WF层
        foreach($body as &$val1)
        {

            $sql1 = "select IMA01 as prd_name from DATA.IMA_FILE A,DATA.IMAICD_FILE B where IMA06='1_WF' AND A.IMA01=B.IMAICD00 AND IMAICD01='".$val1['name']."'";
            $wf = model('Oracle')->getOracleData($sql1);
            if(!$wf){
               continue;
            }
            foreach($wf as &$val_wf)
            {
                $val_wf['name'] = $val_wf['prd_name'];
                unset($val_wf['prd_name']);
            }
            $val1['children'] = $wf;
            //CP层
            foreach($val1['children'] as &$val_cp)
            {
                $val_cp['level'] = 2;
                $sql2 = "SELECT BMB01 as prd_name FROM DATA.BMB_FILE WHERE BMB03 = '".$val_cp['name']."' ";
                $cp = model('Oracle')->getOracleData($sql2);
                if(!$cp){
                    continue;
                }
                foreach($cp as &$val_cp_1)
                {
                    $val_cp_1['name'] = $val_cp_1['prd_name'];
                    unset($val_cp_1['prd_name']);
                }
                $val_cp['children'] = $cp;

                //PKG段
                foreach($val_cp['children'] as &$val_pkg)
                {
                    $val_pkg['level'] = 3;
                    $sql3 = "SELECT BMB01 as prd_name FROM DATA.BMB_FILE WHERE BMB03 = '".$val_pkg['name']."'";
                    $pkg = model('Oracle')->getOracleData($sql3);

                    if(!$pkg){
                        continue;
                    }

                    foreach($pkg as &$val_pkg_1)
                    {
                        $val_pkg_1['name'] = $val_pkg_1['prd_name'];
                        unset($val_pkg_1['prd_name']);
                    }
                    $val_pkg['children'] = $pkg;
                    //FT段
                    foreach($val_pkg['children'] as &$val_ft)
                    {
                        $val_ft['level'] = 4;
                        $sql4 = "SELECT BMB01 as prd_name FROM DATA.BMB_FILE WHERE BMB03 = '".$val_ft['name']."'";
                        $ft = model('Oracle')->getOracleData($sql4);

                        if(!$ft){
                            continue;
                        }

                        foreach($ft as &$val_ft_1)
                        {
                            $val_ft_1['level'] = 5;
                            $val_ft_1['name'] = $val_ft_1['prd_name'];

                            unset($val_ft_1['prd_name']);
                        }
                        $val_ft['children'] = $ft;
//                        //最终段
                        foreach($val_ft['children'] as &$val_fl)
                        {
                            $sql5 = "SELECT BMB01 as prd_name FROM DATA.BMB_FILE WHERE BMB03 = '".$val_fl['name']."'";
                            $fl = model('Oracle')->getOracleData($sql5);
                            if(!$fl){
                                continue;
                            }
                            foreach($fl as &$val_fl_1)
                            {
                                $val_fl_1['level'] = 6;
                                $val_fl_1['name'] = $val_fl_1['prd_name'];
                                unset($val_fl_1['prd_name']);
                            }
//                            var_dump($val_ft);

                            $val_fl['children'] = $fl;

                        }
                    }
                }
            }
        }
        $json_body = json_encode($body);
        return $this->fetch('',[
            'body'=> $json_body
        ]);
    }
//    public function index()
//    {
//        $sql = "select distinct(ima01) as prd_name from data.ima_file where ima06 = '0_BODY'";
//        $body = model('Oracle')->getOracleData($sql);
//
//        $_body_str = '';
//        foreach($body as $val_body)
//        {
//            $_body_str .= "'".$val_body['prd_name']."'".",";
//        }
//        $_body_str = rtrim($_body_str,',');
//        $_body_str = ltrim($_body_str,"'");
//        $_body_str = rtrim($_body_str,"'");
////        var_dump($_body_str);die;
//
//
//        $sql1 = "select IMA01 as prd_name,IMAICD01 from DATA.IMA_FILE A,DATA.IMAICD_FILE B  where IMA06='1_WF' AND A.IMA01=B.IMAICD00 AND IMAICD01 in('".$_body_str."') ";
////        var_dump($sql1);die;
//        $wf = model('Oracle')->getOracleData($sql1);
////        var_dump($wf);die;
//
//        $body_ = [];
//        $wf_ = [];
//
//
//        //取出所有的body
//        //以及所有的wf
//
//        foreach($wf as $val)
//        {
//            if(!in_array($val['imaicd01'],$body_)){
//                $body_[] = $val['imaicd01'];
//            }
//            if(!in_array($val['prd_name'],$wf_)){
//                $wf_[] = $val['prd_name'];
//            }
//
//
//        }
//
//
//        //
//        $_wf_str = '';
//        foreach($wf_ as $val_wf)
//        {
//            $_wf_str .= "'".$val_wf."'".",";
//        }
//        $_wf_str = rtrim($_wf_str,',');
//        $_wf_str = ltrim($_wf_str,"'");
//        $_wf_str = rtrim($_wf_str,"'");
//
//
//        $res = [];
//        foreach($body_ as  $k => $v1)
//        {
//            $res[$k]['name'] = $v1 ;
//            foreach($wf as $v2){
//                if($v2['imaicd01'] === $v1){
//                    $res[$k]['children'][]['name'] = $v2['prd_name'];
//                }
//            }
//
//        }
////        var_dump($res[1]);die;
//        // $res body->wf
//
//        $sql3 = "SELECT BMB01,BMB03 as prd_name FROM DATA.BMB_FILE WHERE BMB03 in ('".$_wf_str."') ";
//        $cp = model('Oracle')->getOracleData($sql3);
////        var_dump($cp);die;
//
//
//
//        //cp字符串
//        $_cp_str = '';
//        foreach($cp as $val_cp)
//        {
//
//            $_cp_str .= "'".$val_cp['bmb01']."'".",";
//        }
//        $_cp_str = rtrim($_cp_str,',');
//        $_cp_str = ltrim($_cp_str,"'");
//        $_cp_str = rtrim($_cp_str,"'");
//
//        //查找PKG段
//        $sql4 = "SELECT BMB01,BMB03 as prd_name FROM DATA.BMB_FILE WHERE BMB03 in ('".$_cp_str."')";
//        $pkg = model('Oracle')->getOracleData($sql4);
//
//
//        //pkg字符串
//        $_pkg_str = '';
//        foreach($pkg as $val_pkg)
//        {
//
//            $_pkg_str .= "'".$val_pkg['bmb01']."'".",";
//        }
//        $_pkg_str = rtrim($_pkg_str,',');
//        $_pkg_str = ltrim($_pkg_str,"'");
//        $_pkg_str = rtrim($_pkg_str,"'");
//
//        //FT段。。
//        $sql5 = "SELECT BMB01,BMB03 as prd_name FROM DATA.BMB_FILE WHERE BMB03 in ('".$_pkg_str."')";
//        $ft = model('Oracle')->getOracleData($sql5);
////        var_dump($ft);die;
//
//
//
//
//        //pkg字符串
//        $_ft_str = '';
//        foreach($ft as $val_ft)
//        {
//            $_ft_str .= "'".$val_ft['bmb01']."'".",";
//        }
//        $_ft_str = rtrim($_ft_str,',');
//        $_ft_str = ltrim($_ft_str,"'");
//        $_ft_str = rtrim($_ft_str,"'");
//
////        var_dump($_ft_str);die;
//
//        //查找最后一段
//        $sql6 =  "SELECT BMB01,BMB03 as prd_name FROM DATA.BMB_FILE WHERE BMB03 in ('".$_ft_str."')";
////        var_dump($sql6);die;
//        $final = model('Oracle')->getOracleData($sql6);
////        var_dump($final);die;
//
//
//
//
//        //
//        foreach($res as $k2 => &$v2)
//        {
//            foreach($v2['children'] as $k3 => $v3)
//            {
//                foreach($cp as $k4 => $v4)
//                {
//                    if($v3['name'] === $v4['prd_name']){
//                        $v2['children'][$k3]['children'][]['name'] =  $v4['bmb01'];
////                       var_dump($v2['children'][$k3]['children']);die;
////                       var_dump($v2['children'][$k3]['children'][$k4]['title']);die;
//
//
//                    }
//                }
//            }
//        }
////        var_dump($res[0]['children'][0]['children']);die;
//        //pkg段
//        foreach($res as $k_1 => &$v_1){
//            $v_1['level'] = 1;
////            var_dump($v_1);die;
//            if(isset($v_1['children'])){
//                foreach($v_1['children'] as $k_2 => &$v_2) {
//                    $v_2['level'] = 2;
//                    if(isset($v_2['children']))
//                    {
//                        foreach($v_2['children'] as $k_3 => &$v_3)
//                        {
//                            foreach($pkg as $k_4 => &$v_4)
//                            {
//                                $v_3['level'] = 3;
//                                if($v_3['name'] == $v_4['prd_name']){
//                                    $v_3['children'][]['name'] = $v_4['bmb01'];
//                                    foreach($v_3['children'] as $k_5 => &$v_5)
//                                    {
//                                        $v_5['level'] = 4;
//                                        foreach($ft as $k_6 => $v_6)
//                                        {
//                                            if($v_5['name'] === $v_6['prd_name']){
//                                                $v_5['children'][]['name'] = $v_6['bmb01'];
//                                                foreach($v_5['children'] as $_7 => &$v_7)
//                                                {
//                                                    $v_7['level'] = 5;
////                                                    var_dump($v_7);die;
//                                                    foreach($final as $k_8 => $v_8)
//                                                    {
//                                                        if($v_7['name'] === $v_8['prd_name']){
//                                                            $v_7['children'][]['name'] = $v_8['bmb01'];
//                                                            foreach($v_7['children'] as $k_9 => &$v_9)
//                                                            {
//                                                                $v_9['level'] = 6;
//                                                            }
//                                                        }
//                                                    }
//                                                }
//                                            }
//                                        }
//                                    }
//                                }
//                            }
//                        }
//                    }
//                }
//            }
//        }
//
//        $json_body = json_encode($res);
//        return $this->fetch('',[
//            'body'=> $json_body
//        ]);
//
//
//
//
//    }
    /**\
     *  获得供应商信息  2018/3/4 周仁杰修改
     */
//        public function get_agent(){
//            $level = input('post.level','','trim');
//            $name = input('post.name','','trim');
//            $data = [];
////            $name = "SS486A_CP";
//            if($level == '2')
//            {
//                //查找fabName信息
//                $sql_pmj01 = "select PMJ07, PMJ01 from PMJ_FILE where PMJ03 = '".$name."'";
//                $pmj01 = model('Oracle')->getOracleData($sql_pmj01);
//                $pmj01[1] = [
//                    'pmj07' => 2989,
//                    'pmj01' => 'P51-1712290004'
//                ];
//                //查找单尾信息
//                $map = [];
//                $data = [];
//                foreach($pmj01 as $key => $val)
//                {
//                    $sql_pmi = "select PMI03 from PMI_FILE where PMICONF = 'Y' and PMI01 = '".$val['pmj01']."'";
//                    $pmi_data = model('Oracle')->getOracleData($sql_pmi);
//                    var_dump($pmi_data);die;
//                    //查找FAB PROCES
//                    $sql_IMA021 = "select IMA021 from IMA_FILE where IMA01 = '".$name."'";
//                    $FAB_PROCES = model('Oracle')->getOracleData($sql_IMA021);
//                    //核价档信息
//                    $wg_price = $val['pmj07'];
//                    //GROSS DIE信息
//                    $sql_gross_die = "select IMAICD14,IMAICD04 from DATA.IMAICD_FILE where IMAICD00 = '".$name."'";
//                    $IMAICD14_result = model('Oracle')->getOracleData($sql_gross_die);
//                    $IMAICD14 = $IMAICD14_result[0]['imaicd14'];
//                    //生产阶段
//                    $imaicd04 = $IMAICD14_result[0]['imaicd04'];
//                    //WF PRICE DIE 信息
//                    $wf_price_die = ($wg_price/$IMAICD14);
//                    $wf_price_die = round($wf_price_die,4);
//                    //查询MYSQL的有没有此供应商相关信息相关信息
//                    $Erp_ct_result = Db::name('ErpCtFac')->where('status',1)->where('prd_no',$name)->where('imaicd04',$imaicd04)->where('fac_name',$pmi_data[0]['pmi03'])->find();
//                    if($Erp_ct_result)
//                    {
//                        //准备相关信息
//                        if($Erp_ct_result['is_release'] == 1)
//                        {
//                            $temp['fab_release'] = 'Y';
//                        }else
//                        {
//                            $temp['fab_release'] = 'N';
//                        }
//                        $temp['fab_release'] =
//                        $temp['PM'] = $Erp_ct_result['PM']?$Erp_ct_result['PM']:'';
//                        $temp['UM'] = $Erp_ct_result['UM']?$Erp_ct_result['UM']:'';
//                    }else
//                    {
//                        //插入相关的信息
//                        $insert_data['prd_no'] = $name;
//                        $insert_data['imaicd04'] = $imaicd04;
//                        $insert_data['fac_name'] = $pmi_data[0]['pmi03'];
//                        $insert_data['status'] = 1;
//                        $result = Db::name('ErpCtFac')->insert($insert_data);
//                        if(!$result)
//                        {
//                            echo setServerBackJson(0,"mysql数据同步错误");exit;
//                        }
//                        //准备展示数据
//                        $temp['fab_release'] = '';
//                        $temp['PM'] = '';
//                        $temp['UM'] = '';
//
//                    }
//                    $temp['fab_name'] = $pmi_data[0]['pmi03'];
//                    $temp['fab_proces'] = $FAB_PROCES[0]['ima021'];
//                    $temp['wf_price'] = $wg_price;
//                    $temp['wf_price_die'] = $wf_price_die;
//                    $temp['groces_dire'] = $IMAICD14;
//                    $temp['level'] = $imaicd04;
//                    $temp['name'] = $name;
//                    $data[] = $temp;
//                    //ERP数据的保存
//                    $map[] = [
//                        'prd_no' => $name,
//                        'imaicd04' => $imaicd04,
//                        'fac_name' => $pmi_data[0]['pmi03']
//                    ];
//
//                }
//                //同步原有数据
//                $this->SynchronousData($map);
//
//            }else{
//                $this->getSupplierInfo($name,$level);
//
//                $sql = "select PMH02 from PMH_FILE where PMH01 = '".$name."'";
//                $sql_pmh02 = model('Oracle')->getOracleData($sql);
//                $html = '';
//                foreach($sql_pmh02 as $k => $v)
//                {
//
//                    $html .= "<tr><td>".$v['pmh02']."</td></tr>";
//                }
//
//            }
//            return json_encode($data);
//        }

    //获得BOM节点数据
    public  function get_agent()
    {
        $level = input('post.level','','trim');
        $name = input('post.name','','trim');
        $data = [];
        if($level == '2')
        {
//            //查找对应的供应商信息
//            $sql = "select distinct PMH02 as supplier_name from PMH_FILE where PMH01 = '".$name."'";
//            $SupplierInfo = model('Oracle')->getOracleData($sql);
//            //查找对应的阶段的信息
//            foreach($SupplierInfo as $k => $v)
//            {
//
//                //查找FAB PROCES
//                $sql_IMA021 = "select IMA021 from IMA_FILE where IMA01 = '".$name."'";
//                $FAB_PROCES = model('Oracle')->getOracleData($sql_IMA021);
//                //核价档信息
//
//                //GROSS DIE信息
//                $sql_gross_die = "select IMAICD14,IMAICD04 from DATA.IMAICD_FILE where IMAICD00 = '".$name."'";
//                $IMAICD14_result = model('Oracle')->getOracleData($sql_gross_die);
//                $IMAICD14 = $IMAICD14_result[0]['imaicd14'];
//                //生产阶段
//                $imaicd04 = $IMAICD14_result[0]['imaicd04'];
//                //WF PRICE DIE 信息
//                //查询MYSQL的有没有此供应商相关信息相关信息
//                $Erp_ct_result = Db::name('ErpCtFac')->where('status',1)->where('prd_no',$name)->where('imaicd04',$imaicd04)->where('fac_name',$v['supplier_name'])->find();
//                if($Erp_ct_result)
//                {
//                    //准备相关信息
//                    if($Erp_ct_result['is_release'] == 1)
//                        {
//                            $temp['fab_release'] = 'Y';
//                        }else
//                        {
//                            $temp['fab_release'] = 'N';
//                        }
//                        $temp['PM'] = $Erp_ct_result['PM']?$Erp_ct_result['PM']:'';
//                        $temp['UM'] = $Erp_ct_result['UM']?$Erp_ct_result['UM']:'';
//                }else
//                {
//                    //准备插入的数据
//                    $temp['wf_price'] = '';
//                    $temp['wf_price_die'] = '';
//                    $insert_data['prd_no'] = $name;
//                    $insert_data['imaicd04'] = $imaicd04;
//                    $insert_data['fac_name'] = $v['supplier_name'];
//                    $insert_data['status'] = 1;
//                    $result = Db::name('ErpCtFac')->insert($insert_data);
//                    if(!$result)
//                    {
//                        echo setServerBackJson(0,"mysql数据同步错误");exit;
//                    }
//                    //准备展示数据
//                    $temp['fab_release'] = '';
//                    $temp['PM'] = '';
//                    $temp['UM'] = '';
//                }
//
//                    $temp['fab_name'] = $v['supplier_name'];
//                    $temp['fab_proces'] = $FAB_PROCES[0]['ima021'];
//                    $temp['wf_price'] = '';
//                    $temp['wf_price_die'] = '';
//                    $temp['groces_dire'] = $IMAICD14;
//                    $temp['level'] = $imaicd04;
//                    $temp['name'] = $name;
//                    $data[] = $temp;
//                    //ERP数据的保存
//                    $map[] = [
//                        'prd_no' => $name,
//                        'imaicd04' => $imaicd04,
//                        'fac_name' => $v['supplier_name']
//                    ];
//                   $this->SynchronousData($map);
//            }
            $data = $this->getSupplierInfo($name,$level);
        }else
        {
            $data = $this->getSupplierInfo($name,$level);
        }
//        var_dump($data);die;

        return json_encode($data);

    }



//    private function getSupplierInfo($name,$level)
//    {
//         //查找fabName信息
//        $sql_pmj01 = "select distinct PMH02 as supplier_name from PMH_FILE where pmh01 = '".$name."'";
//        $pmj01 = model('Oracle')->getOracleData($sql_pmj01);
//        if(!$pmj01){
//            return [];
//        }
//        //查找对应的供应商信息
//        $data = [];
//        $map = [];
//        foreach($pmj01 as $v)
//        {
//            //共供应商名字
//            $fab_name = $v['supplier_name'];
//            //核价档信息
//            $sql_IMA021 = "select IMA021,IMA131 from IMA_FILE where IMA01 = '".$name."'";
//            $FAB_PROCES = model('Oracle')->getOracleData($sql_IMA021);
//
//            //GROSS DIE信息
//            $sql_gross_die = "select IMAICD14,IMAICD04 from DATA.IMAICD_FILE where IMAICD00 = '".$name."'";
//            $IMAICD14_result = model('Oracle')->getOracleData($sql_gross_die);
//            $IMAICD14 = $IMAICD14_result[0]['imaicd14'];
//            //生产阶段
//            $imaicd04 = $IMAICD14_result[0]['imaicd04'];
//            //核价档、作业编号
//            $where['site_code'] =  'T_SHSINO';
//            $where['fac_num'] = $fab_name;
//            $where['ima06'] = $this->get_flow($imaicd04);
//            $p_num = Db::name('ErpPConfig')->where($where)->value('p_num');
//            //判断是否存在核价档
//            if($p_num)
//            {
//                $PMJ = Db::name('ErpPmjFile')->field('pmj07,pmj10')->where('pmj03',$name)->where('pmj01',$p_num)->select();
//                if($PMJ)
//                {
//                    $pmj07 = $PMJ['pmj07'];
//                    $pmj10 = $PMJ['pmj10'];
//                }else
//                {
//                    $pmj07 = '';
//                    $pmj10 = '';
//                }
//
//            }else
//            {
//                $pmj07 = '';
//                $pmj10 = '';
//            }
//
//
//            //查询MYSQL的有没有此供应商相关信息相关信息
//            $Erp_ct_result = Db::name('ErpCtFac')->where('status',1)->where('prd_no',$name)->where('imaicd04',$imaicd04)->where('fac_name',$fab_name)->find();
//            if($Erp_ct_result)
//            {
//                if($Erp_ct_result['is_release'] == 1)
//                {
//                    $temp['fab_release'] = 'Y';
//                }else
//                {
//                    $temp['fab_release'] = 'N';
//                }
//                $temp['PM'] = $Erp_ct_result['PM']?$Erp_ct_result['PM']:'';
//                $temp['UM'] = $Erp_ct_result['UM']?$Erp_ct_result['UM']:'';
//                $temp['good_die'] = $Erp_ct_result['good_die']?$Erp_ct_result['good_die']:'';
//                $temp['layer_num'] = $Erp_ct_result['layer_num']?$Erp_ct_result['layer_num']:'';
//                $temp['layer_day'] = $Erp_ct_result['layer_day']?$Erp_ct_result['layer_day']:'';
//                $temp['lt_day'] = $Erp_ct_result['lt_day']?$Erp_ct_result['lt_day']:'';
//                $temp['p_day'] = $Erp_ct_result['p_day']?$Erp_ct_result['p_day']:'';
//                $temp['yld'] = $Erp_ct_result['yld']?$Erp_ct_result['yld']:'';
//                $temp['wire_stock'] = $Erp_ct_result['wire_stock']?$Erp_ct_result['wire_stock']:'';
//                $temp['drawing'] = $Erp_ct_result['drawing']?$Erp_ct_result['drawing']:'';
//            }else
//            {
//                //准备插入的数据
//                $insert_data['prd_no'] = $name;
//                $insert_data['imaicd04'] = $imaicd04;
//                $insert_data['fac_name'] = $v['supplier_name'];
//                $insert_data['status'] = 1;
//                $result = Db::name('ErpCtFac')->insert($insert_data);
//                if(!$result)
//                {
//                    echo setServerBackJson(0,"mysql数据同步错误");exit;
//                }
//                //准备展示数据
//                $temp['fab_release'] = '';
//                $temp['PM'] = '';
//                $temp['UM'] = '';
//                $temp['good_die'] = '';
//                $temp['layer_num'] = '';
//                $temp['layer_day'] = '';
//                $temp['lt_day'] = '';
//                $temp['p_day'] = '';
//                $temp['yld'] = '';
//                $temp['wire_stock'] = '';
//                $temp['drawing'] = '';
//            }
//            //产品线
//            $temp['line'] = $FAB_PROCES[0]['ima131'];
//
//
//            $temp['fab_name'] = $v['supplier_name'];
//            $temp['fab_proces'] = $FAB_PROCES[0]['ima021'];
//            $temp['wf_price'] = $pmj07;
//            $temp['wf_price_die'] = '';
//            $temp['work_num'] = $pmj10;
//            $temp['groces_die'] = $IMAICD14;
//            $temp['level'] = $imaicd04;
//            $temp['name'] = $name;
//            $data[] = $temp;
//            //ERP数据的保存
//            $map[] = [
//                'prd_no' => $name,
//                'imaicd04' => $imaicd04,
//                'fac_name' => $v['supplier_name']
//            ];
//        }
//        $this->SynchronousData($map);
//        return $data;
//    }

    private function getSupplierInfo($name,$level)
    {

        //查找fabName信息
        $sql_pmj01 = "select distinct PMH02 as supplier_name,TA_PMH09,TA_PMH11,TA_PMH12,TA_PMH13,TA_PMH14,TA_PMH16,PMH20,TA_PMH15 from PMH_FILE where pmh01 = '".$name."'";
        $pmj01 = model('Oracle')->getOracleData($sql_pmj01);
        if(!$pmj01){
            return ;
        }
        $temp = [];
        $data = [];
        $level = '';
        foreach($pmj01 as $v)
        {
            //共供应商名字
            $fab_name = $v['supplier_name'];
            //核价档信息
            $sql_IMA021 = "select IMA021,IMA131 from IMA_FILE where IMA01 = '".$name."'";
            $FAB_PROCES = model('Oracle')->getOracleData($sql_IMA021);
            //GROSS DIE信息
            $sql_gross_die = "select IMAICD14,IMAICD04 from DATA.IMAICD_FILE where IMAICD00 = '".$name."'";
            $IMAICD14_result = model('Oracle')->getOracleData($sql_gross_die);
            $IMAICD14 = $IMAICD14_result[0]['imaicd14'];
            //生产阶段
            $imaicd04 = $IMAICD14_result[0]['imaicd04'];
            $temp['name'] = $name;
            $temp['line'] = $FAB_PROCES[0]['ima131'];
            $temp['groces_die'] = $IMAICD14;
            $temp['fab_proces'] = $FAB_PROCES[0]['ima021'];
            $temp['level'] = $imaicd04;
            $temp['fab_name'] = $v['supplier_name'] ;
            $temp['ta_pmh09'] = $v['ta_pmh09']?$v['ta_pmh09']:'';  //PM
            $temp['ta_pmh11'] = $v['ta_pmh11']?$v['ta_pmh11']:'';  //UM
            $temp['ta_pmh12'] = $v['ta_pmh12']?$v['ta_pmh12']:'';  //LAYER_NUM
            $temp['ta_pmh13'] = $v['ta_pmh13']?$v['ta_pmh13']:'';  //LAYER_DAY
            $temp['ta_pmh14'] = $v['ta_pmh14']?$v['ta_pmh14']:'';  //LT_DAY
            $temp['ta_pmh16'] = $v['ta_pmh16']?$v['ta_pmh16']:'';  //P_DAY
            $temp['pmh20'] = $v['pmh20']?$v['pmh20']:'';         //DRAWING
            $temp['ta_pmh15'] = $v['ta_pmh15']?$v['ta_pmh15']:'';  //GOOD_DIE
            $data[] = $temp;
            $level = $imaicd04;
        }

        $sql = $this->getFlow($name,$level);
        $result =  model('Oracle')->getOracleData($sql);
        $result_str = $result[0]['b_body'].'-'.$result[0]['wf_name'].'-'.$result[0]['cp_name'].'-'.$result[0]['cp3_name'].'-'.$result[0]['pkg_name'];

        $temp = [
          'data' => $data,
          'flow' => $result_str
        ];
//        var_dump($temp);die;
        return $temp;
//        return $data;
    }


    /** 同步供应商信息到sw_erp_ct_fac数据表中
     * @param $data
     */
    private  function  SynchronousData($data)
    {
        $fac_name_str = '';
        $prd_no = '';
        $level = '';
        foreach($data as $k => $v)
        {
            $fac_name_str .= "'".$v['fac_name']."',";
            $prd_no = $v['prd_no'];
            $level = $v['imaicd04'];
        }
        $fac_name = rtrim($fac_name_str,',');
        $sql = "update sw_erp_ct_fac set `status` = 0 where imaicd04 =".$level." and prd_no = '".$prd_no."' and fac_name not in(".$fac_name.")";
        $result = Db::execute($sql);
        if($result === false)
        {
            echo setServerBackJson(0,'供应商数据同步错误');exit;
        }
    }

    /**
     * 编辑数据
     */
    public function edit_bom()
    {
        $data = input('get.','','trim');
        $site = config('site_name');
        $sql = "select * from ".$site.".PMH_FILE where PMH01='".$data['name']."' and PMH02='".$data['fac_name']."'";
        $result = model('Oracle')->getOracleData($sql);
        //取出对应的信息
//        $bom_data = Db::name('ErpCtFac')->where('prd_no',$data['name'])->where('fac_name',$data['fac_name'])->where('imaicd04',$data['level'])->find();
        return $this->fetch('',[
              'bom_data' => $data,
              'ErpData' => $result[0]
        ]);

    }

    /**保存数据
     *
     */
    public function save_bom()
    {
        $data = input('post.','','trim');

        $validate = validate("ErpCtFac");
        if(!$validate->check($data))
        {
            echo setServerBackJson(0,$validate->getError());exit;
        }
        $site = config('site_name');
        $sql = "update ".$site.".PMH_FILE SET TA_PMH09='".$data['ta_pmh09']."',TA_PMH11='".$data['ta_pmh11']."',TA_PMH12
              ='".$data['ta_pmh12']."',TA_PMH13='".$data['ta_pmh13']."',TA_PMH14='".$data['ta_pmh14']."',TA_PMH16='".$data['ta_pmh16']."',PMH20='".$data['pmh20']
               ."',TA_PMH15='".$data['ta_pmh15']."' where PMH01='".$data['prd_no']."' and PMH02='".$data['fac_name']."'";
        try{
            exec_oracle($sql);
        }catch (\Exception $e)
        {
            echo setServerBackJson(0,"错误异常");exit;
        }
        echo setServerBackJson(1,"更新成功");exit;





//        echo setServerBackJson(1,"更新成功");exit;

//
//        //更新数据条件
//        $map['prd_no'] = $data['prd_no'];
//        $map['fac_name'] = $data['fac_name'];
//        $map['imaicd04'] = $data['imaicd04'];
//        try{
//            model('ErpCtFac')->save_data($map,$data);
//        }catch (\Exception $e)
//        {
//            echo setServerBackJson(0,$e->getMessage());exit;
//        }
//
//       echo setServerBackJson(1,"更新成功");exit;

    }

    /**   获得料号对应的生产阶段
     * @param $flow
     * @return string
     */
    private function get_flow($flow)
    {
        if($flow == 1)
        {
            return '1_WF';
        }else if($flow == 2)
        {
            return '2_CP';
        }else if($flow == 3)
        {
            return '3_PKG';
        }else{
            return '4_FT';
        }
    }


    private function getFlow($padno = '',$imaicd04)
    {
        $sql = "select
  i.imaicd00 as prd_name,
  i.imaicd00||'_PKG' as pkg_name,
  (select bmb03 from t_shsino.bmb_file where bmb01=concat(i.imaicd00,'_PKG')) as cp3_name,
  (
     case
       when substr((select bmb03 from t_shsino.bmb_file where bmb01=(select bmb03 from t_shsino.bmb_file where bmb01=concat(i.imaicd00,'_PKG'))),-3)='_CP' then
        (select bmb03 from t_shsino.bmb_file where bmb01=(select bmb03 from t_shsino.bmb_file where bmb01=concat(i.imaicd00,'_PKG')))
       else
         (select bmb03 from t_shsino.bmb_file where bmb01=concat(i.imaicd00,'_PKG'))
       end
  ) as cp_name,
  (
     case
       when substr((select bmb03 from t_shsino.bmb_file where bmb01=(select bmb03 from t_shsino.bmb_file where bmb01=concat(i.imaicd00,'_PKG'))),-3)<>'_CP' then
        (select bmb03 from t_shsino.bmb_file where bmb01=(select bmb03 from t_shsino.bmb_file where bmb01=concat(i.imaicd00,'_PKG')))
       else
         (select bmb03 from t_shsino.bmb_file where bmb01=(select bmb03 from t_shsino.bmb_file where bmb01=(select bmb03 from t_shsino.bmb_file where bmb01=concat(i.imaicd00,'_PKG'))))
        end
  ) as wf_name,
  i.IMAICD01 AS B_BODY
from
  T_SHSINO.imaicd_file i
where
       i.imaicd00 in('".$padno."') and imaicd04 = ".$imaicd04;
        return $sql;
    }







}