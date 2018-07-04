<?php
namespace app\erp\model;
use think\Model;
class ImaFile extends Basic{
    protected $table = 'DATA.ima_file';

    /** 鏇存柊erp涓殑ima_file鐨勯瑙佸簱瀛?
     * @param $ima01_arr
     * @return
     */
    public function update_erp($ima01_arr){
         foreach($ima01_arr as $v){
             $sql = "update data.ima_file set imaud10 = '".$v['imaud10']."' where ima01 = '".$v['xmf03']."'";
             try {
                  $this->execute($sql);
             }catch (\Exception $e) {
                 throw new \Exception($e->getMessage());
             }
         }
    }

    public function get_filed_comment($filed){
        $array = [
            'xmf08' => '折扣',
            'ta_xmf01' => 'L/T Days',
            'ta_xmf02' => '定下单MOQ',
            'ta_xmf03' => 'Pack 最小包装数量',
            'ta_xmf04' => 'Marke Price_内',
            'ta_xmf05' => 'Marke Price_外',
            'ta_xmf06' => 'Agent Price_内',
            'ta_xmf07' => 'Agent Price_外',
            'ta_xmf08' => 'Refund Qty(Kpcs)',
//            'ta_xmf11' => '带Code单价',
            'ima27' => '业务部安全库存',
            'imaud10' => '预建库存',
            'ima10' => '归属地',
            'xmf07' => '美金价格',
            'ta_xmf11' => 'Code USD'
        ];
        return $array[$filed];
    }




}

