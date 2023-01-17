<?php 
//serve XHR to display a list of items with a particular TAG id (on edittag page)
require("../init.php");

$tagid=$_GET['tagid'];
if (!is_numeric($tagid)) {
  echo "invalid tagid ($tagid)";exit;

}



$sql="SELECT items.id, agents.title || ' ' || items.model || ' [' || itemtypes.typedesc || ', ID:' || items.id || ']' as txt ".
     "FROM agents,items,itemtypes WHERE ".
     " agents.id=items.manufacturerid AND items.itemtypeid=itemtypes.id AND ".
     " items.id IN (SELECT itemid from tag2item where tagid = '$tagid')";
$sthi=db_execute($dbh,$sql);
$ri=$sthi->fetchAll(PDO::FETCH_ASSOC);
$nitems=count($ri);
$institems="";
for ($i=0;$i<$nitems;$i++) {
  $x=($i+1).": ".$ri[$i]['txt'];
  if ($i%2) $bcolor="#D9E3F6"; else $bcolor="#ffffff";
  $institems.="\t<div style='margin:0;padding:0;background-color:$bcolor'>".
	      "<a href='?action=edititem&amp;id={$ri[$i]['id']}'>$x</a></div>\n";
}

echo "<h3>".t('Associated Items')." (".tagid2name($tagid).")</h3>";
echo $institems;

?>
