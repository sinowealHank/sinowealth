<?php
namespace app\common\validate;

use think\Validate;

class Site extends Validate
{
	protected $rule = [
			'site'  =>  'require|max:35',
			'site_code' =>  'require|max:10',
			'zh_name'=>'require',
			'en_name'=>'require',
			'conn_name'=>'require',
			'address'=>'require',
	];

	protected $message = [
			'site.require'=>'站点不能为空',
			'site_code.require'=>'站点编号不能为空',
			'zh_name.require'  =>  '中文名必须填写',
			'en_name.require' =>  '英文名必须填写',
			'conn_name.require'=>'联系人不能为空',
			'address.require'=>'地址不能为空'
	];

	protected $scene = [
			'add'   =>  ['site','site_code','zh_name','en_name','conn_name','address'],
			'edit'  =>  ['site','site_code','zh_name','en_name','conn_name','address'],
	];

	
}