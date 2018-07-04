<?php
function ajax_page($user,$hou,$page,$user_all,$page_size=''){
	$page_num='';
	//获取所有用户
	//$user_all=count($user);
	//获取每页显示数量
	if($page_size==''){
		$page_size=\think\Config::get('page_size');
	}
	//获取总分页数
	$zy = $user_all / $page_size;
	$zy = ceil($zy);
	//判断页码正确性
	if($page>$zy){$page=$zy;}
	if($page<=0){$page=1;}
	//如果超过一页拼接底部分页
	if($zy>1){
		$s=$page-1;
		$class='';
		if($s<=0){$class='disabled';}
		$page_num='<ul class="pagination"><li class="'.$class.'"><a href="javascript:void(expression)" onclick="page(\'?page='.$s.$hou.'\')">&laquo;</a></li>';
		$class='';
		for ($i = 1; $i <= $zy; $i++) {
			if($i==$page){$class='active';}
			$page_num=$page_num.'<li class="'.$class.'"><a href="javascript:void(expression)" onclick="page(\'?page='.$i.$hou.'\')">'.$i.'</a></li>';
			$class='';
		}
		$x=$page+1;
		$class='';
		if($x>$zy){$class='disabled';}
		$page_num=$page_num.'<li class="'.$class.'"><a href="javascript:void(expression)" onclick="page(\'?page='.$x.$hou.'\')">&raquo;</a></li></ul>';
		//$star=$page*$page_size-$page_size;
		//$user = array_slice($user, $star, $page_size);
	}else{$page_num='';}
	$all=array($page_num,$user);
	return $all;
}

//上传
function save($user_path,$name){
	//1.接收提交文件的用户
	$username='www';//$_POST['username'];
	$fileintro='ppp';//$_POST['fileintro'];
	 
	//我们这里需要使用到 $_FILES
	/*echo "<pre>";
	 print_r($_FILES);
	 echo "</pre>";*/
	 
	//其实我们在上传文件时，点击上传后，数据由http协议先发送到apache服务器那边，这里apache服务器已经将上传的文件存放到了服务器下的C:\windows\Temp目录下了。这时我们只需转存到我们需要存放的目录即可。
	 
	//php中自身对上传的文件大小存在限制默认为2M
	 
	//获取文件的大小
	$file_size=$_FILES['file']['size'];
	if($file_size>200*1024*1024) {
		echo "文件过大，不能上传大于200M的文件";
		exit();
	}
	 
	$file_type=$_FILES['file']['type'];
	//echo $file_type;
	//if($file_type!="image/jpeg" && $file_type!='image/pjpeg') {
	//echo "文件类型只能为jpg格式";
	//exit();
	//}
	 
	 
	//判断是否上传成功（是否使用post方式上传）
	if(is_uploaded_file($_FILES['file']['tmp_name'])) {
		//把文件转存到你希望的目录（不要使用copy函数）
		$uploaded_file=$_FILES['file']['tmp_name'];
		 
		//我们给每个用户动态的创建一个文件夹
		 
		//判断该用户文件夹是否已经有这个文件夹
		//if(!file_exists($user_path)) {
		//mkdir($user_path);
		//}
		if(!is_dir($user_path)){
			//mkdir($user_path,777,true);
			mkdir($user_path);
			//chmod($user_path,777);
		}
		//$move_to_file=$user_path."/".$_FILES['myfile']['name'];
		$file_true_name=$_FILES['file']['name'];
		$arr=explode('.',$file_true_name);
		if(!isset($arr[1])){
			$file_true_name='';
		}
		$move_to_file=$user_path."/".$name.substr($file_true_name,strrpos($file_true_name,"."));
		//echo "$uploaded_file   $move_to_file";
		if(move_uploaded_file($uploaded_file,iconv("utf-8","gb2312",$move_to_file))) {
			return $_FILES['file']['name']."上传成功";
		} else {
			return json_encode("上传失败");
		}
	} else {
		return json_encode("上传失败");
	}
}
//下载
function xz($file_name,$name,$url){
	$file_name=$file_name;  //出现中文 程序无法完成下载 提示：文件不存在
	//使用绝对路径
	$file_path=$url.$file_name;

	//打开文件---先判断再操作
	if(!file_exists($file_path)){
		echo "<meta http-equiv='Content-Type'' content='text/html; charset=utf-8'>";
		echo "文件不存在";
		return ; //直接退出
	}

	//存在--打开文件

	$fp=fopen($file_path,"r");

	//获取文件大小
	$file_size=filesize($file_path);

	//http 下载需要的响应头
	header("Content-type: application/octet-stream"); //返回的文件
	header("Accept-Ranges: bytes");   //按照字节大小返回
	header("Accept-Length: $file_size"); //返回文件大小
	header("Content-Disposition: attachment; filename=\"".$name."\"");//这里客户端的弹出对话框，对应的文件名

	//向客户端返回数据
	//设置大小输出
	$buffer=1024;

	//为了下载安全，我们最好做一个文件字节读取计数器
	$file_count=0;
	//判断文件指针是否到了文件结束的位置(读取文件是否结束)
	while(!feof($fp) && ($file_size-$file_count)>0){

		$file_data=fread($fp,$buffer);
		//统计读取多少个字节数
		$file_count+=$buffer;
		//把部分数据返回给浏览器
		echo $file_data;
	}
	//关闭文件

	fclose($fp);exit;
}
