<?php 
//serve XHR to display a list of software with a particular TAG id (on edittag page)
require("../init.php");

$tagid=$_GET['tagid'];
if (!is_numeric($tagid)) {
  echo "invalid tagid ($tagid)";exit;
}

$sql="SELECT software.id, agents.title || ' ' || software.stitle ||' '|| software.sversion || ' [ID:' || software.id || ']' as txt ".
     "FROM agents,software WHERE ".
     " agents.id=software.manufacturerid AND ".
     " software.id IN (SELECT softwareid from tag2software where tagid = '$tagid')";

$sthi=db_execute($dbh,$sql);
$ri=$sthi->fetchAll(PDO::FETCH_ASSOC);
$nsoftware=count($ri);
$instsoftware="";
for ($i=0;$i<$nsoftware;$i++) {
  $x=($i+1).": ".$ri[$i]['txt'];
  if ($i%2) $bcolor="#D9E3F6"; else $bcolor="#ffffff";
  $instsoftware.="\t<div style='margin:0;padding:0;background-color:$bcolor'>".
	      "<a href='?action=editsoftware&amp;id={$ri[$i]['id']}'>$x</a></div>\n";
}
echo "<h3>".t('Associated Software')." (".tagid2name($tagid).")</h3>";
echo $instsoftware;

?>
