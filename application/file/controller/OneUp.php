<?php
namespace app\file\controller;
use think\Cache;
use think\Db;
use app\index\controller\Admin;

class OneUp extends Up {
	//同步权限
	public function qx_tongbu(){
		$id=isset($_POST['ml'])?$_POST['ml']:'';//目录id
		if($id==''){$id=isset($_GET['lei'])?$_GET['lei']:'';}
		if($id==''){
			$id=isset($_POST['id'])?$_POST['id']:'';
			$re_id=db()->query("select * from sw_file_list where id='$id'");
			$id=$re_id[0]['fenlei'];
		}
		$qx=db()->query("select * from sw_file_config where id='$id'");
		if($qx){
			if($qx[0]['dep_qx_open']==2){
				db()->query("update sw_file_list set qx='".$qx[0]['dep_qx']."' where fenlei in ($id)");
			}
		}
	}
	
	//引入主页面
	public function index(){
		$this->assign('up_sour', 'yes');
		//判断哪些文件为vip文件
		$vip=db()->query("select file_id from sw_file_vip_and_files where vip_use='".get_user_id()."'");
		$file_id='';
		foreach ($vip as $v){
			$file_id[]=$v['file_id'];
		}
		$this->assign('vip', json_encode($file_id));
		
		$this->assign('meta_title', '上传');
		return $this->fetch('one_up/index');
	}
	//页面ajax
	public function index_ajax(){
		$this->index_n('up');
	}
	//文件详情
	public function file_xx(){
		return $this->file_xx_all('up');
	}
	//下载
	public function file_xz(){
		$this->xz('up');
	}
	//预览
	public function open(){
		$this->open_all('up');
	}
	//菜单ajax
	public function menu_ajax(){
		if($_SESSION['u_i']['user_gh']=='a01'){
			echo json_encode($this->menu_ajax_all("",'up'));
		}else{
			echo json_encode($this->menu_ajax_all("where id = '".$_SESSION['u_i']['dep_id']."'",'up'));
		}
	}
	//修改文件ajax拉取
	public function change_ajax(){
		if($_GET['id']==''){
			echo json_encode($this->change_ajax_div('yes'));exit;
		}
		echo $this->change_ajax_div('yes').'<div><button wtq_up_index_alert_close="close" class="btn btn-primary btn-small layui-layer-close" style="float:right;margin-right:20px;">关闭</button> 
		        		<button onclick="change_ok(\'x\')" class="btn btn-primary btn-small" style="float:right;margin-right:5px;">保存并关闭</button>
		        		<button onclick="change_ok()" class="btn btn-primary btn-small" style="float:right;margin-right:5px;">保存</button></div> ';
	}
	//发表留言
	public function file_xx_up(){
		return $this->file_xx_up_all('up','look');
	}
	//加一
	public function add(){
		$this->add1('','up','look');
	}
	//添加新文件夹
	public function fenlei_add(){
		$this->fenlei_add_all('one_up/change_ajax');
	}
	//添加新文件夹
	public function fenlei_add_all($uo='up'){
		$fenlei=isset($_POST['fenlei'])?$_POST['fenlei']:'';
		$feifa=array('>','<');
		//$i=0;
		foreach ($feifa as $fa){
			if(strpos($fenlei,$fa)!==false){
				echo json_encode(array(0,'存在非法字符'));exit;
			}
		}
		//if($i==2){echo json_encode(array(0,'存在非法字符'));exit;}
		if(!$fenlei){echo json_encode(array(0,'数据不足'));exit;}
		$parents_id=isset($_GET['parents_id'])?$_GET['parents_id']:'';
		$dep_id=isset($_GET['dep_id'])?$_GET['dep_id']:'';
		$lv=isset($_GET['lv'])?$_GET['lv']:'';
		if($lv>10){echo json_encode(array(0,'目录已打最上限'));exit;}
		$fen_pd=db()->query("SELECT * FROM sw_file_config where name='".$fenlei."' and lv = '".$lv."' and parents_id = '".$parents_id."' and dep_id = '".$dep_id."'");
		if($fen_pd){echo json_encode(array(0,'该分类已存在'));exit;}
		$arr=array('name'=>$fenlei,'parents_id'=>$parents_id,'dep_id'=>$dep_id,'lv'=>$lv);
		//加入数据库并返回id
		$userId = db('file_config')->insertGetId($arr);
		$arr_1=explode('/',$_SERVER['PHP_SELF']);
		//组合返回
		$url='$(\'input[wtq_up_index_name=xin]\').val('.$userId.');ajax_index(\'?xing='.$userId.'\');';
		if($uo=='one_up/change_ajax'){
			$up_onclick='<div title="上传" class="tree-folder-name layerIframe" url="'.url('/file/').$uo.'?id='.$userId.'" hig="740px" wid="620px" onclick="return '.$uo.'(\''.$userId.'\')" class="tree-folder-name">上传</div>';
		}else{
			$up_onclick='<div title="上传" class="tree-folder-name layerIframe" url="'.url('/file/').$uo.'?id='.$userId.'" hig="90%" wid="70%">上传</div>';
		}
		$up_div='<div class="tree-folder">
									<div class="tree-folder-header">
										<i class="icon-cloud-upload" style="color: #31e30d"></i>
	
										'.$up_onclick.'
								</div>
							</div>';
		$del_div='<div class="tree-folder">
									<div class="tree-folder-header">
										<i class="icon-remove" style="color: red"></i>
	
										<div class="tree-folder-name" onclick="return del_this_fenlei(\''.$userId.'\')">删除</div>
								</div>
							</div>';
		if($lv<10){
			$new_div='<div class="tree-folder">
									<div class="tree-folder-header" style="white-space: nowrap;">
										<i class="icon-plus" style="color: #004FFF;"></i>
	
										<div class="tree-folder-name" add="no" onclick="if($(this).attr(\'add\')==\'no\'){$(\'div[wtq_bs_id='.$userId.']\').css(\'display\',\'\');$(this).prev(\'i\').attr(\'class\',\'icon-minus\');$(this).html(\'取消\');$(this).attr(\'add\',\'yes\');}else{$(\'div[wtq_bs_id='.$userId.']\').css(\'display\',\'none\');$(this).prev(\'i\').attr(\'class\',\'icon-plus\');$(this).html(\'新建文件夹\');$(this).attr(\'add\',\'no\');}">新建文件夹</div>
								</div>
							</div>';
			$add_div='<div class="tree-folder" style="display:none" wtq_bs_id="'.$userId.'">
									<div class="tree-folder-header" style="white-space: nowrap;">
										<i class="red icon-folder-close"></i>
	
										<div class="tree-folder-name" onclick=""><input value="new" style="width:50px;" wtq_new_id="'.$userId.'"><button onclick="fenlei_add(\''.$userId.'\',\'\',\''.$lv.'\')" class="btn btn-minier">确定</button></div>
								</div>
							</div>';
		}else{$new_div='';$add_div='';}
		$div='<div class="tree-folder" wtq_del_id="'.$userId.'">
								<div title="'.$fenlei.'" style="white-space: nowrap;" class="tree-folder-header" onclick="var a=$(this).next(\'div\');if(a.attr(\'op\')==\'close\'){a.css(\'display\',\'\');a.attr(\'op\',\'open\');$(this).children(\'i\').attr(\'class\',\'icon-folder-open\');}else{a.css(\'display\',\'none\');a.attr(\'op\',\'close\');$(this).children(\'i\').attr(\'class\',\'icon-folder-close\');}">
									<i class="icon-folder-close" style="color: #004FFF;"></i>
	 
									<div onclick="'.$url.'" class="tree-folder-name">'.$fenlei.'</div>
								</div>
						<div class="tree-folder-content" style="display:none" op="close">';
		$div=$div.$new_div.$up_div.$add_div.$del_div.'</div>';
		echo json_encode(array(1,'成功',$div));
	}
	//删除分类
	public function del_fenlei(){
		$id=isset($_POST['id'])?$_POST['id']:'';
		if($id==''){echo json_encode(array('0','缺少数据'));exit;}
		$config=db()->query("select * from sw_file_config where id='".$id."'");
		$idd='';
		for ($i=$config[0]['lv'];$i<=10;$i++){
			if($config){
				$id='';
				foreach ($config as $c){
					if($id==''){
						$id=$c['id'];
					}else{
						$id=$id.','.$c['id'];
					}
				}
				$config=db()->query("select * from sw_file_config where parents_id in(".$id.")");
				if($idd==''){
					$idd=$id;
				}else{
					$idd=$idd.','.$id;
				}
			}
	
		}
		//超级删除
		$sup=isset($_POST['sup'])?$_POST['sup']:'';
		$files=db()->query("select id,url,file_sc_name,file_houzhui from sw_file_list where fenlei in(".$idd.")");
		if($sup=='super' && in_array(get_cache_data('user_info',get_user_id(),'user_gh'),\think\Config::get('del_super'))){
			$del_id='';$fuck_id='';
			foreach ($files as $f){
				if($del_id==''){
					$del_id=$f['id'];
				}else{
					$del_id=$del_id.','.$f['id'];
				}
				
				$dir=$f['url'];
				$dh = opendir($dir);
				closedir(opendir($dir));
				$file  =  readdir ( $dh);
				$fullpath = $dir.'/'.$file;
				$fullpath=$f['url'].$f['file_sc_name'].'.'.$f['file_houzhui'];
				if (!unlink($fullpath)){
					if($del_id==''){
						$fuck_id='以下文件删除失败'.$f['id'];
					}else{
						$fuck_id=$del_id.','.$f['id'];
					}
				}
			}
			db('file_config')->where('id in ('.$idd.')')->delete();
			if($del_id){
				db('file_list')->where('id in ('.$del_id.')')->delete();
			}
			echo json_encode(array(1,'尊汝指令，删除完毕  '.$fuck_id));
		}else{
			if(!$files){
				//db('file_config')->delete([$idd]);
				db('file_config')->where('id in ('.$idd.')')->delete();
				echo json_encode(array('1','完成'));
			}else{
				echo json_encode(array('0','文件夹下有内容无法删除'));
			}
		}
	}
	//批量文件上传
	public function file_up(){
		if(!Cache::get('ppp')){$this->errorUpload('缺少数据');}
		if(!$_FILES['file']['name']){$this->errorUpload('缺少文件');}
		$page_info=Cache::get('ppp');
	
		$user_path=\think\Config::get('url').'/'.$page_info['fenlei'];
		$page_info=$this->file_x($page_info,$user_path,'no');
		//判断是否为修改
		
		if(Cache::get('more_id')){
			$more_id=Cache::get('more_id');
		}else{
			if(Cache::get('more_id2')){
				$this_more_files=db()->query("SELECT * FROM sw_file_batch_file_list where id='".Cache::get('more_id2')."'");
				$this_files=db()->query("SELECT * FROM sw_file_list where id in (".rtrim($this_more_files[0]['files_id'],',').") and more_id_bb_no='0'");
				if(!$this_files){
					$this_he=db()->query("SELECT * FROM sw_file_batch_file_list where parents_id='".Cache::get('more_id2')."'");
					if(!$this_he){
						$this->errorUpload('不允许修改');
					}
				}
				if(!isset($this_he)){
					$this_files=db()->query("UPDATE sw_file_list SET bb_new=0,more_id_bb_no=1 where id in (".rtrim($this_more_files[0]['files_id'],',').")");
				}
			}
			if(isset($this_he)){
				$more_id=$this_he[0]['id'];
			}else{
				//判断是否有上一版本
				if(Cache::get('more_id2')){$parents_id=Cache::get('more_id2');}else{$parents_id='0';}
				$page_more=array('parents_id'=>$parents_id,'this_bb'=>$page_info['bb'],'qx'=>$page_info['qx'],'bf_qx'=>$page_info['qx'],'import_text'=>$page_info['import_text'],'down'=>$page_info['down'],'bb'=>$page_info['bb'],'file_sta'=>$page_info['file_sta'],'die_time'=>$page_info['die_time'],'fenlei'=>$page_info['fenlei']);
				$more_id=db('file_batch_file_list')->insertGetId($page_more);
			}
	
		}
		
		
		$page_info['url']=\think\Config::get('url').'/'.$page_info['fenlei'].'/';
		$page_info['log']=get_user_nickname().'_'.date("Y-m-d H:i:s").'_上传了'.$_FILES['file']['name'];
		$page_info['more_id']=$more_id;
		$page_info['more_id_bb_no']='0';
		//判断是否为更新上传
		if(Cache::get('more_id2')){
			$this_more_files=db()->query("SELECT * FROM sw_file_batch_file_list where id='".Cache::get('more_id2')."'");
			$this_files=db()->query("SELECT * FROM sw_file_list where id in (".rtrim($this_more_files[0]['files_id'],',').")");
			$bb_this_new='0';
			foreach ($this_files as $t){
				echo $t['file_name'].';'.$_FILES['file']['name'].'<br>';
				if($t['file_name']==$_FILES['file']['name']){
					$bb_this_new='1';
					$bb_this_new_id=$t['id'];
					$tt=$t;
				}
			}
			if($bb_this_new=='1'){
				if($tt['bb_id']=='0'){
					$page_info['bb_id']=$bb_this_new_id;
				}else{
					$page_info['bb_id']=$tt['bb_id'];
				}
				$file_id=db('file_list')->insertGetId($page_info);
			}else{
				$file_id=db('file_list')->insertGetId($page_info);
			}
	
		}else{
			$file_id=db('file_list')->insertGetId($page_info);
		}
		if(Cache::get('more_id')){
			$page_id['files_id']=array('exp','CONCAT("'.$file_id.','.'",files_id)');
		}else{
			Cache::set('more_id',$more_id,300);
			$page_id['files_id']=$file_id.',';
		}
		db('file_batch_file_list')
		->where('id', $more_id)
		->update($page_id);
		$this->qx_tongbu();
	}
	//批量修改上传前数据缓存
	public function file_sql_change(){
		$more_id=isset($_GET['more_id'])?$_GET['more_id']:'';
		if($more_id==''){echo json_encode(array(0,'未知错误'));exit;}
		$this_sta=db()->query("SELECT * FROM sw_file_batch_file_list where id='".$more_id."'");
		$_GET['lei']=$this_sta[0]['fenlei'];
		$this->file_sql('all');
	}
	//文件上传前数据缓存
	public function file_sql($all=''){
		Cache::rm('more_id2');
		if($all=='all'){
			Cache::set('more_id2',$_GET['more_id'],300);
		}
		$xz=isset($_GET['xz'])?$_GET['xz']:'0';
		if($xz==''){$xz='0';}
		$lei=isset($_GET['lei'])?$_GET['lei']:'';
		$gj=isset($_GET['gj'])?$_GET['gj']:'';
		$sta=isset($_GET['sta'])?$_GET['sta']:'';
		$id=isset($_GET['id'])?$_GET['id']:'all';
		if($id==''){$id='all';}
		$bb=isset($_GET['bb'])?$_GET['bb']:'v1.0';
		if($bb==''){$bb='v1.0';}
		$die_time=isset($_GET['die_time'])?$_GET['die_time']:'9999-05-19 16:00:58';
		if($die_time==''){$die_time='9999-05-19 16:00:58';}
		if(!$lei){echo json_encode(array(0,'请选择分类'));exit;}
		$ppp=array('down'=>$xz,'fenlei'=>$lei,'import_text'=>$gj,'file_sta'=>$sta,'qx'=>$id,'bb'=>$bb,'die_time'=>$die_time);
		if(Cache::get('ppp')!=$ppp  || Cache::get('only_identification_probably')!=$_GET['only_identification_probably']){
			Cache::rm('more_id');
		}
		Cache::set('only_identification_probably',$_GET['only_identification_probably'],300);
		Cache::set('ppp',$ppp,300);
		//echo json_encode(array(1,'即将上传'));
		$this->file_up();
	}
	//单个文件上传
	public function change(){
		$bb=isset($_POST['bb'])?$_POST['bb']:'';
		if($bb==''){$bb='v1.0';}
		$die_time=isset($_POST['old_time'])?$_POST['old_time']:'9999-05-19 16:00:58';
		if($die_time==''){$die_time='9999-05-19 16:00:58';}
		$sta=isset($_POST['sta'])?$_POST['sta']:'';
		$qx_id=isset($_GET['qx_id'])?$_GET['qx_id']:'';
		$xz=isset($_POST['xz'])?$_POST['xz']:'';
		$gj=isset($_POST['gj'])?$_POST['gj']:'';
		
		$id=isset($_POST['id'])?$_POST['id']:'';
		$sh=$this->sql('up','new');
		if($id){
			$old=db()->query("SELECT * FROM sw_file_list where ".$sh." and id='".$id."'");
			if(!$old){echo json_encode(array(0,'你没有权限 '));exit;}
			$old=$old[0];
		}
		//判断修改还是上传
		if($_FILES['file']['name']){
			//判断是否更新版本
			if($id){
				$page_info=array('down'=>$xz,'fenlei'=>$old['fenlei'],'import_text'=>$gj,'file_sta'=>$sta,'qx'=>$qx_id,'bf_qx'=>$qx_id,'bb'=>$bb);
				if($old['bb_id']=='0'){
					$page_info['bb_id']=$id;
				}else{
					$page_info['bb_id']=$old['bb_id'];
				}
				if($old['more_id']!='0'){
					$page_info['more_id']=$old['more_id'];
					$page_info['more_id_bb_no']=$old['more_id_bb_no'];
				}
				
			}else{
				$fenlei=isset($_POST['ml'])?$_POST['ml']:'';
				if($fenlei==''){echo json_encode(array(0,'请选择分类 '));exit;}
				$page_info=array('down'=>$xz,'fenlei'=>$fenlei,'import_text'=>$gj,'file_sta'=>$sta,'qx'=>$qx_id,'bf_qx'=>$qx_id,'bb'=>$bb);
				$page_info['bb_id']='0';
				$page_info['more_id']='0';
			}
			
			$user_path=\think\Config::get('url').'/'.$page_info['fenlei'];
			$page_info=$this->file_x($page_info,$user_path);
			if($id){
				//版本改为旧版本
				db('file_list')
				->where('id', $id)
				->update(['bb_new' => '0']);
			}
			$page_info['die_time']=$die_time;
			$page_info['url']=\think\Config::get('url').'/'.$page_info['fenlei'].'/';
			$page_info['log']=get_user_nickname().'_'.date("Y-m-d H:i:s").'_上传了'.$_FILES['file']['name'];
			$file_id=db('file_list')->insertGetId($page_info);
			if($id){
				//判断更新的是否为批量文件
				if($old['more_id']!='0'){
					$page_id['files_id']=array('exp','CONCAT("'.$file_id.','.'",files_id)');
					db('file_batch_file_list')
					->where('id',$old['more_id'])
					->update($page_id);
				}
			}
			$this->qx_tongbu();
			echo json_encode(array(1,'上传完成'));
		}else{
			if($id==''){echo json_encode(array(0,'没有该项数据或未选择文件'));exit;}
			$file_id=isset($_POST['file_id'])?$_POST['file_id']:'';
			if($file_id!=''){
				$pd_id=db()->query("SELECT * FROM sw_file_batch_file_list where id='".$old['more_id']."'");
				$nid='';//$a='';
				foreach (explode(',',$pd_id[0]['files_id']) as $fff){
					foreach ($file_id as $f){
						if($fff==$f){
							if($nid==''){
								$nid=$f;
							}else{
								$nid=$nid.','.$f;
							}
							//$a='1';
						}
					}
					/*if($a!=1){$a='';
						$peo=' <span style="color:red">检测到不属于该批次的项以跳过该项</span>';
					}*/
				}
				$id='('.$nid.')';
			}else{
				$pd_id=db()->query("SELECT * FROM sw_file_list where id='".$id."'");
				$id='('.$id.')';
			}
			$page_info['ok']='2';
			$page_info['down']=$xz;
			$page_info['import_text']=$gj;
			$page_info['file_sta']=$sta;
			$page_info['qx']=$qx_id;
			$page_info['bf_qx']=$qx_id;
			$page_info['bb']=$bb;
			$page_info['die_time']=$die_time;
			$page_info['log']=array('exp','CONCAT("'.get_user_nickname().'_'.date("Y-m-d H:i:s").'_修改了<br>'.'",log)');
			db('file_list')
			->where('id in'.$id)
			->update($page_info);
			if(isset($peo)){}else{$peo='';}
			$this_shu=db()->query("SELECT count(*) FROM sw_file_list where fenlei = ".$pd_id[0]['fenlei']."");
			$this->qx_tongbu();
			echo json_encode(array(1,'修改完成'.$peo,$this_shu));
		}
		
		
	}
		
	//批量修改页面
	public function change_up(){
		$id=$_GET['more_id'];
		$this_sta=db()->query("SELECT * FROM sw_file_batch_file_list where id='".$id."'");
		if(!$this_sta){
			echo '未检测到该批次';exit;
		}
		$data3[0]=$this->he($this_sta);
		$data3[1]=$this->zhuti($this_sta,'<input wtq_is="more_id" name="id" style="display:none" value="'.$id.'">');
		$this->assign('list3',$data3[1]);
		$this->assign('list2',$data3[0]);
		
		$this->assign('only_identification_probably',md5(time().$this->randCode('20','-1')));
		
		return $this->fetch('up/all_up');
	}
	/**
	 +----------------------------------------------------------
	 * 生成随机字符串
	 +----------------------------------------------------------
	 * @param int       $length  要生成的随机字符串长度
	 * @param string    $type    随机码类型：0，数字+大小写字母；1，数字；2，小写字母；3，大写字母；4，特殊字符；-1，数字+大小写字母+特殊字符
	 +----------------------------------------------------------
	 * @return string
	 +----------------------------------------------------------
	 */
	public function randCode($length = 5, $type = 0) {
		$arr = array(1 => "0123456789", 2 => "abcdefghijklmnopqrstuvwxyz", 3 => "ABCDEFGHIJKLMNOPQRSTUVWXYZ", 4 => "~@#$%^&*(){}[]|");
		if ($type == 0) {
			array_pop($arr);
			$string = implode("", $arr);
		} elseif ($type == "-1") {
			$string = implode("", $arr);
		} else {
			$string = $arr[$type];
		}
		$count = strlen($string) - 1;
		$code = '';
		for ($i = 0; $i < $length; $i++) {
			$code .= $string[rand(0, $count)];
		}
		return $code;
	}
}