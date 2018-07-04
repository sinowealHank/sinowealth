<?php
namespace app\sys\controller;
set_time_limit(0);
ini_set("memory_limit","-1");

use think\Controller;
use think\Db;
use think\Model;
use app\erp\model\Oracle;
use app\index\controller\Admin;
class Access extends Admin{
	
	//角色->权限设定
    public function index(){  
    
    
//$conn = oci_connect('WTQ','123456','192.9.230.22/orcl');//不能是超级管理员账户
//if (!$conn) {
//$e = oci_error();
//print htmlentities($e['message']);
//exit;
//}
//$sql = "select * from sino.xmf_file";//只能访问对应用户名下的数据库，或许账户配置好了也或许有其他方法可以访问到其他用户的
//$ora_test = oci_parse($conn,$sql); //编译sql语句
//oci_execute($ora_test,OCI_DEFAULT); //执行
//while ($result = oci_fetch_assoc($ora_test))
//{
//$data[] = $result;
//}
//print_r($data);
//EXIT;
//
//
//    $sql = "select * from SINO.XMF_FILE";
//    $result_arr = model('Oracle')->getOracleData($sql);
//    var_dump($result_arr);die;

		
    	/*
    	header("Content-type: text/html; charset=utf-8");
    	
//     	exit;
    	
//     	set_entry_status(array('2017-08-01','2017-08-05'));
//     	echo 'over';exit;
//     	exit;
    	
//     	echo cal_sick_leave(581,35);
//     	exit;
    	
    	
//     	$hr_sec_range=config('hr_sec_range');
//     	$hr_work_time=config('hr_work_time')*60*60-$hr_sec_range;
    	
//     	$hr_work_time_cal=get_user_work_time(1,11517355,'2017-07-21');
//     	echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /> ';
//     	echo '正常工作时间,8小时59分,对应秒数--'.$hr_work_time.',实际计算后秒数'.$hr_work_time_cal.'<br>';
//     	//echo get_user_work_time_more();
//     	exit;
    	
    	
    	$month_arr[0]='2017-09-01';
    	$month_arr[1]='2017-09-31';
    	
    	$date='2017-09-01';
    	$site_id=1;
    	$cal_flag='';
    	$user_arr=db('sys_user')->where('status=1 and hr_status in (1) and id in (103)  and user_status=1 and site_id='.$site_id)->select();
    	$month_arr=get_begin_last_date($date);
    	
    	db()->query('truncate table sw_sys_temp_log');
    	
    	if($site_id==1){
    		//规避非有效打卡记录
    		//set_entry_status($month_arr);
    	}
//     	echo 'over-'.rand(1,10000);
//     	exit;
    	
    	foreach ($user_arr as $key=>$val){
    		$month_hr_table_arr=calculate_user_hr_table($site_id,$date,$val['id'],$cal_flag);
    		
    		foreach ($month_hr_table_arr as $k=>$v){
    			//判断本条记录在hr_table中是否存在,如果存在,则更新,如果不存在,则插入
    			$hr_table_id=db('hr_table')->where('user_id='.$v['user_id']." and hr_date='".$v['hr_date']."'")->value('id');
    			if($hr_table_id){
    				$v['id']=$hr_table_id;
    				db('hr_table')->update($v);
    			}else{
    				unset($v['id']);
    				db('hr_table')->insert($v);
    			}
    		}
    	}
    	
    	echo 'over-'.rand(1,10000);
    	exit;
    	*/
//        $sql = "select
//  i.imaicd00 as prd_name,
//  i.imaicd00||'_PKG' as pkg_name,
//  (select bmb03 from t_shsino.bmb_file where bmb01=concat(i.imaicd00,'_PKG')) as cp3_name,
//  (
//     case
//       when substr((select bmb03 from t_shsino.bmb_file where bmb01=(select bmb03 from t_shsino.bmb_file where bmb01=concat(i.imaicd00,'_PKG'))),-3)='_CP' then
//        (select bmb03 from t_shsino.bmb_file where bmb01=(select bmb03 from t_shsino.bmb_file where bmb01=concat(i.imaicd00,'_PKG')))
//       else
//         (select bmb03 from t_shsino.bmb_file where bmb01=concat(i.imaicd00,'_PKG'))
//       end
//  ) as cp_name,
//  (
//     case
//       when substr((select bmb03 from t_shsino.bmb_file where bmb01=(select bmb03 from t_shsino.bmb_file where bmb01=concat(i.imaicd00,'_PKG'))),-3)<>'_CP' then
//        (select bmb03 from t_shsino.bmb_file where bmb01=(select bmb03 from t_shsino.bmb_file where bmb01=concat(i.imaicd00,'_PKG')))
//       else
//         (select bmb03 from t_shsino.bmb_file where bmb01=(select bmb03 from t_shsino.bmb_file where bmb01=(select bmb03 from t_shsino.bmb_file where bmb01=concat(i.imaicd00,'_PKG'))))
//        end
//  ) as wf_name,
//  i.IMAICD01 AS B_BODY
//from
//  T_SHSINO.imaicd_file i
//where
//       i.imaicd00  in('SH79F166AF/044FR') and imaicd04 = 4 ";
//        $sql = "select * from data.ima_file where rownum < 300";
        $sql = "select * from data.pmh_file where pmh01 = 'SH79F9801L/016LU_PKG' ";
        $data = (new Oracle())->getOracleData($sql);
        var_dump($data);die;



//        $sql = " select * from ( select a.pmh01 as a_pmh01,a.pmh02 as a_pmh02,a.pmh11 as a_pmh11,a.pmh13 as a_pmh13 from t_shsino.pmh_file a where pmh01 = 'SS338AC')  cross join (select b.pmh01 as b_pmh01,b.pmh02 as b_pmh02,b.pmh11 as b_pmh11,b.pmh13 as b_pmh13 from t_shsino.pmh_file b where pmh01 = 'SS338AC_CP')";
//        $sql1 = "select * from ( select c.pmh01 as c_pmh01,c.pmh02 as c_pmh02,c.pmh11 as c_pmh11,c.pmh13 as c_pmh13 from t_shsino.pmh_file c where pmh01 = 'SH69P26K_PKG')  cross join (select d.pmh01 as d_pmh01,d.pmh02 as d_pmh02,d.pmh11 as d_pmh11,d.pmh13 as d_pmh13 from t_shsino.pmh_file d where pmh01 = 'SH69P26K')";
//        $sql2 = "select * from ( select a.pmh01 as a_pmh01,a.pmh02 as a_pmh02,a.pmh11 as a_pmh11,a.pmh13 as a_pmh13 from t_shsino.pmh_file a where pmh01 = 'SS338AC')  cross join (select b.pmh01 as b_pmh01,b.pmh02 as b_pmh02,b.pmh11 as b_pmh11,b.pmh13 as b_pmh13 from t_shsino.pmh_file b where pmh01 = 'SS338AC_CP') ";
//        $sql3 = "select * from ( select * from ( select c.pmh01 as c_pmh01,c.pmh02 as c_pmh02,c.pmh11 as c_pmh11,c.pmh13 as c_pmh13 from pmh_file c where pmh01 = 'SH69P26K_PKG')  cross join (select d.pmh01 as d_pmh01,d.pmh02 as d_pmh02,d.pmh11 as d_pmh11,d.pmh13 as d_pmh13 from pmh_file d where pmh01 = 'SH69P26K') y)";
//
//        $sql4 = "select * from ( select * from ( select a.pmh01 as a_pmh01,a.pmh02 as a_pmh02,a.pmh11 as a_pmh11,a.pmh13 as a_pmh13 from t_shsino.pmh_file a where pmh01 = 'SS338AC')  cross join (select b.pmh01 as b_pmh01,b.pmh02 as b_pmh02,b.pmh11 as b_pmh11,b.pmh13 as b_pmh13 from t_shsino.pmh_file b where pmh01 = 'SS338AC_CP') x )
// cross join ( select * from ( select c.pmh01 as c_pmh01,c.pmh02 as c_pmh02,c.pmh11 as c_pmh11,c.pmh13 as c_pmh13 from t_shsino.pmh_file c where pmh01 = 'SH69P26K_PKG')  cross join (select d.pmh01 as d_pmh01,d.pmh02 as d_pmh02,d.pmh11 as d_pmh11,d.pmh13 as d_pmh13 from t_shsino.pmh_file d where pmh01 = 'SH69P26K') y)";
//
//        $sql5 = " select distinct pmh02 as a_pmh02,pmh01 as a_pmh01,pmh11 as a_pmh11 from t_shsino.pmh_file  where pmh01 = 'SS323AB' ";
//        $data = (new Oracle())->getOracleData($sql5);
//        var_dump($data);die;

        /*取出所有的角色*/
        $role = new \app\sys\model\Role();
        $data = $role->getTree();
        $roleResult = object_change_array($data);
        /*角色*/
        foreach($roleResult as $k => $v){
            $role .= '<option value="'.$v['id'].'">'.str_repeat('&nbsp;', 8*$v['level']).$v['role_name'].'</option>';
        }
        /*取出所有的权限*/
        $privilegeData = db('privilege')->field('pri_name as name,id,parent_id')->select();
        $privilegeData = _reSort($privilegeData);
        $privilegeData = make_tree($privilegeData);

        /*输出复选框树形结构*/
//        $tree = set_MakeTree($privilegeData);

        /*无限极复选框打印*/
        $tree = setMakeTree($privilegeData);
        $this->assign('tree',$tree);
        $this->assign('list',$privilegeData);
        $this->assign('roleResult',$roleResult);
        $this->assign('role',$role);
        return $this->fetch();
    }


    /**  获得BOM数据
     * @param array $data
     */
    private function getData($data = [])
    {
        foreach($data as $k => $v)
        {
            //查找
            $wfName = $v['wf_name'];
             self::getFabName($wfName);
        }
    }

    /** 查找对应阶段的外包厂信息
     * @param string $name
     */
    public static function getFabName($name = '')
    {
        //查找fabName信息
        $sql_pmj01 = "select distinct PMH02 as supplier_name,TA_PMH09,TA_PMH11,TA_PMH12,TA_PMH13,TA_PMH14,TA_PMH16,PMH20,TA_PMH15 from PMH_FILE where pmh01 = '".$name."'";
        $pmj01 = (new Oracle())->getOracleData($sql_pmj01);
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
            $FAB_PROCES = (new Oracle())->getOracleData($sql_IMA021);
            //GROSS DIE信息
            $sql_gross_die = "select IMAICD14,IMAICD04 from DATA.IMAICD_FILE where IMAICD00 = '".$name."'";
            $IMAICD14_result = (new Oracle())->getOracleData($sql_gross_die);
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
            $temp['pmh20'] = $v['pmh20']?$v['pmh20']:'';         //DRAWING
            $temp['ta_pmh15'] = $v['ta_pmh15']?$v['ta_pmh15']:'';  //GOOD_DIE
            $data[] = $temp;
        }
        var_dump($data);die;

    }





    public function addPrivilege(){
        $roleId = $_POST['roleid'];
        $pridArr = $_POST['idData'];

        if(empty($pridArr)){
            db('role_privilege')->where('role_id',$roleId)->delete();
            echo setServerBackJson(1,"修改权限成功");exit;
        }

        $pridArr = explode(',',$pridArr);
        //先删除原先所有的权限//
        db('role_privilege')->where('role_id',$roleId)->delete();
        foreach ($pridArr as $k => $v){
            $insert['role_id'] =  $roleId ;
            $insert['pri_id'] = $v;
            $save[] = $insert;
        }
        /*链接数据库，添加数据*/
        db('role_privilege')->insertAll($save);
        echo setServerBackJson(1,"修改权限成功");

    }
    public function getPrivilege(){
        $id = $_POST['id'];
        if(empty($id)){
            echo setServerBackJson(0,"没有获得部门组别id");exit;
        }
        /*取出所有的权限id和权限名称*/
        $pri_id = Db::query("select pri_id from sw_role_privilege where role_id = {$id}");

        $privilegeData = db('privilege')->field('pri_name as name,id,parent_id')->select();
        $privilegeData = _reSort($privilegeData);
        $privilegeData =  make_tree($privilegeData);

        /*三级循环打印*/
//        $result = set_MakeTree($privilegeData,$pri_id,'pri_id');
        /*无限极循环打印*/
//        $result = setMakeTree($privilegeData,$pri_id,'pri_id');
        $result = setMakeTree($privilegeData,'pri_id',$pri_id,'pri_id');
        echo json_encode($result);
    }
    //批量赋予权限
    public function addBatchPri(){
        //取出所有的权限
        $privilegeData = Db::name('privilege')->field('pri_name as name,id,parent_id')->select();
        $privilegeData = _reSort($privilegeData);
        $privilegeData = make_tree($privilegeData);
        $pri_tree = setMakeTree($privilegeData,'pri',array(),'',true,1);

        //取出角色所有的数据
        $roleData = Db::name('role')->field('c_group_name as name,id,parent_id')->select();
        $roleData = _reSort($roleData);
        $roleData = make_tree($roleData);
        $role_tree = setMakeTree($roleData,'role',array(),'',true,1);
        $this->assign('role_tree',$role_tree);
        $this->assign('pri_tree',$pri_tree);
        return $this->fetch();
    }

    public function addbatch(){
         //只能选择一个权限
        $auth_id_str = $_POST['pri'];
        $role_id_str = $_POST['role'];
        if(empty($auth_id_str)){
            echo setServerBackJson(0,"请选择相应的权限信息");exit;
        }
        $role_id_arr = explode(',',$role_id_str);
        //先删除当前权限的所有的角色
        db('role_privilege')->where('pri_id',$auth_id_str)->delete();
        //查出所有的
        foreach($role_id_arr as $val){
            //判断当前角色下有无这个权限
            $result = db('role_privilege')->where('role_id',$val)->where('pri_id',$auth_id_str)->select();
            if(!$result){
                //如果查询数据库没有此权限，则添加
                $insert_arr = array();
                $insert_arr['role_id'] = $val;
                $insert_arr['pri_id'] = $auth_id_str;
                db('role_privilege')->insert($insert_arr);
            }
        }
        echo setServerBackJson(1,"添加成功");

    }

    public function getrole(){
        $pri_id = $_POST['pri'];
       //查找当前权限的角色
 
        $sql = "select role_id from sw_role_privilege where pri_id = ".$pri_id;
        $result = Db::query($sql);
        echo json_encode($result);
    }

//查看权限组别
    public function checkRoleUser(){
        /*取出所有的角色*/
        $role = new \app\sys\model\Role();
        $data = $role->getTree();
        $roleResult = object_change_array($data);
        /*角色*/
        foreach($roleResult as $k => $v){
            $role .= '<option value="'.$v['id'].'">'.str_repeat('&nbsp;', 8*$v['level']).$v['role_name'].'</option>';
        }

        $this->assign('role',$role);
        return $this->fetch();
    }


    //获得权限组下面的成员
    public function get_user(){
        $id = $_POST['id'];
        $user_data = Db::name('user_role')->where('role_id',$id)->select();
        $user_str = '';
        foreach($user_data as $k => $v){
            $nickname = get_cache_data('user_info',$v['user_id'],'nickname');
            $user_str .= '<button class="btn btn-primary btn-xs" name="sys_user_edit" user_id="'.$v['user_id'].'">'.$nickname.'</button>';
        }
        return $user_str;
    }
    
    public function product_excel_out(){
    	set_time_limit(0);
    	ini_set("memory_limit","-1");
    	$and_sql="select
						nx.*,'nx' as type
					from
						prd_info_nx as nx
					union all
					select
						*,'wx' as type
					from
						prd_info_wx as wx
					union all
					select
						*,'xa' as type
					from
						prd_info_xa as x";
    	$repeat_sql="SELECT
					*,COUNT(0) as shu,sum(SITE_FLAG) as he
				FROM
					( ".$and_sql." ) as a
				GROUP BY
					product_fullname
				HAVING COUNT(product_fullname)";
    	$field_sql="select CONCAT(COLUMN_NAME,',') as name from information_schema.COLUMNS where table_name = 'prd_info_wx' and COLUMN_NAME!='SITE_FLAG' and COLUMN_NAME!='WAFER_PRICE' and table_schema='".\think\Config::get('database')['database']."';";
    	$field_arr=db()->query($field_sql);
    
    	$field='';
    	foreach ($field_arr as $p){
    		$field=$field.$p['name'];
    	}
    	$sql="SELECT ".$field."
				(CASE
						WHEN shu=1 THEN type
						WHEN shu=2 THEN
							(CASE
								WHEN he=3 THEN 'nx,wx'
								WHEN he=4 THEN 'nx,xa'
								WHEN he=5 THEN 'wx,xa' END)
						WHEN shu=3 THEN 'nx,wx,xa' END )
				as end_type
			FROM
				( ".$repeat_sql." ) as b;";
    	
    	echo $repeat_sql; exit;
    	
    	$product_arr=db()->query($sql);
    	//dump($product_arr);
    	$data=array();
    
    	$title=array('产品线ID','产品线名称','产品ID','产品名称','产品名称2','TYPE ID','产品客户名称','产品完整名称','ROUTE ID','GROSS die','Family ID','FAMILY NAME','MATERIAL_MASTER_ID','MATERIAL_SUP_ID','FAMILY_ROOT_ID','第一次出货时间','第一次出货单号','最后一次出货时间','最后一次出货单号','第一次PO操作时间','第一次PO操作单号','最后一次PO时间','最后一次PO单号','第一次出库时间','最后一次出库时间','库存数量','所属站点');
    	//$data[0]['data'][]=$title;
    	$data[0]['style']['sheet']='全部';
    	$data[0]['style']['style']=array('style');
    	$data[0]['data']=$product_arr;
    	array_unshift($data[0]['data'],$title);
    
    	$data[1]['data'][]=$title;
    	$data[1]['style']['sheet']='FXXX';
    	$data[1]['style']['style']=array('style');
    
    	$data[2]['data'][]=$title;
    	$data[2]['style']['sheet']='SSXXX';
    	$data[2]['style']['style']=array('style');
    
    	$data[3]['data'][]=$title;
    	$data[3]['style']['sheet']='SHXXX';
    	$data[3]['style']['style']=array('style');
    
    	$data[4]['data'][]=$title;
    	$data[4]['style']['sheet']='其他';
    	$data[4]['style']['style']=array('style');
    
    	foreach ($product_arr as $p){
    		$PRD_NO=substr(trim($p['PRD_NO']),0,2);
    		if($PRD_NO=='SS'){
    			$data[2]['data'][]=$p;
    		}else if($PRD_NO=='SH'){
    			$data[3]['data'][]=$p;
    		}else{
    			$PRD_NO=substr(trim($PRD_NO),0,1);
    			if($PRD_NO=='F'){
    				$data[1]['data'][]=$p;
    			}else{
    				$data[4]['data'][]=$p;
    			}
    
    		}
    	}
    	$data['style']=array(
    			'freezePane'=>'1',
    			'ret'=>'1',
    			'cell'=>array(
    					'A'=>array('width'=>40),'C'=>array('width'=>40),'K'=>array('width'=>40),'M'=>array('width'=>40),'N'=>array('width'=>40),'O'=>array('width'=>40),
    					'D'=>array('width'=>21),'G'=>array('width'=>21),'H'=>array('width'=>21),'L'=>array('width'=>21),
    					'F'=>array('width'=>13.5)
    			)
    	);
    	foreach(range('P','Y') as $v){
    		$data['style']['cell'][$v]=array('width'=>19.5);
    	}
    	/*foreach (range('B','Z') as $v){
    	 $data['style']['cell'][$v]['backgroundcolor']='00CC00FF';
    	 }*/
    	$data['name']='ERP产品信息表';
    	excel_css($data);
    }
    

    //关闭所有用户的修改功能
    public function closeEdit(){
        $sql = "update sw_sys_user set is_edit = 0 where id not in (1)";
        Db::query($sql);
        echo setServerBackJson(1,"设置成功");
    }

    //全部开放用户的修改功能
    public function openEdit(){
        $sql = "update sw_sys_user set is_edit = 1 where id not in (1)";
        Db::query($sql);
        echo setServerBackJson(1,"设置成功");
    }

    //部分开发人员的修改功能
    public function editPart(){
        $id_array = $_POST['id'];
        $id_str = implode(',',$id_array);
        Db::name('sys_user')->where('id','in',$id_str)->setField('is_edit',1);
        echo setServerBackJson(1,"设置成功");

    }

    public function closePartEdit(){
        $id_array = $_POST['id'];
        $id_str = implode(',',$id_array);
        Db::name('sys_user')->where('id','in',$id_str)->setField('is_edit',0);
        echo setServerBackJson(1,"设置成功");
    }





    //添加个人单独权限。 2018 2/7 周仁杰修改
    public function add_person_auth(){
        //获得当前所有的权限
        $nav_arr = Db::name('privilege')->field('id,pri_name as name,parent_id')->select();
        $pri_result = _reSort($nav_arr);
        $pri_result = make_tree($pri_result);
        $tree = make_Nav($pri_result,array(),'id');
        $this->assign('tree',$tree);
        return $this->fetch();
    }


    //添加个人权限接口  2018 2/8 周仁杰修改
    public function add_auth()
    {
        //获得权限id和parent_id
        $id_str = input('post.id_par_arr','','trim');
        //人员id
        $user_id = input('post.id','','trim');
        if(empty($user_id))
        {
            echo setServerBackJson(0,"请选择对应人员!");exit;
        }
        //可以使个人模块添加权限为空
        if(empty($id_str))
        {
            $update =  Db::name('SysPersonAuth')->where('user_id',$user_id)->setField('auth_str','');
            if($update == 0 || $update){
                echo setServerBackJson(1,"修改成功!");exit;
            }

        }
        $id_arr = explode(',',$id_str);
        $id_level_1 = [];
        $id_temp_str = '';
        foreach($id_arr as $k => $v){
            $temp = explode('-',$v);
            $id = $temp[0];
            $parent_id = $temp[1];
            if($parent_id != 0)
            {
                $id_level_1[] = $id;
            }
            $id_temp_str .= $id.',';
        }
        //顶级权限、二级权限字符串。
        $id_temp_str = rtrim($id_temp_str,',');
        //三级权限字符串
        $id_level_1_str = implode(',',$id_level_1);
        //找出第三级权限
        $id_level_2_arr = Db::name('Privilege')->field('id')->where('parent_id','in',$id_level_1_str)->select();
        $id_level_2_str = '';
        foreach($id_level_2_arr as $val)
        {
            $id_level_2_str .= $val['id'].',';
        }
        $id_level_2_str = rtrim($id_level_2_str,',');
        $id_total_str = $id_temp_str.','.$id_level_2_str;
        //构建插入数组
        $insert_data['user_id'] = $user_id;
        $insert_data['auth_str'] = $id_total_str;
        //判断是否已经插入
        $is_isset = Db::name('SysPersonAuth')->where('user_id',$user_id)->select();
        if($is_isset){
            $result = Db::name('SysPersonAuth')->where('user_id',$user_id)->setField('auth_str',$insert_data['auth_str']);
        }else{
            //插入数据
            $result = Db::name('SysPersonAuth')->insert($insert_data);
        }
        if($result !== false || $result == 0){
            echo setServerBackJson(1,"添加成功");
        }else{
            echo setServerBackJson(1,"添加失败");
        }
        
    }

    /** 查找对应人员  2018 2/7 周仁杰修改
     * @return string|void
     */
    public function search_user(){
        $nickname = input('nickname','','trim');
        if(empty($nickname)){
            return;
        }
        $html = '';
        if(preg_match('/^[\x{4e00}-\x{9fa5}]+$/u',$nickname) >0 )
        {
            $result = Db::name('sys_user')->field('id,nickname')->where('nickname','like',"%".$nickname."%")->limit(10)->select();
            foreach($result as $val){
                $html .= '<span style="margin-left:10px;width: 180px;height: 20px;display: inline-block;text-align: left;"><input  value="'.$val['id'].'"  type="radio" name="id"><button class="btn btn-primary btn-sm" style="width:65px;" value="'.$val['nickname'].'">'.$val['nickname'].'</button></span>';
            }
        }else
        {
            $result = Db::name('sys_user')->field('id,nickname')->where('user_gh','like',"%".$nickname."%")->limit(10)->select();
            foreach($result as $val){
                $html .= '<span style="margin-left:10px;width: 180px;height: 20px;display: inline-block; text-align: left;"><input  value="'.$val['id'].'" type="radio" name="id"><button class="btn btn-primary btn-sm" style="width:65px;" value="'.$val['nickname'].'">'.$val['nickname'].'</button></span>';
            }

        }
        return $html;
    }

    /**获得个人的模块权限  2018 2/7 周仁杰修改
     * @return string
     */
    public function get_user_auth(){
        $id = input('post.id','','trim');
        //找出人员对应的权限
        $auth_str = Db::name('sys_person_auth')->where('user_id',$id)->value('auth_str');
        $auth_temp = explode(',',$auth_str);
        $auth_data = [];
        foreach($auth_temp as $val){
            $auth_data[]['id'] = $val;
        }

        //取出系统的所有权限
        $nav_arr = Db::name('privilege')->field('id,pri_name as name,parent_id')->select();
        $pri_result = _reSort($nav_arr);
        $pri_result = make_tree($pri_result);
        $tree = make_Nav($pri_result,$auth_data,'id');
        return $tree;

    }







}