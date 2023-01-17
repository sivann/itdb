<?php 
//print_r($_REQUEST);
if (!isset($initok)) {echo "do not run this script directly";exit;}

/* Spiros Ioannou 2009 , sivann _at_ gmail.com */

$internaltypes=1;

//form submitted
if  (isset($deltype) && $deltype<=$internaltypes) { //delete an entry
  echo "Type '$deltype' cannot be deleted: internal type";
}
elseif  (isset($deltype)) { //delete an item entry

  $sql="SELECT count(id) count from contracts WHERE type=$deltype";
  $sth=db_execute($dbh,$sql);
  $r=$sth->fetch(PDO::FETCH_ASSOC);
  $count=$r['count'];

  if ($count>0) {
    echo t("<b>Warning! There are $count contract(s) of this type registered. Type not deleted!</b>");
  }
  else {
    $sql="DELETE from contracttypes where id=".$_GET['deltype'];
    $sth=db_exec($dbh,$sql);

    $sql="DELETE from contractsubtypes where contypeid=".$_GET['deltype'];
    $sth=db_exec($dbh,$sql);

    echo "<script>document.location='$scriptname?action={$_GET['action']}'</script>";
    echo "<a href='$scriptname?action={$_GET['action']}'>Go here</a></body></html>";
  }
}

//form submitted
if  (isset($delsubtype)) { //delete an item entry
    $sql="DELETE from contractsubtypes where id=".$_GET['delsubtype'];
    $sth=db_exec($dbh,$sql);
    echo "<script>document.location='$scriptname?action={$_GET['action']}'</script>";
    echo "<a href='$scriptname?action={$_GET['action']}'>Go here</a></body></html>";
}

if (isset($savetype)) {
  if (isset ($_POST['newtype'])) {
    $name=$_POST['newtype'];
    if  (strlen($name)>1) { //add new type
      $sql="INSERT into contracttypes (name) values ('$name')";
      $sth=db_execute($dbh,$sql);
    }//add new type

    //and update all old types
    if (isset($_POST["ids"]))
    for ($i=0;$i<count($_POST["ids"]);$i++) {
      $descs=$_POST['descs'];
      $ids=$_POST['ids'];
      $sql="UPDATE contracttypes SET name='".$descs[$i]."'".
	   " WHERE id='".$ids[$i]."'";
      db_exec($dbh,$sql);

    }
  }
}
elseif (isset($savesubtype)) {

  if (isset ($_POST['newsubtype'])) {
    $name=$_POST['newsubtype'];
    $subtypesof=$_POST['subtypesof'];
    if  (strlen($name)>1) { //add new type
      $sql="INSERT into contractsubtypes (name,contypeid) values ('$name',$subtypesof)";
      $sth=db_execute($dbh,$sql);
    }//add new type

    //and update all old types
    if (isset($_POST["subids"]))
    for ($i=0;$i<count($_POST["subids"]);$i++) {
      $subdescs=$_POST['subdescs'];
      $subids=$_POST['subids'];
      $sql="UPDATE contractsubtypes SET name='".$subdescs[$i]."'".
	   " WHERE id='".$subids[$i]."'";
      db_exec($dbh,$sql);

    }
  }



}

//echo "<pre>"; print_r($_GET); echo "</pre>";

//$sql="SELECT * from contracttypes order by name";
$sql="select * from contracttypes where id <=$internaltypes UNION all select * from (select * from contracttypes where id>1 order by name)";
$sth = $dbh->query($sql);
$contracttypes=$sth->fetchAll(PDO::FETCH_ASSOC);

?>

<form method=post name='typeaddfrm'>
<input type=hidden name=action value="<?php echo $_GET["action"]?>">
<h1><?php te("Edit Contract Types");?></h1>

<div style='width:80%;border:0px solid red;margin-left:auto;margin-right:auto;'>
  <div style='float:left;margin-right:15px;'>
  <table border=0 class='brdr' >
  <tr><th>&nbsp;</th><th><?php te("Type Names");?></th><th></th></tr>

  <?php 
  //print contract type list
  for ($i=0;$i<count($contracttypes);$i++) {
    $dbid=$contracttypes[$i]['id'];
    $itype=$contracttypes[$i]['name']; 
  if ($dbid>$internaltypes) //change this to remove X from internal types
    echo "\n<tr><td><a href='javascript:delconfirm(\"$itype\",\"$scriptname?action=$action&amp;deltype=$dbid\");'><img title='delete ID:$dbid' src='images/delete.png' border=0></a></td>";
  else 
    echo "\n\n<tr><td title='ID:$dbid'>-</td>";

    if ($contracttypes[$i]['hassoftware']) $s="selected"; else $s="";

    echo "<td><input size=30 type='text' name='descs[]' ".
    "value=\"".$contracttypes[$i]['name']."\">\n".
    "\n<input type=hidden name='ids[]' value='$dbid' >\n";
    echo "</td>";
    echo "<td><button type=submit name='subtypesof' value='$dbid'>".t("View/Edit Subtypes")."</button></td>";
    echo "</tr>";
  }

  ?>

  <tr><td><?php te("New");?></td><td><input size='30' name='newtype' type='text'></td></tr>

  <tr><td style='text-align: right' colspan=4><button name='savetype' type="submit"><img src="images/save.png" alt="Save" > <?php te("Save");?></button></td></tr>
  </form>
  </table>
  </div>

  <?php 
    if (isset($_POST['subtypesof'])) {
  ?>
      <div style='float:left;'>
      <form method=post name='subtypeaddfrm'>
      <table class='brdr'> <!-- subtypes -->
      <tr><th>&nbsp;</th><th>ID</th><th><?php te("Subtypes of  type ");?><?php echo $subtypesof?> <?php te("Names");?></th></tr>
      <?php 
      $subtypesof=$_POST['subtypesof'];
      $sql="SELECT * from contractsubtypes  WHERE contypeid=$subtypesof order by name";
      $sth = $dbh->query($sql);
      $contractsubtypes=$sth->fetchAll(PDO::FETCH_ASSOC);
      echo "<input type=hidden name='subtypesof' value='$subtypesof' >\n";

      for ($i=0;$i<count($contractsubtypes);$i++) {
	$dbid=$contractsubtypes[$i]['id'];
	$itype=$contractsubtypes[$i]['name']; 
	echo "\n<tr><td><a href='javascript:delconfirm(\"$itype\",\"$scriptname?action=$action&amp;delsubtype=$dbid\");'><img title='delete' src='images/delete.png' border=0></a></td>";
	echo "\n<td>$dbid</td>".
	"<td><input size=30 type='text' name='subdescs[]' ".
	"value=\"".$contractsubtypes[$i]['name']."\">\n".
	"\n<input type=hidden name='subids[]' value='$dbid' >\n";
      }
      ?>
      <tr><td>&nbsp;</td><td><?php te("New");?></td><td><input size='30' name='newsubtype' type='text'></td></tr>
      <tr><td style='text-align: right' colspan=4><button type='submit' name='savesubtype'><img src='images/save.png' alt='Save' > <?php te("Save");?></button></td></tr>
      <?php 
    }
  ?>
  </table>
  </div>
  </form>
</div>

