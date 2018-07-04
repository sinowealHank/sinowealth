<?php
namespace app\sys\controller;

use app\index\controller\Admin;
use Mockery\CountValidator\Exception;
use think\Validate;
use think\Db;
class Ip extends Admin{
    /**
     *  首页
     */
    public function index()
    {
        //取出IP维护逇数据
        $data = Db::name('SysIp')->paginate(8);
        $page = $data->render();
        return $this->fetch('',[
            'data' => $data,
            'page' => $page
        ]);

    }

    /**
     * 显示添加IP的页面
     */
    public function add_ip()
    {
        return $this->fetch();
    }

    /**
     * 添加ip地址
     */
    public function save_ip()
    {
       if(!request()->isPost())
       {
           echo setServerBackJson(0,"请求的方法不合法!");exit;
       }
        $data = input('post.','','trim');
        $validate = $this->validate([
            'ip'  => $data['ip'],
            'ip_type' => $data['ip_type'],
        ],
            [ 'ip' => 'require' ,
            'ip_type'=> 'require|number',]);
       if(true !== $validate)
       {
           echo setServerBackJson(0,$validate);exit;
       }
        $ip_data = Db::name('SysIp')->insert($data);
        if($ip_data)
        {
            echo setServerBackJson(1,"添加成功");exit;
        }else
        {
            echo setServerBackJson(1,"添加失败");exit;
        }
    }

    /**
     * 显示修改页面
     */
    public function edit_ip()
    {
        if(!request()->isGet()){
            echo setServerBackJson(0,"请求的方法不合法!");exit;
        }
        $id = input('get.id','','trim');
        $id = intval($id);
        //找出对应的数据
        $ip_data = Db::name('SysIp')->where('id',$id)->find();
        return $this->fetch('',[
            'ip' => $ip_data['ip'],
            'ip_type' => $ip_data['ip_type'],
            'id' => $id
        ]);
    }

    /**
     *  修改IP接口
     */
    public function update_ip()
    {
        if(!request()->isPost())
        {
            echo setServerBackJson(0,"请求的方法不合法!");exit;
        }
        $data = input('post.','','trim');
        $id = $data['id'];
        unset($data['id']);
        $update_result =  Db::name('SysIp')->where('id',$id)->update($data);
        if($update_result !== false)
        {
            echo setServerBackJson(1,"修改成功!");
        }else
        {
            echo setServerBackJson(0,"修改失败!");
        }

    }

    /**
     * 删除ip地址
     */

    public function delete_ip()
    {
        if(!request()->isGet())
        {
            echo setServerBackJson(0,"请求的方法不合法!");exit;
        }
        $id = input('get.id','','trim');
        $id = intval($id);
        $delete_return = Db::name('SysIp')->where('id',$id)->delete();
        if($delete_return)
        {
            echo setServerBackJson(1,"删除成功!",1);
        }else
        {
            echo setServerBackJson(0,"删除失败!");
        }




    }




}