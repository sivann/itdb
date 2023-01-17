<?php 
if (!isset($initok)) {echo "do not run this script directly";exit;}

/* Spiros Ioannou 2009 , sivann _at_ gmail.com */

//echo "<pre>"; print_r($_GET); print_r($_POST);

$internaltypes="10";

$formvars=array("id", "typedesc");

//form submitted
if  (isset($_GET['deltype']) && $_GET['deltype']<=$internaltypes) { //delete an entry
  echo "Type '{$_GET['deltype']}' cannot be deleted: internal type";
}
elseif  (isset($_GET['deltype'])) { //delete an item entry
  $deltype=$_GET['deltype'];
  $sql="SELECT count(id) count from files WHERE type=$deltype";
  $sth=db_execute($dbh,$sql);
  $r=$sth->fetch(PDO::FETCH_ASSOC);
  $count=$r['count'];

  if ($count>0) {
    echo t("<b>Warning! There are $count file(s) of this file type registered. Type not deleted!</b>");
  }
  else {
    $sql="DELETE from filetypes where id=".$_GET['deltype'];
    $sth=db_exec($dbh,$sql);
    echo "<script>document.location='$scriptname?action={$_GET['action']}'</script>";
    echo "<a href='$scriptname?action={$_GET['action']}'>Go here</a></body></html>";
  }

}


//if came here from a form post, update db with new values
if (isset($_POST['typedesc'])) {
  $nrows=count($_POST['id']); //number of rows

  for ($rn=0;$rn<$nrows;$rn++) {
    $id=$_POST['id'][$rn];
      if (($id == "new") && (strlen($_POST['typedesc'][$rn])>1) )  {//new item -- insert
      $sql="INSERT into filetypes ".
          "(typedesc) ".
	  " values (".
	  "'".($_POST['typedesc'][$rn])."')";
      }
      elseif ($id!="new" && strlen($_POST['typedesc'][$rn])){ //existing item -- update
	$sql="UPDATE filetypes set ".
	  " typedesc='".($_POST['typedesc'][$rn])."' ".
	  " WHERE id=$id";
      }
      else {continue;}

    db_exec($dbh,$sql);
  }//for


} //if




//if (!isset ($_GET['itemid']) || !strlen($_GET['itemid'])) {echo "$scriptname: wrong arguments";exit;}

$sql="SELECT * from filetypes order by id";

/// make db query
$sth=db_execute($dbh,$sql);

?>
<form method=post name='actionaddfrm'>
<h1><?php te("Edit File Types");?></h1>
<table class=brdr>
<tr><th>&nbsp;</th><th><?php te("Description");?></th></tr>

<?php 
$i=0;
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
$i++;
  $dbid=$r['id'];
  $dbtypedesc=$r['typedesc'];

  if ($dbid>$internaltypes) 
    echo "\n<tr><td title='Delete [ID: ".$dbid."]'><a href='javascript:delconfirm(\"[ID: {$dbid}] $dbtypedesc\",\"$scriptname?action=$action&amp;deltype=$dbid\");'>".
         "<img src='images/delete.png' border=0></a></td>";
  else
    echo "\n\n<tr><td title='internal type ($dbid), cannot be deleted or changed.'></td>";
  echo "<td nowrap><input type=hidden name='id[]' value='".$r['id']."' readonly size=3>\n";
  if ($dbid<=$internaltypes) echo "<input size=15 maxlen=20 type=text name='typedesc[]' readonly value=\"".$r['typedesc']."\"></td>\n";
  if ($dbid>$internaltypes) echo "<input size=15 maxlen=20 type=text name='typedesc[]' value=\"".$r['typedesc']."\"></td>\n";
  echo "</tr>\n\n";
}

//empty line to add new items at bottom
echo "<tr><td><input type=hidden name='id[]' value='new' readonly size=3>New:</td>\n";
echo "<td><input size=15 maxlen=20 type=text name='typedesc[]' ></td>\n";

?>
<tr><td colspan=2><button type="submit"><img src="images/save.png" alt="Save" > <?php te("Save");?></button></td></tr>

</table>
</form>
</body>
</html>
