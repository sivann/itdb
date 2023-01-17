<script type="text/javascript">
$(function () {
    //$('#typeaddfrm').ajaxForm(function() { alert("Thank you for your comment!"); }); 
/*
    $('table#itypetbl').dataTable({
		  "sPaginationType": "full_numbers",
		  "bJQueryUI": true,
		  "iDisplayLength": 15,
		  "bLengthChange": true,
		  "bFilter": true,
		  "bSort": true,
		  "bInfo": true,

    });
$('#itypetbl_wrapper').css('width','350px');
*/

  });
</script>
<?php 
if (!isset($initok)) {echo "do not run this script directly";exit;}

/* Spiros Ioannou 2009 , sivann _at_ gmail.com */

$internaltypes=0;

//form submitted
if  (isset($deltype) && $deltype<$internaltypes) { //delete an item entry
  echo "Type '$deltype' cannot be deleted: internal type";
}
elseif  (isset($deltype)) { //delete an item entry

  $sql="SELECT count(id) count from items WHERE itemtypeid=".$_GET['deltype'];
  $sth=db_execute($dbh,$sql);
  $r=$sth->fetch(PDO::FETCH_ASSOC);
  $count=$r['count'];

  if ($count>0) {
    echo t("<b>Warning! There are $count item(s) of this type registered. Type not deleted!</b>");
  }
  else {
    $sql="DELETE from itemtypes where id=".$_GET['deltype'];
    $sth=db_exec($dbh,$sql);
    echo "<script>document.location='$scriptname?action=edititypes'</script>";
    echo "<a href='$scriptname?action=edititypes'>Go here</a></body></html>"; 
    exit;
  }
}
if (isset ($newtype)) {

//print_r($_REQUEST);

  if  (strlen($newtype)>1) { //add new type
    $sql="INSERT INTO itemtypes (typedesc,hassoftware) values ('$newtype','$newhassoftware')";
    $sth=db_execute($dbh,$sql);
  }//add new type

  //and update all old types
  if (isset($_POST["ids"]))
  for ($i=0;$i<count($_POST["ids"]);$i++) {
    $descs=$_POST['descs'];
    $ids=$_POST['ids'];
    $sql="UPDATE itemtypes SET typedesc='".$descs[$i]."', hassoftware=".$hassoftware[$i]." ".
         " WHERE id='".$ids[$i]."'";
    db_exec($dbh,$sql);

  }
}
//echo "<pre>"; print_r($_POST); echo "</pre>";

$sql="SELECT * from itemtypes order by typedesc";
$sth = $dbh->query($sql);
$fixtypes=$sth->fetchAll(PDO::FETCH_ASSOC);


echo "<form method=post id='typeaddfrm' action='?action=edititypes&amp;dlg=$dlg' name='typeaddfrm'>";
echo "<input type=hidden name=action value='".$_POST["action"]."'>";
?>

<h1><?php te("Edit Item Types");?></h1>

<table id='itypetbl' class='brdr' border=0 >
<thead>
<tr><th>&nbsp;</th><th><?php te("Description");?></th><th><?php te("Supports<br>Software");?><sup>1</sup></th></tr>
</thead>

<tbody>

<?php
//print items type list
for ($i=0;$i<count($fixtypes);$i++) {
  $dbid=$fixtypes[$i]['id'];
  $itype=$fixtypes[$i]['typedesc'];

if ($dbid>="0") //change this to remove X from internal types
echo "\n<tr><td title='Delete ID:$dbid'><a href='javascript:delconfirm(\"$itype\",\"$scriptname?action=edititypes&amp;deltype=$dbid\");'><img title='delete' src='images/delete.png' border=0></a></td>";
else echo "\n\n<tr><td>--</td>";

  if ($fixtypes[$i]['hassoftware']) $s="selected"; else $s="";

  echo "<td><input type='text' name='descs[]' ".
  "value=\"".$fixtypes[$i]['typedesc']."\">\n".
  "<td><select name='hassoftware[]'>".
  "<option value='0'>No</option>".
  "<option $s value='1'>Yes</option></select>";
 echo "\n<input type=hidden name='ids[]' value='$dbid' >\n";
  echo "</td></tr>";
}

if (!isset($dbid)) $dbid=0;
?>

<!--</tbody> </table> <table>-->

<tr><th>&nbsp;</th><th><?php te("Description");?></th><th><?php te("Supports<br>Software");?><sup>1</sup></th></tr>
<tr><td colspan=1><?php te("New");?>:</td><td>
     <input name='newtype' type='text'></td>
     <td><select name='newhassoftware'>
     <option value='0'><?php te("No");?></option>
     <option value='1'><?php te("Yes");?></option></select></td>
     </tr>

<tr><td style='text-align: right' colspan=4><button type="submit"><img src="images/save.png" alt="Save" > Save</button></td></tr>
<tr><td style='text-align: left' colspan=4>
      <sup>1</sup><?php te("Select 'YES' if software can be installed <b>on</b> this item.<br> Only items supporting software are listed when <br>performing software - item associations");?>
    </td></tr>
</tbody>
</table>

</form>
