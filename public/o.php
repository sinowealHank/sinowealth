<?php
$conn = oci_connect('T_SHSINO','t_shsino','192.9.231.201/topprod');//�����ǳ�������Ա�˻�
if (!$conn) {
$e = oci_error();
print htmlentities($e['message']);
exit;
}
$sql = "insert into t_shsino.xmf_file(xmf01,xmf03,XMF02,XMF04,XMF05) values('SINO','SH366001U/048UR','123','222',to_date('2018-01-18','yyyy-mm-dd'))";//ֻ�ܷ��ʶ�Ӧ�û����µ����ݿ⣬�����˻����ú���Ҳ�����������������Է��ʵ������û���
$ora_test = oci_parse($conn,$sql); //����sql���
oci_execute($ora_test); //ִ��

?>
