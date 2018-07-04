<?php
namespace app\erp\model;
use think\image\Exception;
use think\Model;
class CostTable extends Basic{

    /**  获得Bom数据
     * @param $prdNo
     */
    public function getBomData($prdNo)
    {
       $sql = "select
  i.imaicd00 as prd_name,
  i.imaicd00||'_PKG' as pkg_name,
  (select bmb03 from t_shsino.bmb_file where bmb01=concat(i.imaicd00,'_PKG')) as cp3_name,
  (
     case
       when substr((select bmb03 from t_shsino.bmb_file where bmb01=(select bmb03 from t_shsino.bmb_file where bmb01=concat(i.imaicd00,'_PKG'))),-3)='_CP' then
        (select bmb03 from t_shsino.bmb_file where bmb01=(select bmb03 from t_shsino.bmb_file where bmb01=concat(i.imaicd00,'_PKG')))
       else
         (select bmb03 from t_shsino.bmb_file where bmb01=concat(i.imaicd00,'_PKG'))
       end
  ) as cp_name,
  (
     case
       when substr((select bmb03 from t_shsino.bmb_file where bmb01=(select bmb03 from t_shsino.bmb_file where bmb01=concat(i.imaicd00,'_PKG'))),-3)<>'_CP' then
        (select bmb03 from t_shsino.bmb_file where bmb01=(select bmb03 from t_shsino.bmb_file where bmb01=concat(i.imaicd00,'_PKG')))
       else
         (select bmb03 from t_shsino.bmb_file where bmb01=(select bmb03 from t_shsino.bmb_file where bmb01=(select bmb03 from t_shsino.bmb_file where bmb01=concat(i.imaicd00,'_PKG'))))
        end
  ) as wf_name,
  i.IMAICD01 AS B_BODY
from
  T_SHSINO.imaicd_file i
where
       i.imaicd00  in('".$prdNo."') and imaicd04 = 4 ";

        $oracle =  new Oracle();
        $data = $oracle->getOracleData($sql);
        if(empty($data))
        {
            return null;
        }

        $bomData = $this->getBomRelation($data[0]);
        return $bomData;
    }

    /**  处理料号上下级关系
     * @param $data
     * @return array
     */
    private function getBomRelation($data)
    {
        //判断是否为五级关系
        if($data['cp3_name'] == $data['cp_name'])
        {
            //四级关系
            $data =  $this->getBomSqlReturnData($data['wf_name'],$data['cp_name'],$data['pkg_name'],$data['prd_name']);
            return $data;
        }else{
            //五级关系
        }
    }

    /**   返回各个阶段领料关系
     * @param $wf_name
     * @param $cp_name
     * @param $pkg_name
     * @param $ft_name
     * @return array
     */
    private function getBomSqlReturnData($wf_name,$cp_name,$pkg_name,$ft_name)
    {
        $sql = "select * from (select * from ( select * from ( select distinct pmh02 as a_pmh02,TA_PMH09 as a_ta_pmh09,TA_PMH11 as a_ta_pmh11,TA_PMH12 as a_ta_pmh12,TA_PMH13 as a_ta_pmh13,TA_PMH14 as a_ta_pmh14,TA_PMH16 as a_ta_pmh16,PMH20 as a_pmh20,TA_PMH15 as a_ta_pmh15,IMAICD14 as a_imaicd14,IMAICD04 as a_imaicd04,IMA021 as a_ima021,IMA131 as a_ima131
  from pmh_file ,IMAICD_FILE,IMA_FILE where pmh01 = '".$wf_name."'  and  IMAICD00 = '".$wf_name."' and IMA01 = '".$wf_name."')  cross join ( select distinct pmh02 as b_pmh02,TA_PMH09 as b_ta_pmh09,TA_PMH11 as b_ta_pmh11,TA_PMH12 as b_ta_pmh12,TA_PMH13 as b_ta_pmh13,TA_PMH14 as b_ta_pmh14,TA_PMH16 as b_ta_pmh16,PMH20 as b_pmh20,TA_PMH15 as b_ta_pmh15,IMAICD14 as b_imaicd14,IMAICD04 as b_imaicd04,IMA021 as b_ima021,IMA131 as b_ima131
  from pmh_file ,IMAICD_FILE,IMA_FILE where pmh01 = '".$cp_name."'  and  IMAICD00 = '".$cp_name."' and IMA01 = '".$cp_name."') )) cross join (  select * from (select * from ( select distinct pmh02 as c_pmh02,TA_PMH09 as c_ta_pmh09,TA_PMH11 as c_ta_pmh11,TA_PMH12 as c_ta_pmh12,TA_PMH13 as c_ta_pmh13,TA_PMH14 as c_ta_pmh14,TA_PMH16 as c_ta_pmh16,PMH20 as c_pmh20,TA_PMH15 as c_ta_pmh15,IMAICD14 as c_imaicd14,IMAICD04 as c_imaicd04,IMA021 as c_ima021,IMA131 as c_ima131
  from pmh_file ,IMAICD_FILE,IMA_FILE where pmh01 = '".$pkg_name."' and  IMAICD00 = '".$pkg_name."' and IMA01 = '".$pkg_name."') cross join ( select distinct pmh02 as d_pmh02,TA_PMH09 as d_ta_pmh09,TA_PMH11 as d_ta_pmh11,TA_PMH12 as d_ta_pmh12,TA_PMH13 as d_ta_pmh13,TA_PMH14 as d_ta_pmh14,TA_PMH16 as d_ta_pmh16,PMH20 as d_pmh20,TA_PMH15 as d_ta_pmh15,IMAICD14 as d_imaicd14,IMAICD04 as d_imaicd04,IMA021 as d_ima021,IMA131 as d_ima131
  from pmh_file ,IMAICD_FILE,IMA_FILE where pmh01 = '".$ft_name."'  and  IMAICD00 =  '".$ft_name."' and IMA01 = '".$ft_name."')) )
  ";
        $oracle =  new Oracle();
        try{
            $data = $oracle->getOracleData($sql);
        }catch (\Exception $e)
        {
            throw $e;
        }
        return $data;
    }






}