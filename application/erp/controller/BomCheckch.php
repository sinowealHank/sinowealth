<?php
namespace app\erp\controller;

use think\Controller;
use app\index\controller\Admin;
use think\Db;
use think\db\Query;
//用户首页详情
class BomCheckch extends Admin{
	private $data_ku='DATA.';
	public function index(){
		$sql="SELECT
				rownum as id,
		b.IMAICD00 as BMB01,
		(CASE WHEN bb.BMB03 IS NULL THEN b.IMAICD01 ELSE bb.BMB03 END) as bmb03,
		(CASE WHEN b.IMAICD04=a.IMAICD04 THEN '2_CP_5' WHEN b.IMAICD04=0 THEN '0_BODY' WHEN b.IMAICD04=1 THEN '1_WF' WHEN b.IMAICD04=2 THEN '2_CP' WHEN b.IMAICD04=3 THEN '3_PKG' WHEN b.IMAICD04=4 THEN '4_FT' ELSE '没有等级' END) as IMAICD04,
		b.IMAICD01,
		
		
				(CASE WHEN b.imaicd14>=1 THEN 'ok' ELSE (CASE WHEN b.IMAICD04 in (1,2) THEN 'no' ELSE '' END) END) as IMAICD14_PD,
				(CASE WHEN b.imaicd14=0 THEN NULL ELSE b.imaicd14 END) as imaicd14,
				(CASE WHEN b.IMAICD00=pmh01 THEN 'ok' ELSE (CASE WHEN b.IMAICD04 in (1,2,3,4) THEN 'no' ELSE '' END) END) as ok_shop,
				(CASE WHEN b.IMAICD00=pmj03 THEN 'ok' ELSE 'no' END) as jiage,
				(CASE WHEN b.IMAICD00=icf01 THEN 'ok' ELSE (CASE WHEN b.IMAICD04 in (1,2) THEN 'no' ELSE '' END) END) as kehao,
				(CASE WHEN imaud02 IS NULL THEN (CASE WHEN b.IMAICD04 in (2,4) THEN 'no' ELSE '' END) ELSE 'ok' END) as IMAUD02_PD,imaud02,
				(CASE WHEN pmh04 IS NULL THEN (CASE WHEN b.IMAICD04 in (3) THEN 'no' ELSE '' END) ELSE 'ok' END) as pmh04_PD,pmh04,
				(CASE WHEN b.IMAICD04=3 THEN (CASE WHEN a.imaicd14=bb.BMB07 THEN 'ok' ELSE 'no' END) ELSE (CASE WHEN aa.bmb07=bb.BMB07 THEN 'ok' ELSE (CASE WHEN b.IMAICD04 in (2,4) THEN 'no' ELSE '' END) END) END) as 主件底数,/*bb.BMB07,aa.bmb07 as oldbmb07,a.imaicd14 as oldimaicd14,*/
				(CASE WHEN bb.ta_bmb01 IS NULL THEN 'no' ELSE 'ok' END) as ta_bmb01_PD,bb.ta_bmb01,
				(CASE WHEN imaud07 IS NULL THEN (CASE WHEN b.IMAICD04 in (4) THEN 'no' ELSE '' END) ELSE 'ok' END) as imaud07_PD,imaud07,
				(CASE WHEN ima10 like '%XA%' OR ima10 like '%SH%' OR ima10 like '%HK%' THEN 'ok' ELSE (CASE WHEN b.IMAICD04 in (4) THEN 'no' ELSE '' END) END) as ima10_PD,ima10,
				(CASE WHEN b.imaicd18 IS NULL THEN (CASE WHEN b.IMAICD04 in (4) THEN 'no' ELSE '' END) ELSE 'ok' END) as imaicd18_PD,b.imaicd18,
				(CASE WHEN b.imaicd16 IS NULL THEN 'no' ELSE 'ok' END) as imaicd16_PD,b.imaicd16,
				(CASE WHEN imaud01 IS NULL THEN 'no' ELSE 'ok' END) as imaud01_PD,imaud01,
				(CASE WHEN imaud05 IS NULL THEN 'no' ELSE 'ok' END) as imaud05_PD,imaud05,
				(CASE WHEN ima641='0.001' THEN 'ok' ELSE (CASE WHEN b.IMAICD04 in (2) THEN 'no' ELSE '' END) END) as ima641_pd,ima641
		
		
		FROM
			".$this->data_ku."imaicd_file b
		LEFT JOIN
			".$this->data_ku."bmb_file bb
		ON
			b.imaicd00=bb.bmb01
		
		
		
		
		LEFT JOIN ".$this->data_ku."IMAICD_FILE a ON a.imaicd00=bb.bmb03
		
		LEFT JOIN ".$this->data_ku."bmb_file aa ON aa.bmb01=bb.bmb03
		
		
		LEFT JOIN
			".$this->data_ku."pmh_file
		ON
			b.IMAICD00=pmh01
		
		LEFT JOIN
			".$this->data_ku."PMJ_FILE
		ON
			b.IMAICD00=PMJ03
		
		LEFT JOIN
			".$this->data_ku."icf_file
		ON
			b.IMAICD00=icf01
		
		LEFT JOIN
			".$this->data_ku."ima_file
		ON
			b.IMAICD00=ima01
		ORDER BY
			b.IMAICD04 desc";
		
		
		//设置所有字段对应名称
		$name=array(
				'ROWNUM'=>'id',
				'BMB01'=>'料号',//'本体',
				'BMB03'=>'领料',//'上级',
				'IMAICD04'=>'阶段',//'等级',
				'IMAICD01'=>'body',//'最顶级',
				
				
				'IMAICD14'=>'GROSS_DIE',
				
				'OK_SHOP'=>'合格供应商',
				'JIAGE'=>'核价信息',
				'KEHAO'=>'PASS BIN',
				
				'IMAUD02'=>'测试程序',
				'PMH04'=>'图号',
				
				'主件底数'=>'主件底数',
				
				
				'TA_BMB01'=>'主芯片',
				'IMAUD07'=>'pass b',
				'IMA10'=>'产品归属地',
				'IMAICD18'=>'封装形式',
				'IMAICD16'=>'光罩版本',
				'IMAUD01'=>'光罩层次',
				'IMAUD05'=>'生产REFER TO',
				'IMA641'=>'最小发料',
		);
		$name_pd=array(
				'IMAICD14'=>'IMAICD14_PD',
				
				'IMAUD02'=>'IMAUD02_PD',
				'PMH04'=>'PMH04_PD',
				
				'TA_BMB01'=>'TA_BMB01_PD',
				'IMAUD07'=>'IMAUD07_PD',
				'IMA10'=>'IMA10_PD',
				'IMAICD18'=>'IMAICD18_PD',
				'IMAICD16'=>'IMAICD16_PD',
				'IMAUD01'=>'IMAUD01_PD',
				'IMAUD05'=>'IMAUD05_PD',
				'IMA641'=>'IMA641_PD',
		);
		$this->assign('name',$name);
		
		
		
		//$color="IMAICD14_PD,IMAUD02_PD,PMH04_PD,TA_BMB01_PD,IMAUD07_PD,IMA10_PD,IMAICD18_PD,IMAICD16_PD,IMAUD01_PD,IMAUD05_PD,IMA641_PD";
		//OK_SHOP   JIAGE   KEHAO  主件底数
		//$left="ROWNUM,BMB01,BMB03,IMAICD04,IMAICD01";
		//$right="IMAICD14,OK_SHOP,JIAGE,KEHAO,IMAUD02,PMH04,主件底数,TA_BMB01,IMAUD07,IMA10,IMAICD18,IMAICD16,IMAUD01,IMAUD05,IMA641";
		
		
		
		$left="ROWNUM,BMB01,BMB03,IMAICD04,IMAICD01";
		$title['left']= explode(',',$left);
		
		
		
		/*//设置属于哪个权限组 显示格式 “  ，登陆id，”
		$id_n_arr=array(0=>',1,',1=>'');
		$id_n='';
		//判断属于哪个权限组
		foreach ($id_n_arr as $key=>$n){
			if(strpos($n, ','.$_SESSION['u_i']['dep_id'].',')!==false){
				$id_n=$key;
			}
		}
		//拼出改组的字段名
		$id="body_Jur_".$id_n;
		//jy:1排除这些2只显示这些
		$body_Jur_=array('jy'=>2,'show'=>array_flip($name),'change'=>array('IMAICD14','IMAUD07','TA_BMB01','IMA641'));
		$body_Jur_0=array('jy'=>2,'show'=>array_flip($name),'change'=>array('IMAUD07','TA_BMB01','IMA641'));
		$body_Jur_1=array('jy'=>1,'show'=>array('IMA641'),);*/
		
		$erp_bom_qx=db()->query("select * from sw_erp_bom_qx where dep_id='".$_SESSION['u_i']['dep_id']."'");
		$id="ok";
		$ko_fuck=array();
		if(isset($erp_bom_qx[0]['change'])){
			if($erp_bom_qx[0]['change']){
				$ko_fuck=explode(',',$erp_bom_qx[0]['change']);
			}
		}
		${$id}=array('jy'=>2,'show'=>array_flip($name),'change'=>$ko_fuck);
		$right='';$color='';
		if(${$id}['jy']==2){
			foreach (${$id}['show'] as $l){
				if(!in_array($l,$title['left'])){
					
					
					if($right==''){
						$right=$l;
					}else{
						if(in_array($l,${$id}['change'])){
							$right=$l.','.$right;
						}else{
							$right=$right.','.$l;
						}
						
					}
					
					$color_a=isset($name_pd[$l])?$name_pd[$l]:'';
					if($color_a){
						if($color==''){
							$color=$color_a;
						}else{
							$color=$color.','.$color_a;
						}
					}
				}
			}
		}else if(${$id}['jy']==1){
			foreach (array_flip($name) as $l){
				if(!in_array($l,$title['left']) && !in_array($l,${$id}['show'])){
					if($right==''){
						$right=$l;
					}else{
						if(in_array($l,${$id}['change'])){
							$right=$l.','.$right;
						}else{
							$right=$right.','.$l;
						}
					}
					$color_a=isset($name_pd[$l])?$name_pd[$l]:'';
					if($color_a){
						if($color==''){
							$color=$color_a;
						}else{
							$color=$color.','.$color_a;
						}
					}
				}
			}
		}
		
		$sql_ban=trim("$left,$right,$color", ',');
		
		
		$ii=isset($_GET['ii'])?$_GET['ii']:'ROWNUM';
		$title['ii']=$ii;		
		$body=Db::connect("wtq_orc")->query("SELECT $sql_ban FROM ($sql) ORDER BY $ii");
		$this->assign('body',$body);
		
		//传递表头
		if($right==''){
			$title['right']= array();
		}else{
			$title['right']= explode(',',$right);
		}
		
		//$title['right'][]='ROWNUM';
		
		if($right==''){
			$title['color']= array();
		}else{
			$title['color']= explode(',',$color);
		}
		$this->assign('title',$title);
		$this->assign('change',${$id}['change']);
		if(isset($_POST['ok'])){
			return $this->fetch("index_bro");
			exit;
		}else{
			return $this->fetch();
		}
	}
		
	public function save(){
		exit;
		$name=isset($_POST['name'])?$_POST['name']:'';//改变的名字
		$val=isset($_POST['val'])?$_POST['val']:'';//改变的内容
		$key=isset($_POST['key'])?$_POST['key']:'';//改变的哪一条
		if($name && $val && $key){
			$erp_bom_qx=db()->query("select * from sw_erp_bom_qx where dep_id='".$_SESSION['u_i']['dep_id']."'");
			if (!in_array($name,explode(',',$erp_bom_qx[0]['change']))){
				exit(json_encode(array(0,'你没有此权限')));
			}
			$data=array(
					'PMH04'=>array('pmh_file','PMH01','40'),
					
					'TA_BMB01'=>array('bmb_file','bmb01','1'),
					
					'IMAICD14'=>array('imaicd_file','imaicd00','3','NUMBER'),
					'IMAICD16'=>array('imaicd_file','imaicd00','40'),
					'IMAICD18'=>array('imaicd_file','imaicd00','40'),
					
					'IMA10'=>array('ima_file','ima01','10'),
					'IMA641'=>array('ima_file','ima01','15','NUMBER'),
					'IMAUD01'=>array('ima_file','ima01','255'),
					'IMAUD02'=>array('ima_file','ima01','40'),
					'IMAUD05'=>array('ima_file','ima01','40'),
					'IMAUD07'=>array('ima_file','ima01','1'),
			);
			if(strlen($val)<=$data[$name][2]){
				$all=isset($_POST['all'])?$_POST['all']:'';
				$table=$data[$name][0];$table_key=$data[$name][1];
				if($key=='all'){
					$sql="UPDATE ".$this->data_ku."$table SET $name = '$val'";
				}elseif($all=='all'){
					$sql="UPDATE ".$this->data_ku."$table SET $name = '$val' WHERE $table_key in ($key)";
				}else{
					$sql="UPDATE ".$this->data_ku."$table SET $name = '$val' WHERE $table_key = '$key'";
				}
				$body=Db::connect("wtq_orc")->query($sql);
				echo json_encode(array(1));
			}else{
				echo json_encode(array(0,'数值过长'));
			}
		}else{
			echo json_encode(array(0,'参数有空值'));
		}
	}
	public function qx(){
		$erp_bom_qx=db()->query("select sw_sys_dep.id idd,sw_erp_bom_qx.id,en_name,sw_erp_bom_qx.`change`  from sw_sys_dep LEFT JOIN sw_erp_bom_qx ON sw_sys_dep.id=dep_id ORDER BY sw_erp_bom_qx.`change` DESC");
		$left='';$right='';
		$name=array(
				'IMAICD14'=>'GROSS_DIE',
				'IMAUD02'=>'测试程序',
				'PMH04'=>'图号',
				'TA_BMB01'=>'主芯片',
				'IMAUD07'=>'pass b',
				'IMA10'=>'产品归属地',
				'IMAICD18'=>'封装形式',
				'IMAICD16'=>'光罩版本',
				'IMAUD01'=>'光罩层次',
				'IMAUD05'=>'生产REFER TO',
				'IMA641'=>'最小发料',
		);
		$data=array_flip($name);
		foreach ($erp_bom_qx as $key=>$e){
			$tite='<table><tr><td  colspan="5">'.$e['en_name'].'</td></tr><tr>';$i=0;
			if($e['change']){
				$e['change']=explode(',',$e['change']);
			}else{
				$e['change']=array();
			}
			if($e['id']){
				foreach ($e['change'] as $d){
					$i++;
					if($i==6){
						$tite=$tite."</tr><tr>";
					}if($i==11){
						$tite=$tite."</tr><tr>";
					}
					$tite=$tite."<td><input name='".$e['idd']."' checked='checked' type='checkbox' value='$d'>$name[$d]&nbsp&nbsp&nbsp</td>";
				}
				$pd=1;
			}else{
				$pd=2;
			}
			foreach ($data as $d){
				if(!in_array($d,$e['change'])){
					$i++;
					if($i==6){
						$tite=$tite."</tr><tr>";
					}if($i==11){
						$tite=$tite."</tr><tr>";
					}
					$tite=$tite."<td><input name='".$e['idd']."' type='checkbox' value='$d'>$name[$d]&nbsp&nbsp&nbsp</td>";
				}
			}
			$tite=$tite."</tr>";
			
			
			$active='';
			if($key==0){$active='active';}
			$left=$left.'<li class="'.$active.'">
				<a data-toggle="tab" wtq_pd='.$pd.' href="#a'.$e['idd'].'">'.$e['en_name'].'</a>
			</li>';
			//pd=0第一次pd=1修改
			$tite=$tite."<tr><td><button wtq_id='".$e['idd']."' onclick='staradd($pd,".$e['idd'].",\"".$e['id']."\",this)'>保存</buttton></td></tr></table>";
			$right=$right.'<div $pd id="a'.$e['idd'].'" class="tab-pane '.$active.'">'.$tite.'</div>';
		}
		//in_array($e['change']);
		$this->assign('left',$left);
		$this->assign('right',$right);
		$this->Inverse($name,$erp_bom_qx);
		return $this->fetch();
	}
	public function Inverse($name,$erp_bom_qx){
		$ni_left='';$ni_right_o='';
		foreach ($name as $key=>$n){
			$active='';
			if($key=='IMAICD14'){$active='active';}
			$ni_left=$ni_left.'<li class="'.$active.'">
				<a data-toggle="tab" href="#'.$key.'">'.$n.'</a>
			</li>';
			$ni_right='<table><tr><td colspan="5">'.$n.'</td></tr><tr>';$i=0;
			foreach ($erp_bom_qx as $q){
				$checkbox='';$pd=1;
				$change_arr= explode(',',$q['change']);
				foreach ($change_arr as $c){
					if($key==$c){
						$checkbox="checked='checked'";
						$pd=1;
						break;
					}else{
						$checkbox='';
						$pd=2;
					}
				}
				$i++;
				if($i==6){
					$ni_right=$ni_right."</tr><tr>";
					$i=1;
				}
				$ni_right=$ni_right."<td><input wtq_ajax='$pd' $checkbox name='$key' type='checkbox' value='".$q['idd']."'>".$q['en_name']."&nbsp&nbsp&nbsp</td>";
			}
			//pd=0第一次pd=1修改
			$ni_right=$ni_right."<tr><td><button onclick='Inverse_staradd(\"$key\")'>保存</buttton></td></tr></table>";
			$ni_right_o=$ni_right_o.'<div id="'.$key.'" class="tab-pane '.$active.'">'.$ni_right.'</div>';
		}
		$this->assign('ni_left',$ni_left);
		$this->assign('ni_right',$ni_right_o);
	}
	public function qx_save(){
		$pd=isset($_POST['pd'])?$_POST['pd']:'';
		$change=isset($_POST['change'])?$_POST['change']:'';
		if($pd==2){
			$dep_id=isset($_POST['dep_id'])?$_POST['dep_id']:'';
			$id=db('erp_bom_qx')->insertGetId(['change' => $change,'dep_id' => $dep_id,'id' => $dep_id]);
		}elseif($pd==1){
			$id=isset($_POST['id'])?$_POST['id']:'';
			db('erp_bom_qx')->where('id', $id)->update(['change' => $change]);
		}else{
			exit(json_encode(array(0,'bad')));
		}
		echo json_encode(array(1,'完成',$id));
	}
}
