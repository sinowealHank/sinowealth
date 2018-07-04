<?php
namespace app\common\helper;

use Gregwar\Captcha\CaptchaBuilder;

class VerifyHelper{
    /**
     * ������֤��
     */
    public static function verify()
    {
        $builder = new CaptchaBuilder();
        $builder->build()->output();

        session('verify_code', $builder->getPhrase());
    }

    /**
     * �����֤���Ƿ���ȷ
     * @param $code
     * @return bool
     */
    public static function check($code)
    {
        return ($code == session('verify_code') && $code != '') ? true : false;
    }

}