<?php
namespace app\common\validate;

use think\Validate;

class Dep extends Validate
{
	protected $rule = [
			'zh_name'  =>  'require|max:45',
			'en_name' =>  'require|max:45',
			'user_gh'=>'require',
	];

	protected $message = [
			'zh_name.require'  =>  '中文名必须填写',
			'en_name.require' =>  '英文名必须填写',
			'user_gh.require'=>'主管不能为空'
	];

	protected $scene = [
			'add'   =>  ['zh_name','en_name','user_gh'],
			'edit'  =>  ['zh_name','en_name','user_gh'],
	];

	
}