<?php
namespace app\user\model;
use think\Model;
use think\Validate;

//2017-11-9 周仁杰创建UserMain类
class UserMain extends Model{
    //检验当前允许修改字段
    static function check_Senility_field($data){
          $check_field_array = ['id','identity_card','birthday','mobile',
                                'university','hr_edu_id','eme_contact_name',
                                'eme_contact_info','address','address1','ext_tel','bank_card'
          ];

          foreach($data as $k => $v){
              if(!in_array($k,$check_field_array)){
                  echo setServerBackJson(0,"当前有非法修改字段！");exit;
              }
          }
          unset($data['id']);
         //调用校验数据合法性的方法
          UserMain::check_field_data($data);
    }

    //校验数据的合法性
    protected static function check_field_data($data){
        if(empty($data['identity_card'])){echo setServerBackJson(0,"证件不能为空");exit;}
        if(empty($data['birthday'])){echo setServerBackJson(0,"生日不能为空");exit;}
        if(empty($data['mobile'])){echo setServerBackJson(0,"手机号码不能为空");exit;}
        if(empty($data['university'])){echo setServerBackJson(0,"毕业院校不能为空");exit;}
        if(empty($data['hr_edu_id'])){echo setServerBackJson(0,"学历需要指定");exit;}
        if(empty($data['eme_contact_name'])){echo setServerBackJson(0,"紧急联络人不能为空");exit;}
        if(empty($data['eme_contact_info'])){echo setServerBackJson(0,"紧急联络人联系方式");exit;}
        if(empty($data['address'])){echo setServerBackJson(0,"地址1不能为空");exit;}
        if(empty($data['ext_tel'])){echo setServerBackJson(0,"分机号不能为空");exit;}
        if(empty($data['bank_card'])){echo setServerBackJson(0,"银行账号不能为空");exit;}

        //验证身份证号码位数
        $temp = ['15','18'];
        $identity_length = strlen($data['identity_card']);
        if(!in_array($identity_length ,$temp)){
            echo setServerBackJson(0,"证件号位数有错误重新输入");exit;
        }

        $rule = [
            'birthday' => 'date',
            'mobile' => 'number|length:11',
            'bank_card' => 'number',
            'ext_tel' => 'number'
        ];

        $msg = [
            'birthday.date' => '生日必须为日期格式',
            'mobile.number' => '手机号必须为数字或数字之间不能留空格',
            'mobile.length' => '手机号长度不对',
            'bank_card.number' => '银行账号必须全部为数字',
            'ext_tel.number' => '分机号必须为数字'

        ];

        $data = [
            'identity_card' => $data['identity_card'],
            'birthday' => $data['birthday'],
            'mobile' => $data['mobile'],
            'bank_card' => $data['bank_card'],
            'ext_tel' => $data['ext_tel']
        ];

        $validate = new Validate($rule,$msg);
        $result = $validate->check($data);
        if(!$result){
            echo setServerBackJson(0,$validate->getError());exit;
        }

    }





}