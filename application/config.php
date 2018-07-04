<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
return [
	'user_img_url'=>'head_img/',
	'user_img_type'=>array('png','jpg','jpeg','gif','bmp'),
    // +----------------------------------------------------------------------
    // | 应用设置
    // +----------------------------------------------------------------------

    // 应用命名空间
    'app_namespace'          => 'app',
    // 应用调试模式
    'app_debug'              => true,
    // 应用Trace
    'app_trace'              => false,
    // 应用模式状态
    'app_status'             => '',
    // 是否支持多模块
    'app_multi_module'       => true,
    // 入口自动绑定模块
    'auto_bind_module'       => false,
    // 注册的根命名空间
    'root_namespace'         => [],
    // 扩展配置文件
    'extra_config_list'      => ['database', 'validate'],

    // 扩展函数文件
    /*
    'extra_file_list'        => [
					    			THINK_PATH . 'helper' . EXT,
					    			APP_PATH.'Common/common'.EXT,
					    			APP_PATH.'Common/admin'.EXT
    							],
    							*/
    // 默认输出类型
    'default_return_type'    => 'html',
    // 默认AJAX 数据返回格式,可选json xml ...
    'default_ajax_return'    => 'json',
    // 默认JSONP格式返回的处理方法
    'default_jsonp_handler'  => 'jsonpReturn',
    // 默认JSONP处理方法
    'var_jsonp_handler'      => 'callback',
    // 默认时区
    'default_timezone'       => 'PRC',
    // 是否开启多语言
    'lang_switch_on'         => false,
    // 默认全局过滤方法 用逗号分隔多个
    'default_filter'         => 'trim',
    // 默认语言
    'default_lang'           => 'zh-cn',
    // 应用类库后缀
    'class_suffix'           => false,
    // 控制器类后缀
    'controller_suffix'      => false,

    // +----------------------------------------------------------------------
    // | 模块设置
    // +----------------------------------------------------------------------

    // 默认模块名
    'default_module'         => 'index',
    // 禁止访问模块
    'deny_module_list'       => ['common'],
    // 默认控制器名
    'default_controller'     => 'Index',
    // 默认操作名
    'default_action'         => 'index',
    // 默认验证器
    'default_validate'       => '',
    // 默认的空控制器名
    'empty_controller'       => 'Error',
    // 操作方法后缀
    'action_suffix'          => '',
    // 自动搜索控制器
    'controller_auto_search' => false,

    // +----------------------------------------------------------------------
    // | URL设置
    // +----------------------------------------------------------------------

    // PATHINFO变量名 用于兼容模式
    'var_pathinfo'           => 's',
    // 兼容PATH_INFO获取
    'pathinfo_fetch'         => ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'],
    // pathinfo分隔符
    'pathinfo_depr'          => '/',
    // URL伪静态后缀
    'url_html_suffix'        => '',
    // URL普通方式参数 用于自动生成
    'url_common_param'       => false,
    // URL参数方式 0 按名称成对解析 1 按顺序解析
    'url_param_type'         => 0,
    // 是否开启路由
    'url_route_on'           => true,
    // 路由配置文件（支持配置多个）
    'route_config_file'      => ['route'],
    // 是否强制使用路由
    'url_route_must'         => false,
    // 域名部署
    'url_domain_deploy'      => false,
    // 域名根，如thinkphp.cn
    'url_domain_root'        => '',
    // 是否自动转换URL中的控制器和操作名
    'url_convert'            => true,
    // 默认的访问控制器层
    'url_controller_layer'   => 'controller',
    // 表单请求类型伪装变量
    'var_method'             => '_method',

    // +----------------------------------------------------------------------
    // | 模板设置
    // +----------------------------------------------------------------------

    'template'               => [
        // 模板引擎类型 支持 php think 支持扩展
        'type'         => 'Think',
        // 模板路径
        'view_path'    => '',
        // 模板后缀
        'view_suffix'  => 'html',
        // 模板文件名分隔符
        'view_depr'    => DS,
        // 模板引擎普通标签开始标记
        'tpl_begin'    => '{',
        // 模板引擎普通标签结束标记
        'tpl_end'      => '}',
        // 标签库标签开始标记
        'taglib_begin' => '{',
        // 标签库标签结束标记
        'taglib_end'   => '}',
    ],

    // 视图输出字符串内容替换
    'view_replace_str'      => [
    		'__ROOT__'      =>  '',
    		'__PUBLIC__'    =>  '',
    ],
    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl'  => THINK_PATH . 'tpl' . DS . 'dispatch_jump.tpl',
    'dispatch_error_tmpl'    => THINK_PATH . 'tpl' . DS . 'dispatch_jump.tpl',

    // +----------------------------------------------------------------------
    // | 异常及错误设置
    // +----------------------------------------------------------------------

    // 异常页面的模板文件
    'exception_tmpl'         => THINK_PATH . 'tpl' . DS . 'think_exception.tpl',

    // 错误显示信息,非调试模式有效
    'error_message'          => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'         => false,
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle'       => '',

    // +----------------------------------------------------------------------
    // | 日志设置
    // +----------------------------------------------------------------------

    'log'                    => [
        // 日志记录方式，内置 file socket 支持扩展
        'type'  => 'File',
        // 日志保存目录
        'path'  => LOG_PATH,
        // 日志记录级别
        'level' => [],
    ],

    // +----------------------------------------------------------------------
    // | Trace设置 开启 app_trace 后 有效
    // +----------------------------------------------------------------------
    'trace'                  => [
        // 内置Html Console 支持扩展
        'type' => 'Html',
    ],

    // +----------------------------------------------------------------------
    // | 缓存设置
    // +----------------------------------------------------------------------

    'cache'                  => [
        // 驱动方式
        'type'   => 'File',
        // 缓存保存目录
        'path'   => CACHE_PATH,
        // 缓存前缀
        'prefix' => '',
        // 缓存有效期 0表示永久缓存
        'expire' => 0,
    ],

    // +----------------------------------------------------------------------
    // | 会话设置
    // +----------------------------------------------------------------------

    'session'                => [
        'id'             => '',
        // SESSION_ID的提交变量,解决flash上传跨域
        'var_session_id' => '',
        // SESSION 前缀
        'prefix'         => 'think',
        // 驱动方式 支持redis memcache memcached
        'type'           => '',
        // 是否自动开启 SESSION
        'auto_start'     => true,
    ],

    // +----------------------------------------------------------------------
    // | Cookie设置
    // +----------------------------------------------------------------------
    'cookie'                 => [
        // cookie 名称前缀
        'prefix'    => '',
        // cookie 保存时间
        'expire'    => 0,
        // cookie 保存路径
        'path'      => '/',
        // cookie 有效域名
        'domain'    => '',
        //  cookie 启用安全传输
        'secure'    => false,
        // httponly设置
        'httponly'  => '',
        // 是否使用 setcookie
        'setcookie' => true,
    ],		
    'mail'					=>[
    		'protocol'	=>	'smtp',
    		'smtp_host'	=>	'mail.sinowealth.com',
    		'smtp_user'	=>	'Finance.sh@sinowealth.com',
    		'smtp_pass'	=>	'Fin05)!ancE'
    ],

    //分页配置
    'paginate'               => [
        'type'      => 'bootstrap',
        'var_page'  => 'page',
        'list_rows' => 30
    ],    
    
    //AUTH授权验证配置
    'auth_config' => array(
    		'auth_on' => true, //认证开关
    		'auth_type' => 1, // 认证方式，1为实时认证；2为登录认证。
    		'auth_group' => 'sw_auth_group', //用户组数据表名
    		'auth_group_access' => 'sw_auth_group_access', //用户组明细表
    		'auth_rule' => 'sw_auth_rule', //权限规则表
    		'auth_user' => 'sw_admin'//用户信息表
    ),
    
    // +----------------------------------------------------------------------
    // | 系统设置
    // +----------------------------------------------------------------------
    
    'company_name_en'=>'Sino Wealth',
    'company_name_en_short'=>'中颖',
    'company_name_zh'=>'中颖电子',
   
    'system_name_en'=>'SinoWealth System',
    'system_name_zh'=>'内部系统',
    'ADMIN_ID'=>1,
    'BOSS_ID'=>38,
    'ADMIN_NAME'=>'a01',
    
    'SYS_URL'=>'http://hr.sinowealth.com/',
    
    //考勤中需要扣除休假时间的类型
    'hr_note_id_level'=>'2',
    
    //事假定义hr_note_id
    'casual_id'=>9,
    
    //病假定义hr_note_id
    'sick_id'=>3,
    
    'hr_cr_date'=>14,	//生成考勤统计数据最早日期
    
    'pay_cr_date'=>18,	//生成薪资数据最早日期
	
    'hr_count'=>22,		//本月人力统计生成日期
    /*
     * $db=C('SINOCOM_DB_NAME');
			$host=C('SINOCOM_DB_HOST');
			$uid=C('SINOCOM_DB_USER');
			$pwd=C('SINOCOM_DB_PWD');
     */
    
    //考勤数据抓取位置设置
    'hr_souce_data'=>[
    		'sh'=>array(
    				'DB_NAME'	=>	'inter',
    				//'HOST'		=>	'localhost',
    				'HOST'		=>	'192.9.231.241',
    				'USER'		=>	'sa',
    				'PWD'		=>	'123'
    		),
    		'sz'=>array(
    				'DB_NAME'	=>	'HXData',
    				'HOST'		=>	'192.168.1.124',
    				'USER'		=>	'sa',
    				'PWD'		=>	'123'
    		),
    		'xa'=>array(
    				'DB_NAME'	=>	'xa_inter',
    				'HOST'		=>	'localhost',
    				//'HOST'		=>	'192.168.3.22',
    				'USER'		=>	'sa',
    				'PWD'		=>	'123'
    		),
    		'tw'=>array(
    				'DB_NAME'	=>	'inter',
    				'HOST'		=>	'localhost',
    				'USER'		=>	'sa',
    				'PWD'		=>	'aaa'
    		),
    		'mms'=>array(
    				'DB_NAME'	=>	'mms',
    				'HOST'		=>	'192.9.230.15',
    				'USER'		=>	'sa',
    				'PWD'		=>	'It_soft'
    		),
    ],
    //公司门卡站点设置_上海
    'hr_card_site_sh'=>[
    		'door1'=>array(
    				'id'=>23,
    				'name'=>'前台',
    				'hr_flag'=>1,
    				'food_flag'=>0
    		),
    		'door2'=>array(
    				'id'=>2,
    				'name'=>'车库门',
    				'hr_flag'=>1,
    				'food_flag'=>0
    		),
    		'door3'=>array(
    				'id'=>24,
    				'name'=>'食堂',
    				'hr_flag'=>0,
    				'food_flag'=>1
    		),
    ],
    //公司门卡站点设置_西安
    'hr_card_site_xa'=>[
    		'door1'=>array(
    				'id'=>31,
    				'name'=>'前台',
    				'hr_flag'=>1,
    				'food_flag'=>0
    			),
    		'door2'=>array(
    				'id'=>99,
    				'name'=>'食堂',
    				'hr_flag'=>0,
    				'food_flag'=>1
    		)
    		], 		
    //公司门卡站点设置_深圳
    'hr_card_site_sz'=>[
    		'door1'=>array(
    				'id'=>31,
    				'name'=>'前台',
    				'hr_flag'=>1,
    				'food_flag'=>0
    			),
    		'door2'=>array(
    				'id'=>99,
    				'name'=>'食堂',
    				'hr_flag'=>0,
    				'food_flag'=>1
    				)
    		],
    //公司门卡站点设置_台湾
    'hr_card_site_tw'=>[
    		'door1'=>array(
    				'id'=>31,
    				'name'=>'前台',
    				'hr_flag'=>1,
    				'food_flag'=>0
    		),
    				'door2'=>array(
    						'id'=>99,
    						'name'=>'食堂',
    						'hr_flag'=>0,
    						'food_flag'=>1
    				)
    				],
    //晚餐通知对应邮箱地址
    'supper_mail'=>[
    		'qt'=>'ad_service@sinowealth.com'
    ],
    
    //文件路径
    'LIN_FILE_PATH'=>'/data/wwwroot/hr-t.sinowealth.com/application/upload',
    
    'WIN_FILE_PATH'=>"d:/1web/hr_sw/application/upload",
    'UPLOAD_FILE_URL'=> ROOT_PATH . DS .'application'.DS.'upload',
    
    //sw_pay表中非计算数量字段数
    "PAY_FIELD_NO_CAL_NUM"=>8,
    
    //每月工作日常量
    "MONTH_WORK_DAY"=>21.75,
    
    //月奖单默认显示前面几个月数据
    "PAY_MONTH_HIST_NUM"=>5,
    
    //税收减去基准值--大陆籍
    "PAY_BASE_MONEY_ML"=>3500,
    
    //税收减去基准值--非大陆籍
    "PAY_BASE_MONEY_NML"=>4800,
    
    //是否开启薪资结转判断标记
    "PAY_TABLE_FINISH_FLAG"=>0,
    
    //年度工资计算基准值
    "PAY_YEAR_DAY"=>174,
    
    //薪资验证码存活时间,3分钟
    "PAY_CHECK_CODE_TIME"=>180,
    
    //薪资页面存活时间,10分钟
    "PAY_CHECK_PAGE_TIME"=>600,
    		
   //薪资站点字段标记  1内地,2香港,3台湾
   'site_pay_flag'=>[
   		1=>'深圳',
   		2=>'香港',
   		3=>'台湾'
   ],
   
   'url'=>"../application/upload/file",//上传路径
   'page_size'=>"10",//行数
   'public'=>"../public",

    //IT部门id
    'IT_DEP_ID' => 16,
    //统购部门id
    'TONG_GOU_MANAGE_ID' => 2,
    //董事长部门
    'BOSS_DEP_ID' => '5',
    'STOCK_DEP_ID' => '1',
    'Entry_admin_email'=>'hank.zhou@sinowealth.com.cn',
    
    'wtq_user_msg'=>array(
    		'1'=>'上月考勤异常',
    		'2'=>'需要审核的单据',
    		'3'=>'试用期到期提醒（转正）',
    		'4'=>'年度考核',
    		'5'=>'生日提醒',
    		'6'=>'合约到期',
    ),
    'Record_user'=>array('a01'),
    'del_super'=>array('a01'),
    'free_little_money'=>3000,
    

    'captcha'  => [
        // 验证码字符集合
        'codeSet'  => '2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY',
        // 验证码字体大小(px)
        'fontSize' => 15,
        'useCurve' => false,
        'imageH'   => 30,
        'imageW'   => 100,
        'length'   => 4,
        'reset'    => true
    ]

];
