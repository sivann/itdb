<?php
//XHR list matched tags when user starts typing on the input box

require("../init.php");

$srch=$_GET['term']; //contains user typed term part
$srch=trim($srch);

$tags=array();
$sql="SELECT * from tags where name like '%$srch%' order by name";
$sth = $dbh->query($sql);

while ($r=$sth->fetch(PDO::FETCH_ASSOC)) array_push($tags,$r['name']);

echo json_encode($tags);

?>
