<?php
/* display areas of specific location on select list*/
include('../init.php');

if(is_numeric($_POST['locationid'])) {
  $id=$_POST['locationid'];

  $sql="SELECT locations.floorplanfn FROM locations WHERE id=$locationid";
  $sth=$dbh->query($sql);
  $locareas=$sth->fetchAll(PDO::FETCH_ASSOC);
  
  if (count($locareas)){
    foreach($locareas  as $key=>$locarea)
  {
	  $mapButton=$locarea['floorplanfn'];
    echo "<a id='locareaidButton' title='View Building Floor Map' href='../data/files/$mapButton'><img src='images/bldgmap.png' height=20px width=20px alt='+'></a>";
  }
}
}
?>