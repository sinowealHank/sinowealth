<?php
namespace app\user\controller;

use think\Controller;
use app\index\controller\Admin;
use think\Db;
set_time_limit(0);
//用户首页详情
class Newfile extends Admin{
	public function index(){
		
		$imaicd=db()->query("select * from imaicd_file");
		$bmb   =db()->query("select * from bmb_file");
		//dump($imaicd);
		//dump($bmb);
		$p='';$a='0';$b='0';$c='0';$d='0';$e='0';
		foreach ($imaicd as $i){
			
			if($i['imaicd04']==0){
				$ok=0;
				foreach ($imaicd as $im){
					if($im['imaicd01']==$i['imaicd00'] && $im['imaicd04']==1){
						foreach ($bmb as $b_2){
							if($b_2['bmb03']==$im['imaicd00']){
								foreach ($bmb as $b_3){
									if($b_3['bmb03']==$b_2['bmb01']){
										foreach ($bmb as $b_4){
											if($b_4['bmb03']==$b_3['bmb01']){
												//echo $b_4['bmb01'].'||'.$b_4['bmb03'].'<br>';
												$ok='1';
												break;
											}
											if($ok==1){break;}
										}
									}
									if($ok==1){break;}
								}
							}
						}
					}
					if($ok==1){break;}
				}
				if($ok==0){$a++;
					echo '<a href="'.url('index_rb').'?sj=0&name='.$i['imaicd01'].'" target="view_window")">'.$i['imaicd01'].'</a>  0<br>';
				}
			}
			
			if($i['imaicd04']==1){
				$ok=0;$ok_n=0;
				foreach ($imaicd as $im){
					if($i['imaicd01']==$im['imaicd00'] && $im['imaicd04']==0){
						$ok=1;
					}
					if($ok==1){break;}
				}
				foreach ($bmb as $b_2){
					if($b_2['bmb03']==$i['imaicd00']){
						foreach ($bmb as $b_3){
							if($b_3['bmb03']==$b_2['bmb01']){
								foreach ($bmb as $b_4){
									if($b_4['bmb03']==$b_3['bmb01']){
										//echo $b_4['bmb01'].'||';echo $b_4['bmb03'].'<br>';
										$ok_n='1';
										break;
									}
									if($ok_n==1){break;}
								}
							}
							if($ok_n==1){break;}
						}
					}
				}
				if($ok==0 || $ok_n==0){$b++;
					echo '<a href="'.url('index_rb').'?sj=1&name='.$i['imaicd00'].'" target="view_window">'.$i['imaicd00'].'</a>  1<br>';
				}
			}
			
			if($i['imaicd04']==2){
				$ok=0;$ok_n=0;
				foreach ($bmb as $b_2){
					if($b_2['bmb01']==$i['imaicd00']){
						foreach ($imaicd as $im){
							if($im['imaicd00']==$b_2['bmb03'] && $im['imaicd04']==1){
								//dump($im);
								foreach ($imaicd as $ima){
									if($im['imaicd01']==$ima['imaicd00'] && $ima['imaicd04']==0){
										//dump($ima);
										$ok=1;
									}
									if($ok==1){break;}
								}
							}
							if($ok==1){break;}
						}
							
							
						foreach ($bmb as $b_3){
							if($b_3['bmb03']==$b_2['bmb01']){
								foreach ($bmb as $b_4){
									if($b_4['bmb03']==$b_3['bmb01']){
										//dump($b_4);
										//echo 'a<br>';
										$ok_n='1';
										break;
									}
								}
							}
							if($ok_n==1){break;}
						}
					}
				}
				if($ok==0 || $ok_n==0){$c++;
					echo '<a href="'.url('index_rb').'?sj=2&name='.$i['imaicd00'].'" target="view_window">'.$i['imaicd00'].'</a>  2<br>';
				}
			}
			
			if($i['imaicd04']==3){
				$ok=0;$ok_n=0;
				foreach ($bmb as $b_3){
					if($b_3['bmb01']==$i['imaicd00']){
						foreach ($bmb as $b_2){
							if($b_2['bmb01']==$b_3['bmb03']){
								foreach ($imaicd as $im){
									if($im['imaicd00']==$b_2['bmb03'] && $im['imaicd04']==1){
										//dump($im);
										foreach ($imaicd as $ima){
											if($im['imaicd01']==$ima['imaicd00'] && $ima['imaicd04']==0){
												//dump($ima);
												$ok=1;
											}
											if($ok==1){break;}
										}
									}
									if($ok==1){break;}
								}
							}
							if($ok==1){break;}
						}
						foreach ($bmb as $b_4){
							if($b_4['bmb03']==$b_3['bmb01']){
								//dump($b_4);
								//echo 'a<br>';
								$ok_n='1';
								break;
							}
						}
					}
				}
				if($ok==0 || $ok_n==0){$d++;
					echo '<a href="'.url('index_rb').'?sj=3&name='.$i['imaicd00'].'" target="view_window">'.$i['imaicd00'].'</a>  3<br>';
				}
			}
			
			if($i['imaicd04']==4){
				$ok=0;
				foreach ($bmb as $b_4){
					if($b_4['bmb01']==$i['imaicd00']){
						foreach ($bmb as $b_3){
							if($b_3['bmb01']==$b_4['bmb03']){
								foreach ($bmb as $b_2){
									if($b_2['bmb01']==$b_3['bmb03']){
										foreach ($imaicd as $im){
											if($im['imaicd00']==$b_2['bmb03'] && $im['imaicd04']==1){
												//dump($im);
												foreach ($imaicd as $ima){
													if($im['imaicd01']==$ima['imaicd00'] && $ima['imaicd04']==0){
														//dump($ima);
														$ok=1;
													}
													if($ok==1){break;}
												}
											}
											if($ok==1){break;}
										}
									}
									if($ok==1){break;}
								}
							}
						}
					}
				}
				if($ok==0){$e++;
					echo '<a href="'.url('index_rb').'?sj=4&name='.$i['imaicd00'].'" target="view_window">'.$i['imaicd00'].'</a>  4<br>';
				}
			}
			
		}
		echo 'BODY  '.$a.'<br>';
		echo 'WF  '.$b.'<br>';
		echo 'CP  '.$c.'<br>';
		echo 'PKG '.$d.'<br>';
		echo 'FT '.$e;
	}
	public function index_rb(){
		$sj=isset($_GET['sj'])?$_GET['sj']:'';
		$name=isset($_GET['name'])?$_GET['name']:'';
		echo '<input id="sj" value="'.$sj.'"><input id="name" value="'.$name.'"><button onclick="go()">go</button><br>';
		echo '<script>function go(){window.location.href="'.url().'?sj="+document.getElementById("sj").value+"&name="+document.getElementById("name").value}</script>';
		if($sj=='' || $name==''){
			echo '请输入';
		}
		$a_0=array();$a_1=array();$a_2=array();$a_3=array();$a_4=array();
		if($sj==0){
			$a_0[0]['imaicd00']=$name;$a_0[0]['imaicd01']=$name;$a_0[0]['imaicd04']=0;
			$a_1=db()->query("select * from imaicd_file where imaicd01='".$name."' and imaicd04='1'");
			//dump($a_1);
			if($a_1){
				$sql='';
				foreach ($a_1 as $a){
					$sql=$sql."'".$a['imaicd00']."',";
				}
				$sql=rtrim($sql, ',');
				$a_2=db()->query("select * from bmb_file where bmb03 in (".$sql.")");
				//dump($a_2);
				if($a_2){
					$sql='';
					foreach ($a_2 as $a){
						$sql=$sql."'".$a['bmb01']."',";
					}
					$sql=rtrim($sql, ',');
					$a_3=db()->query("select * from bmb_file where bmb03 in (".$sql.")");
					//dump($a_3);
					if($a_3){
						$sql='';
						foreach ($a_3 as $a){
							$sql=$sql."'".$a['bmb01']."',";
						}
						$sql=rtrim($sql, ',');
						$a_4=db()->query("select * from bmb_file where bmb03 in (".$sql.")");
						//dump($a_4);
					}
				}
			}
		}
		
		if($sj==1){
			$a_1=db()->query("select * from imaicd_file where imaicd00='".$name."'");
			//dump($a_1);
			if($a_1){
				$a_0=db()->query("select * from imaicd_file where imaicd00='".$a_1[0]['imaicd01']."'");
				//dump($a_0);
				$a_2=db()->query("select * from bmb_file where bmb03='".$a_1[0]['imaicd00']."'");
				//dump($a_2);
				if($a_2){
					$sql='';
					foreach ($a_2 as $a){
						$sql=$sql."'".$a['bmb01']."',";
					}
					$sql=rtrim($sql, ',');
					$a_3=db()->query("select * from bmb_file where bmb03 in (".$sql.")");
					//dump($a_3);
					if($a_3){
						$sql='';
						foreach ($a_3 as $a){
							$sql=$sql."'".$a['bmb01']."',";
						}
						$sql=rtrim($sql, ',');
						$a_4=db()->query("select * from bmb_file where bmb03 in (".$sql.")");
						//dump($a_4);
					}
				}
			}
		}
		
		if($sj==2){
			$a=db()->query("select * from imaicd_file where imaicd00='".$name."'");
			//dump($a);
			if($a){
				$a_2=db()->query("select * from bmb_file where bmb01='".$name."'");
				//dump($a_2);
				if($a_2){
					$a_1=db()->query("select * from imaicd_file where imaicd00='".$a_2[0]['bmb03']."' and imaicd04=1");
					//dump($a_1);
					if($a_1){
						$a_0=db()->query("select * from imaicd_file where imaicd00='".$a_1[0]['imaicd01']."' and imaicd04=0");
						//dump($a_0);
					}
					$a_3=db()->query("select * from bmb_file where bmb03 = '".$a_2[0]['bmb01']."'");
					//dump($a_3);
					if($a_3){
						$sql='';
						foreach ($a_3 as $a){
							$sql=$sql."'".$a['bmb01']."',";
						}
						$sql=rtrim($sql, ',');
						$a_4=db()->query("select * from bmb_file where bmb03 in (".$sql.")");
						//dump($a_4);
					}
				}
			}
		}
		
		if($sj==3){
			$a=db()->query("select * from imaicd_file where imaicd00='".$name."'");
			//dump($a);
			if($a){
				$a_3=db()->query("select * from bmb_file where bmb01='".$name."'");
				//dump($a_3);
				if($a_3){
					$a_2=db()->query("select * from bmb_file where bmb01='".$a_3[0]['bmb03']."'");
					//dump($a_2);
					if($a_2){
						$a_1=db()->query("select * from imaicd_file where imaicd00='".$a_2[0]['bmb03']."' and imaicd04=1");
						//dump($a_1);
						if($a_1){
							$a_0=db()->query("select * from imaicd_file where imaicd00='".$a_1[0]['imaicd01']."' and imaicd04=0");
							//dump($a_0);
						}
					}
					$a_4=db()->query("select * from bmb_file where bmb03 = '".$a_3[0]['bmb01']."'");
					//dump($a_4);
				}
			}
		}
		if($sj==4){
			$a=db()->query("select * from imaicd_file where imaicd00='".$name."'");
			//dump($a);
			if($a){
				$a_4=db()->query("select * from bmb_file where bmb01='".$name."'");
				//dump($a_4);
				if($a_4){
					$a_3=db()->query("select * from bmb_file where bmb01='".$a_4[0]['bmb03']."'");
					//dump($a_3);
					if($a_3){
						$a_2=db()->query("select * from bmb_file where bmb01='".$a_3[0]['bmb03']."'");
						//dump($a_2);
						if($a_2){
							$a_1=db()->query("select * from imaicd_file where imaicd00='".$a_2[0]['bmb03']."' and imaicd04=1");
							//dump($a_1);
							if($a_1){
								$a_0=db()->query("select * from imaicd_file where imaicd00='".$a_1[0]['imaicd01']."' and imaicd04=0");
								//dump($a_0);
							}
						}
					}
				}
			}
		}
		//dump($a_0);
		//dump($a_1);
		//dump($a_2);
		//dump($a_3);
		//dump($a_4);
		foreach ($a_0 as $a0){
			if($a0['imaicd00']==$name){
				echo '<span style="color:blue">'.$a0['imaicd00'].'</span><br>';
			}else{
				echo $a0['imaicd00'].'<br>';
			}
			
			
			foreach ($a_1 as $a1){
				if($a1['imaicd01']==$a0['imaicd00']){
					
					if($a1['imaicd00']==$name){
						echo '<span style="color:blue">-'.$a1['imaicd00'].'</span><br>';
					}else{
						echo '-'.$a1['imaicd00'].'<br>';
					}
					//echo '-'.$a1['imaicd00'].'<br>';
					
					foreach ($a_2 as $a2){
						if($a2['bmb03']==$a1['imaicd00']){
							
							if($a2['bmb01']==$name){
								echo '<span style="color:blue">--'.$a2['bmb01'].'</span><br>';
							}else{
								echo '--'.$a2['bmb01'].'<br>';
							}
							//echo '--'.$a2['bmb01'].'<br>';
							
							foreach ($a_3 as $a3){
								if($a3['bmb03']==$a2['bmb01']){
									
									if($a3['bmb01']==$name){
										echo '<span style="color:blue">---'.$a3['bmb01'].'</span><br>';
									}else{
										echo '---'.$a3['bmb01'].'<br>';
									}
									//echo '---'.$a3['bmb01'].'<br>';
									
									foreach ($a_4 as $a4){
										if($a4['bmb03']==$a3['bmb01']){
											
											if($a4['bmb01']==$name){
												echo '<span style="color:blue">----'.$a4['bmb01'].'</span><br><br>';
											}else{
												echo '----'.$a4['bmb01'].'<br><br>';
											}
											//echo '----'.$a4['bmb01'].'<br><br>';
											
										}
										
									}
								}
							}
						}
					}
				}
			}
		}
		
		//$imaicd=db()->query("select * from imaicd_file where imaicd00=".$name."");
		//$bmb   =db()->query("select * from bmb_file where bmb01='".$name."'");
	}
	
	
	
}