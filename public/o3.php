<?php

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


$stmt = $conn->exec("INSERT INTO ICL_FILE(ICL01,ICL02,ICL03,ICL04,TA_ICL01) VALUES('SH998BB',1,3,'N','ACECO')");
print_r($stmt);
?>