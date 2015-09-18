<?php
/* display areas of specific location on select list*/
include('../init.php');

if(is_numeric($_POST['departmentsid'])) {
  $id=$_POST['departmentsid'];

  $sql="SELECT * FROM departments WHERE id=$departmentsid order by abbr";
  $sth=$dbh->query($sql);
  $departments=$sth->fetchAll(PDO::FETCH_ASSOC);

  if (count($departments))
    echo "<option value=''>".t("Select")."</option>";
  else
    echo "<option value=''>".t("No departments defined")."</option>";
  foreach ($departments as $key=>$d ) {
    $dbid=$d['id'];
    $itype=$d['abbr'];
    $s="";
    if (($departmentsid=="$dbid")) $s=" SELECTED ";
    echo "<option $s value='$dbid'>$itype</option>\n";
  }
}
?>