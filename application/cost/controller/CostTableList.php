<?php
namespace app\cost\controller;

use think\Controller;
use app\index\controller\Admin;
use think\Db;

class CostTableList extends Admin
{


	function change_sqlserver(){
		$sql = 'select id from sw_cost_cost  where isnull(o_id)=false and flow=4 and o_id>2135';
		$sql_str = Db::query($sql);
		return $sql_str;

	}
	
	//costTable页面
	public function index(){
		 $page_info = cost_get_field();
	     $key = cost_string_to_array(cost_config('6')); //模糊查询字段
	     
	     foreach($key as $k=>$v){
	     	$page_info['key'][$k]['name'] = $v;
	     	$page_info['key'][$k]['show'] = db('cost_field')->where('field',$v)->column('field_show');
	     	$page_info['key'][$k]['show'] = $page_info['key'][$k]['show']['0'];
	     }
	     //去掉rodno和type
	     unset($page_info['key']['0']); 
	     unset($page_info['key']['1']);


	    //產品線下拉框字段
	    $lines = db('cost_config')->where('name','line')->column('content');
	    $page_info['line'] = cost_string_to_array($lines['0']);
		$this->assign('page_info',$page_info);
		return  $this->fetch();
	}
	//获取cost数据
	public function costDate(){	
		$sort = isset($_POST['sort']) ? $_POST['sort'] : 'id';
		$order = isset($_POST['order']) ? $_POST['order'] : 'desc';
		$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
		$rows = isset($_POST['rows']) ? intval($_POST['rows']) : 10;
		$search = $_POST;
		//如果有查詢，則根據查詢条件筛选数据
		if(isset($search['key_field'])){
			if($search['key_field'] == '1'){
				if(!empty($search['key'])){
					$like_search = '%'.trim($search['key']).'%';
					$map['fab|CP_factory|Assy_ab|PIN|F_T_Out|tester|F_T_Tester']  = ['like',$like_search];
				}
			}else{
				if(!empty($search['key'])){
					$like_search = '%'.trim($search['key']).'%';
					$map[$search['key_field']]  = ['like',$like_search];
				}
			}
		}
		if(isset($search['key_field1'])){
			if($search['key_field1'] == '1'){
				if(!empty($search['key1'])){
					$like_search = '%'.trim($search['key']).'%';
					$map['fab|CP_factory|Assy_ab|PIN|F_T_Out|tester|F_T_Tester']  = ['like',$like_search];
				}
			}else{
				if(!empty($search['key1'])){
					$like_search = '%'.trim($search['key1']).'%';
					$map[$search['key_field1']]  = ['like',$like_search];
				}
			}
		}



		if(!empty($search['prdno'])){
			$map['prdno'] = ['like','%'.trim($search['prdno']).'%'];
		}
		if(!empty($search['type'])){
			$map['type'] = ['like','%'.trim($search['type']).'%'];
		}
		if(!empty($search['line']) && $search['line'] != '1'){
			$map['line'] = trim($search['line']);
		}
		if(!empty($search['cost_id'])){
			$map['id'] = trim($search['cost_id']);
		}

		$offset = ($page-1)*$rows;
		$map['show_type'] = '1';
		$qq = db('cost_cost')->where($map)->limit($offset,$rows)->order($sort,$order)->select();
		$qty= db('cost_cost')->where($map)->count();
		$json='{"total":'.$qty.',"rows":'.json_encode($qq).'}';
		echo $json;
	} 

	//单行修改页面
	function edit(){

		$cost_id = $_GET['id'];
		//获取字段信息
		$field = db('cost_field')->order('order','asc')->select();
		//获取登录人员权限
		$authorily = cost_get_authorily();		
	    //获取此条数据
		$res = db('cost_cost')->where('id',$cost_id)->find();

		
		//整合数据
		if($authorily == '2'){
			$page_info['info'] = array();
			foreach($field as $k=>$v){
				if($v['section'] == '4'){
					continue;
				}
				$page_info['info'][$k]['field'] = $v['field'];
				$page_info['info'][$k]['field_show'] = $v['field_show'];
				$page_info['info'][$k]['value'] = $res[$v['field']];
				$page_info['info'][$k]['section'] = $v['section'];
			}
		}
		if($authorily != '2'){
			$page_info['info'] = array();
			foreach($field as $k=>$v){
				if($v['section'] == '0' || $v['section'] == '4'){
					continue;
				}
				$page_info['info'][$k]['field'] = $v['field'];
				$page_info['info'][$k]['field_show'] = $v['field_show'];
				$page_info['info'][$k]['value'] = $res[$v['field']];
				$page_info['info'][$k]['section'] = $v['section'];			
			}
		}

	
		$page_info['id'] = $cost_id;
		$page_info['indexRow'] = $_GET['indexRow'];

		$page_info['authorily'] = $authorily;
		$page_info['field_category_1'] = cost_config(1);
		$page_info['field_category_4'] = cost_config(4);//需要下拉框字段

		$page_info['flow'] = $res['flow'];
		$this->assign('page_info',$page_info);
		return  $this->fetch();
	}

	//操作页面框
	function action(){

		//取出当前用户身份权限
		$author = cost_get_authorily();

		$page_info = cost_get_field();
		$page_info['id'] = $_GET['id'];
		$page_info['indexRow'] = $_GET['indexRow'];
		$flow = db('cost_cost')->where('id',$_GET['id'])->column('flow');//获取流程
		$flow = $flow['0'];
		$page_info['edit_show'] = '0';
		$page_info['copy_show'] = '0';
		$page_info['delete_show'] = '0';

		//当PE可修改人员操作时，可以看到copy按钮 ;流程为0或者4时，可以看到修改按钮
		if($page_info['authorily'] =='4'){
			$page_info['copy_show'] = '1';
			if($flow == '0' || $flow == '4'){
				$page_info['edit_show'] = '1';
			}
			$page_info['return'] = '0';
		}	
		//当TE可修改人员操作时，流程为1或者4时，可以看到修改按钮
		if($page_info['authorily'] =='6'){
			if($flow == '1' || $flow == '4'){
				$page_info['edit_show'] = '1';
			}
			if($flow == '1'){
				$page_info['return'] = '1';
			}else{
				$page_info['return'] = '0';
			}
			$page_info['ftCp'] = '1';
		}else{
			$page_info['ftCp'] = '0';
		}
		//当QE可修改人员操作时，流程为2或者4时，可以看到修改按钮，也有删除功能
		if($page_info['authorily'] =='8'){
			if($flow == '2' || $flow == '4'){
				$page_info['edit_show'] = '1';
				$page_info['delete_show'] = '1';
			}
			if($flow == '2'){
				$page_info['return'] = '1';
			}else{
				$page_info['return'] = '0';
			}
			
		}
		//当PP可修改人员操作时，流程为3或者4时，可以看到修改按钮
		if($page_info['authorily'] =='2'){
			if($flow == '3' || $flow == '4'){
				$page_info['edit_show'] = '1';
				
			}
			if($flow == '3'){
				$page_info['return'] = '1';
			}else{
				$page_info['return'] = '0';
			}
		}
		if($flow == '0'){
			$page_info['flow'] = "流程：<span style='color:red'>PE</span>->TE->QA->PP";
		}
		if($flow == '1'){
			$page_info['flow'] = "流程：PE-><span style='color:red'>TE</span>->QA->PP";
		}
		if($flow == '2'){
			$page_info['flow'] = "流程：PE->TE-><span style='color:red'>QA</span>->PP";
		}
		if($flow == '3'){
			$page_info['flow'] = "流程：PE->TE->QA-><span style='color:red'>PP</span>";
		}
		if($flow == '4'){
			$page_info['flow'] = "";
			$page_info['return'] = '0';
		}



        //当前操作流程确认
		//确定是否有提交申请
		$apply_result = Db::name('cost_apply')->where('cost_id',$page_info['id'])->find();
		$page_info['apply_check'] = $apply_result;
		$page_info['author'] = $author;
		$page_info['process_flow'] = $flow;


		$this->assign('page_info',$page_info);
		return  $this->fetch();
	}

	//单行内容修改
	function editRow(){
		$data = $_POST;
		$old = db('cost_cost')->where('id',$_POST['id'])->find();//获取修改之前数据
        //过滤数据
		foreach($old as &$val){
			$val = trim($val);
		}

		$old_flow = $old['flow']; //此条数据的流程
		$old = array_intersect_key($old,$data);//老数据中找出新数据提交的字段
		$changes = array_diff_assoc($old,$data);//两次数据不同
		unset($data['id']);
		if($old_flow == '4'){ //已经走完的流程再次修改
			if(empty($changes)){
				echo setServerBackJson(0,'数据未做任何修改!');exit;
			}	
			$log_changes = '';
			$reFlow = cost_string_to_array(cost_config(5));//需要重新走流程字段

			foreach($changes as $k=>$v){
				$explain = db('cost_field')->where('field',$k)->column('field_show');
				$explain = $explain['0'];//字段显示名称
				$log_changes .= "将".$explain."由<span style='color:red'> ".$v." </span>改为<span style='color:red'> ".$data[$k].'</span>;';
				if (in_array($k,$reFlow)){
					$data['flow'] = '0';
					if(isset($data['email'])){//如果点击提交下一步按钮
						$data['flow'] = '1';
						unset($data['email']);
					}
					$oldFlow = '4';
					$email = '1';

				}

			}
			$log = date('Y-m-d H:i:s',time()).' '.power_show(cost_get_authorily(),1).'部门'.get_user_nickname().$log_changes;
			//修改通知，部门后面加1与新建通知区分开
			$email_flag = power_show(cost_get_authorily(),1).'1';

//			$email = '1';	//发送邮件

			if(cost_get_authorily() == '2'){//如果为PP修改，log记录type为0，pe等部门无法查看
				cost_set_log($_POST['id'],$log,0);
			}else{
				cost_set_log($_POST['id'],$log,1);
			}


		}else{
			if(isset($data['email'])){
				//如果点击提交下一步按钮
				$data['flow'] = cost_get_flow();

				$email_flag = power_show(cost_get_authorily(),1);
				$email = '1';
				unset($data['email']);
				$log = date('Y-m-d H:i:s',time()).' '.power_show(cost_get_authorily(),1).'部门'.get_user_nickname().'提交了数据';
				if(cost_get_authorily() == '2'){//如果为PP修改，log记录type为0，pe等部门无法查看
					cost_set_log($_POST['id'],$log,0);
				}else{
					cost_set_log($_POST['id'],$log,1);
				}
				
			}else{
				if(empty($changes)){ 
					//如果没有提交下一步且没做任何修改 报错
					echo setServerBackJson(0,'数据未做任何修改!');exit;
				}
			}	
		}
		
		if(isset($data['email'])){
			unset($data['email']);
		}
			

		//修改数据
		$result =db('cost_cost')->where('id',$_POST['id'])->update($data);//修改数据

        if(isset($data['flow'])){
            //插入sqlserver数据库
            if($data['flow'] == 4 && isset($email)){
                cost_insert_sqlServer($_POST['id']);
                //获得当前最新的sqlserver
                $sql = "select max(id) as id from dbo.pp_cost";
                $sql=auto_charset($sql);
                $max_id = get_mssql_info('mms',$sql);
                $max_id = $max_id['0']['id'];
                //把sqlserver最新的id插入到mysql中
                Db::name('cost_cost')->where('id',$_POST['id'])->setField('o_id',$max_id);
            }
        }		

		//将数据插入mssql数据库
//		if($old_flow == '3' && isset($email)){
//			//sqlserver新增一条数据
//			$sql = "insert into dbo.pp_cost (prdno) values ('new')";
//			$sql=auto_charset($sql);
//			get_mssql_info('mms',$sql);
//			$sql = "select max(id) as id from dbo.pp_cost";
//			$sql=auto_charset($sql);
//			$max_id = get_mssql_info('mms',$sql);
//			$max_id = $max_id['0']['id'];
//			$data['o_id'] = $max_id;
//		}


		//这里预留发邮件；$email = 1时 发邮件
		if(isset($email)){
			if(isset($oldFlow)){
				$data =db('cost_cost')->where('id',$_POST['id'])->find();
				$content = 'ID号为'.$_POST['id'].'<br><br>';
				$content .= "<table border='1'style='border-collapse:collapse' width='500' cellspacing='0' cellpadding='0'>
						    <tr><th>id</th><th>产品线</th><th>typ no</th><th>pro no</th></tr>
						   <tr><td>&nbsp;&nbsp;".$_POST['id']."</td><td>&nbsp;&nbsp;".$data['line']."</td><td>&nbsp;&nbsp;".$data['type']."</td><td>&nbsp;&nbsp;".$data['prdno']."</td></tr>
					        </table>"."<br>";
				$content .= $log_changes."<br><br>";
				$content .= "下一步流程:TE确认";
				$address = explode(',', cost_sent_email($email_flag));


				send_email($address,'[新流程]',$content,$from='Finance.sh@sinowealth.com',"CostTable系统通知",1);
//				foreach($address as $v){
//					send_email($v,'[新流程]',$content,$from='Finance.sh@sinowealth.com',"CostTable系统通知",1);
//				}


			}else{

				$data =db('cost_cost')->where('id',$_POST['id'])->find();
	//			$body = $log.'<br><br>';
				$flowMessage = get_flow($data['flow']);
				$content = date('Y-m-d H:i:s',time()).'当前流程为'.$flowMessage.'<br><br>'.power_show(cost_get_authorily(),1).'部门'.get_user_nickname()."提交如下框所示的数据";
				$body = $content.'<br><br>';
				$body .= "<table border='1'style='border-collapse:collapse' width='500' cellspacing='0' cellpadding='0'>
						<tr><th>id</th><th>产品线</th><th>typ no</th><th>pro no</th></tr>
						<tr><td>&nbsp;&nbsp;".$_POST['id']."</td><td>&nbsp;&nbsp;".$data['line']."</td><td>&nbsp;&nbsp;".$data['type']."</td><td>&nbsp;&nbsp;".$data['prdno']."</td></tr>
					</table>"."<br>".date('Y-m-d H:i:s',time());
				$address = explode(',', cost_sent_email($email_flag));

				send_email($address,'CostTable数据提交',$body,$from='Finance.sh@sinowealth.com',"CostTable系统通知",1);
//				foreach($address as $v){
//					send_email($v,'CostTable数据提交',$body,$from='Finance.sh@sinowealth.com',"CostTable系统通知",1);
//				}
			}
		}else{
			$data =db('cost_cost')->where('id',$_POST['id'])->find();
			$content = 'ID号为'.$_POST['id'].'<br><br>';
			$content .=  power_show(cost_get_authorily(),1).'部门'.get_user_nickname()."修改如下框所示";
			$content .= "<table border='1'style='border-collapse:collapse' width='500' cellspacing='0' cellpadding='0'>
						<tr><th>id</th><th>产品线</th><th>typ no</th><th>pro no</th></tr>
						<tr><td>&nbsp;&nbsp;".$_POST['id']."</td><td>&nbsp;&nbsp;".$data['line']."</td><td>&nbsp;&nbsp;".$data['type']."</td><td>&nbsp;&nbsp;".$data['prdno']."</td></tr>
					</table>"."<br>";
			$content .= $log_changes."<br><br>";
			$address = explode(',', cost_sent_email($email_flag));
			send_email($address,'[已完成]数据修改',$content,$from='Finance.sh@sinowealth.com',"CostTable系统通知",1);
//			foreach($address as $v){
//				send_email($v,'[已完成]数据修改',$content,$from='Finance.sh@sinowealth.com',"CostTable系统通知",1);
//			}

		}
		//如果当前流程为已经走完的流程

		cost_edit_sqlSever($_POST['id']);
		echo setServerBackJson(1,'修改成功!','','closeDialog');	
	}
	//单行内容删除
	function deleteRow(){
		$info = db('cost_cost')->where('id',$_GET['id'])->select();
		$result = db('cost_cost')->where('id',$_GET['id'])->setField('show_type','0');
		$log = date('Y-m-d H:i:s',time()).' '.get_user_nickname().'删除了数据';
		cost_set_log($_GET['id'],$log,1);
		//这里预留发邮件
		$body = get_user_nickname().'删除了以下数据'.':<br><br>';
		$body .= "<table border='1' style='border-collapse:collapse' width='500' cellspacing='0' cellpadding='0'>
					<tr><th>id</th><th>产品线</th><th>typ no</th><th>pro no</th></tr>
					<tr><td>&nbsp;&nbsp;".$info['0']['id']."</td><td>&nbsp;&nbsp;".$info['0']['line']."</td><td>&nbsp;&nbsp;".$info['0']['type']."</td><td>&nbsp;&nbsp;".$info['0']['prdno']."</td></tr>	
				</table>"."<br>".date('Y-m-d H:i:s',time());
		$address = explode(',', cost_sent_email('QA1'));\

		send_email($address,'QA删除记录',$body,'Finance.sh@sinowealth.com','Cost Table通知');
//		foreach($address as $v){
//			send_email($v,'QA删除记录',$body,'Finance.sh@sinowealth.com','Cost Table通知');
//		}
		echo setServerBackJson(1,'操作成功!');
	}
	//pe复制整行
	function copy(){
		$cost_id = $_GET['id'];
		//获取字段信息
		$field = db('cost_field')->order('id','asc')->select();	
	    //获取此条数据
		$res =db('cost_cost')->where('id',$cost_id)->find();
		$page_info['prdno'] = $res['prdno'];
		$res['prdno'] = $res['prdno'].'-copy';
		//架构的表达 
		$page_info['info'] = array();
		foreach($field as $k=>$v){
			if($v['section'] == '1'){ //只获取pe可修改字段
			$page_info['info'][$k]['field'] = $v['field'];
			$page_info['info'][$k]['field_show'] = $v['field_show'];
			$page_info['info'][$k]['value'] = $res[$v['field']];
			}
		}
		$page_info['field_category_2'] = cost_config(2);//数据较长字段
		$page_info['field_category_4'] = cost_config(4);//需要下拉框字段
		$this->assign('page_info',$page_info);

		return  $this->fetch();
	}
	//复制内容添加
	function copyRow(){
		$data = $_POST;
		unset($data['id']);
		if(isset($data['email'])){//如果点击提交下一步按钮
			$data['flow'] = 1;
			unset($data['email']);
		}				
		
		$id = db('cost_cost')->insertGetId($data);
		if(isset($data['flow'])){
			$log = date('Y-m-d H:i:s',time()).' '.get_user_nickname().'新增并提交了数据';
		}else{
			$log = date('Y-m-d H:i:s',time()).' '.get_user_nickname().'新增了数据';
		}
		
		cost_set_log($id,$log,'1');//写进log
		
		if(isset($data['flow'])){ //如果点击提交下一步按钮，发送邮件
			$body = get_user_nickname().'新增了数据:<br><br>';
			$body .= "<table border='1' style='border-collapse:collapse' width='500' cellspacing='0' cellpadding='0'>
					  <tr><th>id</th><th>产品线</th><th>typ no</th><th>pro no</th></tr>
					  <tr><td>&nbsp;&nbsp;".$id."</td><td>&nbsp;&nbsp;".$data['line']."</td><td>&nbsp;&nbsp;".$data['type']."</td><td>&nbsp;&nbsp;".$data['prdno']."</td></tr>
				     </table>"."<br>".date('Y-m-d H:i:s',time());
			$address = explode(',', cost_sent_email('PE'));

			send_email($address,'[新流程]PE新增记录',$body,'Finance.sh@sinowealth.com','Cost Table通知',1);
//			foreach($address as $v){
//				send_email($v,'[新流程]PE新增记录',$body,'Finance.sh@sinowealth.com','Cost Table通知',1);
//			}
		}
		
		echo setServerBackJson(1,'添加成功!','','closeDialog');
	}
	//pe添加页面弹出
	function add(){
		//获取字段信息
		$field = db('cost_field')->order('order','asc')->select();
		//整合数据
		$page_info['info'] = array();
		foreach($field as $k=>$v){

			if($v['section'] == '1'){

				$page_info['info'][$k]['field'] = $v['field'];
			    $page_info['info'][$k]['field_show'] = $v['field_show'];
			}			
		}

		$page_info['field_category_4'] = cost_config(4);//需要下拉框字段
//		$page_info['field_category_4'] = cost_config(4);//需要下拉框字段
		$this->assign('page_info',$page_info);
	
		return  $this->fetch();
	}
	//添加数据写入
	function addRow(){
		$data = $_POST;
		if(!is_numeric($data['CP_Yld'])){
			echo setServerBackJson(0,"CP_YId栏位只能填写数字");exit;
		}
		if(isset($data['email'])){//如果点击提交下一步按钮
			$data['flow'] = 1;
			unset($data['email']);
		}
        //判断是否叠片
		$dp_arr = array("Y","y","N","n");
		if(!in_array(trim($data['dp']),$dp_arr)){
			echo setServerBackJson(0, "叠片只能填写Y或N");exit;
		}
		$data['dp'] = trim($data['dp']);
		$data['type_p'] = trim($data['type']);

		$id = db('cost_cost')->insertGetId($data);
		//log记录 多个type时存入多条log
		if(isset($data['email'])){
			$log = date('Y-m-d H:i:s',time()).' '.get_user_nickname().'新增并提交了数据';
		}else{
			$log = date('Y-m-d H:i:s',time()).' '.get_user_nickname().'新增了数据';
		}			
		cost_set_log($id,$log,'1');//写进log
			
		if($data['flow'] = 1){
			//发送邮件
			$body = 'PE部门的'.get_user_nickname().'新增了数据:<br><br>';
			$body .= "<table border='1' style='border-collapse:collapse' width='500' cellspacing='0' cellpadding='0'>
					<tr><th>id</th><th>产品线</th><th>typ no</th><th>pro no</th></tr>
					<tr><td>&nbsp;&nbsp;".$id."</td><td>&nbsp;&nbsp;".$data['line']."</td><td>&nbsp;&nbsp;".$data['type']."</td><td>&nbsp;&nbsp;".$data['prdno']."</td></tr>
				</table>"."<br>".date('Y-m-d H:i:s',time());
			$address = explode(',', cost_sent_email('PE'));

			send_email($address,'[新流程]PE新增记录',$body,'Finance.sh@sinowealth.com','Cost Table通知',1);
//			var_dump($address);die;
//			foreach($address as $v){
//				send_email($v,'[新流程]PE新增记录',$body,'Finance.sh@sinowealth.com','Cost Table通知',1);
//			}
		}
		echo setServerBackJson(1,'添加成功!','','closeDialog');
	}
	//搜索按钮
	function search(){
		//获取字段信息
		$field = db('cost_field')->select();		
		//整合数据
		$page_info['info'] = array();
		foreach($field as $k=>$v){
			if($k > 10){
				continue;
			}		
			$page_info['info'][$k]['field'] = $v['field'];
			$page_info['info'][$k]['field_show'] = $v['field_show'];
		}
		$this->assign('page_info',$page_info);		
		return  $this->fetch();
	}
	//显示日志
	function showLog(){
		$res = cost_get_authorily();
		if($res == '1' || $res == '2' || $res == '9'){
			$result = db('cost_log')->where('data_id',$_GET['id'])->order('id','desc')->select();
		}else{
			$result = db('cost_log')->where('data_id',$_GET['id'])->where('type','1')->order('id','desc')->select();
		}
		
		$log = '';
		foreach($result as $v){
			$log .= $v['log'].'<br>';
		}	
		echo $log;
	}
	//导出excel报表
	function getExcel(){
		$search = $_GET;
		//关键词一
		if($search['key_field'] == '1'){
			if(!empty($search['key'])){
				$like_search = '%'.$search['key'].'%';
				$map['type|prdno|fab|CP_factory|Assy_ab|PIN|F_T_Out|tester|F_T_Tester']  = ['like',$like_search];
			}
		}else{
			if(!empty($search['key'])){
					$like_search = '%'.$search['key'].'%';
					$map[$search['key_field']]  = ['like',$like_search];
				}
		}

		//关键词二
		if($search['key_field1'] == '1'){
			if(!empty($search['key1'])){
				$like_search1 = '%'.$search['key1'].'%';
				$map['type|prdno|fab|CP_factory|Assy_ab|PIN|F_T_Out|tester|F_T_Tester']  = ['like',$like_search1];
			}
		}else{
			if(!empty($search['key1'])){
				$like_search1 = '%'.$search['key1'].'%';
				$map[$search['key_field1']]  = ['like',$like_search1];
			}
		}
		
		if(!empty($search['prdno'])){
			$map['prdno'] = ['like','%'.$search['prdno'].'%'];
		}
		if(!empty($search['type'])){
			$map['type'] = ['like','%'.$search['type'].'%'];
		}
		if(!empty($search['line']) && $search['line'] != '1'){
			$map['line'] = $search['line'];
		}
		$res = cost_get_authorily();
		if($res == '1' || $res == '2' || $res == '9'){
			$data['file_key'] = 'cost_table_list';
			$field = db('cost_field')->where('section <>4')->order('order','asc')->select();
		}else{
			$data['file_key'] = 'cost_table';
			$field = db('cost_field')->where('section <>0')->where('section <>4')->order('order','asc')->select();
		}
		$head = array();
		foreach($field as $k=>$v){
			$head[] = $v['field_show'];
			$select[] = $v['field'];
		}
		
		array_unshift($select,"flow");
		$data['tb_head'] = $select;
		$select = cost_array_to_string($select);
		$map['show_type'] = '1';
		$qq =db('cost_cost')->where($map)->field($select)->select();
		foreach($qq as $k=>$v){
			if($v['flow'] == '0'){
				$qq[$k]['flow'] = 'PE';
			}
			if($v['flow'] == '1'){
				$qq[$k]['flow'] = 'TE';
			}
			if($v['flow'] == '2'){
				$qq[$k]['flow'] = 'QA';
			}
			if($v['flow'] == '3'){
				$qq[$k]['flow'] = 'PP';
			}
			if($v['flow'] == '4'){
				$qq[$k]['flow'] = '已完成';
			}
		}
		if(empty($qq)){
			echo '<meta charset="utf-8">';
			echo "此搜索条件下无数据";
			echo "&nbsp;&nbsp;&nbsp;<a href='#' onClick='javascript :history.back(-1);'>返回</a>";
			exit;
		}
		// $data['file_name']-->文件名,$data['tb_tit']-->标题,$data['tb_head']-->表头,$data['tb_body']-->表格内容,如果为模板文件构建,则需要有字段$data['file_key']对应数据表sw_sys_file_normal
		//$data['file_info']-->Excel文件信息,默认设置项, $data['file_type']-->输出文件类型,默认Excel5
		$data['file_name'] = 'CostTable';
		$data['tb_tit'] = 'CostTable';
		//$data['td_format']=array('','string');
		//$data['file_key'] = 'costtable';
		$data['tb_body'] = $qq;
		ext_excel('normal',$data);

	}
	//批量修改页面
	function editBySearch(){
		$res = cost_get_authorily();
		$search = $_GET;
//		var_dump($search);die;
		if($search['key_field'] == '1'){
			if(!empty($search['key'])){
				$like_search = '%'.$search['key'].'%';
				$map['type|prdno|fab|CP_factory|Assy_ab|PIN|F_T_Out|tester|F_T_Tester']  = ['like',$like_search];
			}
		}else{
			if(!empty($search['key'])){
					$like_search = '%'.$search['key'].'%';
					$map[$search['key_field']]  = ['like',$like_search];
				}
		}


		if($search['key_field'] == '1'){
			if(!empty($search['key1'])){
				$like_search1 = '%'.trim($search['key1']).'%';
				$map['type|prdno|fab|CP_factory|Assy_ab|PIN|F_T_Out|tester|F_T_Tester']  = ['like',$like_search1];
			}
		}else{
			if(!empty($search['key1'])){
				$like_search1 = '%'.trim($search['key1']).'%';
				$map[$search['key_field1']]  = ['like',$like_search1];
			}
		}

		
		if(!empty($search['prdno'])){
			$map['prdno'] = ['like','%'.trim($search['prdno']).'%'];
		}
		if(!empty($search['type'])){
			$map['type'] = ['like','%'.trim($search['type']).'%'];
		}
		if(!empty($search['line']) && $search['line'] != '1'){
			$map['line'] = trim($search['line']);
		}
		$map['flow'] = '4';
		$map['show_type'] = '1';
		$page_info['count'] = db('cost_cost')->where($map)->count();
		
		if($res == '2'){
			$field = db('cost_field')->where("field in ('by60','PUG','by23')")->order('id','asc')->select();
		}
		if($res == '4'){
			$field = db('cost_field')->where('section','1')->order('id','asc')->select();
		}
		if($res == '6'){
			$field = db('cost_field')->where('section','2')->order('id','asc')->select();
		}
		if($res == '8'){
			$field = db('cost_field')->where('section','3')->order('id','asc')->select();
		}
		$page_info['field'] = $field;
		$this->assign('page_info',$page_info);
		return  $this->fetch();
	}
	//批量修改select展示
	function ajaxSetField(){
		//判断字段是否需要下拉框
		$field = $_POST['field'];
		if(in_array($field,cost_string_to_array(cost_config(4)))){
			$res = db('cost_config')->where('name',$field)->column('content');
			$content = cost_string_to_array($res['0']);	
			$content = array_filter($content);
			//如果所选字段是下拉框，则拼接下拉框显示
			$html = "<select name='content'>";		
			foreach($content as $v){
				$html.="<option value='".$v."'>".$v.'</option>';
			}
			$html.='</select>';
			echo $html;
		}else{
			echo '1';
		}
	}

	//批量修改
	function doEdits(){
		$search = $_POST;
		if($search['field'] == ''){
			echo setServerBackJson(0,'请选择要修改字段!');exit;
		}
		if($search['content'] == ''){
			echo setServerBackJson(0,'请输入要修改内容!');exit;
		}

		//关键词一
		if($search['key_field'] == '1'){
			if(!empty($search['key'])){
				$like_search = '%'.$search['key'].'%';
				$map['type|prdno|fab|CP_factory|Assy_ab|PIN|F_T_Out|tester|F_T_Tester']  = ['like',$like_search];
			}
		}else{
			if(!empty($search['key'])){
					$like_search = '%'.$search['key'].'%';
					$map[$search['key_field']]  = ['like',trim($like_search)];
				}
		}

		//关键词二
		if($search['key_field1'] == '1'){
			if(!empty($search['key1'])){
				$like_search = '%'.$search['key1'].'%';
				$map['type|prdno|fab|CP_factory|Assy_ab|PIN|F_T_Out|tester|F_T_Tester']  = ['like',$like_search];
			}
		}else{
			if(!empty($search['key1'])){
				$like_search = '%'.$search['key1'].'%';
				$map[$search['key_field1']]  = ['like',trim($like_search)];
			}
		}



		if(!empty($search['prdno'])){
			$map['prdno'] = ['like','%'.trim($search['prdno']).'%'];
		}
		if(!empty($search['type'])){
			$map['type'] = ['like','%'.trim($search['type']).'%'];
		}
		if(!empty($search['line']) && $search['line'] != '1'){
			$map['line'] = trim($search['line']);
		}
		$map['flow'] = '4';
		$ids = db('cost_cost')->where($map)->select();
		//'by60','PUG','by23'
		$userType = cost_get_authorily();
		$res = '';
		//操作人员是PP，且修改了PUG和by23字段，需要计算公式计算出影响的字段（by60字段修改不会影响其他字段）
		if($userType == '2' && $search['field'] != 'by60'){
			foreach($ids as $v){
				$oldList = db('cost_cost')->where('id',$v['id'])->find();
				$editData = array();
				$logHtml = '';
				if($search['field'] == 'PUG'){
					//U_Cost_US = WF_Die+ CP_Die + Ym +PUG+Die+ F_T +by30+by35+by37+by39
					$editData['PUG'] = $search['content'];
					$editData['U_Cost_US'] = floatval($oldList['WF_Die'])+floatval($oldList['CP_Die'])+floatval($oldList['Ym'])+floatval($search['content'])+floatval($oldList['Die'])+floatval($oldList['F_T'])+floatval($oldList['by30'])+floatval($oldList['by35'])+floatval($oldList['by37'])+floatval($oldList['by39']);
					$res =db('cost_cost')->where('id',$v['id'])->setField($editData);
					$logHtml = date('Y-m-d H:i:s',time()).' '.get_user_nickname()."将".fieldGetShow($search['field'])."改为<span style='color:red'> ".$search['content'].'</span>,将'.fieldGetShow('U_Cost_US')."改为<span style='color:red'> ".$editData['U_Cost_US'].'</span>;';
				}
				if($search['field'] == 'by23'){
					//F_T = F_T_TWEO/by23
					//U_Cost_US = WF_Die+ CP_Die + Ym +PUG+Die+ F_T +by30+by35+by37+by39
					$editData['by23'] = $search['content'];
					$editData['F_T'] = floatval($oldList['F_T_TWEO'])/floatval($search['content']);
					$editData['U_Cost_US'] = floatval($oldList['WF_Die'])+floatval($oldList['CP_Die'])+floatval($oldList['Ym'])+floatval($oldList['PUG'])+floatval($oldList['Die'])+floatval($editData['F_T'])+floatval($oldList['by30'])+floatval($oldList['by35'])+floatval($oldList['by37'])+floatval($oldList['by39']);
					$res =db('cost_cost')->where('id',$v['id'])->setField($editData);
					$logHtml = date('Y-m-d H:i:s',time()).' '.get_user_nickname()."将".fieldGetShow($search['field'])."改为<span style='color:red'> ".$search['content'].'</span>,将'.fieldGetShow('F_T')."改为<span style='color:red'> ".$editData['F_T'].'</span>,将'.fieldGetShow('U_Cost_US')."改为<span style='color:red'> ".$editData['U_Cost_US'].'</span>;';

				}
			}
		}else{

			if($search['field'] == 'line' || $search['field'] == 'prdno' || $search['field'] == 'type' ){

				$res = db('cost_cost')->where($map)->setField('flow','0');
			}

			$res = db('cost_cost')->where($map)->setField($search['field'],$search['content']);
		}
		
		if($res>0){	
			if(isset($logHtml)){
				$log = $logHtml;//如果是PP修改计算影响其他字段数据，将所有改变的字段都记入log
			}else{
				$log = date('Y-m-d H:i:s',time()).' '.get_user_nickname()."将".fieldGetShow($search['field'])."改为<span style='color:red'> ".$search['content'].'</span>;';			
			}
			$html = '';
			$id_array = array();
			foreach($ids as $v){
				if($userType == '2'){
					cost_set_log($v['id'],$log,'0');//写进log
				}else{
					cost_set_log($v['id'],$log,'1');//写进log
				}
				$html .= "<tr><td>&nbsp;&nbsp;".$v['id']."</td><td>&nbsp;&nbsp;".$v['line']."</td><td>&nbsp;&nbsp;".$v['type']."</td><td>&nbsp;&nbsp;".$v['prdno']."</td></tr>";
				$id_array[] = $v['id']; 
				cost_edit_sqlSever($v['id']); //批量修改sqlserver数据库数据
			}
			//批量修改记入log
			cost_batch_log(cost_array_to_string($id_array),$log,'1');
			//发邮件写日志
			$body = $log.'<br><br>';
			$body .= "<table border='1'  style='border-collapse:collapse' width='500' cellspacing='0' cellpadding='0'>
					<tr><th>id</th><th>产品线</th><th>typ no</th><th>pro no</th></tr>
					".$html."
				</table>";
			$address = explode(',', cost_sent_email(power_show(cost_get_authorily(),1).'1'));
			send_email($address,'数据批量修改',$body,'Finance.sh@sinowealth.com','Cost Table通知');
//			foreach($address as $v){
//				send_email($v,'数据批量修改',$body,'Finance.sh@sinowealth.com','Cost Table通知');
//			}
			echo setServerBackJson(1,'修改成功!',1);exit;
		}else{
			echo setServerBackJson(0,'本次无数据修改!',1);exit;
		}		
	}
	//返回上个部门页面 （流程回退）
	function back(){
		$page_info['id'] = $_GET['id']; 
		$this->assign('page_info',$page_info);
		return  $this->fetch();
	}
	//返回上个部门
	function doBack(){
		$id = $_POST['id'];
		$reason = $_POST['reason'];  
		if($reason ==''){
			echo setServerBackJson(0,'请填写退回原因!');exit;
		}
		//修改流程
		$oldFlow = db('cost_cost')->where('id',$id)->select();
		$oldFlow = $oldFlow['0'];
		if($oldFlow == '0'){
			$newFlow = 0;
		}else{
			$newFlow = $oldFlow['flow']-1;
		}

		$res = db('cost_cost')->where('id',$id)->setField('flow',$newFlow); 
		
		$html = "<tr><td>&nbsp;&nbsp;".$id."</td><td>&nbsp;&nbsp;".$oldFlow['line']."</td><td>&nbsp;&nbsp;".$oldFlow['type']."</td><td>&nbsp;&nbsp;".$oldFlow['prdno']."</td></tr>";
		//记入log
		$log = date('Y-m-d H:i:s',time()).' '.power_show(cost_get_authorily(),1).'部门'.get_user_nickname().'退回流程，退回原因：'.$reason;
		
		//发送邮件
		$user_id = db('cost_log')->where('data_id',$id)->limit(1)->order('id desc')->column('user_id');
		
		cost_set_log($id,$log,'1');//写进log			
		if($user_id['0'] >0){
			$email = db('sys_user')->where('id',$user_id['0'])->column('email');
			$body = $log.'<br><br>';
			$body .= "<table border='1'  style='border-collapse:collapse' width='500' cellspacing='0' cellpadding='0'>
					  <tr><th>id</th><th>产品线</th><th>typ no</th><th>pro no</th></tr>
					 ".$html."
				     </table>";
			send_email($email['0'],'数据流程回退',$body,'Finance.sh@sinowealth.com','Cost Table通知',1);
		}
		
		echo setServerBackJson(1,'退回成功！',1);exit;
	}



	//提交删除申请
	public function subDelApply(){
		$cost_id = input('id');
		$page_info['id'] = $cost_id;
		$this->assign('page_info',$page_info);
		return $this->fetch();
	}



	//提交申请数据
	public function addSubApply(){
		$login_name = get_cache_data('user_info',session('login_id'),'nickname');
		$data = $_POST;
		$data['apply_reason'] = trim($data['apply_reason']);
		if(empty($data['apply_reason'])){
			echo setServerBackJson(0,"申请理由不能为空");exit;
		}
		$insert_data = array();
		$insert_data['id'] = $data['id'];
//		 $insert_data['is_apply_del'] = 1;
		$insert_data['apply_reason'] = $data['apply_reason'];

		//写进日志记录
		$log = date('Y-m-d H:i:s',time()).' '.power_show(cost_get_authorily(),1).'部门'.get_user_nickname().'提交删除记录请求';
		cost_set_log($insert_data['id'],$log,'1');
//
//		//更新数据
//		Db::name('cost_cost')->where('id',$insert_data['id'])->update($insert_data);

		$apply_data['cost_id'] = $insert_data['id'];
		$apply_data['apply_reason'] = $insert_data['apply_reason'];
		//插入当前的关联数据表
		Db::name('cost_apply')->insert($apply_data);

		//取出当前删除的数据的相关信息
		$cost_data = Db::name('cost_cost')->where('id',$insert_data['id'])->field('id,line,prdno,type')->find();

		//邮件通知QA部门
		$power = cost_get_authorily();
		$power_show = power_show($power,1);

		//取出人员
		$id_str = Db::name('cost_delele_email_config')->where('dep_name',$power_show)->value('id_str');
		$id_arr = explode(',',$id_str);
		//取出发送的信息
		$message = $log.'<br><br>';
		$message .= "<table border='1' style='border-collapse:collapse' width='500' cellspacing='0' cellpadding='0'>
					 <tr><th>id</th><th>产品线</th><th>typ no</th><th>pro no</th></tr>";
		$message .= "<tr><td>&nbsp;&nbsp;".$cost_data['id']."</td><td>&nbsp;&nbsp;".$cost_data['line']."</td><td>&nbsp;&nbsp;".$cost_data['type']."</td><td>&nbsp;&nbsp;".$cost_data['prdno']."</td></tr>";
        $email_arr = array();
		foreach($id_arr as $val){
           	$email_arr[] = get_cache_data('user_info',$val,'email');
		}
		send_email($email_arr,$power_show."部门的".$login_name."提交删除申请",$message,'Finance.sh@sinowealth.com','Cost Table通知',1);

//		foreach($id_arr as $val){
//			$email = get_cache_data('user_info',$val,'email');
//			var_dump($email);die;
//			send_email($email,$power_show."部门的".$login_name."提交删除申请",$message,'Finance.sh@sinowealth.com','Cost Table通知',1);
//		}

		echo setServerBackJson(1,"提交删除申请成功!",1);
	}

	//显示删除请求理由
	public function showDeleteRea(){
		$id = input('id');
		$reason = Db::name('cost_apply')->where('cost_id',$id)->value('apply_reason');
		echo "申请理由：".'&nbsp;&nbsp;'.$reason;
	}

}

