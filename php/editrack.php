<SCRIPT LANGUAGE="JavaScript"> 

$(document).ready(function() {

  $("#tabs").tabs();
  $("#tabs").show();

    $("#locationid").change(function() {
      var locationid=$(this).val();
      var dataString = 'locationid='+ locationid;
      $.ajax ({
          type: "POST",
          url: "php/locarea_options_ajax.php",
          data: dataString,
          cache: false,
          success: function(html) {
            $("#locareaid").html(html);
          }
      });
    });

});


</SCRIPT>
<?php 

if (!isset($initok)) {echo "do not run this script directly";exit;}

/* Spiros Ioannou 2009-2010 , sivann _at_ gmail.com */

$sql="SELECT * FROM locations order by name";
$sth=$dbh->query($sql);
$locations=$sth->fetchAll(PDO::FETCH_ASSOC);



//delete rack
if (isset($_GET['delid'])) { 
  $delid=$_GET['delid'];
  if (!is_numeric($delid)) {
    echo "Non numeric id delid=($delid)";
    exit;
  }

  //first handle rack associations
  $nitems=countitemsinrack($delid);
  if ($nitems>0) {
    echo "<b>Rack not deleted: Please remove items first from this rack<br></b>\n";
    echo "<br><a href='javascript:history.go(-1);'>Go back</a>\n</body></html>";
    exit;
  }
  else {
    delrack($delid,$dbh);
    echo "<script>document.location='$scriptname?action=listracks'</script>\n";
    echo "<a href='$scriptname?action=listracks'>Go here</a>\n</body></html>"; 
    exit;
  }

}

if (isset($_POST['id'])) { //if we came from a post (save), update the rack 
  $id=$_POST['id'];
  $title=$_POST['title'];
  $type=$_POST['type'];
  $date=ymd2sec($_POST['date']);


  //don't accept empty fields
  if ((empty($_POST['usize']))||  (empty($_POST['depth'])))  {
    echo "<br><b>Some <span class='mandatory'> mandatory</span> fields are missing.</b><br>".
         "<a href='javascript:history.go(-1);'>Go back</a></body></html>";
    exit;
  }


  if ($_POST['id']=="new")  {//if we came from a post (save) the add software 

    $sql="INSERT into racks (locationid , usize , depth , comments,model,label, revnums , locareaid) ".
	 " VALUES ('$locationid','$usize','$depth','$comments','$model','$label','$revnums','$locareaid')";
    db_exec($dbh,$sql,0,0,$lastid);
    $lastid=$dbh->lastInsertId();
    print "<br><b>Added Rack <a href='$scriptname?action=$action&amp;id=$lastid'>$lastid</a></b><br>";
    echo "<script>window.location='$scriptname?action=$action&id=$lastid'</script> "; //go to the new rack
    echo "\n</body></html>";
    $id=$lastid;
    exit;

  }//new rack
  else {
    $sql="UPDATE racks set ".
      " locationid='".$_POST['locationid']."', ".
      " locareaid='".$_POST['locareaid']."', ".
      " usize='".$_POST['usize']."', ".
      " revnums='".$_POST['revnums']."', ".
      " depth='".$_POST['depth']."', ".
      " model='".($_POST['model'])."', ".
      " comments='".($_POST['comments'])."' , ".
      " label='".($_POST['label'])."' ".
      " WHERE id=$id";

    db_exec($dbh,$sql);
  }//not new-update

  //update item locations to point to rack location
  $sql="UPDATE items set locationid='".$_POST['locationid']."', locareaid='".$_POST['locareaid']."' WHERE items.rackid=$id";
  db_exec($dbh,$sql);
  te("Location of items in this rack was updated to match rack location");

}//save pressed

/////////////////////////////
//// display data 


if (!isset($_REQUEST['id'])) {echo "ERROR:ID not defined";exit;}
$id=$_REQUEST['id'];

//$sql="SELECT * FROM racks where racks.id='$id'";
$sql="SELECT count(items.id) AS population, sum(items.usize) as occupation,racks.* FROM racks LEFT OUTER JOIN items ON items.rackid=racks.id WHERE racks.id='$id'";
$sth=db_execute($dbh,$sql);
$r=$sth->fetch(PDO::FETCH_ASSOC);

if (($id !="new") && (count($r)<2)) {echo "ERROR: non-existent ID<br>($sql)";exit;}
echo "\n<form id='mainform' method=post  action='$scriptname?action=$action&amp;id=$id' enctype='multipart/form-data'  name='addfrm'>\n";

?>



<?php 
if ($id=="new")
  echo "\n<h1>".t("Add Rack")."</h1>\n";
else
  echo "\n<h1>".t("Edit Rack")."  ($id)"."</h1>\n";

?>

<!-- error errcontainer -->
<div class='errcontainer ui-state-error ui-corner-all' style='padding: 0 .7em;width:700px;margin-bottom:3px;'>
        <p><span class='ui-icon ui-icon-alert' style='float: left; margin-right: .3em;'></span>
        <h4><?php te("There are errors in your form submission, please see below for details");?>.</h4>
        <ol>
                <li><label for="usize" class="error"><?php te("Rack height is missing");?></label></li>
                <li><label for="depth" class="error"><?php te("Rack depth is missing");?></label></li>
                <li><label for="label" class="error"><?php te("Rack label is missing");?></label></li>
                <li><label for="locationid" class="error"><?php te("Rack location is missing");?></label></li>
        </ol>
</div>

<table style='width:100%' border=0>


<tr>
<td class="tdtop" width=20%>

    <table class="tbl2" style='width:300px;'>
    <tr><td colspan=2><h3>File Properties</h3></td></tr>
    <tr><td class="tdt">ID:</td> <td><input  style='display:none' type=text name='id' value='<?php echo $id?>' readonly size=3><?php echo $id?></td></tr>
    <tr><td class="tdt"><?php te("Height (U)")?></td><td>
    <select class='mandatory' validate='required:true' name='usize'>
<?php 
    echo "\n<option  value=''>".t("Select")."</option>";
    for ($s=50;$s>3;$s--) {
      if ($s==$r['usize']) $sel="selected"; else $sel="";
      echo "<option $sel value='$s'>".$s."U</option>\n";
    }
?>
    </select>
    </td></tr>

    <tr><td class="tdt"><?php te("Numbering")?></td><td>
    <select name='revnums'>
<?php
    if ($r['revnums']==1) {
      $s0="";$s1="selected";
    }
    else {
      $s0="selected"; $s1="";
    }
    echo "<option $s0 value='0'>1=Bottom</option>\n";
    echo "<option $s1 value='1'>1=Top</option>\n";
?>
    </select>
    </td>
    </tr>

    <tr><td class="tdt"><?php te("Label");?>:</td> 
        <td><input  class='input2 mandatory' validate='required:true' size=20 type=text name='label' value="<?php echo $r['label']?>"></td></tr>
    <tr><td class="tdt"><?php te("Depth");?>(mm):</td> 
        <td><input  class='input2 mandatory' validate='required:true' size=20 type=text name='depth' value="<?php echo $r['depth']?>"></td></tr>
    <tr><td class="tdt"><?php te("Model");?>:</td> 
        <td><input  class='input2 mandatory' size=20 type=text name='model' value="<?php echo $r['model']?>"></td></tr>
    <tr><td class="tdt"><?php te("Comments");?>:</td> 
        <td><textarea class='tarea1' wrap=soft name=comments><?php echo $r['comments']?></textarea></td></tr>
    <tr><td class="tdt"><?php te("Location");?>:</td> 

    <td>
      <select id='locationid' name='locationid' validate='required:true'>
      <option value=''>Select</option>
      <?php
      $locationid=$r['locationid'];
      foreach ($locations  as $key=>$location ) {
	$dbid=$location['id'];

    if (is_numeric($location['floor']))
            $itype=$location['name'].", ".t("Floor").":".$location['floor'];
    else
            $itype=$location['name'];
	$s="";
	if (($locationid=="$dbid")) $s=" SELECTED ";
	echo "    <option $s value='$dbid'>$itype</option>\n";
      }
      ?>
      </select>
    </td>
    </tr>

    <tr><td class="tdt"><?php te("Area");?>:</td><td>
<?php
    if (is_numeric($locationid)) {
      $sql="SELECT id,areaname FROM locareas WHERE locationid=$locationid order by areaname";
      $stha=$dbh->query($sql);
      $locareas=$stha->fetchAll(PDO::FETCH_ASSOC);
    }
    else {
      $locareas=array();
    }
?>
      <select id='locareaid' name='locareaid'>
	<option value=''>Select</option>
	<?php
	$locareaid=$r['locareaid'];
	foreach ($locareas  as $key=>$locarea ) {
	  $dbid=$locarea['id'];
	  $name=$locarea['areaname'];
	  $s="";
	  if (($locareaid=="$dbid")) $s=" SELECTED ";
	  echo "    <option $s value='$dbid'>$name</option>\n";
	}
	?>
      </select>
    </td>
    </tr>
    <tr><td class="tdt"><?php te("Items");?>:</td> <td><?php echo $r['population']?></td>
    <tr>
       <?php $occupation=(int)$r['occupation'];
	     if ($id!="new")
	       $width=(int)($occupation/$r['usize']*100/(100/150));
	      else 
	        $width=0;
       ?>
       <td class='tdt'><?php te("Occupation");?></td>
       <td title='<?php echo $occupation?> U occupied'>
	 <div style='width:150px;border:1px solid #888;padding:0;'>
	 <div style='background-color:#8ECE03;width:<?php echo $width?>px'>&nbsp;</div></div>
       </td>
    </tr>
    </table>
<?php

?>
</td>

<td class='smallrack' style='padding-left:10px;border-left:1px dashed #aaa'>
  <?php
  if ($id!="new")
    include('viewrack.php');
  ?>
</td>
</tr>


<tr>
<td colspan=2>
<button type="submit"><img src="images/save.png" alt="Save"> <?php te("Save");?></button>
<?php 
echo "\n<button type='button' onclick='javascript:delconfirm2(\"{$r['id']}\",\"$scriptname?action=$action&amp;delid=$id\");'>".
     "<img title='delete' src='images/delete.png' border=0>".t("Delete"). "</button>\n";
?>

</td>
</tr>


</table>

<input type=hidden name='id' value='<?php echo $id ?>'>
<input type=hidden name='action' value='<?php echo $action ?>'>

</form>

</body>
</html>
