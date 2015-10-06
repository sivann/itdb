<?php
/* display areas of specific location on select list*/
include('../init.php');

if(is_numeric($_POST['vouchermins'])) {
  $id=$_POST['vouchermins'];

  $sql="SELECT * FROM vouchers WHERE vouchermins=$vouchermins";
  $sth=$dbh->query($sql);
  $vouchers=$sth->fetchAll(PDO::FETCH_ASSOC);

  if (count($vouchers))
    echo "<option value=''>".t("Select")."</option>";
  else
    echo "<option value=''>".t("No vouchers defined")."</option>";
  foreach ($vouchers as $key=>$v ) {
    $dbid=$v['vouchermins'];
    $itype=$v['voucherroll'];
    $s="";
    if (($vouchermins=="$dbid")) $s=" SELECTED ";
    echo "<option $s value='$dbid'>$itype</option>\n";
  }
}
?>