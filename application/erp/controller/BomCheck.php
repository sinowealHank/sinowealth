<?php
namespace app\erp\controller;

use think\Controller;
use app\index\controller\Admin;
use think\Db;
use think\db\Query;
set_time_limit(0);
//用户首页详情
class BomCheck extends Admin{
	public function index(){
		//正常数据
		$body=db()->query("select imaicd04,imaicd01,count(distinct imaicd01) from imaicd_file where imaicd04='0' group by imaicd01");
		$div='';
		foreach ($body as $b){
			$div=$div.'<div class="tree-folder">
						<div i=0 wtq_color="'.$b['imaicd01'].'" class="tree-folder-header" onclick="show(\''.$b['imaicd01'].'\',1)" style="white-space: nowrap;">
							<i wtq_i="'.$b['imaicd01'].'" class="icon-folder-close" style="color: #F8C600;"></i>
							<div wtq_name="'.$b['imaicd01'].'" class="tree-folder-name">'.$b['imaicd01'].'</div>
						</div>
						<div wtq_add="'.$b['imaicd01'].'" class="tree-folder-content">
						</div>
					   </div>';
		}
		//没有上级数据
		$body=db()->query("select imaicd00 from imaicd_file where imaicd04='1'");
		$bmb_file=db()->query("select bmb01,bmb03 from bmb_file");
		//过滤有上级数据的
		foreach ($bmb_file as $key=>$b){
			foreach ($bmb_file as $b2){
				if($b['bmb03']==$b2['bmb01']){
					unset($bmb_file[$key]);
					break;
				}
			}
		}
		foreach ($bmb_file as $key=>$b){
			foreach ($body as $b2){
				if($b['bmb03']==$b2['imaicd00']){
					unset($bmb_file[$key]);
					break;
				}
			}
		}
		//排除重复的
		foreach ($bmb_file as $key=>$b){
			$new_bmb_file[$b['bmb03']]=$key;
		}
		$new_bmb_file=array_flip($new_bmb_file);
		foreach ($new_bmb_file as $n_b){
			$div=$div.'<div class="tree-folder">
						<div i=0 wtq_color="'.$n_b.'" class="tree-folder-header" onclick="show(\''.$n_b.'\',2)" style="white-space: nowrap;color: red;">
							<i wtq_i="'.$n_b.'" class="icon-folder-close" style="color: #F8C600;"></i>
							<div wtq_name="'.$n_b.'" class="tree-folder-name">'.$n_b.'</div>
						</div>
						<div wtq_add="'.$n_b.'" class="tree-folder-content">
						</div>
					   </div>';
		}
		$this->assign('div', $div);
		return $this->fetch("index_one_sour");
	}
	public function body(){
		$imaicd01=isset($_POST['name'])?$_POST['name']:'';
		$imaicd04=isset($_POST['sort'])?$_POST['sort']:'';
		//判断是不是最后一级
		if($imaicd01=='' || $imaicd04>4){
			$div='';
		}else{
			//从不同表拉取数据
			if($imaicd04==1){
				$body=db()->query("select imaicd00,imaicd04 from imaicd_file where imaicd04='".$imaicd04."' and imaicd01='".$imaicd01."'");
			}else{
				$body=db()->query("select bmb01 as imaicd00 from bmb_file where bmb03='".$imaicd01."'");
			}
			if($body){
				$div='';
				$imaicd04=$imaicd04+1;
				foreach ($body as $key=>$b){
					if($imaicd04>4){
						$this_i='<i wtq_i="'.$b['imaicd00'].'" class="icon-ok" style="color: #F8C600;"></i>';
					}else{
						$this_i='<i wtq_i="'.$b['imaicd00'].'" class="icon-folder-close" style="color: #F8C600;"></i>';
					}
					$div=$div.'<div class="tree-folder">
							<div i="'.$imaicd01.'" wtq_color="'.$b['imaicd00'].'" class="tree-folder-header" onclick="show(\''.$b['imaicd00'].'\','.$imaicd04.')" style="white-space: nowrap;">
								'.$this_i.'
								<div wtq_name="'.$b['imaicd00'].'" class="tree-folder-name">'.$b['imaicd00'].'</div>
							</div>
							<div wtq_add="'.$b['imaicd00'].'" class="tree-folder-content">
							</div>
						   </div>';
				}
				
			}else{
				$div='<div class="tree-folder">
						<div class="tree-folder-header" style="white-space: nowrap;">
							<i class="icon-folder-close" style="color: #F8C600;"></i>
							<div class="tree-folder-name">没有数据或最后一层</div>
						</div>
					</div>';
			}
		}
		
		
		
		$conn = oci_connect('WTQ','123456','192.9.230.22/orcl');
		$sql = "select imaicd00,imaicd01,imaicd16 from DATA.imaicd_file WHERE imaicd00='".$imaicd01."'";
		$ora_test = oci_parse($conn,$sql); //编译sql语句
		oci_execute($ora_test,OCI_DEFAULT); //执行
		$data='';
		while ($result = oci_fetch_assoc($ora_test)){
			$data[] = $result;
		}
		if($data==''){
			$data=array(array());
		}
		$pd=isset($_POST['pd'])?$_POST['pd']:'';
		if($pd=='red' || $pd=='blue'){
			$lev=$_POST['sort']-1;
			$imaicd01;
			$only_s=db()->query("select * from bmb_file where bmb01='".$imaicd01."'");
			$only_s_d=db()->query("select * from imaicd_file where imaicd04=".$lev." and imaicd00='".$imaicd01."'");
			if($only_s){$only_s=$only_s[0]['bmb03'];}else{$only_s='';}
			if($only_s_d){$only_s_d=$only_s_d[0]['imaicd01'];}else{$only_s_d='';}
			$only_s=array($only_s,$only_s_d,$lev);
		}else{
			$only_s='';
		}
		echo json_encode(array($div,$data,isset($key)?$key+1:'0',$only_s));
	}
	
	
	
	public function index_crook(){
		$bg=time();
		//正常数据
		$conn = oci_connect('WTQ','123456','192.9.230.22/orcl');
		$sql = "select distinct ima133 from DATA.IMA_FILE where ima06='4_FT'";
		$ora_test = oci_parse($conn,$sql); //编译sql语句
		oci_execute($ora_test,OCI_DEFAULT); //执行
		$data='';
		while ($result = oci_fetch_assoc($ora_test)){
			$data[] = $result;
		}
		if($data==''){
			$data=array(array());
		}
		$div='';
		foreach ($data as $d){
			$div=$div.'<div class="tree-folder">
						<div i=0 wtq_color="'.$d['IMA133'].$bg.'" class="tree-folder-header" onclick="show(\''.$d['IMA133'].'\',6,'.$bg.')" style="white-space: nowrap;">
							<i wtq_i="'.$d['IMA133'].$bg.'" class="icon-folder-close" style="color: #F8C600;"></i>
							<div wtq_name="'.$d['IMA133'].$bg.'" class="tree-folder-name">'.$d['IMA133'].'</div>
						</div>
						<div wtq_add="'.$d['IMA133'].$bg.'" class="tree-folder-content">
						</div>
					   </div>';
		}
		$this->assign('div', $div);
		return $this->fetch();
	}
	
	public function body_crook(){
		$bg=time();
		$imaicd01=isset($_POST['name'])?$_POST['name']:'';
		$imaicd04=isset($_POST['sort'])?$_POST['sort']:'';
		//链接oracle数据库
		$conn = oci_connect('WTQ','123456','192.9.230.22/orcl');
		//判断是不是最后一级
		if($imaicd01=='' || $imaicd04<3){
			$div='';
		}else{
			
			//判断应该来着那张表//判断是不是第一级
			if($imaicd04==6){
			
				$sql = "select IMA01 as imaicd00,IMA02 from DATA.IMA_FILE where IMA133='".$imaicd01."'";
				$ora_test = oci_parse($conn,$sql); //编译sql语句
				oci_execute($ora_test,OCI_DEFAULT); //执行
				$data='';
				while ($result = oci_fetch_assoc($ora_test)){
					$data[] =array('imaicd00'=>$result['IMAICD00'],'IMA02'=>$result['IMA02']);
				}
				$body=$data;
				//dump($body);exit;
			}else{
				/*if($imaicd04==1){
					$body=db()->query("select imaicd00,imaicd04 from imaicd_file where imaicd04='".$imaicd04."' and imaicd01='".$imaicd01."'");
				}else{}*/
				$body=db()->query("select bmb03 as imaicd00 from bmb_file where bmb01='".$imaicd01."'");
				
			}
			
			if($body){
				$div='';
				$imaicd04=$imaicd04-1;
				foreach ($body as $key=>$b){
					if($imaicd04<3){
						$this_i='<i wtq_i="'.$b['imaicd00'].$bg.'" class="icon-ok" style="color: #F8C600;"></i>';
					}else{
						$this_i='<i wtq_i="'.$b['imaicd00'].$bg.'" class="icon-folder-close" style="color: #F8C600;"></i>';
					}
					$div=$div.'<div class="tree-folder">
						<div i="'.$imaicd01.'" wtq_color="'.$b['imaicd00'].$bg.'" class="tree-folder-header" onclick="show(\''.$b['imaicd00'].'\','.$imaicd04.','.$bg.')" style="white-space: nowrap;">
							'.$this_i.'
							<div wtq_name="'.$b['imaicd00'].$bg.'" class="tree-folder-name">'.$b['imaicd00'].'</div>
						</div>
						<div wtq_add="'.$b['imaicd00'].$bg.'" class="tree-folder-content">
						</div>
					   </div>';
				}
				
			}else{
				$div='<div class="tree-folder">
					<div class="tree-folder-header" style="white-space: nowrap;">
						<i class="icon-folder-close" style="color: #F8C600;"></i>
						<div class="tree-folder-name">没有数据或最后一层</div>
					</div>
				</div>';
			}	
			
		}
	
	
	
		
		$sql = "select imaicd00,imaicd01,imaicd16 from DATA.imaicd_file WHERE imaicd00='".$imaicd01."'";
		$ora_test = oci_parse($conn,$sql); //编译sql语句
		oci_execute($ora_test,OCI_DEFAULT); //执行
		$data='';
		while ($result = oci_fetch_assoc($ora_test)){
			$data[] = $result;
		}
		if($data==''){
			$data=array(array());
		}
	
		echo json_encode(array($div,$data,isset($key)?$key+1:'0'));
	}
	
	
	
	
	public function index_one_sour(){
		return $this->fetch();
	}
	
	public function zero($lev,$name){
		$div=$name;
		$name="'".$name."'";
		if($lev==4){
			$divv_x=array($div=>'');
		}else{
			$div_x=$this->zero_x($lev,$name,array(array($div)),array($div));
			$div_x=array_reverse($div_x);
			if($div_x[0]==''){
				$divv_x=array($div=>'');
			}else{
				//dump($div_x);exit;
				//echo sizeof($div_x);
				$divv='';
				if(sizeof($div_x)==2){
					foreach ($div_x[0] as $key=>$d_x){
						foreach ($d_x as $x){
							$divv[$key][$x]='';
						}
					}
				}
				for ($i=0;$i<sizeof($div_x)-2;$i++){
					if($divv){$div_x[$i]=$divv;}
					$abc=0;
					foreach ($div_x[$i] as $key=>$a){
						foreach ($div_x[$i+1] as $keyy=>$b){
							if($i==sizeof($div_x)-1){
							}else{
								foreach ($b as $bb){
									if($abc==0){$divv[$keyy][$bb]='';}
									if($key==$bb){
										if($i==0){
											foreach ($a as $aa){
												$divv[$keyy][$bb][$aa]='';
											}
										}else{
											$divv[$keyy][$bb]=$a;
										}
									}
									unset($divv[$key]);
								}
								$abc=1;
							}
						}
					}
				}
				$divv_x=$divv;
			}
		}
		
		if($lev==0){
			$divv=$divv_x;
		}else{
			$div=$this->zero_s($lev,$name,$div).";".$div;
			$div=explode(';',$div);
			$a='';$divv='';
			for ($i=$lev-1;$i>=0;$i--){
				//$a=$a."['".$div[$i]."']";
				if($i==$lev-1){
					$divv[$div[$i]]=$divv_x;
				}else{
					$divv[$div[$i]]=$divv;
				}
				unset($divv[$div[$i+1]]);
			}
		}
		return '<div wtq_del id="tree2" class="tree tree-unselectable">'.$this->mulu($divv)."</div>";
	}
	public function zero_s($lev,$name,$div){
		if($name){
			if($lev==1){
				$body=db()->query("select imaicd00 as bmb01,imaicd01 as bmb03 from imaicd_file where imaicd00 =".$name." and imaicd04=$lev");;
			}else{
				$body=db()->query("select bmb01,bmb03 from bmb_file where bmb01 =".$name);
			}
			$lev=$lev-1;
			if($body){
				$div=$body[0]['bmb03'];
				if($lev==0){
						
				}else{
					$div=$this->zero_s($lev,"'".$body[0]['bmb03']."'",$div).";".$div;
				}
			}
		}
		return $div;
	}
	public function zero_x($lev,$name,$div,$div_name){
		if($name){
			if($lev==0){
				$new_lev=$lev+1;
				$body=db()->query("select imaicd00 as bmb01,imaicd01 as bmb03 from imaicd_file where imaicd01 in (".$name.") and imaicd04=$new_lev");
			}else{
				$body=db()->query("select bmb01,bmb03 from bmb_file where bmb03 in (".$name.")");
			}
			$name='';
			
			$divv='';
			foreach ($div_name as $d_m){
				foreach ($body as $b){
					if($b['bmb03']==$d_m){
						$divv[$d_m][]=$b['bmb01'];
					}
				}
			}
			$div[]=$divv;
			
			$div_name='';
			foreach ($body as $b){
				if($name==''){
					$name="'".$b['bmb01']."'";
				}else{
					$name=$name.",'".$b['bmb01']."'";
				}
				$div_name[]=$b['bmb01'];
			}
			
			
			$lev=$lev+1;
			if($lev==4){
				
			}else{
				$div=$this->zero_x($lev,$name,$div,$div_name);
			}
		}
		
		return $div;
	}
	
	
	public function mulu($data,$i=0){
		$div='';
		foreach ($data as $key=>$d){
			if($i>4){
				$this_i='<i wtq_i="'.$key.'" class="icon-ok" style="color: #F8C600;"></i>';
			}else{
				$this_i='<i wtq_i="'.$key.'" class="icon-folder-close" style="color: #F8C600;"></i>';
			}
			$div=$div.'<div class="tree-folder">
							<div i="'.$i.'" wtq_color="'.$key.'" class="tree-folder-header" onclick="show(\''.$key.'\','.$i.')" style="white-space: nowrap;">
								'.$this_i.'
								<div wtq_name="'.$key.'" class="tree-folder-name">'.$key.'</div>
							</div>
							<div wtq_add="'.$key.'" class="tree-folder-content">';
			
			
			if($d){
				$i++;
				$div=$div.$this->mulu($d,$i)."</div></div>";
			}else{
				$div=$div."</div></div>";
			}
		}
		return $div;
	}
	
	public function index_one_sour_ajax(){
		$lev=isset($_POST['lev'])?$_POST['lev']:'';
		$name=isset($_POST['name'])?$_POST['name']:'';
		$sour=isset($_POST['sour'])?$_POST['sour']:'0';
		//$sour=2;
		if($sour==0){
			$div=$this->zero($lev,$name);
			$data=array(0,$div);
		}elseif($sour==1){
			exit;
		}elseif($sour==2){
			if($lev==0){
				$div='';
			}else{
				//蓝无下，黑无上
				$only_s=db()->query("select imaicd00,'blue' as pd from imaicd_file where imaicd04=".$lev);
				$sql_ban='';
				foreach ($only_s as $key=>$s){
					if($key==0){
						$sql_ban=$sql_ban."'".$s['imaicd00']."'";
					}else{
						$sql_ban=$sql_ban.",'".$s['imaicd00']."'";
					}
				}
				
				if($sql_ban){
					//下级
					$only_ss=db()->query("select * from bmb_file where bmb03 in (".$sql_ban.")");
					foreach ($only_s as $key=>$s){
						foreach ($only_ss as $ss){
							if($ss['bmb03']==$s['imaicd00']){
								unset($only_s[$key]);
							}
						}
					}
					
					//上级
					$only_ss=db()->query("select *,bmb01 as imaicd00,'black' as pd from bmb_file where bmb01 in (".$sql_ban.")");
					$sql_ban='';
					foreach ($only_ss as $key=>$ss){
						if($key==0){
							$sql_ban=$sql_ban."'".$ss['bmb03']."'";
						}else{
							$sql_ban=$sql_ban.",'".$ss['bmb03']."'";
						}
					}
					
					if($sql_ban){
						$only_sss=db()->query("select * from bmb_file where bmb01 in (".$sql_ban.")");
						foreach ($only_ss as $key=>$ss){
							foreach ($only_sss as $sss){
								if($ss['bmb03']==$sss['bmb01']){
									unset($only_ss[$key]);
								}
							}
						}
					}else{
						$only_ss=array();
					}
					
				}else{
					$only_s=array();
					$only_ss=array();
				}
				$only_x=$only_s;
				$only_s=$only_ss;
				
				$only_a=array();
				foreach ($only_s as $key=>$s){
					foreach ($only_x as $keyy=>$x){
						if($s['imaicd00']==$x['imaicd00']){
							$only_a[]=array('imaicd00'=>$x['imaicd00'],'pd'=>'red');
							unset($only_s[$key]);
							unset($only_x[$keyy]);
						}
					}
				}
				$only=array_merge($only_s,$only_x);
				$only=array_merge($only,$only_a);
				$div='';
				$lev=$lev+1;
				foreach ($only as $a){
					$div=$div.'<div class="tree-folder">
								<div i=0 wtq_color="'.$a['imaicd00'].'" class="tree-folder-header" onclick="show(\''.$a['imaicd00'].'\','.$lev.',\''.$a['pd'].'\')" style="white-space: nowrap;color: '.$a['pd'].';">
									<i wtq_i="'.$a['imaicd00'].'" class="icon-folder-close" style="color: #F8C600;"></i>
									<div wtq_name="'.$a['imaicd00'].'" class="tree-folder-name">'.$a['imaicd00'].'</div>
								</div>
								<div wtq_add="'.$a['imaicd00'].'" class="tree-folder-content">
								</div>
							   </div>';
				}
			}
			$data=array(2,$div);
			
			
			/*if($lev==4 || $lev==1){$shu=1;}else{$shu=2;}
			$sql="SELECT * FROM 
					(select *, count(*) as shu,(case when pd='".$shu."' then 0 else 1 end) as pdd from 
							(SELECT * from 
								(SELECT *,
									(case when bmb01=imaicd00 then 1 when bmb03=imaicd00 then 2 else 0 end) as pd 
								FROM 
									imaicd_file LEFT JOIN bmb_file ON imaicd00=bmb01 or imaicd00=bmb03 
								where 
									imaicd04=1 and imaicd00='NT6868CH-50271') 
							as 
								one
							group by pd)
							AS
					 two
					group by imaicd00)
				as
					c
				where shu!=".$shu." or pdd=0";*
			if($lev<4){
				//下级是否you
				/*$sql="SELECT *,'x' as ed_pd from
				(
					SELECT *
							(case when bmb01=imaicd00 then 1 when bmb03=imaicd00 then 2 else 0 end) as pd
						FROM
							imaicd_file LEFT JOIN bmb_file ON imaicd00=bmb03
						where
							imaicd_file.imaicd04=".$lev."
				) as one where pd=0";*为什么一模一样的sql上边的报错
				$sql="SELECT *,'x' as ed_pd from 
				(
					SELECT *,
							(case when bmb01=imaicd00 then 1 when bmb03=imaicd00 then 2 else 0 end) as pd 
						FROM 
							imaicd_file LEFT JOIN bmb_file ON imaicd00=bmb03
						where 
							imaicd_file.imaicd04=".$lev."
				) as one where pd=0;";
				$only_s=db()->query($sql);
			}
			
			if($lev>1){
				//上级是否you
				$sql="SELECT imaicd00,'s' as ed_pd from (
					SELECT a.imaicd00,
					(case when b.imaicd00=a.bmb03 then 1 else 0 end) as pdd
					 from
						(SELECT *,
								(case when bmb01=imaicd00 then bmb03 when bmb03=imaicd00 then bmb01 else 0 end) as pd
							FROM
								imaicd_file LEFT JOIN bmb_file ON imaicd00=bmb01
							where
								imaicd04=".$lev.") as a LEFT JOIN imaicd_file as b on a.bmb03=b.imaicd00
				) as en";
				$only_x=db()->query($sql);
			}elseif($lev==1){
				//上级是否you
				$sql="SELECT *,'x' as ed_pd FROM (
						SELECT a.imaicd00,(case when a.imaicd01=b.imaicd00 then 1 else 0 end) as pd 
						 from (SELECT * FROM imaicd_file where imaicd04=1) as a LEFT JOIN imaicd_file b ON a.imaicd01=b.imaicd00 and b.imaicd04=0
						) as c WHERE pd=0";
				$only_x=db()->query($sql);
			}
			$only_a='';
			foreach ($only_s as $key=>$s){
				foreach ($only_x as $keyy=>$x){
					if($s['imaicd00']==$x['imaicd00']){
						$only_a[]=array('imaicd00'=>$x['imaicd00'],'ed_pd'=>'all');
						unset($only_s[$key]);
						unset($only_x[$keyy]);
					}
				}
			}
			//$only_s=array_column($only_s,'imaicd00');
			//$only_x=array_column($only_x,'imaicd00');
			$only=array_merge($only_s,$only_x);
			$only=array_merge($only,$only_a);
			//$only=array_flip(array_flip($only));
			$data=array(2,$only);*/
			
		}elseif($sour==3){
			exit;
		}elseif($sour==4){
			exit;
		}else{
			"fuck";exit;
		}
		
		echo json_encode($data);
		
		
		
		
	}
	
	
	
	public function oracle_sql($sql){
		$conn = oci_connect('WTQ','123456','192.9.230.22/orcl');
		$ora_test = oci_parse($conn,$sql); //编译sql语句
		oci_execute($ora_test,OCI_DEFAULT); //执行
		$data='';
		while ($result = oci_fetch_assoc($ora_test)){
			$data[] = $result;
		}
		return $data;
	}
}