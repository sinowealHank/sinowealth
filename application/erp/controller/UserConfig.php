<?php
namespace app\erp\controller;
use think\Controller;
use app\index\controller\Admin;
use think\Db;
class UserConfig extends admin {
//    private $site = '';
//    public function __construct(){
//        $this->site = config('site_name');
//    }
    public function index(){
        //取出当前的人员的设置信息
        $user_th = input('user_gh','','trim');
        $map = [];
        if(!empty($user_th)){
            $map['user_gh'] = ['like','%'.$user_th.'%'];
        }
        $user_name =  input('user_name','','trim');
        if(!empty($user_name)){
            $map['user_name'] = ['like','%'.$user_name.'%'];
        }

        $data = model('ErpUserType')->where($map)->paginate(10);
        return $this->fetch('',[
            'user_data'=>$data,
            'user_gh' => $user_th?$user_th:'',
            'user_name' => $user_name?$user_name:''
        ]);
    }

    //显示当前的页面
    public function add_user(){
        return $this->fetch();
    }

    //添加数据
    public function user_save(){
       $data = input('post.','','trim');

       if(empty($data['user_gh_val'])){
           echo setServerBackJson(0,'工号必填');exit;
       }
       if($data['dep'] == 2 && empty($data['line'])){
           echo setServerBackJson(0,"请选择PM对应的产品线");exit;
       }
       //插入数据库
        $result = model('ErpUserType')->save_data($data);
        if($result){
            echo setServerBackJson(1,"添加成功",1);
        }else{
            echo setServerBackJson(0,"添加失败");
        }

    }

    //获取erp中PM对应的line
    public function get_line(){
//        $lines = db('cost_config')->where('name','line')->column('content');
//        $lines = cost_string_to_array($lines['0']);
        //查询ERP数据源
        $sql = "select tc_prob06 from ".config('site_name').".tc_prob_file";
        $lineData = model('Oracle')->getOracleData($sql);
        $lines = [];
        foreach($lineData as $val)
        {
            $lines[] = $val['tc_prob06'];
        }
        return $lines;
    }

    //删除用户
    public function delete_user($id){
        model('ErpUserType')->where('id',$id)->delete();
        echo setServerBackJson(1,"删除成功",1);
    }
    //修改用户页面
    public function edit_user($id){
        $data = model('ErpUserType')->get($id);
        //获得产品线数据
//        $lines = db('cost_config')->where('name','line')->column('content');
//        $lines = cost_string_to_array( strtoupper($lines['0']));
        //查询ERP数据
        $sql = "select tc_prob06 from ".config('site_name').".tc_prob_file";
        $lineData = model('Oracle')->getOracleData($sql);
        $lines = [];
        foreach($lineData as $val)
        {
            $lines[] = $val['tc_prob06'];
        }
        //当前产品线
        if(!empty($data['prd_line'])){
            $line_arr =  explode(',',$data['prd_line']);
        }else{
            $line_arr = [];
        }
        $line_html = '';
        $lines = array_unique($lines);
         foreach($lines as $k => $v)
         {
             if(in_array($v,$line_arr)){
                 $check = 'checked="checked"';
             }else{
                 $check = '';
             }
             $line_html .= '<span style="margin-left:5px;">'.$v.'<input '.$check.' value="'.$v.'" type="checkbox" name="line[]"><span>';
         }
        return $this->fetch('',[
            'edit_data'=> $data,
            'line_html'=> $line_html
        ]);
    }

    //修改用户接口
    public function user_update()
    {
        $data = input('post.','','trim');
        if(empty($data['user_gh_val'])){
            echo setServerBackJson(0,'工号必填');exit;
        }
        if($data['dep'] == 2 && empty($data['line'])){
            echo setServerBackJson(0,"请选择PM对应的产品线");exit;
        }
        //修改数据
        $result = model('ErpUserType')->update_data($data);
        if($result || $result === 0){
            echo setServerBackJson(1,"修改成功",1);
        }else{
            echo setServerBackJson(0,"修改失败");
        }

    }











}