<?php
/* display areas of specific location on select list*/
include('../init.php');

if(is_numeric($_POST['location_id'])) {
  $id=$_POST['location_id'];

  $sql="SELECT * FROM racks WHERE location_id=$id order by label,id";
  $sth=$dbh->query($sql);
  $racks=$sth->fetchAll(PDO::FETCH_ASSOC);

  if (count($racks))
    echo "      <option value=''>".t("Select")."</option>\n";
  else
    echo "      <option value=''>".t("No racks in location")."</option>\n";

  foreach ($racks  as $key=>$rack ) {
    $dbid=$rack['id'];
    $name=$rack['label'].",".$rack['usize']."U ". $rack['model'];
    $s="";
    //if (($rack_id=="$dbid")) $s=" SELECTED ";
    echo "    <option $s value='$dbid'>$name</option>\n";
  }
}
?>
