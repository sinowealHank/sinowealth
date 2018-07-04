<?php
namespace app\erp\controller;
use think\Controller;
use think\Db;
use think\Session;

use app\index\controller\Admin;

class CheckPriceList extends admin{
    public function index(){
        //获取对应的查询条件
        $login_id = Session::get('login_id');
        $user_type_data = model('ErpUserType')->where('user_id',$login_id)->find()->toArray();
         //获取最新的pricelist数据同步到mysql中去(pp部门人员打开页面)
        if($user_type_data['user_type'] == 1){
            model('PriceList')->get_erp_priceList();
        }
        return $this->fetch();
    }

    //获取数据datagird数据
    public function get_price_check_data(){
        $sort = isset($_POST['sort']) ? $_POST['sort'] : 'id';
        $order = isset($_POST['order']) ? $_POST['order'] : 'desc';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $rows = isset($_POST['rows']) ? intval($_POST['rows']) : 20;
        //查询条件
        $map = [];
        $prdno = input('post.prd','','trim');
        if(!empty($prdno)){
            $map['prdno'] = ['like','%'.$prdno.'%'];
        }
        $description = input('post.description','','trim');
        if(!empty($description)){
            $map['description'] =  $description;
        }
        $pm = input('post.pm','','trim');
        if(!empty($pm)){
            $map['pm'] =  $pm;
        }
        //判断当前人的身份
        $login_id = Session::get('login_id');
        $user_type_data = model('ErpUserType')->where('user_id',$login_id)->find()->toArray();
        //获取最新的pricelist数据同步到mysql中去(pp部门人员打开页面)
//        if($user_type_data['user_type'] == 1){
//            model('PriceList')->get_erp_priceList();
//        }

        $offset = ($page-1)*$rows;
        $config['offset'] = $offset;
        $config['rows']  = $rows;
        $config['sort']  = $sort;
        $config['order']  = $order;

        $page_info = model('ErpCheckPriceList')->get_price_data($map,$user_type_data,$config);
        $Pri_data = [];

        foreach($page_info as $val){
            $Pri_data[] = $val->toArray();
        }

        $count = model('ErpCheckPriceList')->count();
        $qq  = $Pri_data;
        $qty = $count;
        $json = '{"total":'.$qty.',"rows":'.json_encode($qq).'}';
        echo $json;
    }

    /**
     * @param $id
     * @return 返回当前的值
     */
      public function edit_price($id){
          //获得当前人的身份
          $login_id = Session::get('login_id');
          $user_type = model('ErpUserType')->where('user_id',$login_id)->value('user_type');
          $price_data = model('ErpCheckPriceList')->where('id',$id)->find();
          return $this->fetch('',[
              'user_type'=> $user_type,
              'price_data'=>$price_data
          ]);
      }
     //添加pricelist的数据
      public function save_price()
      {

          $edit_data = input('post.','','trim');
          //修改pricelist数据
          $result = model('ErpCheckPriceList')->save_price($edit_data);
          if($result || $result === 0){
              echo setServerBackJson(1,"添加数据成功");exit;
          }else{
              echo setServerBackJson(0,"添加数据失败");
          }
      }

       public function action($id){
           return $this->fetch('',[
               'id' => $id
           ]);
       }

       //回写到ERP中去
       public function save_erp(){
           $id = input('post.id','','trim');
           //取出对应的数据
           $price_data = model("ErpCheckPriceList")->where('id',$id)->find()->toArray();
           $data = model('PriceList')->insert_erp_price($price_data);

       }




}