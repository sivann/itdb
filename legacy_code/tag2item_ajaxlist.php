<?php 
//serve XHR to display a list of items with a particular TAG id (on edittag page)
require("../init.php");

$tag_id=$_GET['tagid'];
if (!is_numeric($tag_id)) {
  echo "invalid tag_id ($tag_id)";exit;

}



$sql="SELECT items.id, agents.title || ' ' || items.model || ' [' || itemtypes.description || ', ID:' || items.id || ']' as txt ".
     "FROM agents,items,itemtypes WHERE ".
     " agents.id=items.manufacturer_id AND items.item_type_id=itemtypes.id AND ".
     " items.id IN (SELECT item_id from tag2item where tag_id = '$tagid')";
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

echo "<h3>".t('Associated Items')." (".tagid2name($tag_id).")</h3>";
echo $institems;

?>
