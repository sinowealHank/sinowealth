<?php
namespace app\common\validate;

use think\Validate;
class ErpCtFac extends Validate
{
    /** 验证规则
     * @var array
     */
    protected $rule = [
      'ta_pmh09' => 'require',
      'ta_pmh11' => 'require',
      'ta_pmh15' => 'require',
      'ta_pmh12' => 'require',
      'ta_pmh13' => 'require',
      'ta_pmh14' => 'require',
      'ta_pmh16' => 'require',
      'pmh20' => 'require',
//      'wire_stock' => 'require',
//      'drawing' => 'require',
    ];

    protected $message = [
        'ta_pmh09.require' => 'PM必须填写!',
        'ta_pmh11.require' => 'UM必须填写!',
        'ta_pmh15.require' => 'good_die必须填写!',
        'ta_pmh12.require' => '光罩总层数必须填写!',
        'ta_pmh13.require' => '每层工时必须填写!',
        'ta_pmh14.require' => 'lt_day必须填写!',
        'ta_pmh16.require' => '产能必须填写!',
//        'yld.require' => 'yld必须填写!',
//        'wire_stock.require' => '线材必须填写!',
        'pmh20.require' => '打线图号必须填写!',
    ];







}


