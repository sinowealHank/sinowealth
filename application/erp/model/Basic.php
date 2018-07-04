<?php
namespace app\erp\model;
use think\Model;
use PDO;
class Basic extends Model{
    protected $connection = [
       'type'  =>  '\think\oracle\Connection',
       'hostname' => '192.9.231.201',
        'database' => 'T_SHSINO',
        'username' => 'T_SHSINO',
        'password' => 't_shsino',
        'hostport' => '1521',
        'charset'=> 'utf8',
        'params' => array(PDO::ATTR_CASE=> PDO::CASE_LOWER)
    ];




}