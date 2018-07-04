<?php
//获取mssql的配置文件
function get_mssql_data(){
    $data['type'] = 'sqlsrv';
    $data['hostname'] = '192.9.230.22';
    $data['username'] = 'sa';
    $data['password'] = 'It_soft';
    $data['hostport'] = '1433';
    return $data;
}


function get_oracle_data(){
    $data['type']  = 'oci';
    $data['hostname'] = '192.9.230.22'; //数据库地址
    $data['database'] = 'sino'; //数据库SID
    $data['username'] = 'WTQ'; // 用户名
    $data['password'] = '123456'; // 密码
    $data['hostport'] = '1521'; //端口号
    $data['charset'] = 'utf8'; // 数据库编码
    return $data;

}