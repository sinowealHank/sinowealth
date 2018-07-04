<?php
namespace app\user\controller;
use think\Db;
use app\index\controller\Admin;

class PrdLists extends Admin {
	//拉取主页面
	public function index(){
		//$list=db()->query("select distinct belong from sw_lists_list");
		$list=array(array('belong'=>'XA'),array('belong'=>'SH'));
		$select="";
		foreach ($list as $re_b){
			$select=$select.'<option value="'.$re_b['belong'].'">'.$re_b['belong'].'</option>';
		}
		$select=$select.'';
		$this->assign('select', $select);
		$this->assign('result_arr_belong', $list);
		
		$rule_arr=array(array('rule'=>'内销'),array('rule'=>'外销或西安'));
		$rule="";
		foreach ($rule_arr as $re_b){
			$rule=$rule.'<option value="'.$re_b['rule'].'">'.$re_b['rule'].'</option>';
		}
		$rule=$rule.'';
		$this->assign('rule', json_encode($rule));
		return $this->fetch();
	}
	public function index_ajax(){
		$wafer=isset($_POST['wafer'])?$_POST['wafer']:'';
		$Typ_no=isset($_POST['Typ_no'])?$_POST['Typ_no']:'';
		$Partnumber=isset($_POST['Partnumber'])?$_POST['Partnumber']:'';
		$rule=isset($_POST['rule'])?$_POST['rule']:'';
		$belong=isset($_POST['belong'])?$_POST['belong']:'';
		$wafer_sql='where 1=1 ';$Typ_no_sql='';$Partnumber_sql='';$belong_sql='';$rule_sql='';
		
		if($wafer){$wafer_sql='where wafer_belong like "%'.$wafer.'%" ';}
		if($Typ_no){$Typ_no_sql='and Typ_no like "%'.$Typ_no.'%" ';}
		if($Partnumber){$Partnumber_sql='and Partnumber like "%'.$Partnumber.'%" ';}
		if($belong){$belong_sql='and belong like "%'.$belong.'%" ';}
		if($rule){$rule_sql='and rule = "'.$rule.'" ';}
		
		$ii=isset($_GET['ii'])?$_GET['ii']:'id desc';
		if($ii=='id' || $ii=='id desc' || $ii=='wafer_belong' || $ii=='wafer_belong desc' || $ii=='typ_no' || $ii=='typ_no desc' || $ii=='Partnumber' || $ii=='Partnumber desc' || $ii=='belong' || $ii=='belong desc'){}else{$ii='id';}
		if(isset($_GET['a'])?$_GET['a']:''==1){$ii=$ii.' desc';}
		
		$list=db()->query("select * from sw_lists_list ".$wafer_sql.$Typ_no_sql.$Partnumber_sql.$belong_sql.$rule_sql."order by ".$ii);
		$list_belong['belong']=db()->query("select distinct belong from sw_lists_list");
		$list_belong['belong']=array(array('belong'=>'XA'),array('belong'=>'SH'));
		
		$list_belong['rule']=db()->query("select distinct rule from sw_lists_list");
		$list_belong['rule']=array(array('rule'=>'内销'),array('rule'=>'外销或西安'));
		if(isset($_GET['a'])?$_GET['a']:''==1){
			$zt='icon-level-up';
		}else{$zt='icon-level-down';}
		echo json_encode(array($list,$list_belong,$zt));
	}
	//添加
	public function add(){
		$wafer_belong=isset($_POST['wafer_belong'])?$_POST['wafer_belong']:'';
		if(!$wafer_belong){echo json_encode(array('0','缺少wafer'));exit;}
		$typ_no=isset($_POST['typ_no'])?$_POST['typ_no']:'';
		if(!$typ_no){echo json_encode(array('0','缺少typ_no'));exit;}
		$Partnumber=isset($_POST['Partnumber'])?$_POST['Partnumber']:'';
		if(!$Partnumber){echo json_encode(array('0','缺少Partnumber'));exit;}
		$belong=isset($_POST['belong'])?$_POST['belong']:'';
		if(!$belong){echo json_encode(array('0','缺少归属'));exit;}
		$rule=isset($_POST['rule'])?$_POST['rule']:'';
		
		$year=db()->query("select * from sw_lists_list where Partnumber='".$Partnumber."'");
		if($year){echo json_encode(array('0','该Partnumber已存在'));exit;}
		
		$page_info=array(
				'wafer_belong'=>$wafer_belong,
				'typ_no'=>$typ_no,
				'Partnumber'=>$Partnumber,
				'belong'=>$belong,
				'rule'=>$rule,
		);
		db('lists_list')->insert($page_info);
		echo json_encode(array('1','添加成功'));
	}
	//修改
	public function change(){
		$id=isset($_POST['id'])?$_POST['id']:'';
		if(!$id){echo json_encode(array('0','缺少id'));exit;}
		$Partnumber=isset($_POST['Partnumber'])?$_POST['Partnumber']:'';
		if(!$Partnumber){echo json_encode(array('0','缺少Partnumber'));exit;}
		$belong=isset($_POST['belong'])?$_POST['belong']:'';
		if(!$belong){echo json_encode(array('0','缺少归属'));exit;}
		$rule=isset($_POST['rule'])?$_POST['rule']:'';
		if(!$rule){echo json_encode(array('0','缺少规则'));exit;}
		$sql='update sw_lists_list set belong="'.$belong.'" , rule="'.$rule.'" where id="'.$id.'" and Partnumber="'.$Partnumber.'"';
		$count=db()->query($sql);
		echo json_encode(array('1','修改成功'));
	}
	//删除
	public function del(){
		$id=isset($_POST['id'])?$_POST['id']:'';
		if(!$id){echo json_encode(array('0','缺少id'));exit;}
		$Partnumber=isset($_POST['Partnumber'])?$_POST['Partnumber']:'';
		if(!$Partnumber){echo json_encode(array('0','缺少Partnumber'));exit;}
		
		$sql='delete from sw_lists_list where id="'.$id.'" and Partnumber="'.$Partnumber.'"';
		$count=db()->query($sql);
		
		echo json_encode(array('1','删除成功'));
	}
	//导入表格
	public function list_excel_in(){exit;
		$arr_1=explode('.',$_FILES['file']['name']);
		if(strtolower(end($arr_1))!='xls'){
			echo json_encode(array('0','请上传xls格式文件'));exit;
		}
		
		require_once \think\Config::get('public').'/../thinkphp/extend/PHPExcel/PHPExcel.php';//引入文件
		require_once \think\Config::get('public').'/../thinkphp/extend/PHPExcel/PHPExcel/IOFactory.php';
		require_once \think\Config::get('public').'/../thinkphp/extend/PHPExcel/PHPExcel/Reader/Excel5.php';
		$objReader = \PHPExcel_IOFactory::createReader('excel5');//use excel2007 for 2007 format
		//$filename=\think\Config::get('public').'/cs/List_20170803.xls';
		//$filename=$user_path.'/'.$name;
		$filename=$_FILES['file']['tmp_name'];
		//轉西安產品
		$objPHPExcel = $objReader->load($filename); //$filename可以是上传的文件，或者是指定的文件
		$sheet = $objPHPExcel->getSheet(0);
		$highestRow = $sheet->getHighestRow(); // 取得总行数
		$highestColumn = $sheet->getHighestColumn(); // 取得总列数
		$k = 0;
		
		//循环读取excel文件,读取一条,插入一条
		$wafer_belong_bf;$typ_no_bf;
		$page_info=array();
		for($j=2;$j<=$highestRow;$j++){
		
			$wafer_belong = $objPHPExcel->getActiveSheet()->getCell("A".$j)->getValue();//获取A列的值
			$typ_no = $objPHPExcel->getActiveSheet()->getCell("C".$j)->getValue();//获取B列的值
			$Partnumber = $objPHPExcel->getActiveSheet()->getCell("D".$j)->getValue();//获取B列的值
			$belong = $objPHPExcel->getActiveSheet()->getCell("E".$j)->getValue();//获取B列的值
			
			$rule = $objPHPExcel->getActiveSheet()->getCell("B".$j)->getValue();//获取B列的值
			if($rule==''){$rule='内销';}
			if($wafer_belong){
				$wafer_belong_bf=$wafer_belong;
			}else{
				$wafer_belong=$wafer_belong_bf;
			}
			if($typ_no){
				$typ_no_bf=$typ_no;
			}else{
				$typ_no=$typ_no_bf;
			}
			$page_info[]=array(
					'wafer_belong'=>$wafer_belong,
					'typ_no'=>$typ_no,
					'Partnumber'=>$Partnumber,
					'belong'=>$belong,
					'rule'=>$rule
			);
		}
		Db::name('lists_list')->insertAll($page_info);
		echo json_encode(array('1','完成'));
	}
	//导出表格
	public function list_excel(){
		include \think\Config::get('public').'/../thinkphp/extend/PHPExcel/PHPExcel.php';//引入文件
		//创建对象
		$excel = new \PHPExcel();
		$excel->setActiveSheetIndex(0);
		$excel->getActiveSheet()->freezePane('A2');
		//Excel表格式,这里简略写了8列
		$letter = array('A','B','C','D','E','F','F','G');//可随动
		//表头数组
		$tableheader = array('wafer产品归属','下单规则','Typ no','Partnumber','封装产品归属');//可随动
		//填充表头信息
		for($i = 0;$i < count($tableheader);$i++) {
			$excel->getActiveSheet()->setCellValue("$letter[$i]1","$tableheader[$i]");
		}
		
		
		$wafer=isset($_GET['wafer'])?$_GET['wafer']:'';
		$Typ_no=isset($_GET['Typ_no'])?$_GET['Typ_no']:'';
		$Partnumber=isset($_GET['Partnumber'])?$_GET['Partnumber']:'';
		$rule=isset($_GET['rule'])?$_GET['rule']:'';
		$belong=isset($_GET['belong'])?$_GET['belong']:'';
		$wafer_sql='where 1=1 ';$Typ_no_sql='';$Partnumber_sql='';$belong_sql='';$rule_sql='';
		
		if($wafer){$wafer_sql='where wafer_belong like "%'.$wafer.'%" ';}
		if($Typ_no){$Typ_no_sql='and Typ_no like "%'.$Typ_no.'%" ';}
		if($Partnumber){$Partnumber_sql='and Partnumber like "%'.$Partnumber.'%" ';}
		if($belong){$belong_sql='and belong like "%'.$belong.'%" ';}
		if($rule){$rule_sql='and rule = "'.$rule.'" ';}
		
		
		
		
		$data=db()->query("select wafer_belong,rule,typ_no,Partnumber,belong from sw_lists_list ".$wafer_sql.$Typ_no_sql.$Partnumber_sql.$belong_sql.$rule_sql);	
		if(!$data){
			exit('没有该类型数据');
		}
		//边框样式
		$color='auto';
		$styleArray = array(
				'borders' => array(
						'allborders' => array(
								//'style' => PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
								'style' => \PHPExcel_Style_Border::BORDER_THIN,//细边框
								'color' => array('argb' => $color),
						),
				),
		);
		$endd=count($data)+1;
		$excel->getActiveSheet()->getStyle('A1:E'.$endd)->applyFromArray($styleArray);
		//边框
		$excel->getActiveSheet()->getStyle('A1:E'.$endd)->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
		    
		
		
		$excel->getActiveSheet()->getColumnDimension('A')->setWidth(14);
		$excel->getActiveSheet()->getColumnDimension('B')->setWidth(14);
		$excel->getActiveSheet()->getColumnDimension('C')->setWidth(26);
		$excel->getActiveSheet()->getColumnDimension('D')->setWidth(31);
		$excel->getActiveSheet()->getColumnDimension('E')->setWidth(14);//行宽
		$excel->getActiveSheet()->getStyle('A1:C'.count($data))->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
		$excel->getActiveSheet()->setAutoFilter($excel->getActiveSheet()->calculateWorksheetDimension());//搜索
		$wafer=$data[0]['wafer_belong'];
		$typ_no=$data[0]['typ_no'];$star='2';$end='';$pd_end='';
		for ($i = 2;$i <= count($data) + 1;$i++) {
			$j = 0;
		
			foreach ($data[$i - 2] as $key=>$value) {
				if($key=='typ_no'){
					if($value!=$typ_no){
						$end=$i-1;
					}
					$typ_no=$value;
					if($i-1==count($data) + 1){
						$end="$i";
					}
				}
				if($key=='wafer_belong'){
					if($end){
						if($value!=$wafer){
							$end=$i-1;
						}
						$wafer=$value;
						if($i-1==count($data) + 1){
							$end="$i";
						}
					}
				}
				if($end && $key=='typ_no'){
					$excel->getActiveSheet()->mergeCells("A$star:A$end");//合并
					$excel->getActiveSheet()->mergeCells("C$star:C$end");//合并
					$end='';
					$star="$i";
				}
				$excel->getActiveSheet()->setCellValue("$letter[$j]$i","$value");
				$j++;
			}
		}
		//创建Excel输入对象
		$write = new \PHPExcel_Writer_Excel5($excel);
		ob_end_clean();//清除缓冲区,避免乱码
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
		header("Content-Type:application/force-download");
		header("Content-Type:application/vnd.ms-execl");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");;
		header('Content-Disposition:attachment;filename="testdata.xls"');//filename生成文件的名字//可随动
		header("Content-Transfer-Encoding:binary");
		$write->save('php://output');
	}
}