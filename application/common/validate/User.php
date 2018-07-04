<?php
namespace app\common\validate;

use think\Validate;

class User extends Validate
{
	protected $rule = [
			'nickname'  =>  'require|max:31',
			'password' 	=> 'require',
			'eme_contact_name' =>'require',
			'eme_contact_info'=>'require',
			'identity_card' =>  'require|max:50',
			'mobile'=>'require',
			'address'=>'require',
			'user_gh'=>'require',
			'email'=>'require',
			'pact_num'=>'require',
	];

	protected $message = [
			'nickname.require'=>'姓名不能为空',
			'nickname.max'=>'姓名字符过长',
			'password.require'=>'初始密码不能为空',
			'eme_contact_name.require'=>'紧急联络人不能为空',
			'eme_contact_info.require'=>'紧急联络人联系方式不能为空',
			'identity_card.require'=>'身份证不能为空',
			'mobile.require'  =>  '手机必须填写',
			'address.require' =>  '地址必须填写',
			'user_gh.require'=>'工号不能为空',
			'email.require'=>'邮箱不能为空',
			'pact_num.require'=>'N次续约不能为空',
	];

	protected $scene = [
			'add'   =>  ['nickname','eme_contact_name','eme_contact_info','identity_card','mobile','address','user_gh','password','email','pact_num'],
			'edit'  =>  ['nickname','eme_contact_name','eme_contact_info','identity_card','mobile','address','user_gh','email','pact_num'],
	];

	
}