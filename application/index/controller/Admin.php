<?php
namespace  app\index\controller;

use think\Controller;
use think\Session;
use think\Db;

class Admin extends Controller{	

	/**
	 * 后台控制器初始化
	 */
	protected function _initialize(){
		
		// 获取当前用户ID
		define('UID',is_login());
		if( !UID ){// 还没登录 跳转到登录页面
			$this->redirect('index/Login/login');
		}
		
		/*
		// 是否是超级管理员
		define('IS_ROOT',   is_administrator());
		if(!IS_ROOT){			
			$this->error('403:禁止访问');
		}
		
		// 检测访问权限
		$access =   $this->accessControl();
		if ( $access === false ) {
			$this->error('403:禁止访问');
		}elseif( $access === null ){
			$dynamic        =   $this->checkDynamic();//检测分类栏目有关的各项动态权限
			if( $dynamic === null ){
				//检测非动态权限
				$rule  = strtolower(MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME);
				if ( !$this->checkRule($rule,array('in','1,2')) ){
					$this->error('未授权访问!');
				}
			}elseif( $dynamic === false ){
				$this->error('未授权访问!');
			}
		}
		$this->assign('__MENU__', $this->getMenus());
		*/
		
		//常用数据缓存
		cache_data('config');
		cache_data('dep');
		cache_data('site');
		cache_data('user');
		cache_data('hr_role');
		
		//缓存费用报销类型数据
		$cost_type = Db::name('sys_cost_type')->select();
		cache('cost_type_info',$cost_type);


//		//缓存所有的用户信息信息以及部门信息
//		$sys_user = Db::name('sys_user')->field('id,nickname')->select();
//		cache('user_data',$sys_user);
//		$sys_dep = Db::name('sys_dep')->field('id,en_name')->select();
//		cache('dep_data',$sys_dep);


		//定时抓取考勤数据
		//get_card_hr_rec('sh');
		//get_card_hr_rec('xa');
		//get_card_hr_rec('sz');
		
		//计算考勤数据


		//获得超级管理员唯一标识
		$login_id = Session::get('login_id');
		$admin_id = config('ADMIN_ID');
		if($admin_id != $login_id){
			/*获得当前模块、控制器、方法*/
			$request = \think\Request::instance();
			$module = $request->module();
			$controller = $request->controller();
			$action = $request->action();
			$requestUrl['module_name'] = strtolower($module);
			$requestUrl['controller_name'] = strtolower(preg_replace('/_/','',$controller));
			$requestUrl['action_name'] = strtolower(preg_replace('/_/','',$action));

			/*取出所有的权限*/
			$check = Session::get('RBAC');
			$arr = array();
			foreach($check as $key => $val){
				$arr1['module_name'] = strtolower(preg_replace('/_/','',$val['module_name']));
				$arr1['controller_name'] = strtolower(preg_replace('/_/','',$val['controller_name']));
				$arr1['action_name'] = strtolower(preg_replace('/_/','',$val['action_name']));
				$arr[] = $arr1;
			}

			//找出个人模块权限
			$person_auth = session('person_auth');
			$check_arr = [];
			foreach($person_auth as $key1 => $val1)
			{
				$temp['module_name'] = strtolower(preg_replace('/_/','',$val1['module_name']));
				$temp['controller_name'] = strtolower(preg_replace('/_/','',$val1['controller_name']));
				$temp['action_name'] = strtolower(preg_replace('/_/','',$val1['action_name']));
				if(!in_array($temp,$arr)){
					$check_arr[] = $temp;
				}



			}
			$array = array_merge($arr,$check_arr);

//			var_dump($array);die;



			/*验证权限*/
			if(!in_array($requestUrl,$array)){
				echo setServerBackJson(0,"无权访问");exit;
			}
		}

		/*取出左边菜单栏*/
//		$pri = Session::get('Pri');
//		$request = \think\Request::instance();
//		$controller = $request->controller();
//		$controller = strtolower($controller);
//		$this->assign('btn', $pri);
//		$this->assign('controller',$controller);
		
		//获取request信息
		$this->requestInfo();
		
		$this->user_notice();
		if(isset($_GET['msg_no']) || isset($_GET['?msg_no'])){
			session('wtqrbq','2');
		}
	}





	public function user_notice(){
		$msg=db()->query("select * from sw_sys_msg where user_id='".get_user_id()."' and view_flag='0' order by id desc");
		if($msg){
			if(session('wtqrbq')=='' || session('wtqrbq')=='2'){
				session('wtqrbq','1');
			}
		}else{
			if(session('wtqrbq')==1){
				session('wtqrbq','2');
			}
		}
		$this->assign('msg_cou', count($msg));
		$type_flag=Config('wtq_user_msg');
		$new_msg='';
		foreach ($msg as $m){
			if($m['action_url']==''){
				$new_msg[$type_flag[$m['type_flag']]][$m['id']]=array('id'=>$m['id'],'url'=>url('user/UserMsg/index'),'name'=>'<a href="'.url('user/UserMsg/index').'?msg_no=1"><li style="color: black;overflow: hidden;text-overflow: ellipsis;max-width: 250px;white-space: nowrap;">'.$m['msg_tit'].'</li></a>','time'=>$m['create_time'],'msg_desc'=>json_decode($m['msg_desc']));
			}else{
				$new_msg[$type_flag[$m['type_flag']]][$m['id']]=array('id'=>$m['id'],'url'=>$m['action_url'],'name'=>'<a onclick="msg_ok('.$m['id'].',0)" href="'.$m['action_url'].'?msg_no=1"><li style="color: black;overflow: hidden;text-overflow: ellipsis;max-width: 250px;white-space: nowrap;">'.$m['msg_tit'].'</li></a>','time'=>$m['create_time'],'msg_desc'=>json_decode($m['msg_desc']));
			}
			
		}
		//dump($new_msg);
		$this->assign('msg', $new_msg);
	}
	/**
	 * 检测是否是需要动态判断的权限
	 * @return boolean|null
	 *      返回true则表示当前访问有权限
	 *      返回false则表示当前访问无权限
	 *      返回null，则会进入checkRule根据节点授权判断权限
	 *
	 * @author 朱亚杰  <xcoolcc@gmail.com>
	 */
	protected function checkDynamic(){
		if(IS_ROOT){
			return true;//管理员允许访问任何页面
		}
		return null;//不明,需checkRule
	}
	
	
	/**
	 * action访问控制,在 **登陆成功** 后执行的第一项权限检测任务
	 *
	 * @return boolean|null  返回值必须使用 `===` 进行判断
	 *
	 *   返回 **false**, 不允许任何人访问(超管除外)
	 *   返回 **true**, 允许任何管理员访问,无需执行节点权限检测
	 *   返回 **null**, 需要继续执行节点权限检测决定是否允许访问
	 * @author 朱亚杰  <xcoolcc@gmail.com>
	 */
	final protected function accessControl(){
		if(IS_ROOT){
			return true;//管理员允许访问任何页面
		}
		/*
		$allow = C('ALLOW_VISIT');
		$deny  = C('DENY_VISIT');
		$check = strtolower(CONTROLLER_NAME.'/'.ACTION_NAME);
		if ( !empty($deny)  && in_array_case($check,$deny) ) {
			return false;//非超管禁止访问deny中的方法
		}
		if ( !empty($allow) && in_array_case($check,$allow) ) {
			return true;
		}
		
		return null;//需要检测节点权限
		*/
	}


	/**
	 * 获取控制器菜单数组,二级菜单元素位于一级菜单的'_child'元素中
	 * @author 朱亚杰  <xcoolcc@gmail.com>
	 */
	//final public function getMenus($controller=CONTROLLER_NAME){
	final public function getMenus($controller=null){	
		// $menus  =   session('ADMIN_MENU_LIST'.$controller);
		$menus=null;		
		if(empty($menus)){
			/*
			// 获取主菜单
			$where['pid']   =   0;
			$where['hide']  =   0;
			if(!C('DEVELOP_MODE')){ // 是否开发者模式
				$where['is_dev']    =   0;
			}
			$menus['main']  =   M('Menu')->where($where)->order('sort asc')->select();
	
			$menus['child'] = array(); //设置子节点
	
			//高亮主菜单
			$current = M('Menu')->where("url like '%{$controller}/".ACTION_NAME."%'")->field('id')->find();
			if($current){
				$nav = D('Menu')->getPath($current['id']);
				$nav_first_title = $nav[0]['title'];
	
				foreach ($menus['main'] as $key => $item) {
					if (!is_array($item) || empty($item['title']) || empty($item['url']) ) {
						$this->error('控制器基类$menus属性元素配置有误');
					}
					if( stripos($item['url'],MODULE_NAME)!==0 ){
						$item['url'] = MODULE_NAME.'/'.$item['url'];
					}
					// 判断主菜单权限
					if ( !IS_ROOT && !$this->checkRule($item['url'],AuthRuleModel::RULE_MAIN,null) ) {
						unset($menus['main'][$key]);
						continue;//继续循环
					}
	
					// 获取当前主菜单的子菜单项
					if($item['title'] == $nav_first_title){
						$menus['main'][$key]['class']='current';
						//生成child树
						$groups = M('Menu')->where("pid = {$item['id']}")->distinct(true)->field("`group`")->select();
						if($groups){
							$groups = array_column($groups, 'group');
						}else{
							$groups =   array();
						}
	
						//获取二级分类的合法url
						$where          =   array();
						$where['pid']   =   $item['id'];
						$where['hide']  =   0;
						if(!C('DEVELOP_MODE')){ // 是否开发者模式
							$where['is_dev']    =   0;
						}
						$second_urls = M('Menu')->where($where)->getField('id,url');
	
						if(!IS_ROOT){
							// 检测菜单权限
							$to_check_urls = array();
							foreach ($second_urls as $key=>$to_check_url) {
								if( stripos($to_check_url,MODULE_NAME)!==0 ){
									$rule = MODULE_NAME.'/'.$to_check_url;
								}else{
									$rule = $to_check_url;
								}
								if($this->checkRule($rule, AuthRuleModel::RULE_URL,null))
									$to_check_urls[] = $to_check_url;
							}
						}
						// 按照分组生成子菜单树
						foreach ($groups as $g) {
							$map = array('group'=>$g);
							if(isset($to_check_urls)){
								if(empty($to_check_urls)){
									// 没有任何权限
									continue;
								}else{
									$map['url'] = array('in', $to_check_urls);
								}
							}
							$map['pid'] =   $item['id'];
							$map['hide']    =   0;
							if(!C('DEVELOP_MODE')){ // 是否开发者模式
								$map['is_dev']  =   0;
							}
							$menuList = M('Menu')->where($map)->field('id,pid,title,url,tip')->order('sort asc')->select();
							$menus['child'][$g] = list_to_tree($menuList, 'id', 'pid', 'operater', $item['id']);
						}
						if($menus['child'] === array()){
							//$this->error('主菜单下缺少子菜单，请去系统=》后台菜单管理里添加');
						}
					}
				}
			}
			// session('ADMIN_MENU_LIST'.$controller,$menus);
			 */
		}
		return $menus;
	}	
	
	public function index() {
		$page_info=array();
		//列表过滤器，生成查询Map对象
		$map = $this->_search ();
		if (method_exists ( $this, '_filter' )) {
			$this->_filter ( $map );
		}
		
		$name= MODULE_NAME.CONTROLLER_NAME;
		$model = db($name);
		
		if (! empty ( $model )) {
			//$page_info['list']=$model->where($map)->order('id desc')->paginate($paginate['list_rows']);
			$page_info['list']=$this->_list($model,'','site',true);
		}
		$page_info['page']=$page_info['list']->render();
		
		$this->assign('page_info',$page_info);
		return $this->fetch();
	}

	/**
	 +----------------------------------------------------------
	 * 根据表单生成查询条件
	 * 进行列表过滤
	 +----------------------------------------------------------
	 * @access protected
	 +----------------------------------------------------------
	 * @param string $name 数据对象名称
	 +----------------------------------------------------------
	 * @return HashMap
	 +----------------------------------------------------------
	 * @throws ThinkExecption
	 +----------------------------------------------------------
	 */
	protected function _search($name = '') {
		//生成查询条件
		if (empty ( $name )) {
			$name = MODULE_NAME.CONTROLLER_NAME;
		}
		//$name = $this->getActionName();
		$model = db( $name );
		$map = array ();
		foreach ( $model->getTableInfo('','fields') as $key => $val ) {
			if (isset ( $_REQUEST [$val] ) && $_REQUEST [$val] != '') {
				$map [$val] = $_REQUEST [$val];
			}
		}
		return $map;
	}
	
	public function add(){
		return $this->fetch();
	}
	
	public function edit(){
		return $this->fetch();
	}

	/**
	 +----------------------------------------------------------
	 * 根据提交修改status值
	 +----------------------------------------------------------
	 * @param string id 数据对象名称
	 +----------------------------------------------------------
	 * @return string
	 +----------------------------------------------------------
	 */
	public function changeStatus($id) {
		$name = MODULE_NAME.CONTROLLER_NAME;
		db($name)->where('id='.$id)->setField('status',0);
		return setServerBackJson(1,'状态修改成功','index');
	}	
	

	/**
	 +----------------------------------------------------------
	 * 根据表单生成查询条件
	 * 进行列表过滤
	 +----------------------------------------------------------
	 * @access protected
	 +----------------------------------------------------------
	 * @param Model $model 数据对象
	 * @param HashMap $map 过滤条件
	 * @param string $sortBy 排序
	 * @param boolean $asc 是否正序
	 +----------------------------------------------------------
	 * @return void
	 +----------------------------------------------------------
	 * @throws ThinkExecption
	 +----------------------------------------------------------
	 */
	protected function _list($model='', $map=array(), $sortBy = '', $asc = false) {
		/*
		 * 可扩充项,自动接收页面传递过来的表头排序字段进行排序操作
		 */
		
		//排序字段 默认为主键名
		$order = ! empty ( $sortBy ) ? $sortBy : $model->getTableInfo('','pk');
		
		//排序方式默认按照倒序排列
		$sort = $asc ? 'asc' : 'desc';
		
		if(isset($map['exp'])){
			$sql_exp=$map['exp'];
			$sql_exp_logic=$map['exp_logic'];
			unset($map['exp']);
			unset($map['exp_logic']);
			if($sql_exp_logic=='and'){
				return $model->where($map)->where($sql_exp)->order("`" . $order ."` " .' '. $sort)->paginate(config('paginate')['list_rows'],false,['query' => request()->param()]);
			}else{
				return $model->where($map)->whereOr($sql_exp)->order("`" . $order ."` " .' '. $sort)->paginate(config('paginate')['list_rows'],false,['query' => request()->param()]);
			}
			
		}else{
			return $model->where($map)->order("`" . $order ."` " .' '. $sort)->paginate(config('paginate')['list_rows'],false,['query' => request()->param()]);
		}
		
		
		
		/*
		//排序字段 默认为主键名
		if (!empty ( $_REQUEST ['_order'] )) {
			$order = $_REQUEST ['_order'];
		} else {
			$order = ! empty ( $sortBy ) ? $sortBy : $model->getPk ();
		}
		//排序方式默认按照倒序排列
		//接受 sost参数 0 表示倒序 非0都 表示正序
		if (isset ( $_REQUEST ['_sort'] )) {
			//			$sort = $_REQUEST ['_sort'] ? 'asc' : 'desc';
			$sort = $_REQUEST ['_sort'] == 'asc' ? 'asc' : 'desc'; //zhanghuihua@msn.com
		} else {
			$sort = $asc ? 'asc' : 'desc';
		}
		//取得满足条件的记录数
		if(strlen($condition)>0){
			$count = $model->where ( $map )->where($condition)->count('id');
		}else{
			$count = $model->where ( $map )->count ( 'id' );
		}
	
		if ($count > 0) {
			import ( "@.ORG.Util.Page" );
			//创建分页对象
			if (! empty ( $_REQUEST ['listRows'] )) {
				$listRows = $_REQUEST ['listRows'];
			} else {
				$listRows = '';
			}
			$p = new Page ( $count, $listRows ,$parameter=$condition);
			//分页查询数据
			if(strlen($condition)>0){
				$voList = $model->where($map)->where($condition)->order( "`" . $order . "` " . $sort)->limit($p->firstRow . ',' . $p->listRows)->select ( );
			}else{
				$voList = $model->where($map)->order( "`" . $order . "` " . $sort)->limit($p->firstRow . ',' . $p->listRows)->select ( );
			}
				
			//分页跳转的时候保证查询条件
			foreach ( $map as $key => $val ) {
				if (! is_array ( $val )) {
					$p->parameter .= "$key=" . urlencode ( $val ) . "&";
				}
			}
			//分页显示
			$page = $p->show ();
			//列表排序显示
			$sortImg = $sort; //排序图标
			$sortAlt = $sort == 'desc' ? '升序排列' : '倒序排列'; //排序提示
			$sort = $sort == 'desc' ? 1 : 0; //排序方式
			//模板赋值显示
			$this->assign ( 'list', $voList );
			$this->assign ( 'sort', $sort );
			$this->assign ( 'order', $order );
			$this->assign ( 'sortImg', $sortImg );
			$this->assign ( 'sortType', $sortAlt );
			$this->assign ( "page", $page );
		}
	
		//zhanghuihua@msn.com
		$this->assign ( 'totalCount', $count );
		$this->assign ( 'numPerPage', $p->listRows );
		$this->assign ( 'currentPage', !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1);
			
		Cookie::set ( '_currentUrl_', __SELF__ );
		return;
		*/
	}
	
	//request信息
	protected function requestInfo() {
        $navlist = Session::get('Pri');
		$request = \think\Request::instance();
		$controller = $request->controller();
		$controller = strtolower($controller);

		$this->param = $this->request->param();		
		$this->url = strtolower($this->request->module() . '/' . $this->request->controller() . '/' . $this->request->action());
		defined('MODULE_NAME') or define('MODULE_NAME', $this->request->module());
		defined('CONTROLLER_NAME') or define('CONTROLLER_NAME', $this->request->controller());
		defined('ACTION_NAME') or define('ACTION_NAME', $this->request->action());
		defined('IS_POST') or define('IS_POST', $this->request->isPost());
		defined('IS_GET') or define('IS_GET', $this->request->isGet());
		defined('C_YEAR') or define('C_YEAR', date('Y',time()));
		defined('C_MONTH') or define('C_MONTH', date('n',time()));
		defined('C_DAY') or define('C_DAY', date('Y-m-d',time()));
		defined('RBAC_FLAG') or define('RBAC_FLAG',get_current_url());
		defined('PRI_ARR') or define('PRI_ARR',get_session_privilege());


		$this->assign('controller',$controller);
		$this->assign('navlist',$navlist);
		$this->assign('request', $this->request);
		$this->assign('param', $this->param);
	}	
	
	protected function setMeta($title = '') {
		$this->assign('meta_title', $title);
	}
}
