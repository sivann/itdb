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

/* Spiros Ioannou 2009-2010 , sivann _at_ gmail.com */

//delete agent
if (isset($_GET['delid'])) { //if we came from a post (save) the update agent 
  $delid=$_GET['delid'];
  

  //delete entry
  $sql="DELETE from agents where id=".$_GET['delid'];
  $sth=db_exec($dbh,$sql);

  $sql="UPDATE items SET manufacturerid='' where manufacturerid=$delid";
  db_exec($dbh,$sql);

  $sql="UPDATE invoices SET vendorid='' WHERE vendorid=$delid";
  db_exec($dbh,$sql);

  $sql="UPDATE invoices SET buyerid='' where buyerid=$delid";
  db_exec($dbh,$sql);

  $sql="UPDATE software SET manufacturerid='' where manufacturerid=$delid";
  db_exec($dbh,$sql);

  echo "<script>document.location='$scriptname?action=listagents'</script>";
  echo "<a href='$scriptname?action=listagents'>Go here</a></body></html>"; 
  exit;

}


if (isset($_POST['id'])) { //if we came from a post (save) then update agent 
  $id=$_POST['id'];

  //accumulate power of two values for types
  if (!empty($_POST['types'])){
    foreach ($_POST['types'] as $t)
      $type+=(int)$t;
  }

  $row=array();
  $crows=count($_POST['cont_name']);
  for ($i=0;$i<$crows;$i++) {
  
    $_POST['cont_name'] = preg_replace('/[\|#]/', ' ', $_POST['cont_name']);
    $_POST['cont_phones'] = preg_replace('/[\|#]/', ' ', $_POST['cont_phones']);
    $_POST['cont_email'] = preg_replace('/[\|#]/', ' ', $_POST['cont_email']);
    $_POST['cont_role'] = preg_replace('/[\|#]/', ' ', $_POST['cont_role']);
    $_POST['cont_comments'] = preg_replace('/[\|#]/', ' ', $_POST['cont_comments']);

    $row[$i]=implode("#",array(
      $_POST['cont_name'][$i],
      $_POST['cont_phones'][$i],
      $_POST['cont_email'][$i],
      $_POST['cont_role'][$i],
      $_POST['cont_comments'][$i]));
  }
  $contacts=implode("|",$row);



  $row=array();
  $urows=count($_POST['url_url']);
  for ($i=0;$i<$urows;$i++) {
    $_POST['url_url'] = preg_replace('/[\|#]/', ' ', $_POST['url_url']);
    $_POST['url_description'] = preg_replace('/[\|#]/', ' ', $_POST['url_description']);
    $row[$i]=implode("#",array(
      $_POST['url_description'][$i],
      $_POST['url_url'][$i]));
  }
  $urls=implode("|",$row);


  $title=$_POST['title'];
  $contactinfo=$_POST['contactinfo'];
  //don't accept empty fields
  if ((!strlen($title))) {
    echo "<br><b>Name and type are missing.</b><br><a href='javascript:history.go(-1);'>Go back</a></body></html>"; 
    exit;
  }

  if ($_POST['id']=="new")  {//if we came from a post (save) then add agent 
    $sql="INSERT into agents (type,title,contactinfo,contacts,urls)".
         " VALUEs ('$type','$title','$contactinfo','$contacts','$urls')";
    db_exec($dbh,$sql,0,0,$lastid);
    $lastid=$dbh->lastInsertId();
    print "<br><b>Added Agent <a href='$scriptname?action=$action&amp;id=$lastid'>$lastid</a></b><br>";
    echo "<script>window.location='$scriptname?action=$action&id=$lastid'</script> "; //go to the new item
    $id=$lastid;
  }
  else {
    $sql="UPDATE agents SET type='$type', title='$title', ".
       " contactinfo='$contactinfo', contacts='$contacts', urls='$urls' WHERE id=$id";
    db_exec($dbh,$sql);
  }


}//save pressed

/////////////////////////////
//// display data now


if (!isset($_REQUEST['id'])) {echo "ERROR:ID not defined";exit;}
$id=$_REQUEST['id'];

$sql="SELECT * FROM agents WHERE id='$id'";
$sth=db_execute($dbh,$sql);
$r=$sth->fetch(PDO::FETCH_ASSOC);
if (($id !="new") && (count($r)<5)) {echo "ERROR: non-existent ID";exit;}

$type=$r['type'];$title=$r['title'];$contactinfo=$r['contactinfo'];$contacts=$r['contacts'];$urls=$r['urls'];

echo "\n<form method=post  action='$scriptname?action=$action&amp;id=$id' enctype='multipart/form-data'  name='addfrm'>\n";

if ($id=="new")
  echo "\n<h1>".t("Add Agent")."</h1>\n";
else
  echo "\n<h1>".t("Edit Agent")."</h1>\n";

?>


<table >


<tr>
<td class="tdtop">
    <h3><?php te("Agent Properties");?></h3>
    <table border=0 class="tbl2" width='100%'>
    <tr><td class="tdt"><?php te("ID");?>:</td> <td><input  class='input1' type=text name='id' value='<?php echo $id?>' readonly size=3></td></tr>
    <tr><td class="tdt"><?php te("Name");?>:</td> <td><input  class='input1 mandatory' size=20 type=text name='title' value="<?php echo $title?>"></td></tr>
    <tr><td class="tdt"><?php te("Type(s)");?>:</td> 
        <td title='<?php te("Cntrl+Click to select multiple roles for an agent ".
                   "<br><br><u>Vendor &amp; Buyer</u>: will be listed in invoices &amp; Contracts ".
                   "<br><br><u>H/W Manuf.</u>: will be listed in items editing ".
                   "<br><br><u>S/W Manuf.</u>: will be listed in software editing ".
                   "<br><br><u>Contractor</u>: will be listed in contracts");?>'>
    <select class='mandatory' multiple size=5 name='types[]'>
    <?php 
    if (empty($type)) $type=0;
    $s1=($type&1)?"SELECTED":"";
    $s2=($type&2)?"SELECTED":"";
    $s4=($type&4)?"SELECTED":"";
    $s8=($type&8)?"SELECTED":"";
    $s16=($type&16)?"SELECTED":"";
    ?>

    <?php  // ONLY POWER OF 2 VALUES HERE ?>
    <option <?php echo $s4?> value='4'><?php te("Vendor");?></option>
    <option <?php echo $s2?> value='2'><?php te("S/W Manufacturer");?></option>
    <option <?php echo $s8?> value='8'><?php te("H/W Manufacturer");?></option>
    <option <?php echo $s1?> value='1'><?php te("Buyer");?></option>
    <option <?php echo $s16?> value='16'><?php te("Contractor");?></option>
    </select>
    </td></tr>
    </table>
</td>

<td class="tdtop" title='<?php te("Address, Phone number, other info, etc");?>' >
  <h3><?php te("Contact Info");?></h3>
   <textarea name='contactinfo' style='height: 90px;width:550px;' wrap='soft'><?php echo $contactinfo?></textarea> 
</td>
</tr>


<!-- relevant item & software links -->
<tr> 
<td  style='vertical-align:top;' rowspan=2>
  <h3> Related: </h3>
  <div>
    <span class="tita" onclick='showid("items");'><?php te("Items");?></span>  ,
    <span class="tita" onclick='showid("software");'><?php te("Software");?></span>  ,
    <span class="tita" onclick='showid("invoices1");'><?php te("Invoices (vendors)");?></span>  ,
    <span class="tita" onclick='showid("invoices2");'><?php te("Invoices (buyers)");?></span> 
  </div>

  <div class="scrltblcontainer4">

  <div  id='items' class='relatedlist'><?php te("ITEMS");?></div>
  <?php 
  if (is_numeric($id)) {
    //print a table row
    $sql="SELECT items.id, items.model, items.dnsname FROM items WHERE manufacturerid='$id'";
    $sthi=db_execute($dbh,$sql);
    $ri=$sthi->fetchAll(PDO::FETCH_ASSOC);
    $nitems=count($ri);
    $institems="";
    for ($i=0;$i<$nitems;$i++) {
      $x=($i+1).": ({$ri[$i]['id']}) ".$ri[$i]['model']." ".$ri[$i]['dnsname'];
      if ($i%2) $bcolor="#D9E3F6"; else $bcolor="#ffffff";
      $institems.="\t<div style='margin:0;padding:0;background-color:$bcolor'>".
		  "<a href='$scriptname?action=edititem&amp;id={$ri[$i]['id']}'>$x</a></div>\n";
    }
    echo $institems;
  }
  ?>

  <div  id='software' class='relatedlist'><?php te("SOFTWARE");?></div>
  <?php 
  if (is_numeric($id)) {
    //print a table row
    $sql="SELECT software.id,software.stitle  FROM software WHERE manufacturerid='$id'";
    $sthi=db_execute($dbh,$sql);
    $ri=$sthi->fetchAll(PDO::FETCH_ASSOC);
    $nitems=count($ri);
    $institems="";
    for ($i=0;$i<$nitems;$i++) {
      $x=($i+1).": ({$ri[$i]['id']}) ".$ri[$i]['stitle']." ".$ri[$i]['dnsname'];
      if ($i%2) $bcolor="#D9E3F6"; else $bcolor="#ffffff";
      $institems.="\t<div style='margin:0;padding:0;background-color:$bcolor'>".
		  "<a href='$scriptname?action=editsoftware&amp;id={$ri[$i]['id']}'>$x</a></div>\n";
    }
    echo $institems;
  }
  ?>

  <div id='invoices1' class='relatedlist'><?php te("INVOICES (vendor)");?></div>
  <?php 
  if (is_numeric($id)) {
    //print a table row
    $sql="SELECT invoices.id, invoices.number, invoices.date FROM invoices WHERE vendorid='$id'";
    $sthi=db_execute($dbh,$sql);
    $ri=$sthi->fetchAll(PDO::FETCH_ASSOC);
    $nitems=count($ri);
    $institems="";
    for ($i=0;$i<$nitems;$i++) {
      $d=strlen($ri[$i]['date'])?date($dateparam,$ri[$i]['date']):"";
      $x=($i+1).": ({$ri[$i]['id']})  ({$ri[$i]['number']}) - $d";
      if ($i%2) $bcolor="#D9E3F6"; else $bcolor="#ffffff";
      $institems.="\t<div style='margin:0;padding:0;background-color:$bcolor'>".
		  "<a href='$scriptname?action=editinvoice&amp;id={$ri[$i]['id']}'>$x</a></div>\n";
    }
    echo $institems;
  }
  ?>

  <div id='invoices2' class='relatedlist'><?php te("INVOICES (buyer)");?></div>
  <?php 
  if (is_numeric($id)) {
    //print a table row
    $sql="SELECT invoices.id, invoices.number, invoices.date FROM invoices WHERE buyerid='$id'";
    $sthi=db_execute($dbh,$sql);
    $ri=$sthi->fetchAll(PDO::FETCH_ASSOC);
    $nitems=count($ri);
    $institems="";
    for ($i=0;$i<$nitems;$i++) {
      $d=strlen($ri[$i]['date'])?date($dateparam,$ri[$i]['date']):"";
      $x=($i+1).": ({$ri[$i]['id']})  ({$ri[$i]['number']}) - $d ";
      if ($i%2) $bcolor="#D9E3F6"; else $bcolor="#ffffff";
      $institems.="\t<div style='margin:0;padding:0;background-color:$bcolor'>".
		  "<a href='$scriptname?action=editinvoice&amp;id={$ri[$i]['id']}'>$x</a></div>\n";
    }
    echo $institems;
  }
  ?>


  
  </div><!-- scrlbcontainer -->
</td> 
<td><h3> <?php te("Contacts");?> <img id='caddrow' src='images/add.png' title="<?php te("Add Row");?>"> </h3>
  <div class="scrltblcontainer3">
	<table class=tbl2 id="contactstable">
	<tr> 
	    <th>-</th> 
	    <th><?php te("Name");?></th> 
	    <th><?php te("Phone numbers");?></th> 
	    <th><?php te("Email");?></th> 
	    <th><?php te("Role");?></th> 
	    <th><?php te("Comments");?></th> 
	</tr> 

	<?php 
	$allcontacts=explode("|",$contacts);
	for ($i=0;$i<count($allcontacts);$i++) {
	  $row=explode("#",$allcontacts[$i]);
	  $name=$row[0];
	  $phones=$row[1];
	  $email=$row[2];
	  $role=$row[3];
	  $comments=$row[4];
	?>
	  <tr> 
	      <td><img <?php  if (!$i) echo "style='display:none'";?> class='delrow' src='images/delete.png'></td>
	      <td><input type="text" name="cont_name[]" size="15" value='<?php echo $name?>' ></td> 
	      <td><input type="text" name="cont_phones[]" size="15"  value='<?php echo $phones?>'></td> 
	      <td><input type="text" name="cont_email[]" size="15" value='<?php echo $email?>'></td> 
	      <td><input type="text" name="cont_role[]" size="15"  value='<?php echo $role?>'></td> 
	      <td><textarea name="cont_comments[]" size="20" ><?php echo $comments?></textarea></td> 
	  </tr> 
	<?php 
	}
	?>


	</table><br>

  </div><!-- scrlbcontainer -->


</td>

</tr>
<tr>

<td class="tdtop">
  <h3>URLs <img src='images/add.png'  id='uaddrow' title="<?php te("Add Row");?>"> </h3>
  <div class="scrltblcontainer3">
	<table class=tbl2 id="urlstable">
	<tr> 
	    <th>-</th> 
	    <th><?php te("Description");?></th> 
	    <th>URL</th> 
	    <th>LINK</th> 
	</tr> 

	<?php 
	$allurls=explode("|",$urls);
	for ($i=0;$i<count($allurls);$i++) {
	  $row=explode("#",$allurls[$i]);
	  $description=$row[0];
	  $url=urldecode($row[1]);
	?>
	  <tr> 
	      <td><img <?php  if (!$i) echo "style='display:none'";?> class='delrow' src='images/delete.png'></td>
	      <td><input type="text" name="url_description[]" size="25" value='<?php echo $description?>' ></td> 
	      <td><input type="text" name="url_url[]" size="60"  value='<?php echo $url?>'></td> 
	      <td><a target="_blank" href='<?php echo $url?>'><?php te("GO");?></a></td> 
	  </tr> 
	<?php 
	}
	?>
	</table><br>
	<sup>*</sup>
	<?php te("Use the string 'service'  on the description to display this url on the item edit page");?>
</td>
</tr>

<tr><td ><button type="submit"><img src="images/save.png" alt="Save" > <?php te("Save");?></button></td>
<?php 
echo "\n<td><button type='button' onclick='javascript:delconfirm2(\"{$r['id']}\",\"$scriptname?action=$action&amp;delid={$r['id']}\");'>".
     "<img title='Delete' src='images/delete.png' border=0>".t("Delete")."</button></td>\n</tr>\n";
echo "\n</table>\n";
echo "\n<input type=hidden name='action' value='$action'>";
echo "\n<input type=hidden name='id' value='$id'>";
?>

</form>
</body>
</html>
