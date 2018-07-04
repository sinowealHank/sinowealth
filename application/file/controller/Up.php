<?php
namespace app\file\controller;
use think\Cache;
use think\Db;
use app\index\controller\Admin;

class Up extends Admin {
	public function _initialize() {
		parent::_initialize();
		$houzhui=db()->query("select distinct file_houzhui from sw_file_list");
		$hou='';
		foreach ($houzhui as $h){
			$hou=$hou.'<option value="'.$h['file_houzhui'].'">'.$h['file_houzhui'].'</option>';
		}
		$this->assign('hou', $hou);
		$ok_open=array('js','php','sql','mp4','mp3','html','HTML','txt','JPG','jpg','jpeg','gif','png','art','au','aiff','xbm','swf');
		$this->assign('ok_open', json_encode($ok_open));
	}
	//乾坤大挪移
	public function diversion(){
		if(isset($_GET['diversion'])){
			exit(json_encode('<td rowspan="7">
					<div class="widget-body" style="width:250px;float:left;overflow-y:scroll;height:250px">
						<div class="widget-main padding-8" style="display: inline-block;">
							<div id="tree2" class="tree tree-unselectable">
								'.$this->menu_ajax_all("","",'','yes','no','').'
							</div>
						</div>
					</div>
				</td>'));
		}
		if(!in_array(get_cache_data('user_info',get_user_id(),'user_gh'),\think\Config::get('del_super'))){
			exit(json_encode(array(0,'没有权限就不要乱搞啊，有权限在来搞啊')));
		}
		$id=isset($_POST['id'])?$_POST['id']:'';
		$parents_id=isset($_POST['parents_id'])?$_POST['parents_id']:'';
		if($parents_id==''){$parents_id=0;}
		$dep_id=isset($_POST['dep_id'])?$_POST['dep_id']:'';
		$lv=isset($_POST['lv'])?$_POST['lv']:'';
		if($id=='' || $parents_id==='' || $dep_id=='' || $lv==''){echo json_encode(array('0','缺少数据'));exit;}
		
		$config=db()->query("select * from sw_file_config where id='".$id."'");		
		$lv=$config[0]['lv']-($lv+1);
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
			
			db('file_config')
				->where('id', $_POST['id'])
				->update([
					'parents_id'  => $parents_id,
					'dep_id' => $dep_id,
				]);
			$new_user=isset($_POST['new_user'])?$_POST['new_user']:'';
			if($new_user){
				db()->query("update sw_file_config set dep_id=$dep_id,lv=lv-$lv where id in ($idd)");
				$user_id=db()->query("select id,dep_id from sw_sys_user where user_gh='$new_user'");
				db()->query("update sw_file_list set file_dep_id=".$user_id[0]['dep_id'].",file_user_id=".$user_id[0]['id']." where fenlei in ($idd)");
			}
			exit(json_encode(array(1,'大挪移完成')));
	}
	//彻底删除记录
	public function this_del(){
		$id=isset($_POST['id'])?$_POST['id']:'';
		$file_xx=db()->query("select *  from sw_file_list where id = '".$id."'");
		//dump($file_xx);exit;
		if($file_xx[0]['bb_id']==0 && $file_xx[0]['ok']==2){
			$dir=$file_xx[0]['url'];
			$dh = opendir($dir);
			closedir(opendir($dir));
			$file  =  readdir ( $dh);
			$fullpath = $dir.'/'.$file;
			$fullpath=$file_xx[0]['url'].$file_xx[0]['file_sc_name'].'.'.$file_xx[0]['file_houzhui'];
			if (!unlink($fullpath)){
				echo json_encode(array(0,'文件删除失败'));
			}else{
				db('file_list')->delete($id);
				echo json_encode(array(1,'该记录已彻底删除，你将看不到任何痕迹'));
			}
		}else{
			echo json_encode(array(0,'条件不符，禁止删除'));
		}
	}
	//目录权限
	public function folder_qx(){
		$id=isset($_POST['id'])?$_POST['id']:'';
		if($id==''){echo json_encode(array('0','缺少数据'));exit;}
		$config=db()->query("select * from sw_file_config where id='".$id."'");
		
		if(isset($_POST['ok'])){}else{
			if($config[0]['dep_qx_open']==2){
				$check='checked="checked"';
			}else{$check='';}
			exit(json_encode($this->he(array(array('qx'=>$config[0]['dep_qx']))).'<br>是否生效：<label class="inline">
										<input '.$check.' dep_qx_open="ok" value="1" class="ace ace-switch ace-switch-5" type="checkbox">
										<span class="lbl"></span>
									</label>'));
		}
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
		$sql="";
		$qx=isset($_POST['qx_id'])?$_POST['qx_id']:'';
		$dep_qx_open=isset($_POST['dep_qx_open'])?$_POST['dep_qx_open']:'';
		if($dep_qx_open==1){
			db()->query("update sw_file_list set qx=bf_qx where fenlei in ($idd)");
		}else{
			db()->query("update sw_file_list set qx='$qx' where fenlei in ($idd)");
		}
		
		db('file_config')
			->where("id in ($idd)")
			->update([
				'dep_qx'  => $qx,
				'dep_qx_open'=>$dep_qx_open
			]);
			echo json_encode(array(1,'完成'));
	}
	
	
	
	
	
	
	//上传页面人名搜索
	public function up_sour(){
		$sour=isset($_GET['sour'])?$_GET['sour']:'';
		if($sour==''){echo json_encode(' ');exit;}
		$id=isset($_GET['id'])?$_GET['id']:'';
		$user=db()->query("select *  from sw_sys_user where nickname like '%".$sour."%'");
		if($user){
			$input='';
			foreach ($user as $u){
				if(strpos($id, '('.$u['id'].')')!== false){$check='checked="checked"';}else{$check='';}
				$input=$input.'<input '.$check.' clean="clean" onclick="var a=$(\'input[biaoshi=qx_geren][type=checkbox][value='.$u['id'].']\');if($(this).is(\':checked\')){a.prop(\'checked\',true)}else{a.prop(\'checked\',false)}; user_change(a);" value="'.$u['id'].'" type="checkbox" style="margin-left:5px;"><span clean="clean">'.$u['user_gh'].'_'.$u['nickname'].'</span>';
			}
		}else{$input='';}
		echo json_encode($input);
	}
	//文件上传获取文件信息并校验
	public function file_x($page_info,$user_path,$ts='one'){
		$name=time().rand(1,1000);
		$page_info['md5']=md5_file($_FILES['file']['tmp_name']);
		$cf=db()->query("SELECT * FROM sw_file_list where file_name='".$_FILES['file']['name']."' and md5='".$page_info['md5']."' and file_dep_id='".$_SESSION['u_i']['dep_id']."' and bb_new=1 and fenlei='".$page_info['fenlei']."'");//and more_id_bb_no!='1'
		//if($cf){$this->errorUpload('该文件已存在，请改名字或者打开文件加几个空格或者换个部门再上传');}
		if($cf){if($ts=='one'){echo json_encode(array(0,'该文件已存在'));exit;}else{$this->errorUpload('该文件已存在!');}exit;}
		
		$vip_files=db()->query("select name from sw_file_vip_files where dep_id='".$_SESSION['u_i']['dep_id']."'");
		if($ts!='vip'){
			foreach ($vip_files as $v){
				if($_FILES['file']['name']==$v['name']){
					if($ts=='one'){
						echo json_encode(array(0,'该名称禁止'));
					}else{
						$this->errorUpload('该名称禁止!');
					}
					exit;
				}
			}
		}
		$h=save($user_path,$name);
		$page_info['file_name']=$_FILES['file']['name'];
		$page_info['file_size']=$_FILES['file']['size'];
		$page_info['file_time']=date("Y-m-d H:i:s");
		$page_info['file_sc_name']=$name;
		$arr_1=explode('.',$_FILES['file']['name']);
		$page_info['file_houzhui']=end($arr_1);
		$page_info['file_user_id']=get_user_id();
		$page_info['file_user']=get_user_nickname();
		$page_info['file_dep_id']=$_SESSION['u_i']['dep_id'];
		return $page_info;
	}
	//错误提示
	public function errorUpload($message){
		header("HTTP/1.1 455");
		echo $message;
		exit;
	}
	//拉取上传页面弹框
	public function change_ajax_div($up='',$yc_id='',$this_sta=''){
		$id=isset($_POST['id'])?$_POST['id']:'';
		$sh=$this->sql('up','new');
		$ccc='';$up_file='';
		//判断上传页面
		if($id){
			//判断是否有访问权限
			$this_sta=db()->query("SELECT * FROM sw_file_list where ".$sh." and id='".$id."'");
			if(!$this_sta){echo json_encode(array('0','你没有权限 '));exit;}
			
			$vip=db()->query("select file_id from sw_file_vip_and_files where vip_use='".get_user_id()."'");
			foreach ($vip as $v){
				if($id==$v['file_id']){
					echo json_encode(array('0','你没有权限 '));exit;
				}
			}
			
			if($this_sta[0]['more_id']!='0'){
				$ccc=$this->more_file($sh, $this_sta,$id);
			}
			$yc_id='<input name="id" style="display:none" value="'.$id.'" >';
				
		}else{
			if($this_sta==''){
				$this_sta=array(array('qx'=>'all','down'=>'1','import_text'=>'','bb'=>'','file_sta'=>'','die_time'=>''));
			}
			$ccc=$this->up_menu();
		}
		if($up){
			$up_file='<span for="" class="control-label" style="font-size:20px;float:left;padding-right:10px">选择文件:</span>
						<div class="col-md-8 input-group" style="float:left;">
						    <input onchange="$(this).next().next().val($(this).val())" name="file" id="lefile" style="display:none" type="file">
						    <span class="input-group-addon" onclick="$(\'input[id=lefile]\').click();" style="cursor: pointer; background-color: #e7e7e7"><i class="fa fa-folder-open"></i>Browse</span>
						    <input id="photoCover" class="form-control" type="text">
			    		</div>';
		}
		
		$from_qx=$this->he($this_sta);
		return  $this->zhuti($this_sta,$yc_id,$up_file,$ccc,$from_qx);
	}
	//上传页面拼接
	public function zhuti($this_sta='',$yc_id='',$up_file='',$ccc='',$from_qx=''){
		if($this_sta[0]['down']==1){$div_down='checked="checked"';}else{$div_down='';}
		if(strpos($this_sta[0]['qx'], 'all')!== false){$div_qx='checked="checked"';}else{$div_qx='';}
		$div='<div style="padding:10px 0 0 20px;">
					<form wtq_form="id">
						'.$yc_id.$up_file.'
						<table>
							<!--tr><td colspan="2"><span style="color:red">*修改后将重新审核</span></td></tr-->
							<tr height="60px">
								<td width="100px;"><span style="float:right">是否允许下载：</span></td>
								<td width="100px;">
									<label class="inline">
										<input value="1" name="xz" '.$div_down.' class="ace ace-switch ace-switch-5" type="checkbox">
										<span class="lbl"></span>
									</label>
								</td>
								'.$ccc.'
							</tr>
							<tr>
								<td>是否所有人可见:</td>
								<td>
									<label class="inline">
										<input '.$div_qx.' onclick="if($(this).is(\':checked\')){$(\'form[wtq_id=qx]\').css(\'display\',\'none\');}else{$(\'form[wtq_id=qx]\').css(\'display\',\'\');}" biaoshi="all" value="all" class="ace ace-switch ace-switch-5" type="checkbox">
										<span class="lbl"></span>
									</label>
								</td>
							</tr>
							<tr height="50px">
								<td colspan=""><span style="float:right">关键字：</span></td>
								<td>
									<input name="gj" value="'.$this_sta[0]['import_text'].'">
								</td>
							</tr>
							<tr height="50px">
								<td colspan=""><span style="float:right">版本：</span></td>
								<td>
									<input  name="bb" value="'.$this_sta[0]['bb'].'">
								</td>
							</tr>
							<tr>
								<td><span style="float:right">说明：</span></td>
								<td><textarea name="sta" rows="" cols="">'.$this_sta[0]['file_sta'].'</textarea></td>
							</tr>
							<tr>
								<td><span style="float:right">文件过期时间：</span></td>
								<td>
									<!--div class="inline layinput"><input value="'.$this_sta[0]['die_time'].'" name="old_time" placeholder="" onclick="laydate({istime: true, format: \'YYYY-MM-DD hh:mm:ss\'})"></div-->
									<input id="time" name="old_time" time placeholder="" value="'.$this_sta[0]['die_time'].'">
								</td>
							</tr>
						</table>
					</form>
					'.$from_qx.'
				</div>';
		return $div;
	}
	//站点输出
	public function site($this_sta){
		$site=db()->query("select *  from sw_sys_site");
		$dy='';
		foreach ($site as $s){
			if(strpos($this_sta[0]['qx'], '{'.$s['id'].'}')!== false){$ok="checked=checked";}else{$ok='';}
			$dy=$dy.'<br><input '.$ok.' biaoshi="qx_site" name="site[]" type="checkbox" value="'.$s['id'].'"/>'.$s['site'].'_'.$s['site_code'];
		}
		return  $dy;
	}
	//部门输出
	public function dep($this_sta){
		$dep=db()->query("select *  from sw_sys_dep");
		$i=0;
		$depp='<table><tr>';
		foreach ($dep as $dd){
			$i++;
			if($i==7){$i=0;$depp=$depp.'</tr><tr>';}
			if(strpos($this_sta[0]['qx'], '['.$dd['id'].']')!== false){$ok="checked=checked";}else{$ok='';}
			$depp=$depp.'<td style="cursor:pointer;" onclick="if($(this).children(\'input\').is(\':checked\')){$(this).children(\'input\').prop(\'checked\',false)}else{$(this).children(\'input\').prop(\'checked\',true)}"><input onclick="event.stopPropagation();" '.$ok.' biaoshi="qx_dep" name="dep[]" type="checkbox" value="'.$dd['id'].'"/>'.$dd['en_name'].'</td>';
		}
		$depp=$depp.'</table>';
		return  $depp;
	}
	//人员输出
	public function users($this_sta){
		$dep=db()->query("select *  from sw_sys_dep");
		$user=db()->query("select *  from sw_sys_user");
		foreach ($dep as $d){
			foreach ($user as $u){
				if($u['dep_id']==$d['id']){
					$data3[$d['id']][$d['en_name']][$u['id']]=$u['user_gh'].'_'.$u['nickname'];
				}
			}
		}
		
		$dep='<div style="float:left;direction:rtl;overflow-y: scroll;overflow-x: hidden;height:200px;display:block;">
				<div class="tabbable tabs-left">
					<ul class="nav nav-tabs" id="myTab3" style="direction:rtl">
						<li class="active"><a data-toggle="tab" href="#user_all">已选择</a></li>';
		$user='<div class="tab-content" style="overflow-y: scroll;height:200px;display:block;">';
		$yes_user='';$yes_user_i=0;
		foreach ($data3 as $k1=>$dd){
			foreach ($dd as $k2=>$d){
				$user=$user.'<div id="'.$k2.'" class="tab-pane"><table><tr>';$i=0;
				foreach ($d as $k3=>$o){$i++;
					if(strpos($this_sta[0]['qx'], '('.$k3.')')!== false){$ok="checked=checked";}else{$ok='';}
					if($i==4){$i=0;$user=$user.'</tr><tr>';}
					$user=$user.'<td style="cursor:pointer;">
									<input onchange="user_change(this)" '.$ok.' biaoshi="qx_geren" name="geren[]" type="checkbox" value="'.$k3.'" /><span onclick="if($(this).prev(\'input\').is(\':checked\')){$(this).prev(\'input\').prop(\'checked\',false)}else{$(this).prev(\'input\').prop(\'checked\',true)}; user_change($(this).prev(\'input\'));">'.$o.'</span></td>';
					if($ok){
						$yes_user_i++;
						$yes_user=$yes_user.'<div del="del" style="cursor:pointer;float:left;padding-left:10px;">
												<input onchange="user_change(this)" onclick="var a=$(\'input[biaoshi=qx_geren][type=checkbox][value='.$k3.']\');if($(this).is(\':checked\')){a.prop(\'checked\',true)}else{a.prop(\'checked\',false)};" '.$ok.' type="checkbox" value="'.$k3.'" /><span onclick="if($(this).prev(\'input\').is(\':checked\')){$(this).prev(\'input\').prop(\'checked\',false)}else{$(this).prev(\'input\').prop(\'checked\',true)};var a=$(\'input[biaoshi=qx_geren][type=checkbox][value='.$k3.']\');if($(this).is(\':checked\')){a.prop(\'checked\',true)}else{a.prop(\'checked\',false)}; user_change($(this).prev(\'input\'))">'.$o.'</span></div>';
					}
				}
				$user=$user.'</tr></table></div>';
				$dep=$dep.'<li class="">
							<a data-toggle="tab" href="#'.$k2.'">'.$k2.'</a></li>';
			}
		}
		$dep=$dep.'</ul></div></div>';
		$user=$user.'<div id="user_all" class="tab-pane active">'.$yes_user.'</div></div>';
		$gr='<div style="padding-right:40px">'.$dep.$user.'</div>';
		return  $gr;
	}
	//权限选择拼接
	public function he($this_sta){
		//站点
		$dy=$this->site($this_sta);
		//部门
		$depp=$this->dep($this_sta);
		//用户
		$gr=$this->users($this_sta);
		if(strpos($this_sta[0]['qx'], 'all')!== false){$div_qx='checked="checked"';$div_qx_form='none';}else{$div_qx='';$div_qx_form='';}
		$from_qx='<form wtq_id="qx" style="display:'.$div_qx_form.'">
						<div><button type="button" wtq_this="b" onclick="qx(\'diyu\',this)" class="btn btn-purple">站点</button><button type="button" wtq_this="b" onclick="qx(\'bumen\',this)" class="btn btn-light">部门</button><button type="button" wtq_this="b" onclick="qx(\'geren\',this)" class="btn btn-light">个人</button><span wtq_sour_gr_id="gr" style="display:none"><input style="margin-left:20px;" wtq_this="gr_so"> <button type="button" onclick="ggr()" class="btn btn-sm btn-primary">搜索</button>
						</span></div>
						<div wtq_this="a" wtq_up_my_file_fen="diyu">
							'.$dy.'
						</div>
						<div wtq_this="a" wtq_up_my_file_fen="bumen" style="display:none">
							'.$depp.'
						</div>
						<div wtq_this="a" wtq_up_my_file_fen="geren" style="display:none">
							<span>个人：</span>
							<div wtq_up_up_id="sour">
								'.$gr.'
							</div>
						</div>
					</form>';
		return $from_qx;
	}
	//文件夹目录
	public function up_menu($new='',$vie='up_menu'){
		$id=isset($_GET['id'])?$_GET['id']:'';
		if($_SESSION['u_i']['user_gh']==\think\Config::get('ADMIN_NAME')){
			$sql='';
		}else{
			$sql="where id='".$_SESSION['u_i']['dep_id']."'";
		}
		return '<td rowspan="7">
					<div class="widget-body" style="width:250px;float:left;overflow-y:scroll;height:250px">
						<div class="widget-main padding-8" style="display: inline-block;">
							<div id="tree2" class="tree tree-unselectable">
								'.$this->menu_ajax_all($sql,"",'','yes','no',$id).'
							</div>
						</div>
					</div>
				</td>';
	}
	//多文件修改文件拉取
	public function more_file($sh,$this_sta,$id){
		$this_more=db()->query("SELECT * FROM sw_file_batch_file_list where id='".$this_sta[0]['more_id']."'");
		$more_fi=db()->query("SELECT * FROM sw_file_list where ".$sh." and more_id='".$this_sta[0]['more_id']."'");
		$this_all='';$all_id='';
		foreach ($more_fi as $m){
			foreach ($this_more as $t){
				$arr_t=explode(',',$t['files_id']);
				if(in_array($m['id'],$arr_t)){
					if($all_id==''){$all_id=$m['id'];}else{$all_id=$all_id.','.$m['id'];}
					if($m['id']==$id){
						$chec='checked="checked"';
					}else{
						$chec='';
					}
					$this_all=$this_all.'<input '.$chec.' wtq_id="file_id" type="checkbox" value="'.$m['id'].'" name="file_id[]" style="float: left;"><span title="'.$m['file_name'].'" style="display:block;white-space:nowrap; overflow:hidden;text-overflow:ellipsis;width:200px;float: left;">'.$m['file_name'].'</span><br>';
				}
			}
		}
		$arr_1=explode('/',$_SERVER['PHP_SELF']);
		$ccc='<!--td rowspan="7"><div style="overflow-y: scroll;height:270px;">
					<span url="http://'.$_SERVER['HTTP_HOST'].'/'.$arr_1[1].'/public/index.php/file/All_up/change_up?more_id='.$this_sta[0]['more_id'].'" class="btn btn-sm btn-primary layerIframe" wid="70%" hig="90%" title="更新上传" onclick="$(\'button[wtq_up_index_alert_close=close]\').trigger(\'click\')">批量上传修改</span>
					<br><input onclick="var a=$(\'input[type=checkbox][wtq_id=file_id]\');if($(this).is(\':checked\')){a.prop(\'checked\',true)}else{a.prop(\'checked\',false)}" type="checkbox" value="all" name="all">全选<br>
					'.$this_all.'</div>
				</td-->';
		return $ccc;
	}
	//文件详情
	public function file_xx_all($vie='file'){
		$id=addslashes(isset($_GET['id'])?$_GET['id']:'');
		if($id==''){echo '没有该项数据 ';exit;}
		//判断权限
		$sh=$this->sql($vie,'look');
		$file=db()->query("SELECT * FROM sw_file_list where ".$sh." and id='".$id."'");
		if(!$file){echo '没有该项数据或你没有访问权限';exit;}
		$this->assign('file',$file[0]);
		//获取拼接留言
		$div=$this->file_liu('en');
		//旧版本
		$old='';
		if($file[0]['bb_id']=='0'){$file[0]['bb_id']=$id;}
		if($vie=='up'){
			
			$old_all=db()->query("SELECT * FROM sw_file_list where (id='".$file[0]['bb_id']."' or bb_id='".$file[0]['bb_id']."') and (qx like '%all%' or qx like '%".$_SESSION['u_i']['site_id']."%' or qx like '%".$_SESSION['u_i']['dep_id']."%' or qx like '%".get_user_id()."%') and id!='".$id."'");
			if($old_all){
				$old='<span style="font-size:20px">其他版本：</span><br>';
				$arr_1=explode('/',$_SERVER['PHP_SELF']);
				foreach ($old_all as $o){
					if($o['id']==$id){}else{
						$old=$old.'<a style="cursor:pointer;display:block;white-space:nowrap; overflow:hidden;text-overflow:ellipsis;width:90%;" class="layerIframe" wid="70%" hig="90%" title="'.$o['file_name'].' '.$o['bb'].' '.$o['file_time'].'" url="http://'.$_SERVER['HTTP_HOST'].'/'.$arr_1[1].'/public/index.php/file/one_up/file_xx?id='.$o['id'].'">'.$o['file_name'].' '.$o['bb'].' '.$o['file_time'].'</a>';
					}
				}
			}
			
			/*if($file[0]['more_id']!=0){
				$old_more_all=db()->query("SELECT * FROM sw_file_batch_file_list where id='".$file[0]['more_id']."'");
				if($old_more_all){
					$old_more_all[0]['files_id']=rtrim($old_more_all[0]['files_id'],',');
					$old_more_all_id=db()->query("SELECT * FROM sw_file_list where id in (".$old_more_all[0]['files_id'].") and bb_id='0'");
					$old=$old.'本批次其他文件：<br>';
					$arr_1=explode('/',$_SERVER['PHP_SELF']);
					foreach ($old_more_all_id as $o){
						if($o['id']==$id){}else{
							$old=$old.'<a style="cursor:pointer;display:block;white-space:nowrap; overflow:hidden;text-overflow:ellipsis;width:90%;" class="layerIframe" wid="70%" hig="90%" title="'.$o['file_name'].' '.$o['bb'].' '.$o['file_time'].'" url="http://'.$_SERVER['HTTP_HOST'].'/'.$arr_1[1].'/public/index.php/file/one_up/file_xx?id='.$o['id'].'">'.$o['file_name'].' '.$o['bb'].' '.$o['file_time'].'</a>';
						}
					}
					
					
					if($old_more_all[0]['parents_id']!=0){
						$old_more_all_2=db()->query("SELECT * FROM sw_file_batch_file_list where id='".$old_more_all[0]['parents_id']."'");
						if($old_more_all_2){
							$old_more_all_2[0]['files_id']=rtrim($old_more_all_2[0]['files_id'],',');
							$old_more_all_id=db()->query("SELECT * FROM sw_file_list where id in (".$old_more_all_2[0]['files_id'].") and bb_id='0'");
							$old=$old.'上一批次文件：<br>';
							$arr_1=explode('/',$_SERVER['PHP_SELF']);
							foreach ($old_more_all_id as $o){
								if($o['id']==$id){}else{
									$old=$old.'<a style="cursor:pointer;display:block;white-space:nowrap; overflow:hidden;text-overflow:ellipsis;width:90%;" class="layerIframe" wid="70%" hig="90%" title="'.$o['file_name'].' '.$o['bb'].' '.$o['file_time'].'" url="http://'.$_SERVER['HTTP_HOST'].'/'.$arr_1[1].'/public/index.php/file/one_up/file_xx?id='.$o['id'].'">'.$o['file_name'].' '.$o['bb'].' '.$o['file_time'].'</a>';
								}
							}
						}
					}
				}
			}*/
		}
		$this->assign('old',$old);
		$this->assign('div',$div);
		$this_2018_mulu=db()->query("select * from sw_file_config where id='".$file[0]['fenlei']."'");
		$this_menu=$this_2018_mulu[0]['name'];
		for($i=$this_2018_mulu[0]['lv']-1;$i>0;$i--){
			if($i==1){
				$this_2018_mulu=db()->query("select * from sw_sys_dep where id='".$this_2018_mulu[0]['dep_id']."'");
				$this_menu=$this_2018_mulu[0]['en_name'].' -> '.$this_menu;
			}else{
				$this_2018_mulu=db()->query("select * from sw_file_config where id='".$this_2018_mulu[0]['parents_id']."'");
				$this_menu=$this_2018_mulu[0]['name'].' -> '.$this_menu;
			}
		}
		$this->assign('this_menu',$this_menu);
		return $this->fetch('up/file_xx');
	}
	//文件留言异步组合
	public function file_liu($a='',$vie='file'){
		$id=addslashes(isset($_GET['id'])?$_GET['id']:'');
		//拉取属于该文件的留言
		$file_liu=db('file_msg')->where('file_id',$id)->select();
		//遍历组合为合适形态的数组
		foreach ($file_liu as $key2=>$f){
			foreach ($file_liu as $key=>$f2){
				if($f['id']==$f2['parents_id']){
					$file_liu[$key2]['children'][]=$f2;
					unset($file_liu[$key]);
				}
			}
		}
		$div='';
		foreach ($file_liu as $f){
			$name_one=db('sys_user')->where('id',$f['user_id'])->find();
			//判断是否有回复
			if(isset($f['children'])){
				$hui='<div class="col-md-12 col-xs-12">
						 <span style="float:right;padding:10px;">'.$f['time'].' <a onclick="huifu(\''.$f['id'].'\',\''.$f['id'].'\',\''.$name_one['nickname'].'\')" href="javascript:void(0)"><!--收起-->回复</a></span>
					 </div>';
				$hui_c='';
				foreach ($f['children'] as $fc){
					//第二级及之后的回复
					$name=db('sys_user')->where('id',$fc['user_id'])->find();
					$hui_c=$hui_c.'<div class="col-md-12 col-xs-12" style="border:1px solid black;"><div class="row">
										<div class="col-md-2 col-xs-2" style="padding:5px 0;margin-right: -40px;">
											<img width="70px" alt="id_name" src="/new/public/public/tx.jpg" style="">
										</div>
										<div class="col-md-10 col-xs-10" style="">
											<div style="padding-top:5px"><span style="color:blue;">'.$name['nickname'].' ：</span>'.$fc['neirong'].'</div>
											<div><span style="float:right;">'.$f['time'].' <a onclick="huifu(\''.$fc['id'].'\',\''.$f['id'].'\',\''.$name['nickname'].'\')" href="javascript:void(0)">回复</a></span></div>
										</div>
									</div></div>';
				}
				$hui=$hui.$hui_c;
			}else{
				//第一级的右下角回复按钮
				$hui='<div class="col-md-12 col-xs-12">
						 <span style="float:right;padding:10px;">'.$f['time'].' <a onclick="huifu(\''.$f['id'].'\',\''.$f['id'].'\',\''.$name_one['nickname'].'\')" href="javascript:void(0)">回复</a></span>
					 </div>';
			}
			$div=$div.'<div class="row" id='.$f['id'].' style="border:1px solid black;margin-left: 10px;">
					<div class="col-md-2 col-xs-2" style="float:left;text-align: center;">
						<img width="100px" alt="id_name" src="/new/public/public/tx.jpg" style="margin-top:10px;">
						<div style="text-align: center;"><p>'.$name_one['nickname'].'</p></div>
					</div>
					<div class="col-md-9 col-xs-9" style="">
						<div class="row">
							<div style="height:150px;padding:10px">'.$f['neirong'].'</div>
							'.$hui.'
							<div wtq_this_id2="yc" wtq_this_id="'.$f['id'].'" style="display:none"></div>
						</div>
					</div>
				</div>';
		}
		//根据传值判断是异步合适打开页面
		if($a='en'){
			return $div;
		}else{
			echo json_encode($div);
		}
	}
	//文件留言发表
	public function file_xx_up_all($vie='file',$no=''){
		$sh=$this->sql($vie,$no);
		//判断是否有访问权限
		$this_sta=db()->query("SELECT * FROM sw_file_list where ".$sh." and id = '".$_POST['file_id']."'");
		if(!$this_sta){echo '没有该项权限';exit;}
		$page_info['parents_id']=isset($_POST['id'])?$_POST['id']:'0';
		$page_info['file_id']=isset($_POST['file_id'])?$_POST['file_id']:'';
		if(!$page_info['file_id']){echo json_encode(array(0,'文件未不到'));exit;}
		$page_info['user_id']=get_user_id();
		$page_info['neirong']=isset($_POST['text'])?$_POST['text']:'';
		if(!$page_info['neirong']){echo json_encode(array(0,'请输入内容'));exit;}
		$page_info['time']=date("Y-m-d H:i:s");
		db('file_msg')->insert($page_info);
		$page_infoo['log']=array('exp','CONCAT("'.get_user_nickname().'_'.date("Y-m-d H:i:s").'评论了<br>'.'",log)');
		db('file_list')
		->where('id', $_POST['file_id'])
		->update($page_infoo);
		echo json_encode(array(1,'ok'));
	}
	//根据访问页面判断sql
	public function sql($vie='',$no=''){
		$qx_in='';$show='';$time_die='';$banben='';
		//根据不同的页面借口判断可以访问的数据
		if($vie=='file'){
			$qx_in="ok='1' ";
			$show=" and (qx like '%all%' or qx like '%{".$_SESSION['u_i']['site_id']."}%' or qx like '%[".$_SESSION['u_i']['dep_id']."]%' or qx like '%(".get_user_id().")%')";
			$time_die="and die_time>='".date("Y-m-d H:i:s")."'";
			$banben="and bb_new='1' ";
		}else if($vie=='up'){
			$qx_in='1=1';
			if($_SESSION['u_i']['user_gh']=='a01'){
				$show='  ';
			}else{
				if(in_array(get_cache_data('user_info',get_user_id(),'user_gh'),\think\Config::get('Record_user'))){
					$show=" and file_dep_id in ('".$_SESSION['u_i']['dep_id']."','999') ";
				}else{
					$show=" and file_dep_id ='".$_SESSION['u_i']['dep_id']."' ";
				}
			}
			
			if($no=='look'){}else{
				$die_file=isset($_POST['die'])?$_POST['die']:'0';
				if($die_file==''){$die_file=0;}
				if($die_file=='0'){
					$time_die="and die_time>='".date("Y-m-d H:i:s")."'";
				}else if($die_file=='1'){
					$time_die="and die_time<='".date("Y-m-d H:i:s")."'";
				}else{
					$time_die='and 1=1 ';
				}
				//yes更新判断
				if($no=='new'){
					$time_die='and 1=1 ';
					$banben="and bb_new='1' and ok!=0";
				}else{
					$banben="and bb_new='1'";
				}
			}
		}else if($vie=='shen'){
			$zt=addslashes(isset($_POST['zt'])?$_POST['zt']:'');
			if($zt!=''){}else{
				$_POST['zt']='2';
			}
			$qx_in="file_dep_id=".$_SESSION['u_i']['dep_id'];
		}
		$sql=$qx_in.$show.$time_die.$banben;
		return $sql;
	}
	public function un($a){
		if(strpos($a,'undefined')!==false){$a='';}else{$a=$a;}
		return $a;
	}
	//页面内容异步
	public function index_n($vie='file',$no=''){
		//搜索
		$sql_jq=$this->un(addslashes(isset($_POST['sql_jq'])?$_POST['sql_jq']:''));
		$name=$this->un(addslashes(isset($_POST['name'])?$_POST['name']:''));
		$time_star=$this->un(addslashes(isset($_POST['timestar'])?$_POST['timestar']:''));
		if($time_star==''){$time_star='1899-01-01 00:00:00';}
		$time_end=$this->un(addslashes(isset($_POST['timeend'])?$_POST['timeend']:''));
		if($time_end==''){$time='';if($time_star!=='1899-01-01 00:00:00'){$time='>=\''.$time_star.'\'';}}else{$time=" between '".$time_star."' and '".$time_end."'";}
		$size=$this->un(addslashes(isset($_POST['size'])?$_POST['size']:''));
		$hou=$this->un(addslashes(isset($_POST['hou'])?$_POST['hou']:''));
		$xing=$this->un(addslashes(isset($_POST['xing'])?$_POST['xing']:''));
		if($xing){
			$this_xing=db()->query("select *  from sw_file_config where id in (".$xing.")");
			if(!$this_xing){
				//防止被变为空
				if($xing<1){}else{
					$xing='';
				}
			}
		}
		$zt=$this->un(addslashes(isset($_POST['zt'])?$_POST['zt']:''));
		$page=$this->un(addslashes(isset($_POST['page'])?$_POST['page']:'1'));
		$zt_sql='';$import_text_sql='';$name_sql='';$time_sql='';$size_sql='';$hou_sql='';$xing_sql='';
		//排序方式
		$ii=addslashes(isset($_POST['ii'])?$_POST['ii']:'id desc');
		if($ii=='id' || $ii=='id desc' || $ii=='file_size' || $ii=='file_size desc' || $ii=='file_time' || $ii=='file_time desc' || $ii=='ok' || $ii=='ok desc'){}else{$ii='id';}
		if(isset($_POST['a'])?$_POST['a']:''==1){$ii=$ii.' desc';}
		//权限
		$sql=$this->sql($vie,$no);
		//获取分页部分信息
		if($name || $time || $size || $hou || $xing || $zt!='' || $xing=='0'){
			//查询sql组合，想当年第一次做的我竟然把每种情况都写了sql，，
			if($name){
				if($sql_jq=='yes'){
					$name_sql=" and (file_name = '".$name."' or import_text = '".$name."' or id = '".$name."')";
				}else{
					$name_sql=" and (file_name like '%".$name."%' or import_text like '%".$name."%')";
				}
			}
			if($time){$time_sql=" and file_time ".$time;}
			if($size){$size_sql=" and file_size ".$size;}
			if($hou){$hou_sql=" and file_houzhui='".$hou."'";}
			if($zt!=''){$zt_sql=" and ok in(".$zt.")";}
			if($xing || $xing=='0'){
				$xing_sql=" and fenlei in (".$xing.")";
			}
			$file_all=db()->query("SELECT count(*) FROM sw_file_list where ".$sql." ".$zt_sql." ".$import_text_sql." ".$name_sql." ".$time_sql." ".$size_sql." ".$hou_sql." ".$xing_sql." order by ".$ii);
		}else{
			$file_all=db()->query("SELECT count(*) FROM sw_file_list where ".$sql." order by ".$ii);
		}
		$all_shumu=$file_all[0]['count(*)'];
		$page_size=\think\Config::get('page_size');
		//获取总分页数
		$zy = $all_shumu / $page_size;
		$zy = ceil($zy);
		//判断页码正确性
		if($page>$zy){$page=$zy;}
		if($page<=0){$page=1;}
		$star=$page*$page_size-$page_size;
		if($name || $time || $size || $hou || $xing || $zt!='' || $xing=='0'){
			$file=db()->query("SELECT * FROM sw_file_list where ".$sql." ".$zt_sql." ".$import_text_sql." ".$name_sql." ".$time_sql." ".$size_sql." ".$hou_sql." ".$xing_sql." order by ".$ii." limit ".$star.','.$page_size);
		}else{
			$file=db()->query("SELECT * FROM sw_file_list where ".$sql." order by ".$ii." limit ".$star.','.$page_size);
		}
		if(isset($this_xing)){
			foreach ($this_xing as $xin){
				foreach ($file as $key=>$f){
					if($xin['id']==$f['fenlei']){
						$file[$key]['fenlei_name']=$xin['name'];
					}
				}
			}
		}else{
			foreach ($file as $key=>$f){
					$file[$key]['fenlei_name']=$f['fenlei'];
			}
		}
		//分页
		$die=isset($_POST['die'])?$_POST['die']:'';
		$file=ajax_page($file,'&name='.$name.'&hou='.$hou.'&die='.$die.'&xing='.$xing.'&sql_jq='.$sql_jq.'&timestar='.$time_star.'&timeend='.$time_end.'&size='.$size.'&zt='.$zt.'&ii='.$ii,$page,$file_all[0]['count(*)']);
		if(isset($_POST['a'])?$_POST['a']:''==1){
			$file[]='icon-level-up';
		}else{$file[]='icon-level-down';}
		$file[]=$all_shumu;
		$file[]=$this->fenlei_bt($xing);
		echo json_encode($file);
	}
	//踩赞次数+1
	public function add1($name='',$vie='file',$no=''){
		//判断是踩赞还是下载预览
		if($name==''){
			$name=isset($_GET['name'])?$_GET['name']:'';
		}
		$id=isset($_GET['id'])?$_GET['id']:'';
		//获取该页权限
		if($vie=='up'){$no='look';}
		$sh=$this->sql($vie,$no);
		//判断是否有访问权限
		$file=db()->query("SELECT * FROM sw_file_list where ".$sh." and id='".$id."'");
		if(!$file){if($name=='look' || $name=='down_c'){echo '无权限';}else{echo json_encode('无权限');}exit;}
		//获取踩赞过的id
		$arr_1=explode(',',$file[0]['goodbad']);
		//log填词
		if($name=='good'){$name_i='赞';}else if($name=='bad'){$name_i='踩';}else if($name=='look'){$name_i='看';}else if($name=='down_c'){$name_i='下载';}
		//踩赞专享
		if($name=='good' || $name=='bad'){
			//防止重复踩赞时再次记录
			if(!in_array(get_user_id(),$arr_1)){
				//防止字段为空时出错
				if($file[0]['goodbad']){
					$page_info['goodbad']=array('exp','CONCAT("'.get_user_id().','.'",goodbad)');
				}else{
					$page_info['goodbad']=get_user_id().',';
				}
			}
		}
		//log填词
		if(in_array(get_user_id(),$arr_1)){if($name=='good'){$name_i='又赞';}else if($name=='bad'){$name_i='又踩';}}
		//log入库
		$page_info['log']=array('exp','CONCAT("'.get_user_nickname().'_'.date("Y-m-d H:i:s").$name_i.'了<br>'.'",log)');
		db('file_list')
		->where('id', $id)
		->update($page_info);
		//如果刷赞（踩）则鄙视一下（#滑稽）
		if($name=='good' || $name=='bad'){
			if(in_array(get_user_id(),$arr_1)){echo json_encode('刷赞可耻！！！');exit;}
		}
		//+1s
		db('file_list')
		->where('id', $id)
		->setInc($name);
	}
	//文件下载
	public function xz($vie='file'){
		$id=isset($_GET['id'])?$_GET['id']:'';
		if(!$id){echo json_encode(array(0,'缺少id'));exit;}
		//下载次数+1
		$this->add1('down_c',$vie);
		//判断是否有权限访问,因+1s时已经校验，也不清楚个人页面的要求什么的，也不准所以....
		$file=db()->query("SELECT * FROM sw_file_list where id='".$id."'");
		$file=$file[0];
		//返回命名
		$name=$file['file_name'];
		//下载
		xz($file['file_sc_name'].'.'.$file['file_houzhui'],$name,$file['url']);
	}
	//文件预览
	public function open_all($vie='file'){
		header("Content-type: text/html; charset=utf-8");
		$this->add1('look',$vie);
		$url=isset($_GET['url'])?$_GET['url']:'';
		//打开文件---先判断再操作
		if(!file_exists($url)){
			echo "<meta http-equiv='Content-Type'' content='text/html; charset=utf-8'>";
			echo "文件不存在";
			return ; //直接退出
		}
		$houzhui=isset($_GET['houzhui'])?$_GET['houzhui']:'';
		$ok_open=array('js','php','sql','mp4','mp3','html','HTML','txt','jpg','JPG','jpeg','gif','png','art','au','aiff','xbm','swf');
		if(!in_array($houzhui,$ok_open)){
			echo '暂不支持该文件预览';
			exit;
		}
		$file=$url; //旧目录
		$newFile='./open/'.get_user_nickname().'.'.$houzhui; //新目录
		if(!is_dir('./open/')){
			mkdir('./open/',777,true);
		}
		if(is_dir($newFile)){
			unlink($newFile);
		}
		if($_GET['houzhui']=='flv'){
			$cmd = 'FFMPEG  -i  '.$newFile.' -c:v libx264 -strict -2 ./open/'.$newFile.get_user_nickname().'.mp4';
			exec($cmd, $status);
		}else{
			copy($file,$newFile); //拷贝到新目录
		}
		$hou_all=array('mp4','flv');
		$arr_1=explode('/',$_SERVER['PHP_SELF']);
		if(in_array($_GET['houzhui'],$hou_all)){
			$this->assign('hou', $_GET['houzhui']);
			$this->assign('url', 'http://'.$_SERVER['HTTP_HOST'].'/'.$arr_1[1].'/public/open/'.get_user_nickname().'.'.$houzhui);
			return $this->fetch('up/open');
			exit;
		}
		echo "<script>self.location='http://".$_SERVER['HTTP_HOST'].'/'.$arr_1[1]."/public/".$newFile."';</script>";
	}
	//页眉目录
	public function fenlei_bt($xing){
		$urr='全部>';
		//如果有值则
		if($xing){	
			//分割为数组
			$arr_2=explode(',',$xing);
			//dump($arr_2);
			//判断数组是否为空，如果有值则说明点的为部门直接返回空
			if(isset($arr_2['1'])){$urr='';return $urr;}
			//根据某种规则来看下一级的id将永远比上一级的id大，，除非有人手动乱改
			
			$this_xing=db()->query("select *  from sw_file_config where id='".$xing."'");
			if(!$this_xing){return $urr;}
			$fenlei=db()->query("select *  from sw_file_config order by id desc");
			$urr=$this_xing['0']['name'].' > ';
			//根据上边所说的某种规则那么倒叙之后循环将会一级一级的遇到上级元素
			foreach ($fenlei as $f){
				if($this_xing['0']['parents_id']==$f['id']){
					$urr=$f['name'].' > '.$urr;
					$this_xing['0']['parents_id']=$f['parents_id'];
					$this_xing['0']['lv']=$f['lv'];
					$this_xing['0']['dep_id']=$f['dep_id'];
				}
			}
			//根据循环完毕后残留的dep_id获取其所属的部门
			if($this_xing['0']['parents_id']=='0' && $this_xing['0']['lv']=='2'){
				$dep=db()->query("select *  from sw_sys_dep where id='".$this_xing['0']['dep_id']."'");
				$urr=$dep['0']['en_name'].' > '.$urr;
			}
			
		}
		return $urr;
	}
	/**
	 * 
	 * @param string $sql 显示部门
	 * @param string $up_yes 上传
	 * @param string $suv 菜单显示数目
	 * @param string $inp_yes 单选框
	 * @param string $no_m 点击事件
	 * @return string 目录拉取
	 */
	public function menu_ajax_all($sql='where 1=1',$up_yes='',$suv='',$inp_yes='',$no_m='',$idd=''){
		$fenlei=db()->query("select * from sw_file_config");
		$lv_big=db()->query("select max(lv) from sw_file_config");
		$dep=db()->query("select * from sw_sys_dep ".$sql."");
		$ok='';
		foreach ($dep as $d){
			$fenlei[]=array('id'=>'','name'=>$d['en_name'],'parents_id'=>'0','dep_id'=>$d['id'],'lv'=>'1','pid'=>$d['pid']);
			if($d['en_name']=='Record'){
				$ok='ok';
			}
		}
		//特殊权限rbq
		if(in_array(get_cache_data('user_info',get_user_id(),'user_gh'),\think\Config::get('Record_user'))){
			if($ok==''){
				//特殊权限
				$fenlei[]=array('id'=>'','name'=>'Record','parents_id'=>'0','dep_id'=>999,'lv'=>'1','pid'=>$d['pid']);
			}
		}
		return $this->mulu($fenlei,$lv_big[0]['max(lv)'],$up_yes,$suv,$inp_yes,$no_m,$idd);
	}
	//目录数组构成
	public function mulu($fenlei,$all,$up_yes='',$suv='',$inp_yes='',$no_m='',$idd=''){
		if($all==''){$all=1;}
		//根据总级数循环防止因误操作出现真空地段
		for($i=$all;$i>=1;$i--){
			${'men_'.$i}=array();
		}
		//每一级分割为单独的一个数组
		foreach ($fenlei as $m){
			for($i=1;$i<=$all;$i++){
				if($m['lv']==$i){
					$m['b']=$m['id'];
					${'men_'.$i}[]=$m;
				}
			}
		}
		//根据层数循环
		for($i=$all;$i>1;$i--){
			$a=$i-1;
			//遍历最后一级数组
			foreach (${'men_'.$a} as $key=>${'m_'.$a}){
				//遍历上一级
				foreach (${'men_'.$i} as ${'m_'.$i}){
					//判断是否到达第二级，以方便判断对应关系
					if($i==2){
						$c=${'m_'.$a}['dep_id'];
						$d=${'m_'.$i}['dep_id'];
					}else{
						$c=${'m_'.$a}['id'];
						$d=${'m_'.$i}['parents_id'];
					}
					if ($c==$d){
						${'men_'.$a}[$key][]=${'m_'.$i};
						//组合每一级的子id
						if(${'men_'.$a}[$key]['b']==''){
							${'men_'.$a}[$key]['b']=${'m_'.$i}['b'];
						}else{
							${'men_'.$a}[$key]['b']=${'men_'.$a}[$key]['b'].','.${'m_'.$i}['b'];
						}
					}
				}
			}
		}
		//返回完美树状数组
		return $this->new_menu_go($men_1,$up_yes,$suv,$inp_yes,$no_m,$idd);
	}
	//目录内容拼接
	public function new_menu_go($men_1,$up_yes='',$suv='',$inp_yes='',$no_m='',$idd){
		$menu_ok='';$up_div='';$new_div='';$add_div='';$del_div='';$b_all='';$url='';$inp='';$del_div_all='';$qx_div='';
		foreach ($men_1 as $key=>$m_1){
			if (is_array($m_1)){
				if($no_m){}else{
					$b_all='wtq_b_all_id="'.$m_1['b'].'" wtq_b_id="'.$m_1['id'].'"';
					if($m_1['lv']=='1'){
						$on='b';
					}else{
						$on='id';
					}
					//改变传过去的分类id
					if($m_1['lv']==1){
						$url='onclick="$(\'div[wtq_a=wtq_a]\').css(\'background-color\',\'\');$(this).parent(\'div[wtq_a=wtq_a]\').css(\'background-color\',\'gainsboro\');$(\'span[wtq_zs_id=zs]\').html(\''.$m_1['name'].' > \');$(\'input[wtq_up_index_name=xin]\').val(\'0.'.$m_1['dep_id'].','.$m_1['b']/*$m_1[$on]*/.'\');event.stopPropagation();ajax_index(\'?xing=0.'.$m_1['dep_id']/*$m_1[$on]*/.'\',this);"';
					}else{
						$url='onclick="$(\'div[wtq_a=wtq_a]\').css(\'background-color\',\'\');$(this).parent(\'div[wtq_a=wtq_a]\').css(\'background-color\',\'gainsboro\');$(\'span[wtq_zs_id=zs]\').html(\''.$m_1['name'].' > \');$(\'input[wtq_up_index_name=xin]\').val(\''.$m_1[$on].'\');event.stopPropagation();ajax_index(\'?xing='.$m_1[$on].'\',this);"';
					}
					
				}
				if($inp_yes){
					if($m_1['lv']==0){
						$inp='';
					}else{
						if($m_1['id']==$idd){$a='checked="checked"';}else{$a='';}
						$inp='<input name="ml" type="radio" '.$a.' value="'.$m_1['id'].'">';
						if(isset($_GET['diversion'])){
							$inp='<input parents_id="'.$m_1['id'].'" dep_id="'.$m_1['dep_id'].'" lv="'.$m_1['lv'].'" name="ml" type="radio" value="">';
						}
					}
					//定位到根目录
					$linshi_dep=isset($_GET['dep_id'])?$_GET['dep_id']:'';
					$wtq_gen_id=isset($_GET['id'])?$_GET['id']:'';
					if($wtq_gen_id=='sb' && $m_1['lv']==1 && $m_1['dep_id']==$linshi_dep){
						$inp='<input name="ml" type="radio" checked="checked" value="0.'.$linshi_dep.'">';
					}
					$idd_bad='op="open"';
				}else{
					$idd_bad='style="display:none" op="close"';
				}
				if($m_1['b']){
					if($suv){
						$sql=$this->sql($suv);
						//echo $sql.'<br>';
					}else{
						$sql='1=1';
					}
					$all_shu=db()->query("SELECT count(*) FROM sw_file_list where ".$sql." and fenlei in (".$m_1['b'].")");
					$all_shu=$all_shu[0]['count(*)'];
				}else{$all_shu='0';}
				if($all_shu=='0' && $suv=='file'){
					$menu_ok=$menu_ok;
					//自调用
					$menu_ok=$menu_ok.$this->new_menu_go($m_1,$up_yes,$suv,$inp_yes,$no_m,$idd);
					$menu_ok=$menu_ok;
				}else{
					
					if($up_yes){
						if($m_1['lv']=='0'){
							$id='b'.$m_1['dep_id'];
						}else{
							$id=$m_1['id'];$arr_1=explode('/',$_SERVER['PHP_SELF']);
							//强制定义id以拿来传文件
							if($m_1['lv']=='1'){
								$id='b'.$m_1['dep_id'];
								$m_1['id']='sb&dep_id='.$m_1['dep_id'];
							}
							if($up_yes=='up'){
								$up_onclick='<div onclick="return '.$up_yes.'(\''.$m_1['id'].'\')" class="tree-folder-name">上传</div>';
								$up_onclick='<div title="上传" class="tree-folder-name layerIframe" url="'.url('/file/one_up').'/change_ajax?id='.$m_1['id'].'" hig="740px" wid="620px">上传</div>';
							}else{
								$up_onclick='<div title="上传" class="tree-folder-name layerIframe" url="'.url('/file').'/'.$up_yes.'?id='.$m_1['id'].'" hig="90%" wid="70%">上传</div>';
							}
							$up_div='<div class="tree-folder">
											<div class="tree-folder-header">
												<i class="icon-cloud-upload" style="color: #31e30d"></i>
											'.$up_onclick.'
												
										</div>
									</div>';
							if($m_1['lv']>1){
								$qx_div='<div class="tree-folder">
											<div class="tree-folder-header" style="white-space: nowrap;">
												<i class="red icon-eye-close"></i>
		
												<div class="tree-folder-name" onclick="folder_qx(\''.$m_1['id'].'\')">权限设置</div>
										</div>
									</div>';
							}
							if($all_shu==0 && $m_1['lv']>1){
								$del_div='<div class="tree-folder">
											<div class="tree-folder-header">
												<i class="icon-remove" style="color: red"></i>
							
												<div class="tree-folder-name" onclick="return del_this_fenlei(\''.$m_1['id'].'\')">删除</div>
										</div>
									</div>';
							}
							//彻底删除
							if(in_array(get_cache_data('user_info',get_user_id(),'user_gh'),\think\Config::get('del_super')) && $m_1['lv']>1){
								$del_div_all='<div class="tree-folder">
											<div class="tree-folder-header">
												<i class="icon-remove" style="color: red"></i>
							
												<div class="tree-folder-name" onclick="return del_this_fenlei(\''.$m_1['id'].'\',\'super\')">超级删除</div>
										</div>
									</div>';
								//乾坤大挪移
								$del_div_all=$del_div_all.'<div class="tree-folder">
											<div class="tree-folder-header">
												<i class="icon-bolt"></i>
								
												<div class="tree-folder-name" onclick="return diversion(\''.$m_1['id'].'\',\''.$m_1['name'].'\')">乾坤大挪移</div>
										</div>
									</div>';
							}
						}
						if($m_1['lv']<10){
							$new_div='<div class="tree-folder">
											<div class="tree-folder-header" style="white-space: nowrap;">
												<i class="icon-plus" style="color: #004FFF;"></i>
			
												<div class="tree-folder-name" add="no" onclick="if($(this).attr(\'add\')==\'no\'){$(\'div[wtq_bs_id='.$id.']\').css(\'display\',\'\');$(this).prev(\'i\').attr(\'class\',\'icon-minus\');$(this).html(\'取消\');$(this).attr(\'add\',\'yes\');}else{$(\'div[wtq_bs_id='.$id.']\').css(\'display\',\'none\');$(this).prev(\'i\').attr(\'class\',\'icon-plus\');$(this).html(\'新建文件夹\');$(this).attr(\'add\',\'no\');}">新建文件夹</div>
										</div>
									</div>';
							if($m_1['lv']=='1'){
								$m_1['id']='';
							}
							$add_div='<div class="tree-folder" style="display:none" wtq_bs_id="'.$id.'">
											<div class="tree-folder-header" style="white-space: nowrap;">
												<i class="red icon-folder-close"></i>
			
												<div class="tree-folder-name" onclick=""><input value="new" style="width:50px;" wtq_new_id="'.$id.'"><button onclick="fenlei_add(\''.$m_1['id'].'\',\''.$m_1['dep_id'].'\',\''.$m_1['lv'].'\')" class="btn btn-minier">确定</button></div>
										</div>
									</div>';
						}
					}
					$menu_ok=$menu_ok.'<div wtq_del_id="'.$m_1['id'].'" class="tree-folder">
									<div onmouseenter="if($(this).next(\'div\').html()){$(this).children(\'span\').css(\'display\',\'\');}else{$(this).attr(\'onclick\',\'$(this).children(`div`).trigger(`click`)\')}" onmouseleave="$(this).children(\'span\').css(\'display\',\'none\');" title="'.$m_1['name'].'" style="white-space: nowrap;" wtq_a="wtq_a" class="tree-folder-header" onclick="var a=$(this).next(\'div\');if(a.attr(\'op\')==\'close\'){a.css(\'display\',\'\');a.attr(\'op\',\'open\');$(this).children(\'i\').attr(\'class\',\'icon-folder-open\');/*$(this).css(\'background-color\',\'gainsboro\');*/}else{/*$(this).css(\'background-color\',\'\');*/a.css(\'display\',\'none\');a.attr(\'op\',\'close\');$(this).children(\'i\').attr(\'class\',\'icon-folder-close\');}">
										'.$inp.'
										<span style="float: right;display:none;">展开</span>
										<i class="icon-folder-close" style="color: #F8C600;"></i>
					
										<div '.$b_all.' '.$url.' class="tree-folder-name">(<span>'.$all_shu.'</span>)'.$m_1['name'].'</div>
									</div>
							<div class="tree-folder-content" '.$idd_bad.'>';
					//组合
					$menu_ok=$menu_ok.$new_div.$up_div.$add_div.$del_div.$del_div_all.$qx_div;
					//自调用
					$menu_ok=$menu_ok.$this->new_menu_go($m_1,$up_yes,$suv,$inp_yes,$no_m,$idd);
					$menu_ok=$menu_ok.'</div></div>';
				}
			}
		}
		return $menu_ok;
	}
}