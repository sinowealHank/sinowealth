<?php
//pdo

$tns = "
 (DESCRIPTION=
    (ADDRESS=
      (PROTOCOL=TCP)
      (HOST=192.9.231.201)
      (PORT=1521)
    )
    (CONNECT_DATA=
      (SERVER=dedicated)
      (SERVICE_NAME=topprod)
    )
  )
       ";

try {
    $conn = new PDO("oci:dbname=".$tns, "t_shsino", "t_shsino");
    echo ("success");
}
catch (PDOException $e) {
    echo "Failed to obtain database handle " . $e->getMessage();
}


//$stmt = $conn->exec("INSERT INTO ICL_FILE(ICL01,ICL02,ICL03,ICL04,TA_ICL01) VALUES('SH998BB',1,3,'N','ACECO')");
//$stmt = $conn->exec("insert into a(a,b,ooa40) values(1,2,3)");
//$sql="insert into a(a,b,ooa40) values(1,2,3)";
//$sql="delete from IT_TEST where icl01='SH998BBB'";
//$conn->exec($sql);
//$sql="insert into IT_TEST(ICL01,ICL02,ICL03,ICL04,TA_ICL01) VALUES('SH998BBB',2,4,'N','ACECO')";
$sql="insert into t_shsino.xmf_file(xmf01,xmf03,ta_xmf09,Ta_xmf01,Ta_xmf02,Ta_xmf03,Ta_xmf08,xmf02,xmf07,Ta_xmf11,Ta_xmf06,Ta_xmf07,Ta_xmf04,Ta_xmf05,xmf05,xmf04)
                values ('SINO','SH452A','1','1','1','1','1'
                ,'RMB','1.1700','1.1700','1','1',
               '1','1',to_date('2018-01-18','yyyy-mm-dd'),'EA')";
$conn->exec($sql);
//$sql="insert into IT_TEST(NAME,USER_PWD) values('bbb','bbb')";
//$rs=$conn->prepare($sql);
        //$rs->execute();

//print_r($rs->execute());

?>