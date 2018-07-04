<?php
$conn = oci_connect('T_SHSINO','t_shsino','192.9.231.201/topprod');//不能是超级管理员账户
if (!$conn) {
$e = oci_error();
print htmlentities($e['message']);
exit;
}
$sql = "insert into t_shsino.xmf_file(xmf01,xmf03,XMF02,XMF04,XMF05) values('SINO','SH366001U/048UR','123','222',to_date('2018-01-18','yyyy-mm-dd'))";//只能访问对应用户名下的数据库，或许账户配置好了也或许有其他方法可以访问到其他用户的
$ora_test = oci_parse($conn,$sql); //编译sql语句
oci_execute($ora_test); //执行

?>
