<?php
namespace app\common\validate;

use think\Validate;

class PayDep extends Validate
{
	protected $rule = [
			'zh_name'  =>  'require|max:45',
			'en_name' =>  'require|max:45',
	];

	protected $message = [
			'zh_name.require'  =>  '中文名必须填写',
			'en_name.require' =>  '英文名必须填写',
	];

	protected $scene = [
			'add'   =>  ['zh_name','en_name'],
			'edit'  =>  ['zh_name','en_name'],
	];

	
}