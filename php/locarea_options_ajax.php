<?php
/* display areas of specific location on select list*/
include('../init.php');

if(is_numeric($_POST['locationid'])) {
  $id=$_POST['locationid'];

  $sql="SELECT * FROM locareas WHERE locationid=$id order by areaname";
  $sth=$dbh->query($sql);
  $locareas=$sth->fetchAll(PDO::FETCH_ASSOC);

  if (count($locareas))
    echo "      <option value=''>".t("Select")."</option>";
  else
    echo "      <option value=''>".t("No areas defined")."</option>";
  foreach ($locareas  as $key=>$locarea ) {
    $dbid=$locarea['id'];
    $itype=$locarea['areaname'];
    $s="";
    if (($locareaid=="$dbid")) $s=" SELECTED ";
    echo "    <option $s value='$dbid'>$itype</option>\n";
  }
}
?>
