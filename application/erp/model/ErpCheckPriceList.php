<?php
namespace app\erp\model;
use think\Model;
use think\Session;

/**
 * Class ErpCheckPriceList   2018 1/22  周仁杰修改
 * @package app\erp\model
 */
class ErpCheckPriceList extends Model{


    //获得最新的数据
    public function get_price_data($map,$user_type_data,$config){
        if($user_type_data['user_type'] == 1){
            $map['check_flow'] = 1;
            $map['status'] = 1;
        }else if($user_type_data['user_type'] == 2){
            //取出pm下的所有的数据
            $map['check_flow'] = 1;
            $map['pm'] = $user_type_data['user_gh'];
            $map['status'] = 1;
        }else if($user_type_data['user_type'] == 3){
            $map['check_flow'] = 2;
            $map['status'] = 1;
        }
      return $this->where($map)->limit($config['offset'],$config['rows'])->order($config['sort'],$config['order'])->select();

    }

    public function save_price($edit_data){
        //判断当前的身份
        $login_id = Session::get('login_id');
        $user_type = model('ErpUserType')->where('user_id',$login_id)->value('user_type');
        if($user_type == 1){
            if(empty($edit_data['id'])){
              echo setServerBackJson(0,"id不各法!");exit;
              }
              if(empty($edit_data['lt'])){
                  echo setServerBackJson(0,"L/T Days不能为空!");exit;
              }
              if(empty($edit_data['pack'])){
                  echo setServerBackJson(0,"Pack不能为空!");exit;
              }
              if(empty($edit_data['moq'])){
                  echo setServerBackJson(0,"MOQ不能为空!");exit;
              }
        }else if($user_type == 2){
            if(empty($edit_data['prd_rmb_price'])){
                echo setServerBackJson(0,"标准品RMB(不含税)不能为空");exit;
            }
            if(empty($edit_data['prd_rmb_price_vat'])){
                echo setServerBackJson(0,"标准品RMB(含税)不能为空!");exit;
            }
            if(empty($edit_data['prd_usd_price'])){
                echo setServerBackJson(0,"标准品USD不能为空!");exit;
            }
            if(empty($edit_data['ima10'])){
                echo setServerBackJson(0,"归属地不能为空!");exit;
            }
            if(empty($edit_data['imaud07'])){
                echo setServerBackJson(0,"PASSβ不能为空!");exit;
            }

        }

        $result = $this->where('id',$edit_data['id'])->update($edit_data);

        if($result || $result == 0){
            //查询当前字段信息确定是否向李秀兰确认
            $check_data = $this->where('id',$edit_data['id'])->find()->toArray();
            if(trim($check_data['pack']) && trim($check_data['prd_rmb_price_vat'])){
                try{
                    $this->where('id',$edit_data['id'])->setField('check_flow',2);
                }catch (\Exception $e){
                    throw new \Exception($e->getMessage());
                }

            }
        }
        return $result;
    }


    protected static function getPmAttr($value){
        $user_data = config('user_info');
        if($value){
           foreach($user_data as $val){
               if($val['user_gh'] === $value){
                   return $val['nickname'];
               }
           }
        }
    }





}