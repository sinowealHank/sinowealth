<?php
namespace app\erp\controller;

use think\Controller;
use app\index\controller\Admin;
use think\Db;
use think\db\Query;
set_time_limit(0);
//用户首页详情
class BomCheckre extends Admin{
	private $data_ku='DATA.';
	public function index(){
		return $this->fetch();
	}
	public function index_ajax(){
		$excel=isset($_GET['ex'])?$_GET['ex']:'';
		/*
		 * (CASE WHEN b.imaicd14>=1 THEN 'ok' ELSE (CASE WHEN b.IMAICD04 in (1,2) THEN 'no' ELSE 'ok_t' END) END) as gross_die,b.imaicd14,
		(CASE WHEN bb.bmb01=pmh01 THEN 'ok' ELSE (CASE WHEN b.IMAICD04 in (1,2,3,4) THEN 'no' ELSE 'ok_t' END) END) as ok_shop,
		(CASE WHEN bb.bmb01=pmj03 THEN 'ok' ELSE 'no' END) as jiage,
		(CASE WHEN bb.bmb01=icf01 THEN 'ok' ELSE (CASE WHEN b.IMAICD04 in (1,2) THEN 'no' ELSE 'ok_t' END) END) as kehao,
		(CASE WHEN imaud02 IS NULL THEN (CASE WHEN b.IMAICD04 in (2,4) THEN 'no' ELSE 'ok_t' END) ELSE 'ok' END) as cs_app,imaud02,
		(CASE WHEN pmh04 IS NULL THEN (CASE WHEN b.IMAICD04 in (3) THEN 'no' ELSE 'ok_t' END) ELSE 'ok' END) as PKG_ok,pmh04,
		(CASE WHEN b.IMAICD04=3 THEN (CASE WHEN a.imaicd14=bb.BMB07 THEN 'ok' ELSE 'no' END) ELSE (CASE WHEN aa.bmb07=bb.BMB07 THEN 'okk' ELSE (CASE WHEN b.IMAICD04 in (2,4) THEN 'noo' ELSE 'ok_t' END) END) END) as 主件底数,bb.BMB07,aa.bmb07 as oldbmb07,a.imaicd14 as oldimaicd14,
		(CASE WHEN bb.ta_bmb01 IS NULL THEN 'no' ELSE 'ok' END) as xinpian,bb.ta_bmb01,
		(CASE WHEN imaud07 IS NULL THEN (CASE WHEN b.IMAICD04 in (4) THEN 'no' ELSE 'ok_t' END) ELSE 'ok' END) as FT_1,imaud07,
		(CASE WHEN ima10 IS NULL THEN (CASE WHEN b.IMAICD04 in (4) THEN 'no' ELSE 'ok_t' END) ELSE 'ok' END) as FT_2,ima10,
		(CASE WHEN b.imaicd18 IS NULL THEN (CASE WHEN b.IMAICD04 in (4) THEN 'no' ELSE 'ok_t' END) ELSE 'ok' END) as fengzhuang,b.imaicd18,
		(CASE WHEN b.imaicd16 IS NULL THEN 'no' ELSE 'ok' END) as a_1,b.imaicd16,
		(CASE WHEN imaud01 IS NULL THEN 'no' ELSE 'ok' END) as a_2,imaud01,
		(CASE WHEN imaud05 IS NULL THEN 'no' ELSE 'ok' END) as a_3,imaud05,
		(CASE WHEN ima641='0.001' THEN 'ok' ELSE (CASE WHEN b.IMAICD04 in (2) THEN 'no' ELSE 'ok_t' END) END) as min,ima641
		
		 */
		if($excel=='ok'){
			$sql_ban="(CASE WHEN b.imaicd14>=1 THEN 'ok' ELSE (CASE WHEN b.IMAICD04 in (1,2) THEN 'no' ELSE ' ' END) END) as gross_die,
				(CASE WHEN b.imaicd14=0 THEN NULL ELSE b.imaicd14 END) as imaicd14,
				(CASE WHEN b.IMAICD00=pmh01 THEN 'ok' ELSE (CASE WHEN b.IMAICD04 in (1,2,3,4) THEN 'no' ELSE ' ' END) END) as ok_shop,
				(CASE WHEN b.IMAICD00=pmj03 THEN 'ok' ELSE 'no' END) as jiage,
				(CASE WHEN b.IMAICD00=icf01 THEN 'ok' ELSE (CASE WHEN b.IMAICD04 in (1,2) THEN 'no' ELSE ' ' END) END) as kehao,
				(CASE WHEN imaud02 IS NULL THEN (CASE WHEN b.IMAICD04 in (2,4) THEN 'no' ELSE ' ' END) ELSE 'ok' END) as cs_app,imaud02,
				(CASE WHEN pmh04 IS NULL THEN (CASE WHEN b.IMAICD04 in (3) THEN 'no' ELSE ' ' END) ELSE 'ok' END) as PKG_ok,pmh04,
				(CASE WHEN b.IMAICD04=3 THEN (CASE WHEN a.imaicd14=bb.BMB07 THEN 'ok' ELSE 'no' END) ELSE (CASE WHEN aa.bmb07=bb.BMB07 THEN 'okk' ELSE (CASE WHEN b.IMAICD04 in (2,4) THEN 'noo' ELSE ' ' END) END) END) as 主件底数,/*bb.BMB07,aa.bmb07 as oldbmb07,a.imaicd14 as oldimaicd14,*/
				(CASE WHEN bb.ta_bmb01 IS NULL THEN 'no' ELSE 'ok' END) as xinpian,(CASE WHEN bb.TA_BMB01='N' THEN (CASE WHEN (SELECT cc.TA_BMB01 FROM ".$this->data_ku."bmb_file cc WHERE cc.BMB01=bb.BMB01 AND cc.TA_BMB01='Y')='Y' THEN 'N' ELSE 'Y' END) ELSE 'Y' END) as ta_bmb01,
				(CASE WHEN imaud07 IS NULL THEN (CASE WHEN b.IMAICD04 in (4) THEN 'no' ELSE ' ' END) ELSE 'ok' END) as FT_1,imaud07,
				(CASE WHEN ima10 like '%XA%' OR ima10 like '%SH%' OR ima10 like '%HK%' THEN 'ok' ELSE (CASE WHEN b.IMAICD04 in (4) THEN 'no' ELSE ' ' END) END) as FT_2,ima10,
				(CASE WHEN b.imaicd18 IS NULL THEN (CASE WHEN b.IMAICD04 in (4) THEN 'no' ELSE ' ' END) ELSE 'ok' END) as fengzhuang,b.imaicd18,
				(CASE WHEN b.imaicd16 IS NULL THEN 'no' ELSE 'ok' END) as a_1,b.imaicd16,
				(CASE WHEN imaud01 IS NULL THEN 'no' ELSE 'ok' END) as a_2,imaud01,
				(CASE WHEN imaud05 IS NULL THEN 'no' ELSE 'ok' END) as a_3,imaud05,
				(CASE WHEN ima641='0.001' THEN 'ok' ELSE (CASE WHEN b.IMAICD04 in (2) THEN 'no' ELSE ' ' END) END) as min,ima641
				";
			$sql_ban="(CASE WHEN bb.TA_BMB01='N' THEN (CASE WHEN (SELECT cc.TA_BMB01 FROM ".$this->data_ku."bmb_file cc WHERE cc.BMB01=bb.BMB01 AND cc.TA_BMB01='Y')='Y' THEN 'N' ELSE 'Y' END) ELSE 'Y' END) as ta_bmb01";
			$sql_ban_2="CONCAT(CONCAT(b.IMAICD00,'__'),(CASE WHEN bb.TA_BMB01='N' THEN (CASE WHEN (SELECT cc.TA_BMB01 FROM ".$this->data_ku."bmb_file cc WHERE cc.BMB01=bb.BMB01 AND cc.TA_BMB01='Y')='Y' THEN 'N' ELSE 'Y' END) ELSE 'Y' END)) as BMB01,
					(CASE WHEN bb.BMB03 IS NULL THEN CONCAT(CONCAT(b.IMAICD01,'__'),'Y') ELSE CONCAT(CONCAT(bb.BMB03,'__'),'Y') END) as bmb03,
					(CASE WHEN b.IMAICD04=a.IMAICD04 THEN '3' ELSE b.IMAICD04 END) as IMAICD04,
					CONCAT(CONCAT(b.IMAICD01,'__'),'Y') as IMAICD01";
		}else{
			$sql_ban="(CASE WHEN b.imaicd14>=1 THEN 'ok' ELSE (CASE WHEN b.IMAICD04 in (1,2) THEN 'no' ELSE ' ' END) END) as gross_die,
				(CASE WHEN b.IMAICD00=pmh01 THEN 'ok' ELSE (CASE WHEN b.IMAICD04 in (1,2,3,4) THEN 'no' ELSE ' ' END) END) as ok_shop,
				(CASE WHEN b.IMAICD00=pmj03 THEN 'ok' ELSE 'no' END) as jiage,
				(CASE WHEN b.IMAICD00=icf01 THEN 'ok' ELSE (CASE WHEN b.IMAICD04 in (1,2) THEN 'no' ELSE ' ' END) END) as kehao,
				(CASE WHEN imaud02 IS NULL THEN (CASE WHEN b.IMAICD04 in (2,4) THEN 'no' ELSE ' ' END) ELSE 'ok' END) as cs_app,
				(CASE WHEN pmh04 IS NULL THEN (CASE WHEN b.IMAICD04 in (3) THEN 'no' ELSE ' ' END) ELSE 'ok' END) as PKG_ok,
				(CASE WHEN b.IMAICD04=3 THEN (CASE WHEN a.imaicd14=bb.BMB07 THEN 'ok' ELSE 'no' END) ELSE (CASE WHEN aa.bmb07=bb.BMB07 THEN 'okk' ELSE (CASE WHEN b.IMAICD04 in (2,4) THEN 'noo' ELSE ' ' END) END) END) as 主件底数,/*bb.BMB07,aa.bmb07 as oldbmb07,a.imaicd14 as oldimaicd14,*/
				(CASE WHEN bb.ta_bmb01 IS NULL THEN 'no' ELSE 'ok' END) as xinpian,
				(CASE WHEN imaud07 IS NULL THEN (CASE WHEN b.IMAICD04 in (4) THEN 'no' ELSE ' ' END) ELSE 'ok' END) as FT_1,
				(CASE WHEN ima10 like '%XA%' OR ima10 like '%SH%' OR ima10 like '%HK%' THEN 'ok' ELSE (CASE WHEN b.IMAICD04 in (4) THEN 'no' ELSE ' ' END) END) as FT_2,
				(CASE WHEN b.imaicd18 IS NULL THEN (CASE WHEN b.IMAICD04 in (4) THEN 'no' ELSE ' ' END) ELSE 'ok' END) as fengzhuang,
				(CASE WHEN b.imaicd16 IS NULL THEN 'no' ELSE 'ok' END) as a_1,
				(CASE WHEN imaud01 IS NULL THEN 'no' ELSE 'ok' END) as a_2,
				(CASE WHEN imaud05 IS NULL THEN 'no' ELSE 'ok' END) as a_3,
				(CASE WHEN ima641='0.001' THEN 'ok' ELSE (CASE WHEN b.IMAICD04 in (2) THEN 'no' ELSE ' ' END) END) as min
				";
			$sql_ban_2="CONCAT(CONCAT(b.IMAICD00,'__'),(CASE WHEN bb.TA_BMB01='N' THEN (CASE WHEN (SELECT cc.TA_BMB01 FROM ".$this->data_ku."bmb_file cc WHERE cc.BMB01=bb.BMB01 AND cc.TA_BMB01='Y')='Y' THEN 'N' ELSE 'Y' END) ELSE 'Y' END)) as BMB01,
					(CASE WHEN bb.BMB03 IS NULL THEN CONCAT(CONCAT(b.IMAICD01,'__'),'Y') ELSE CONCAT(CONCAT(bb.BMB03,'__'),'Y') END) as bmb03,
					(CASE WHEN b.IMAICD04=a.IMAICD04 THEN '3' ELSE b.IMAICD04 END) as IMAICD04,
					CONCAT(CONCAT(b.IMAICD01,'__'),'Y') as IMAICD01,
					(CASE WHEN bb.TA_BMB01='N' THEN (CASE WHEN (SELECT cc.TA_BMB01 FROM ".$this->data_ku."bmb_file cc WHERE cc.BMB01=bb.BMB01 AND cc.TA_BMB01='Y')='Y' THEN 'N' ELSE 'Y' END) ELSE 'Y' END) as ta_bmb01";
		}
		$sql="SELECT * FROM (SELECT * FROM (
				SELECT 
		$sql_ban_2,
		$sql_ban,b.IMAICD04 as pd
		
		
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
			b.IMAICD04 desc ) ORDER BY IMAICD04 desc,ta_bmb01,BMB01) where ta_bmb01!='N'";
		$body=Db::connect("wtq_orc")->query($sql);
		if($excel=='ok'){
			foreach ($body as $b){
				if($b['IMAICD04']==0){
					$oyeah=$b['BMB01'];
					array_unshift($b,'');
					array_unshift($b,'');
					array_unshift($b,'');
					array_unshift($b,'');
					array_unshift($b,'');
					array_unshift($b,$oyeah);
					
					array_unshift($b,'');
				}
				if($b['IMAICD04']==1){
					$oyeah=$b['BMB01'];
					array_unshift($b,'');
					array_unshift($b,'');
					array_unshift($b,'');
					array_unshift($b,'');
					array_unshift($b,$oyeah);
					array_unshift($b,'');
					
					array_unshift($b,'');
				}
				if($b['IMAICD04']==2){
					$oyeah=$b['BMB01'];
					array_unshift($b,'');
					array_unshift($b,'');
					array_unshift($b,'');
					array_unshift($b,$oyeah);
					array_unshift($b,'');
					array_unshift($b,'');
					
					array_unshift($b,'');
				}
				if($b['IMAICD04']==3){
					$oyeah=$b['BMB01'];
					array_unshift($b,'');
					if($b['PD']==2){
						array_unshift($b,'');
					}
					array_unshift($b,$oyeah);
					if($b['PD']==2){
					
					}else{
						array_unshift($b,'');
					}
					array_unshift($b,'');
					array_unshift($b,'');
					array_unshift($b,'');
					
					array_unshift($b,'');
				}
				if($b['IMAICD04']==4){
					$oyeah=$b['BMB01'];
					array_unshift($b,$oyeah);
					array_unshift($b,'');
					array_unshift($b,'');
					array_unshift($b,'');
					array_unshift($b,'');
					array_unshift($b,'');
					
					array_unshift($b,'');
				}
				
				
				
				
				if($b['IMAICD04']==4){
					$data[$b['BMB03']][]=$b;
				}else if($b['IMAICD04']==0){
					$data['ok'][$b['BMB03'].'__'][]=$b;
					$data['ok'][$b['BMB03'].'__'][$b['BMB01']]=isset($data[$b['BMB01']])?$data[$b['BMB01']]:'';
					unset($data[$b['BMB01']]);
				}else{
					$data[$b['BMB03']][$b['BMB01']][]=$b;
					if(isset($data[$b['BMB01']]) && $b['TA_BMB01']!="N"){
						$data[$b['BMB03']][$b['BMB01']][$b['BMB01']]=$data[$b['BMB01']];
						foreach ($data[$b['BMB01']] as $d){
							if(isset($d[0]['IMAICD04'])?$d[0]['IMAICD04']:$d['IMAICD04']<4){
								if($d['ok']=='no_x'){
									$data[$b['BMB03']][$b['BMB01']]['ok']='no_x';
								}
							}
						}
						$data[$b['BMB03']][$b['BMB01']]['ok']='yeah';
					}else{
						$data[$b['BMB03']][$b['BMB01']]['ok']='no_x';
					}
					unset($data[$b['BMB01']]);
				}
			}
			//提出不完整的
			foreach ($data as $key=>$d){
				if($key!='ok'){
					foreach ($d as $dd){
						if(isset($dd[0]['PD'])){
							$a=$dd[0]['IMAICD01'];
						}else{
							$a=$dd['IMAICD01'];
						}
					}
					if($a){
						$data['ok'][$a.'__'][$key]=$d;
						unset($data[$key]);
					}
				}
			}
			
			$ass='';
			foreach ($data['ok'] as $key=>$dat){
				unset($dat[0]);
				foreach ($dat as $key1=>$da){
					if(is_array($da)){
						foreach ($da as $key2=>$d){
							unset($d[0]);
							unset($d['ok']);
							//dump($data['ok']['B_SS424__']['SS424AC-68P51_CP'])进去
							foreach ($d as $key3=>$o){
								//dump($d);
								if(is_array($o)){
									foreach ($o as $key4=>$oo){
										if(is_array($oo)){
											if(is_array($oo[0])){
												unset($oo[0]);
												unset($oo['ok']);
												foreach ($oo as $key5=>$i){
													foreach ($i as $key6=>$ii){
														if(is_array($ii)){
															if(is_array($ii[0])){
																
																if($ii[0]['PD']!==$ii[0]['IMAICD04']){
																	if(isset($data[$ii[0]['BMB01']])){
																		$data['ok'][$key][$key1][$key2][$key3][$key4][$key5][$key6][$ii[0]['BMB01']]=$data[$ii[0]['BMB01']];
																		$data['ok'][$key][$key1][$key2][$key3][$key4][$key5][$key6]['ok']='yeah';
																		unset($data['ok'][$key][$ii[0]['BMB01']]);
																	}
																	if(isset($data['ok'][$key][$ii[0]['BMB01']])){
																		$data['ok'][$key][$key1][$key2][$key3][$key4][$key5][$key6][$ii[0]['BMB01']]=$data['ok'][$key][$ii[0]['BMB01']];
																		$data['ok'][$key][$key1][$key2][$key3][$key4][$key5][$key6]['ok']='yeah';
																		unset($data['ok'][$key][$ii[0]['BMB01']]);
																	}
																}
															}
														}
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
			foreach ($data['ok'] as $key=>$dat){
				unset($dat[0]['BMB01']);
				unset($dat[0]['BMB03']);
				unset($dat[0]['IMAICD04']);
				unset($dat[0]['IMAICD01']);
				unset($dat[0]['PD']);
				$ass[]=isset($dat[0])?$dat[0]:array($key);
				unset($dat[0]);
				foreach ($dat as $da){
					if(is_array($da)){
						foreach ($da as $d){
							if(is_array($d[0])){
								$wf=explode('__',$d[0]['BMB01'])[0];
								unset($d[0]['BMB01']);
								unset($d[0]['BMB03']);
								unset($d[0]['IMAICD04']);
								unset($d[0]['IMAICD01']);
								unset($d[0]['PD']);
								$ass[]=$d[0];
								unset($d[0]);
								unset($d['ok']);
								foreach ($d as $o){
									foreach ($o as $oo){
										if(is_array($oo)){
											if(is_array($oo[0])){
												$cp=explode('__',$oo[0]['BMB01'])[0];
												if($oo[0]['IMAICD04']==4){
													$oo[0][]="$cp -> $wf";
												}
												unset($oo[0]['BMB01']);
												unset($oo[0]['BMB03']);
												unset($oo[0]['IMAICD04']);
												unset($oo[0]['IMAICD01']);
												unset($oo[0]['PD']);
												$ass[]=$oo[0];
												unset($oo[0]);
												unset($oo['ok']);
												foreach ($oo as $i){
													foreach ($i as $ii){
														if(is_array($ii)){
															if(is_array($ii[0])){
																$cp2=explode('__',$ii[0]['BMB01'])[0];
																if($ii[0]['IMAICD04']==4){
																	$ii[0][]="$cp2 -> $cp -> $wf";
																}
																unset($ii[0]['BMB01']);
																unset($ii[0]['BMB03']);
																unset($ii[0]['IMAICD04']);
																unset($ii[0]['IMAICD01']);
																unset($ii[0]['PD']);
																$ass[]=$ii[0];
																unset($ii[0]);
																unset($ii['ok']);
																foreach ($ii as $p){
																	if(is_array($p)){
																		foreach ($p as $k=>$pp){
																			if(is_array($pp)){
																				if(is_array($pp[0])){
																					$pkg=explode('__',$pp[0]['BMB01'])[0];
																					if($pp[0]['IMAICD04']==4){
																						$pp[0][]="$pkg -> $cp2 -> $cp -> $wf";
																					}
																					unset($pp[0]['BMB01']);
																					unset($pp[0]['BMB03']);
																					unset($pp[0]['IMAICD04']);
																					unset($pp[0]['IMAICD01']);
																					unset($pp[0]['PD']);
																					$ass[]=$pp[0];
																					
																					unset($pp[0]);
																					unset($pp['ok']);
																					foreach ($pp as $e){
																						if(isset($e[0]['BMB01'])){
																							$ft=explode('__',$e[0]['BMB01'])[0];
																							if($e[0]['IMAICD04']==4){
																								$e[0][]="$ft -> $pkg -> $cp2 -> $cp -> $wf";
																							}
																							unset($e[0]['BMB01']);
																							unset($e[0]['BMB03']);
																							unset($e[0]['IMAICD04']);
																							unset($e[0]['IMAICD01']);
																							unset($e[0]['PD']);
																							$ass[]=$e[0];
																						}else{
																							$ft=explode('__',$e['BMB01'])[0];
																							if($e['IMAICD04']==4){
																								$e[]="$ft -> $pkg -> $cp2 -> $cp -> $wf";
																							}
																							unset($e['BMB01']);
																							unset($e['BMB03']);
																							unset($e['IMAICD04']);
																							unset($e['IMAICD01']);
																							unset($e['PD']);
																							$ass[]=$e;
																						}
																					}
																				}else{
																					$pkg=explode('__',$pp['BMB01'])[0];
																					if($pp['IMAICD04']==4){
																						$pp[]="$pkg -> $cp2 -> $cp -> $wf";
																					}
																					unset($pp['BMB01']);
																					unset($pp['BMB03']);
																					unset($pp['IMAICD04']);
																					unset($pp['IMAICD01']);
																					unset($pp['PD']);
																					$ass[]=$pp;
																				}
																			}
																				
																			if($k>0){
																				//echo 'safsafsdafsd';
																			}
																				
																		}
																	}
																		
																}
															}else{
																$cp2=explode('__',$ii['BMB01'])[0];
																if($ii['IMAICD04']==4){
																	$ii[]="$cp2 -> $cp -> $wf";
																}
																unset($ii['BMB01']);
																unset($ii['BMB03']);
																unset($ii['IMAICD04']);
																unset($ii['IMAICD01']);
																unset($ii['PD']);
																$ass[]=$ii;
															}
														}
													}
												}
											}else{
												$cp=explode('__',$oo['BMB01'])[0];
												if($oo['IMAICD04']==4){
													$oo[]="$cp -> $wf";
												}
												unset($oo['BMB01']);
												unset($oo['BMB03']);
												unset($oo['IMAICD04']);
												unset($oo['IMAICD01']);
												unset($oo['PD']);
												$ass[]=$oo;
											}
										}
									}
								}
							}else{
								$wf=explode('__',$d['BMB01'])[0];
								unset($d['BMB01']);
								unset($d['BMB03']);
								unset($d['IMAICD04']);
								unset($d['IMAICD01']);
								unset($d['PD']);
								$ass[]=$d;
							}
						}
					}
				}
			}
			foreach ($data as $key=>$dat){
				if($key!='ok'){
					foreach ($dat as $da){
						if(isset($da[0]['PD'])){}else{$fuck[0]=$da;$da=$fuck;}
						unset($da[0]['BMB01']);
						unset($da[0]['BMB03']);
						unset($da[0]['IMAICD04']);
						unset($da[0]['IMAICD01']);
						unset($da[0]['PD']);
						$ass[]=$da[0];
						unset($da[0]);
						unset($da['ok']);
						foreach ($da as $d){
							unset($d[0]['BMB01']);
							unset($d[0]['BMB03']);
							unset($d[0]['IMAICD04']);
							unset($d[0]['IMAICD01']);
							unset($d[0]['PD']);
							$ass[]=$d[0];
						}
					}
					
				}
			}
			foreach ($ass as $key=>$as){
				$ass[$key]['TA_BMB01']='';
				foreach ($as as $k=>$a){
					$ass[$key][$k]=explode('__',$ass[$key][$k])[0];
				}
			}
			//dump($ass);
			//$add=array('','body','wf','cp前','cp后','pkg','ft','GROSS_DIE','','合格供应商','核价信息','PASS BIN','测试程序','','图号','','主件底数',/*'本级','上级','上级GROSS_DIE',*/'主芯片','','pass b','','产品归属地','','封装形式','','光罩版本','','光罩层次','','生产REFER TO','','最小发料','');
			$add=array('','body','wf','cp前','cp后','pkg','ft'/*,'pkg','cp后','cp前','wf',*/);
			array_unshift($ass,$add);
			$data=array(
					'name'=>'bom关系整理',
					array(
							'data'=>$ass,
							'style'=>array(
									'ret'=>'1',
									'star'=>1,
									'freezePane'=>'1',
									'cell'=>array(
											'B'=>array('width'=>'10'),
											'C'=>array('width'=>'30'),
											'D'=>array('width'=>'30'),
											'E'=>array('width'=>'30'),
											'F'=>array('width'=>'30'),
											'G'=>array('width'=>'30'),
									)
							),
					)
			);
			excel_css($data);
			exit;/**/
		}
		
		$div=array();
		foreach ($body as $b){
			if($b['IMAICD04']==0){
				$div[$b['IMAICD04']][$b['BMB01']]=array('<div class="tree-folder">
						<div class="tree-folder-header" onclick="show(\''.$b['BMB01'].'\');ojbk(\''.$b['BMB01'].'\',\''.$b['IMAICD04'].'\',this)" style="white-space: nowrap;">
							<i wtq_i="'.$b['BMB01'].'" class="icon-folder-close" style="color: #F8C600;"></i>
							<div wtq_color="'.$b['BMB01'].'" class="tree-folder-name">'.explode('__',$b['BMB01'])[0].'<span shu="0" wtq_good="'.$b['BMB01'].'">(0)</span><span shu="0" style="color:red" wtq_bad="'.$b['BMB01'].'">(0)</span><span shu="0" style="color:blue" wtq_bad_x="'.$b['BMB01'].'">(0)</span></div>
						</div>
						<div wtq_add="'.$b['BMB01'].'" wtq_lev="'.$b['IMAICD04'].'" wtq_zero="'.$b['IMAICD01'].'" wtq_prev="'.$b['BMB03'].'" class="tree-folder-content" style="display: none;"></div>
					   </div>',$b['BMB03'],$b['IMAICD01'],$b['BMB01'],$b['IMAICD04']);
			}else{
				//低速完美加载用
				$div[$b['IMAICD04']][$b['BMB01']]=array('<div class="tree-folder">
						<div class="tree-folder-header" onclick="show(\''.$b['BMB01'].'\');ojbk(\''.$b['BMB01'].'\',\''.$b['IMAICD04'].'\',this)" style="white-space: nowrap;">
							<i wtq_i="'.$b['BMB01'].'" class="icon-folder-close" style="color: #F8C600;"></i>
							<div wtq_color="'.$b['BMB01'].'" class="tree-folder-name">'.explode('__',$b['BMB01'])[0].'<span shu="0" wtq_good="'.$b['BMB01'].'">(0)</span><span shu="0" style="color:red" wtq_bad="'.$b['BMB01'].'">(0)</span><span shu="0" style="color:blue" wtq_bad_x="'.$b['BMB01'].'">(0)</span></div>
						</div>
						<div wtq_add="'.$b['BMB01'].'" wtq_lev="'.$b['IMAICD04'].'" wtq_zero="'.$b['IMAICD01'].'" wtq_prev="'.$b['BMB03'].'" class="tree-folder-content" style="display: none;"></div>
					   </div>',$b['BMB03'],$b['IMAICD01'],$b['BMB01'],$b['IMAICD04']);
				//快速加载用
				$div['no'][$b['IMAICD04']][$b['BMB03']][$b['BMB01']]=array('<div class="tree-folder">
						<div class="tree-folder-header" onclick="show(\''.$b['BMB01'].'\');ojbk(\''.$b['BMB01'].'\',\''.$b['IMAICD04'].'\',this)" style="white-space: nowrap;">
							<i wtq_i="'.$b['BMB01'].'" class="icon-folder-close" style="color: #F8C600;"></i>
							<div wtq_color="'.$b['BMB01'].'" class="tree-folder-name">'.explode('__',$b['BMB01'])[0].'<span shu="0" wtq_good="'.$b['BMB01'].'">(0)</span><span shu="0" style="color:red" wtq_bad="'.$b['BMB01'].'">(0)</span><span shu="0" style="color:blue" wtq_bad_x="'.$b['BMB01'].'">(0)</span></div>
						</div>
						<div wtq_add="'.$b['BMB01'].'" wtq_lev="'.$b['IMAICD04'].'" wtq_zero="'.$b['IMAICD01'].'" wtq_prev="'.$b['BMB03'].'" class="tree-folder-content" style="display: none;"></div>
					   </div>',$b['BMB03'],$b['IMAICD01'],$b['BMB01'],$b['IMAICD04']);
			}
			foreach ($b as $key=>$a){
				$div[$b['IMAICD04']][$b['BMB01']][$key]=$a;
			}
		}
		echo json_encode($div);
	}
	
}
