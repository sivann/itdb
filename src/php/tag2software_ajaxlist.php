<?php 
//serve XHR to display a list of software with a particular TAG id (on edittag page)
require("../init.php");

$tag_id=$_GET['tagid'];
if (!is_numeric($tag_id)) {
  echo "invalid tag_id ($tag_id)";exit;
}

$sql="SELECT software.id, agents.title || ' ' || software.title ||' '|| software.version || ' [ID:' || software.id || ']' as txt ".
     "FROM agents,software WHERE ".
     " agents.id=software.manufacturer_id AND ".
     " software.id IN (SELECT software_id from tag2software where tag_id = '$tagid')";

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
echo "<h3>".t('Associated Software')." (".tagid2name($tag_id).")</h3>";
echo $instsoftware;

?>
