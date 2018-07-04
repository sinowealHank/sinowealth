<?php
$conn = oci_connect('WTQ', '123456', '192.9.230.22/ORCL');
$stid = oci_parse($conn, 'select * FROM SINO.XMF_FILE');
oci_execute($stid);
echo "<table>\n";
while (($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) != false) {
  echo "<tr>\n";
  foreach ($row as $item) {
    echo " <td>".($item !== null ? htmlentities($item, ENT_QUOTES) : " ")."</td>\n";
  }
  echo "</tr>\n";
}
echo "</table>\n";
?>