<?php
/* display areas of specific location on select list*/
include('../init.php');

if(is_numeric($_POST['vlanid'])) {
  $id=$_POST['vlanid'];

  $sql="SELECT * FROM vlans WHERE id=$vlanid order by vlanid";
  $sth=$dbh->query($sql);
  $vlans=$sth->fetchAll(PDO::FETCH_ASSOC);

  if (count($vlans))
    echo "<option value=''>".t("Select")."</option>";
  else
    echo "<option value=''>".t("No VLAN defined")."</option>";
  foreach ($vlans as $key=>$v ) {
    $dbid=$v['id'];
    $itype=$v['vlanname'];
    $s="";
    if (($vlanid=="$dbid")) $s=" SELECTED ";
    echo "<option $s value='$dbid'>$itype</option>\n";
  }
}
?>