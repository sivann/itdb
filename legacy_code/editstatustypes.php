<?php 
if (!isset($initok)) {echo "do not run this script directly";exit;}

/* Spiros Ioannou 2009 , sivann _at_ gmail.com */

//echo "<pre>"; print_r($_GET); print_r($_POST);

$internaltypes="4";

$formvars=array("id", "statusdesc");

//form submitted
if  (isset($_GET['deltype']) && $_GET['deltype']<=$internaltypes) { //delete an entry
  echo "Type '{$_GET['deltype']}' cannot be deleted: internal type";
}
elseif  (isset($_GET['deltype'])) { //delete an item entry
  $deltype=$_GET['deltype'];
  $sql="SELECT count(id) count from items WHERE status=$deltype";
  $sth=db_execute($dbh,$sql);
  $r=$sth->fetch(PDO::FETCH_ASSOC);
  $count=$r['count'];

  if ($count>0) {
    echo t("<b>Warning! There are $count items(s) of this status registered. Type not deleted!</b>");
  }
  else {
    $sql="DELETE from statustypes where id=".$_GET['deltype'];
    $sth=db_exec($dbh,$sql);
    echo "<script>document.location='$scriptname?action={$_GET['action']}'</script>";
    echo "<a href='$scriptname?action={$_GET['action']}'>Go here</a></body></html>";
  }

}


//if came here from a form post, update db with new values
if (isset($_POST['statusdesc'])) {
  $nrows=count($_POST['id']); //number of rows

  for ($rn=0;$rn<$nrows;$rn++) {
    $id=$_POST['id'][$rn];
      if (($id == "new") && (strlen($_POST['statusdesc'][$rn])>1) )  {//new item -- insert
      $sql="INSERT into statustypes ".
          "(statusdesc) ".
	  " values (".
	  "'".($_POST['statusdesc'][$rn])."')";
      }
      elseif ($id!="new"){ //existing item -- update
	$sql="UPDATE statustypes set ".
	  " statusdesc='".($_POST['statusdesc'][$rn])."' ".
	  " WHERE id=$id";
      }
      else {continue;}

    //echo "$rn $sql<br>";
    db_exec($dbh,$sql);
  }//for


} //if




//if (!isset ($_GET['itemid']) || !strlen($_GET['itemid'])) {echo "$scriptname: wrong arguments";exit;}

$sql="SELECT * from statustypes order by id";

/// make db query
$sth=db_execute($dbh,$sql);

?>
<form method=post name='actionaddfrm'>
<h1><?php te("Edit Status Types");?></h1>
<table class=brdr>
<tr><th>&nbsp;</th><th><?php te("Description");?></th></tr>

<?php 
$i=0;
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
$i++;
  $dbid=$r['id'];

  if ($dbid>$internaltypes) //change this to remove X from internal types
    echo "\n<tr><td title='Delete'><a href='javascript:delconfirm(\"$itype\",\"$scriptname?action=$action&amp;deltype=$dbid\");'>".
         "<img src='images/delete.png' border=0></a></td>";
  else
    echo "\n\n<tr><td title='internal type ($dbid), cannot delete'></td>";
  $x=attrofstatus($dbid,$dbh);
  $attr=$x[0];
  $statustxt=$x[1];
  echo "<td nowrap><input type=hidden name='id[]' value='".$r['id']."' readonly size=3>\n";
  echo "<span $attr>&nbsp;</span><input size=15 maxlen=20 type=text name='statusdesc[]' value=\"".$r['statusdesc']."\"></td>\n";
  echo "</tr>\n\n";
}

//empty line to add new items at bottom
echo "<tr><td><input type=hidden name='id[]' value='new' readonly size=3>New:</td>\n";
echo "<td><input size=15 maxlen=20 type=text name='statusdesc[]' ></td>\n";

?>
<tr><td colspan=2><button type="submit"><img src="images/save.png" alt="Save" > <?php te("Save");?></button></td></tr>

</table>
</form>
</body>
</html>
