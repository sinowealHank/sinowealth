<?php
namespace app\erp\model;
use think\Model;
set_time_limit(0);
class ErpIcl extends Model{

    protected $auto = ['icl02'=>1,'icl04'=> 'N','iclacti'=> 'Y'];
    protected $autoWriteTimestamp = false;


     public function getIcl02Attr($value)
     {
         return "正印";
     }


    //同步数据到mysql中。
//    public function get_icl_data(){
//        $sql = 'select icl01,icl02,icl03,icl04,icl05,Iclacti,ta_icl01 from t_shsino.icl_file';
//        $icl_data = model('Oracle')->getOracleData($sql);
//        //取出需要对比的字段
//        $icl_check_data = [];
//        foreach($icl_data as $k => $v){
//            $temp['icl01'] = $v['icl01'];
//            $temp['icl03'] = $v['icl03'];
//            $temp['icl05'] = $v['icl05'];
//            $temp['ta_icl01'] = $v['ta_icl01'];
//            $icl_check_data[] = $temp;
//        }
//
//        var_dump($icl_check_data);die;
//        //取出mysql中的数据
//        $data = model('ErpIcl')->field('id,icl01,icl02,icl03,icl04,icl05,iclacti,ta_icl01')->select();
//
//        $mysql_data = [];
//        foreach($data as $v){
//            $mysql_data[] = $v->toArray();
//        }
//
//        $mysql_data1 = [];
//        foreach($mysql_data as $va11){
//            unset($va11['id']);
//            $mysql_data1[] = $va11;
//        }
//
//        var_dump($mysql_data1);die;
//
//
//
//
//        //获取最新的pricelist数据
//        $update_data = [];
//        foreach($icl_check_data as $k){
//            if(!in_array($k,$mysql_data)){
//
//            }
//        }
//
//        //判断是否首次同步
//        if(!empty($data)){
//            foreach($update_data as $k1 => $v1){
//                //判断是否是更新和插入数据库
//                if(in_array($arr,$prdno)){
//
//                }else{
//                    //增加新的数据
//                    try{
//                        model('ErpIcl')->insert($v1);
//                    }catch (\Exception $e){
//                        throw new \Exception($e->getMessage());
//                    }
//                }
//            }
//        }else{
//
//            //更新到mysql中去进行处理
//            if(!empty($update_data)){
//                try {
//                    model('ErpIcl')->insertAll($update_data);
//                } catch (\Exception $e) {
//                    throw new \Exception($e->getMessage());
//                }
//            }
//        }
//    }
    /**处理插入的数据及会写ERP数据
     * @param $data
     */
      public function insert_icl($data){

          //查询ERP的数据
          $data['icl02'] = 1;
          //判断当前的数据是否已经存在icl01
          $sql_icl03 = "select count(icl01) as count_num from ".config('site_name2').".icl_file where icl01 = '".$data['icl01']."'";
          $count = model('Oracle')->getOracleData($sql_icl03);
          //查询对应的icl01相同数量
          if($count){
              $data['icl03'] = $count[0]['count_num'] + 1;
          }else{
              $data['icl03'] = 1;
          }

          $data['key'] = $data['icl01'].','.$data['icl03'].','.$data['icl05'].','.$data['ta_icl01'];
          $temp =  $data['key'];

          //判断是否插入相同的key
          $key = $this->where('key',$data['key'])->select();
          if($key){
              echo setServerBackJson(0,"不能重复添加已有的数据");exit;
          }
//          var_dump($data);die;
          //删除对应的的key
          unset($data['key']);
          $data['iclacti'] = 'Y';
          $data['icl04'] = 'N';
          $sql_erp = "insert into ".config("site_name2").".icl_file (icl01,icl02,icl03,icl04,icl05,iclacti,ta_icl01) values
                    ("."'".$data['icl01']."'".","."'".$data['icl02']."'".","."'".$data['icl03']."'".","."'".$data['icl04']."'"."
                    ,"."'".$data['icl05']."'".","."'".$data['iclacti']."'".","."'".$data['ta_icl01']."'".")";

          $erp_result = exec_oracle($sql_erp);
          if($erp_result == false){
              echo setServerBackJson(0,"回写ERP失败！");exit;
          }

          //插入到
          if($erp_result){
              $data['key'] = $temp;
              var_dump($data);
              $result = $this->insert($data);
              if(!$result){
                  echo setServerBackJson(0,"添加数据失败");exit;
              }
              return $result;
          }
      }





      // 写入ERP、以及mysql中。
      public function update_icl($data){
           $arr = explode(',',$data['key']);
           $arr['icl01'] = $arr[0];
           $arr['icl02'] = 1;
           $arr['icl03'] = $arr[1];
           $arr['ta_icl01'] = $arr[3];
          //拼接更新语句
          $sql = "update ".config("site_name2").".icl_file set icl05 ='".$data['icl05']."',ta_icl01 ='".$data['ta_icl01']."'
                  where icl01 = '".$arr[0]."' and icl03 = '".$arr[1]."' and icl05 ='".$arr[2]."'"." and ta_icl01 = '".$arr[3]."'";
//          var_dump($sql);die;

          $oracle_result = exec_oracle($sql);
          if(!$oracle_result){
              echo setServerBackJson(0,'ERP数据会写失败');exit;
          }
          //更新sql语句
          $data['key'] = $arr[0].','. $arr[1].','.$data['icl05'].','.$data['ta_icl01'];
          $update = $this->save($data,['id'=>$data['id']]);
          //记录用户修改记录
//          $login_id = session('login_id');
//          $log['user_name'] = get_cache_data('user_info',$login_id,'nickname');


          return $update;
      }




}
