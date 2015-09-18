<?php /* Cory Funk 2015, cafunk@fhsu.edu */?>

<SCRIPT LANGUAGE="JavaScript"> 

  function confirm_filled($row)
  {
	  var filled = 0;
	  $row.find('input,select').each(function() {
		  if (jQuery(this).val()) filled++;
	  });
	  if (filled) return confirm('Do you really want to remove this row?');
	  return true;
  };

 $(document).ready(function() {

    //delete table row on image click
    $('.delrow').click(function(){
        var answer = confirm("Are you sure you want to delete this row ?")
        if (answer) 
	  $(this).parent().parent().remove();
    });

    $("#caddrow").click(function($e) {
	var row = $('#contactstable tr:last').clone(true);
        $e.preventDefault();
	row.find("input:text").val("");
	row.find("img").css("display","inline");
	row.insertAfter('#contactstable tr:last');
    });
    $("#uaddrow").click(function($e) {
	var row = $('#urlstable tr:last').clone(true);
        $e.preventDefault();
	row.find("input:text").val("");
	row.find("img").css("display","inline");
	row.insertAfter('#urlstable tr:last');
    });
  });
</SCRIPT>
<?php 
if (!isset($initok)) {echo "do not run this script directly";exit;}

//error_reporting(E_ALL);
//ini_set('display_errors', '1');

$sql="SELECT * FROM users order by upper(username)";
$sth=$dbh->query($sql);
$userlist=$sth->fetchAll(PDO::FETCH_ASSOC);

//delete vlan
if (isset($_GET['delid'])) { //if we came from a post (save) the update vlan 
  $delid=$_GET['delid'];
  

  //delete entry
  $sql="DELETE from vlans where id=".$_GET['delid'];
  $sth=db_exec($dbh,$sql);

  echo "<script>document.location='$scriptname?action=listvlans'</script>";
  echo "<a href='$scriptname?action=listvlans'></a>"; 
  exit;

}


if (isset($_POST['id'])) { //if we came from a post (save) then update vlan 
  $id=$_POST['id'];

  if ($_POST['id']=="new")  {//if we came from a post (save) then add vlan 
    $sql="INSERT INTO vlans (vlanid, vlanname) VALUES ('$vlanid', '$vlanname')";
		  
    db_exec($dbh,$sql,0,0,$lastid);
    $lastid=$dbh->lastInsertId();
    print "<br><b>Added VLAN <a href='$scriptname?action=$action&amp;id=$lastid'>$lastid</a></b><br>";
    echo "<script>window.location='$scriptname?action=$action&id=$lastid'</script> "; //go to the new item
    $id=$lastid;
  }
  else {
    $sql="UPDATE vlans SET ".
       " vlanid='$vlanid',vlanname='$vlanname' WHERE id=$id";
    db_exec($dbh,$sql);
	
  echo "<script>document.location='$fscriptname?action=editvlan&id=$id'</script>";
  echo "<a href='$fscriptname?action=editvlan&id=$id'></a>"; 
  exit;
  }


}//save pressed

///////////////////////////////// display data now


if (!isset($_REQUEST['id'])) {echo "ERROR:ID not defined";exit;}
$id=$_REQUEST['id'];

$sql="SELECT * FROM vlans WHERE id='$id'";
$sth=db_execute($dbh,$sql);
$r=$sth->fetch(PDO::FETCH_ASSOC);

if ($id !="new")
$vlanid=$r['vlanid'];$vlanname=$r['vlanname'];

echo "\n<form method=post  action='$scriptname?action=$action&amp;id=$id' enctype='multipart/form-data'  name='addfrm'>\n";

if ($id=="new")
  echo "\n<h1>".t("Add VLAN")."</h1>\n";
else
  echo "\n<h1>".t("Edit VLAN $id")."</h1>\n";

?>
<table border="0" cellpadding="5" cellspacing="5" class="tbl1">

<!-- VLAN Properties Title -->
    <tr> 
      <td class='tdtop'>
        <table border='0' class="tbl2">
          
<!-- vlan Properties Title -->
      <tr>
          <td class='tdt'><?php te("VLAN ID");?>:</td>
          <td><input style="width:33em" id='vlanid' name='vlanid' value='<?php echo $vlanid?>'></input></td>
      </tr>
      <tr>
          <td class='tdt'><?php te("VLAN Name");?>:</td>
          <td><input style="width:33em" id='vlanname' name='vlanname' value='<?php echo $vlanname?>'></input></td>
      </tr>
<!-- end, vlan Properties Title -->
</table>
<table border="0" class="tbl2">
          <tr>
            <td><button type="submit"><img src="images/save.png" alt="Save" />
              <?php te("Save");?>
            </button></td>
            <?php echo "\n<td><button type='button' onclick='javascript:delconfirm2(\"{$r['id']}\",\"$scriptname?action=$action&amp;delid={$r['id']}\");'>"."<img title='Delete' src='images/delete.png' border=0>".t("Delete")."
		</button></td>\n</tr>\n";
		echo "\n</table>\n";
		echo "\n<input type=hidden name='action' value='$action'>";
		echo "\n<input type=hidden name='id' value='$id'>";
		?> </tr>
        </table></td>
      </tr>
    </table>
    </form>
</body>
</html>