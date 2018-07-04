<?php
namespace app\erp\model;
use think\Model;
use PDO;
use PDOException;
class Oracle extends Basic{
    public function getOracleData($sql1){
        $config_oracle_info = $this->connection;
        $dsn_con = 'oci:dbname=//'.$config_oracle_info['hostname'].':'.$config_oracle_info['hostport'].'/topprod;charset=UTF8';
        try{
            $dbh= new PDO($dsn_con,$config_oracle_info['username'],$config_oracle_info['password'],array(PDO::ATTR_PERSISTENT => true));
        } catch (PDOException $e) {
            print "oci: " . $e->getMessage() . "<br/>";
            die();
        }
        $sql = $sql1;
        $dbh->setAttribute(PDO::ATTR_CASE,PDO::CASE_LOWER);
        $rs=$dbh->prepare($sql);
        $rs->execute();
        $rs->setFetchMode(PDO::FETCH_ASSOC);
        $result_arr = $rs->fetchAll();
        return $result_arr;
    }
}