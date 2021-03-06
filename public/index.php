<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');

/**
 * 项目定义
 * 扩展类库目录
 */
define('BASE_PATH', substr($_SERVER['SCRIPT_NAME'], 0, -10));
define('ROOT_PATH', dirname(APP_PATH) . DIRECTORY_SEPARATOR);
define('EXTEND_PATH', ROOT_PATH . 'thinkphp' . DIRECTORY_SEPARATOR . 'extend' . DIRECTORY_SEPARATOR);

define('ICONV_ENABLED', TRUE);

define('MEMORY_LIMIT_ON',function_exists('memory_get_usage'));

// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';
