<?php 
if (!isset($initok)) {echo "do not run this script directly";exit;}

/* Spiros Ioannou 2011 , sivann _at_ gmail.com */

$internaltags=0;

//form submitted
if  (isset($delid) && $delid<$internaltags) { //delete an item entry
  echo "Type '$delid' cannot be deleted: internal tag";
}
elseif  (isset($delid)) { //delete an item entry

  $sql="SELECT count(tagid) count from tag2item WHERE tagid=".$_GET['delid'];
  $sth=db_execute($dbh,$sql);
  $r=$sth->fetch(PDO::FETCH_ASSOC);
  $count_i=$r['count'];

  $sql="SELECT count(tagid) count from tag2software WHERE tagid=".$_GET['delid'];
  $sth=db_execute($dbh,$sql);
  $r=$sth->fetch(PDO::FETCH_ASSOC);
  $count_s=$r['count'];


  if ($count>0) {
    echo t("<b>Warning! There are $count_i item(s) and $count_s software associated to this tag. Tag $delid not deleted!</b>");
  }
  else {
    $sql="DELETE from tags where id=".$_GET['delid'];
    $sth=db_exec($dbh,$sql);
    echo "<script>document.location='$scriptname?action=edittags'</script>";
    echo "<a href='$scriptname?action=edittags'>Go here</a></body></html>"; 
    exit;
  }
}
if (isset ($newtag)) {
  $newtag=trim($newtag);

//print_r($_REQUEST);

  if  (strlen($newtag)>1) { //add new tag
    $sql="INSERT INTO tags (name) values ('$newtag')";
    $sth=db_execute($dbh,$sql);
  }//add new tag

  //and update all old tags
  if (isset($_GET["ids"]))
    for ($i=0;$i<count($_GET["ids"]);$i++) {
      $names=$_GET['names'];
      $ids=$_GET['ids'];
      $sql="UPDATE tags SET name='".$names[$i]."'".
	   " WHERE id='".$ids[$i]."'";
      db_exec($dbh,$sql);

    }
}
//echo "<pre>"; print_r($_GET); echo "</pre>";

$sql="SELECT * from tags order by name";
$sth = $dbh->query($sql);
$tags=$sth->fetchAll(PDO::FETCH_ASSOC);


echo "<form method=get name='tagaddfrm'>";
echo "<input type=hidden name=action value='".$_GET["action"]."'>";
?>

<h1><?php te("Edit Tags");?></h1>

<div style='float:left'>
<table border=0 class='brdr' >

<tr><th>&nbsp;</th><th><?php te("TAG");?></th><th><?php te("Associated Items");?></th><th><?php te("Associated Software");?></th></tr>

<?php 
//print tag list
for ($i=0;$i<count($tags);$i++) {
  $dbid=$tags[$i]['id'];
  $name=$tags[$i]['name'];

if ($dbid>=$internaltags) //change this to remove X from internal tags
  echo "\n\n<tr><td><a href='javascript:delconfirm(\"$name\",\"$scriptname?action=edittags&amp;delid=$dbid\");'><img title='delete' src='images/delete.png' border=0></a></td>";
else echo "\n\n<tr><td>--</td>\n";

  echo "<td><input size='30' type='text' name='names[]' ".
    "value=\"".$tags[$i]['name']."\">\n";
  echo "\n<input type=hidden name='ids[]' value='$dbid' >\n</td>\n";

  echo "<td>";
  $cnt=countitemtags($dbid);
  echo "<a href='$dbid' class='showitems'>$cnt</a>";
  echo "</td>\n";

  echo "<td>";
  $cnt=countsoftwaretags($dbid);
  echo "<a href='$dbid' class='showsoftware'>$cnt</a>";
  echo "</td>\n";
  echo "</tr>\n";
}

if (!isset($dbid)) $dbid=0;
?>

    <tr><td colspan=1>New:</td>
      <td><input size='30' name='newtag' type='text'></td>
      <td colspan=2></td>
    </tr>

<tr><td style='text-align: right' colspan=4><button type="submit"><img src="images/save.png" alt="Save" > <?php te("Save");?></button></td></tr>
<tr><td style='text-align: left' colspan=4> </td></tr>
</table>
</form>

</div>

<div style='text-align:left;float:left;margin-left:50px;min-width:350px;max-width:500px;min-height:300px;border:1px solid #fff;' 
     id='itemresults'><?php te("Click on Item count column on the left to display associated items");?></div>

<div style='text-align:left;float:left;margin-left:50px;min-width:350px;max-width:500px;min-height:300px;border:1px solid #fff;' 
     id='softwareresults'><?php te("Click on Software count column on the left to display associated software");?></div>

<script>
  $(document).ready(function(){    

    $(".showitems" ).click(function() {
      $("#itemresults").html('<center><img src="images/ajaxload.gif"></center>').load('php/tag2item_ajaxlist.php?tagid='+ $(this).attr('href'));
      return false;
    });

    $(".showsoftware" ).click(function() {
      $("#softwareresults").html('<center><img src="images/ajaxload.gif"></center>').load('php/tag2software_ajaxlist.php?tagid='+ $(this).attr('href'));
      return false;
    });



 }); 
</script>
