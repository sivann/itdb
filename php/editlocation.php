<script>
  function ajaxify(response, status, xhr, form){
    //alert('ajaxifying');
     $('#areafrm').ajaxForm({
	'success': ajaxify,
        target: '#locareas'
     });
  }

  $(document).ready(function() {
    ajaxify(null,null,null,null);
  });

</script>

<?php 

if (!isset($initok)) {echo "do not run this script directly";exit;}

/* Spiros Ioannou 2009-2010 , sivann _at_ gmail.com */


if (isset($_GET['delid'])) { //if we came from delete
  $delid=$_GET['delid'];

  //remove file:
  $sql="SELECT * from locations where id=$delid";
  $sth=db_execute($dbh,$sql);
  $rf=$sth->fetch(PDO::FETCH_ASSOC);
  $oldfname=$rf['floorplanfn'];
  unlink($uploaddir.$oldfname);

  //delete entry
  $sql="DELETE from locations where id=$delid";
  $sth=db_exec($dbh,$sql);

  $sql="UPDATE items set locationid=0 where locationid=$delid";
  $sth=db_exec($dbh,$sql);

  echo "\n<script>document.location='$scriptname?action=listlocations'</script>";
  echo "<a href='$scriptname?action=listlocations'>Go here</a></body></html>"; 
  exit;
}


if (isset($_POST['id'])) { //if we came from a post (save), update 
  $id=$_POST['id'];
  $name=$_POST['name'];
  $floor=$_POST['floor'];


  //don't accept empty fields
  if ((empty($_POST['name']))|| empty($_POST['floor']) ) {
    echo "<br><b>".t("Some <span class='mandatory'> mandatory</span> fields are missing").".</b><br>".
         "<a href='javascript:history.go(-1);'>Go back</a></body></html>";
    exit;
  }

  if ($_POST['id']=="new")  {//if we came from a post (save) the add software 
    if (strlen($_FILES['file']['name'])>2) { //insert file
      $filefn=strtolower("floorplan-".validfn($name)."-$unique.$fileext");
      $uploadfile = $uploaddir.$filefn;
      $result = '';

      //Move the file from the stored location to the new location
      if (!move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
	  $result = "Cannot upload the file '".$_FILES['file']['name']."'"; 
	  if(!file_exists($uploaddir)) {
	      $result .= " : Folder doesn't exist.";
	  } elseif(!is_writable($uploaddir)) {
	      $result .= " : Folder not writable.";
	  } elseif(!is_writable($uploadfile)) {
	      $result .= " : File not writable.";
	  }
	  $filefn = '';

	  echo "<br><b>ERROR: $result</b><br>";
      }
      else { //file ok

	  $sql="INSERT into locations (name,floor,floorplanfn)".
	       " VALUES ('$name','$floor','$filefn')";
	  db_exec($dbh,$sql,0,0,$lastid);
	  $lastid=$dbh->lastInsertId();
	  print "<br><b>Added Location <a href='$scriptname?action=$action&amp;id=$lastid'>$lastid</a></b><br>";
	  echo "<script>window.location='$scriptname?action=$action&id=$lastid'</script> "; //go to the new item
	  echo "\n</body></html>";
	  $id=$lastid;
	  exit;

	}

    }//insert file
    else { //new and no file defined
	  $sql="INSERT into locations (name,floor)".
	       " VALUES ('$name','$floor')";
	  db_exec($dbh,$sql,0,0,$lastid);
	  $lastid=$dbh->lastInsertId();
	  print "<br><b>Added Location <a href='$scriptname?action=$action&amp;id=$lastid'>$lastid</a></b><br>";
	  echo "<script>window.location='$scriptname?action=$action&id=$lastid'</script> "; //go to the new item
	  echo "\n</body></html>";
	  $id=$lastid;
	  exit;
      echo "<br><b>No file uploaded.</b><br>";
    }
  }//new location
  else {
    $sql="UPDATE locations set name='$name', floor='$floor' ".
       " WHERE id=$id";
    db_exec($dbh,$sql);

    if (strlen($_FILES['file']['name'])>2) { //update file
      $sql="SELECT * from locations where id=$id";
      $sth=db_execute($dbh,$sql);
      $rf=$sth->fetch(PDO::FETCH_ASSOC);
      $oldfname=$rf['floorplanfn'];

      $path_parts = pathinfo($_FILES['file']["name"]);
      $fileext=$path_parts['extension'];
      $ftypestr=ftype2str($_POST['type'],$dbh);
      $unique=substr(uniqid(),-4,4);

      $filefn=strtolower("floorplan-".validfn($name)."-$unique.$fileext");
      $uploadfile = $uploaddir.$filefn;
      $result = '';

      //Move the file from the stored location to the new location
      if (!move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
	  $result = "Cannot upload the file '".$_FILES['file']['name']."'"; 
	  if(!file_exists($uploaddir)) {
	      $result .= " : Folder doesn't exist.";
	  } elseif(!is_writable($uploaddir)) {
	      $result .= " : Folder not writable.";
	  } elseif(!is_writable($uploadfile)) {
	      $result .= " : File not writable.";
	  }
	  $filefn = '';

	  echo "<br><b>ERROR: $result</b><br>";
      }
      else {
	$sql="UPDATE locations set floorplanfn='$filefn' WHERE id=$id";
	db_exec($dbh,$sql);

	//delete   $oldfname;
	if (strlen($oldfname)) {
	  unlink($uploaddir.$oldfname);
        }
      }
    }//update file

  }//not new

}//save pressed

/////////////////////////////
//// display data now

if (!isset($_REQUEST['id'])) {echo "ERROR:ID not defined";exit;}
$id=$_REQUEST['id'];

$sql="SELECT * FROM locations where  locations.id='$id'";
$sth=db_execute($dbh,$sql);
$r=$sth->fetch(PDO::FETCH_ASSOC);

if (($id !="new") && (count($r)<3)) {echo "ERROR: non-existent ID<br>($sql)";exit;}
echo "\n<form method=post  action='$scriptname?action=$action&amp;id=$id' enctype='multipart/form-data'  name='addfrm'>\n";

?>

<?php 
if ($id=="new")
  echo "\n<h1>Add Location</h1>\n";
else
  echo "\n<h1>Edit Location</h1>\n";

?>
<table style='width:100%' border=0>

<tr>
<td class="tdtop" style='width:35em;'>
    <table class="tbl2" style='width:300px;'>
    <tr><td colspan=2><h3><?php te("Location Properties");?></h3></td></tr>
    <tr><td class="tdt"><?php te("ID");?>:</td> <td><input  class='input2' type=text name='id' value='<?php echo $id?>' readonly size=3></td></tr>
    <tr><td class="tdt"><?php te("Building Name");?>:</td> <td><input  class='input2 mandatory' size=20 type=text name='name' value="<?php echo $r['name']?>"></td></tr>
    <tr><td class="tdt"><?php te("Floor");?>:</td> <td><input  class='input2 mandatory' size=20 type=text name='floor' value="<?php echo $r['floor']?>"></td></tr>
    <tr><td class="tdt"><?php te("Filename");?>:</td><td><a target=_blank href="<?php  echo $uploaddirwww.$r['floorplanfn']; ?>"><?php echo $r['floorplanfn']?></a></td></tr>
    <tr><td title="Number of items/software/invoices/etc which reference this file" 
            class="tdt"><?php te("Associations (items/racks)");?>:</td> <td><b><?php  if ($_GET['id']!="new") echo countloclinks($_GET['id'],$dbh);?></b></td></tr>
    </table>

    <table class="tbl2" width='90%'>
    <tr><td colspan=2 colspan=2><h3>
      <?php 
      if ($id=="new") {
	$tip="";
	echo t("Upload a Floor Plan");
      }
      else{
	$tip=t("If you select a new file, it will replace the current one, <br>while keeping its associations.");
	echo t("Replace Floor Plan");
      }
      ?>
    </h3></td></tr>
    <!-- file upload -->
    <tr> 
      <td class="tdt"><?php te("Floor Plan");?>:</td> <td><input name="file" id="file" size="25" type="file"></td>
    </tr>
    </table>
<?php echo $tip?>


    <h3><?php te("Associations Overview");?></h3>
    <div style='text-align:center'>
      <span class="tita" onclick='showid("items");'>Items</span>
    </div>

    <div class="scrltblcontainer4" style='height:40ex' >

      <div  id='items' class='relatedlist'><?php te("ITEMS");?></div>
      <?php 
      if (is_numeric($id)) {
	$sql="SELECT items.id, agents.title || ' ' || items.model || ' [' || itemtypes.typedesc || ', ID:' || items.id || ']' as txt ".
	     "FROM agents,items,itemtypes WHERE ".
	     " agents.id=items.manufacturerid AND items.itemtypeid=itemtypes.id AND ".
	     " locationid=$id";
	$sthi=db_execute($dbh,$sql);
	$ri=$sthi->fetchAll(PDO::FETCH_ASSOC);
	$nitems=count($ri);
	$institems="";
	for ($i=0;$i<$nitems;$i++) {
	  $x=($i+1).": ".$ri[$i]['txt'];
	  if ($i%2) $bcolor="#D9E3F6"; else $bcolor="#ffffff";
	  $institems.="\t<div style='margin:0;padding:0;background-color:$bcolor'>".
		      "<a href='$scriptname?action=edititem&amp;id={$ri[$i]['id']}'>$x</a></div>\n";
	}
	echo $institems;
      }
      ?>
    </div>

  <br>

  <button type="submit"><img src="images/save.png" alt="Save"> <?php te("Save");?></button>
  <?php 
  echo "\n<button type='button' onclick='javascript:delconfirm2(\"{$r['id']}\",\"$scriptname?action=$action&amp;delid=$id\");'>".
     "<img title='delete' src='images/delete.png' border=0> ".t("Delete")." </button>\n";
  ?>

  <input type=hidden name='action' value='<?php echo $action?>'>
  <input type=hidden name='id' value='<?php echo $id?>'>
  </form>

</td>

<td class="tdtop">
<h3><?php te("Areas: rooms, offices");?></h3>
  <div class='scrltblcontainer4'  id='locareas'>

  
  <?php  //include ajax form which returns itself updated
  if ($id!="new") {
   require('php/locareas.php');
  }
  else {
    echo t("Save new location first to define areas onto it");

  }
  ?>

  </div>
</td> <!-- upload -->

<td>
<?php 
if (strlen($r['floorplanfn'])) {?>
<img width=600 src='<?php  echo $fuploaddirwww.$r['floorplanfn']; ?>'>
<?php  }?>

</td>


</tr>
</table>


</body>
</html>
