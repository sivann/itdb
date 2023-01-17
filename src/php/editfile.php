<SCRIPT LANGUAGE="JavaScript"> 

$(document).ready(function() {

  $("#tabs").tabs();
  $("#tabs").show();
  $(function () {
   $('input#itemsfilter').quicksearch('table#itemslisttbl tbody tr');
   $('input#softwarefilter').quicksearch('table#softwarelisttbl tbody tr');
   $('input#contractsfilter').quicksearch('table#contractslisttbl tbody tr');
   });

});


</SCRIPT>
<?php 

if (!isset($initok)) {echo "do not run this script directly";exit;}

/* Spiros Ioannou 2009-2010 , sivann _at_ gmail.com */


$sql="SELECT * FROM filetypes order by typedesc";
$sth=db_execute($dbh,$sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $filetypes[$r['id']]=$r;


//delete file
if (isset($_GET['delid'])) { //if we came from a post (save) the update file 
  $delid=$_GET['delid'];
  if (!is_numeric($delid)) {
    echo "Non numeric id delid=($delid)";
    exit;
  }

  //first handle file associations
  $nlinks=countfileidlinks($delid,$dbh);
  if ($nlinks>0) {
    echo "<b>File not deleted: Please remove associations first for this file<br></b>";
    echo "<br><a href='javascript:history.go(-1);'>Go back</a></body></html>";
    echo "\n</body>\n</html>";
    exit;
  }
  else {
    delfile($delid,$dbh);
    echo "<script>document.location='$scriptname?action=listfiles'</script>";
    echo "<a href='$scriptname?action=listfiles'>Go here</a></body></html>"; 
    exit;
  }

}

if (isset($_POST['id'])) { //if we came from a post (save), update the file 
  $id=$_POST['id'];
  $title=$_POST['title'];
  $type=$_POST['type'];
  $date=ymd2sec($_POST['date']);


  //don't accept empty fields
  if ((empty($_POST['title']))|| ($_POST['type']<1) || (empty($_POST['date'])))  {
    echo "<br><b>Some <span class='mandatory'> mandatory</span> fields are missing.</b><br>".
         "<a href='javascript:history.go(-1);'>Go back</a></body></html>";
    exit;
  }


  if ($_POST['id']=="new")  {//if we came from a post (save) the add software 

    if (strlen($_FILES['file']['name'])>2) { //insert file
      $path_parts = pathinfo($_FILES['file']["name"]);
      $fileext=$path_parts['extension'];
      $ftypestr=ftype2str($_POST['type'],$dbh);
      $unique=substr(uniqid(),-4,4);

      $filefn=strtolower("$ftypestr-".validfn($title)."-$unique.$fileext");
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

	  $sql="INSERT into files (title,type,fname,uploader,uploaddate,date)".
	       " VALUES ('$title','$type','$filefn','{$userdata[0]['username']}','".time()."','$date')";
	  db_exec($dbh,$sql,0,0,$lastid);
	  $lastid=$dbh->lastInsertId();
	  print "<br><b>Added File <a href='$scriptname?action=$action&amp;id=$lastid'>$lastid</a></b><br>";
	  echo "<script>window.location='$scriptname?action=$action&id=$lastid'</script> "; //go to the new item
	  echo "\n</body></html>";
	  $id=$lastid;
	  exit;
	}

    }//insert file
    else {
      echo "<br><b>No file uploaded.</b><br>".
	   "<a href='javascript:history.go(-1);'>Go back</a></body></html>";
      exit;
    }

  }//new file
  else {
    $sql="UPDATE files set title='$title', type='$type', uploader='{$userdata[0]['username']}', uploaddate='".time()."', ".
       " date='$date' WHERE id=$id";
    db_exec($dbh,$sql);

    if (strlen($_FILES['file']['name'])>2) { //update file
      $sql="SELECT * from files where id=$id";
      $sth=db_execute($dbh,$sql);
      $r=$sth->fetch(PDO::FETCH_ASSOC);
      $oldfname=$r['fname'];

      $path_parts = pathinfo($_FILES['file']["name"]);
      $fileext=$path_parts['extension'];
      $ftypestr=ftype2str($_POST['type'],$dbh);
      $unique=substr(uniqid(),-4,4);

      $filefn=strtolower("$ftypestr-".validfn($title)."-$unique.$fileext");
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
	$sql="UPDATE files set fname='$filefn' WHERE id=$id";
	db_exec($dbh,$sql);

	//delete   $oldfname;
	if (strlen($oldfname))
	  unlink($uploaddir.$oldfname);
      }
    }//update file

  }//not new

  /* Redefine associations here */

//echo "<pre>"; print_r($_REQUEST); echo "</pre>";

  //update item - file links 
  //remove old links for this object
  $sql="delete from item2file where fileid=$id";
  db_exec($dbh,$sql);
  //add new links for each checked checkbox
  for ($i=0;$i<count($itlnk);$i++) {
    $sql="INSERT into item2file (fileid,itemid) values ($id,".$itlnk[$i].")";
    db_exec($dbh,$sql);
  }

  //update software - file links 
  //remove old links for this object
  $sql="delete from software2file where fileid=$id";
  db_exec($dbh,$sql);
  //add new links for each checked checkbox
  for ($i=0;$i<count($softlnk);$i++) {
    $sql="INSERT into software2file (fileid,softwareid) values ($id,".$softlnk[$i].")";
    db_exec($dbh,$sql);
  }

  //update contract - file links 
  //remove old links for this object
  $sql="delete from contract2file where fileid=$id";
  db_exec($dbh,$sql);
  //add new links for each checked checkbox
  for ($i=0;$i<count($contrlnk);$i++) {
    $sql="INSERT into contract2file (fileid,contractid) values ($id,".$contrlnk[$i].")";
    db_exec($dbh,$sql);
  }

}//save pressed

/////////////////////////////
//// display data now


if (!isset($_REQUEST['id'])) {echo "ERROR:ID not defined";exit;}
$id=$_REQUEST['id'];

$sql="SELECT * FROM files,filetypes where files.type=filetypes.id AND files.id='$id'";
$sth=db_execute($dbh,$sql);
$r=$sth->fetch(PDO::FETCH_ASSOC);

if ($id!="new") 
  $mytype=$r['typedesc'];
else 
  $mytype="";

if (($id !="new") && (count($r)<3)) {echo "ERROR: non-existent ID<br>($sql)";exit;}
echo "\n<form id='mainform' method=post  action='$scriptname?action=$action&amp;id=$id' enctype='multipart/form-data'  name='addfrm'>\n";

?>



<?php 
if ($id=="new")
  echo "\n<h1>".t("Add File")."</h1>\n";
else
  echo "\n<h1>".t("Edit File")."</h1>\n";

?>

<!-- error errcontainer -->
<div class='errcontainer ui-state-error ui-corner-all' style='padding: 0 .7em;width:700px;margin-bottom:3px;'>
        <p><span class='ui-icon ui-icon-alert' style='float: left; margin-right: .3em;'></span>
        <h4>There are errors in your form submission, please see below for details.</h4>
        <ol>
                <li><label for="type" class="error"><?php te("File Type is missing");?></label></li>
                <li><label for="title" class="error"><?php te("File title is missing");?></label></li>
                <li><label for="date" class="error"><?php te("Contract Type is missing");?></label></li>
                <li><label for="file" class="error"><?php te("You forgot to select the file?");?></label></li>
        </ol>
</div>

<table style='width:100%' border=0>

<tr>
<td class="tdtop">

    <table class="tbl2" style='width:300px;'>
    <tr><td colspan=2><h3>File Properties</h3></td></tr>
    <tr><td class="tdt">ID:</td> <td><input  class='input2' type=text name='id' value='<?php echo $id?>' readonly size=3></td></tr>
    <tr><td class="tdt">Type</td><td>
    <select class='mandatory' validate='required:true' name='type'>
<?php 
     if ($mytype=="invoice")
       echo "<option  value='3'>Invoice</option>";
     else {
       echo "\n<option  value=''>--- Please Select ---</option>";
       foreach ($filetypes as $ftype) {
	 $dbid=$ftype['id'];
	 $ftypedesc=ucfirst($ftype['typedesc']);
	 if ($ftype['typedesc']=="invoice") continue;
	 if ($r['type']==$dbid) $s=" SELECTED "; else $s="";
	 echo "\n<option $s value='$dbid'>$ftypedesc</option>";
}
     }
?>
    </select>
    </td></tr>
    <tr><td class="tdt"><?php te("Title");?>:</td> <td><input  class='input2 mandatory' validate='required:true' size=20 type=text name='title' value="<?php echo $r['title']?>"></td></tr>
    <tr><td class="tdt"><?php te("Issue Date");?>:</td> <td><input  class='input2 dateinp mandatory' validate='required:true' id='date' size=20 type=text name='date' 
        value="<?php  if (!empty($r['date'])) echo date($dateparam,$r['date'])?>"></td></tr>
    <tr><td class="tdt"><?php te("Filename");?>:</td><td><a target=_blank href="<?php  echo $uploaddirwww.$r['fname'] ?>"><?php echo $r['fname']?></a></td></tr>
    <tr><td title='Number of items/software/invoices/etc which reference this file'
            class="tdt"><?php te("Associations");?>:</td> <td><b><?php  if ($_GET['id']!="new") echo countfileidlinks($_GET['id'],$dbh);?></b></td></tr>
    <tr><td class="tdt"><?php te("Uploaded by");?>:</td> <td><?php echo $r['uploader']?> on <?php  if (!empty($r['uploaddate'])) echo date($dateparam." H:m",$r['uploaddate'])?></td></tr>


    </table>
</td>

<td>

    <h3><?php te("Associations Overview");?></h3>
    <div style='text-align:center'>
      <span class="tita" onclick='showid("items");'><?php te("Items");?></span> |
      <span class="tita" onclick='showid("invoices1");'><?php te("Invoices");?></span> |
      <span class="tita" onclick='showid("contracts");'><?php te("Contracts");?></span> |
      <span class="tita" onclick='showid("software");'><?php te("Software");?></span>
    </div>

    <div class="scrltblcontainer4" style='height:20ex' >

    <div  id='items' class='relatedlist'><?php te("ITEMS");?></div>
    <?php 
    if (is_numeric($id)) {
      $sql="SELECT items.id, agents.title || ' ' || items.model || ' [' || itemtypes.typedesc || ', ID:' || items.id || ']' as txt ".
           "FROM agents,items,itemtypes,item2file WHERE ".
           " agents.id=items.manufacturerid AND items.itemtypeid=itemtypes.id AND ".
           " item2file.itemid=items.id AND item2file.fileid=$id";
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

   <div id='invoices1' class='relatedlist'><?php te("INVOICES");?></div>
    <?php 
    if (is_numeric($id)) {
      //print a table row
      $sql="SELECT invoices.id, invoices.number, invoices.date FROM invoices,invoice2file ".
           " WHERE invoice2file.invoiceid=invoices.id AND invoice2file.fileid=$id";
      $sthi=db_execute($dbh,$sql);
      $ri=$sthi->fetchAll(PDO::FETCH_ASSOC);
      $nitems=count($ri);
      $institems="";
      for ($i=0;$i<$nitems;$i++) {
        $d=strlen($ri[$i]['date'])?date($dateparam,$ri[$i]['date']):"";
        $x=($i+1).":  ({$ri[$i]['number']}) - $d [ID:{$ri[$i]['id']}]";
        if ($i%2) $bcolor="#D9E3F6"; else $bcolor="#ffffff";
        $institems.="\t<div style='margin:0;padding:0;background-color:$bcolor'>".
                    "<a href='$scriptname?action=editinvoice&amp;id={$ri[$i]['id']}'>$x</a></div>\n";
      }
      echo $institems;
    }
    ?>

    <div  id='software' class='relatedlist'><?php te("SOFTWARE");?></div>
    <?php 
    if (is_numeric($id)) {
      //print a table row

      $sql="SELECT software.id, agents.title || ' ' || software.stitle ||' '|| software.sversion || ' [ID:' || software.id || ']' as txt ".
           "FROM agents,software,software2file WHERE ".
           " agents.id=software.manufacturerid AND software2file.softwareid=software.id AND software2file.fileid='$id'";
      $sthi=db_execute($dbh,$sql);
      $ri=$sthi->fetchAll(PDO::FETCH_ASSOC);
      $nitems=count($ri);
      $institems="";
      for ($i=0;$i<$nitems;$i++) {
        $x=($i+1).": ".$ri[$i]['txt'];
        if ($i%2) $bcolor="#D9E3F6"; else $bcolor="#ffffff";
        $institems.="\t<div style='margin:0;padding:0;background-color:$bcolor'>".
                    "<a href='$scriptname?action=editsoftware&amp;id={$ri[$i]['id']}'>$x</a></div>\n";
      }
      echo $institems;
    }
    ?>



   <div id='contracts' class='relatedlist'><?php te("CONTRACTS");?></div>
    <?php 
    if (is_numeric($id)) {
      //print a table row
      $sql="SELECT contracts.id, type,title,number,startdate,currentenddate FROM contracts,contract2file ".
           " WHERE contract2file.contractid=contracts.id AND contract2file.fileid=$id";
      $sthi=db_execute($dbh,$sql);
      $ri=$sthi->fetchAll(PDO::FETCH_ASSOC);
      $nitems=count($ri);
      $institems="";
      for ($i=0;$i<$nitems;$i++) {
        $d=date($dateparam,$ri[$i]['startdate'])."-".date($dateparam,$ri[$i]['currentenddate']);
        $x=($i+1).":  (".$ri[$i]['title']." ".$ri[$i]['number'].") - $d [ID:{$ri[$i]['id']}]";
        if ($i%2) $bcolor="#D9E3F6"; else $bcolor="#ffffff";
        $institems.="\t<div style='margin:0;padding:0;background-color:$bcolor'>".
                    "<a href='$scriptname?action=editcontract&amp;id={$ri[$i]['id']}'>$x</a></div>\n";
      }
      echo $institems;
    }
    ?>

</td>

<td class="tdtop">
    <table class="tbl2" width='90%'>
    <tr><td colspan=2 colspan=2><h3>
      <?php 
      if ($id=="new") {
	$tip="";
	echo t("Upload a File");
  $file_validate_required='true';
      }
      else{
	$tip=t("If you select a new file, it will replace the current one, <br>while keeping its associations.");
	echo t("Replace File");
  $file_validate_required='false';
      }
      ?>
    </h3></td></tr>
    <!-- file upload -->
    <tr> 
      <td class="tdt">File:</td> <td><input validate='required:<?php echo $file_validate_required;?>' name="file" id="file" size="25" type="file"></td>
    </tr>
    </table>
<?php echo $tip?>
</td>
<!-- upload -->
</tr>


<tr>
<td colspan=3>
<h3><?php te("Associations");?></h3>
<div id="tabs">
<ul >
<li><a href="#tab1"><?php te("Items");?></a></li>
<li><a href="#tab2"><?php te("Software");?></a></li>
<li><a href="#tab3"><?php te("Contracts");?></a></li>
</ul>



<div id="tab1" class="tab_content"><!-- item associations -->
<?php   if (($id!="new") && ($mytype!="invoice")) { ?>
      <table border='0' class=tbl2 style='width:100%;border-bottom:1px solid #cecece'><!-- connect to other items -->
	<tr><td colspan=2 ><?php te("Associate file with the following items");?>:
           <input class='filter' style='color:#909090' id="itemsfilter" 
               name="itemsfilter" value='Filter' onclick='this.style.color="#000"; this.value=""' size="20"><br>
	</td></tr>
	<tr><td colspan=2>

	  <div class='scrltblcontainer' style='height:30em'>
	  <table width='100%' class='sortable brdr' id='itemslisttbl'>
	  <thead><tr><th><?php te("Rel");?></th><th><?php te("ID");?></th><th><?php te("Type");?></th><th><?php te("Manuf.-Model");?></th>
                     <th><?php te("Label");?></th><th>DNS</th><th><?php te("Users");?></th><th><?php te("S/N");?></th></tr></thead>
	  <tbody>
	  <?php 
	  //////////////////////////////////////////////
	  //connect to other Items
	  //insert into tt ids that link to $id. It will remain empty if $id not defined
	  $dbh->exec("CREATE TEMP TABLE IF NOT EXISTS tt (ids integer)");
	  $error = $dbh->errorInfo();if($error[0] && isset($error[2])) echo "Error ei0:".$error[2]."<br>";
	  $dbh->exec("DELETE FROM tt");
	  $error = $dbh->errorInfo();if($error[0] && isset($error[2])) echo "Error ei0:".$error[2]."<br>";

	  if (isset($id) && strlen($id)) { //item links
	    //fill in tt with linked items
	    $sql="INSERT INTO tt SELECT id from items WHERE id IN ".
		  "(SELECT itemid FROM item2file WHERE fileid=$id )";
	    db_exec($dbh,$sql);
	  }

	  //linked items
	  $sql="SELECT items.id,manufacturerid,model,itemtypeid,sn || ' '||sn2 ||' ' || sn3 as sn,label,dnsname,users.username AS username, ".
               " typedesc, agents.title AS agtitle ".
	       " FROM items,users,itemtypes,agents WHERE itemtypes.id=items.itemtypeid AND agents.id=items.manufacturerid AND  ".
               " userid=users.id AND items.id in (SELECT * from tt) ".
	       " order by itemtypeid,items.id DESC, manufacturerid,model, dnsname";
	  $sth=db_execute($dbh,$sql);

	  while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
	    if (isset($id) && ($r['id']==$id)) continue; //dont link recursively 
	    echo "\n <tr><td><input name='itlnk[]' value='".$r['id'].
	     "' checked type='checkbox' /></td>".
	     "<td class='bld' style='white-space:nowrap'><a title='Edit item {$r['id']} in new window' ".
	     "target=_blank href='$scriptname?action=edititem&id=".$r['id']."'><img src='images/edit.png'>".
	     $r['id']."</a></b></td>".
	     "<td class='bld'>".$r['typedesc']."</td>".
	     "<td class='bld'>".$r['agtitle']."&nbsp;".$r['model']."</td>".
	     "<td class='bld'>".$r['label']."&nbsp;</td>".
	     "<td class='bld'>".$r['dnsname']."&nbsp;</td>".
	     "<td class='bld'>".$r['username']."&nbsp;</td>".
	     "<td class='bld'>".$r['sn']."&nbsp;</td></tr>\n";
	  }

	  //not linked items
	  $sql="SELECT items.id,manufacturerid,model,itemtypeid, sn || ' '||sn2 ||' ' || sn3 as sn,label,dnsname, users.username AS username, ".
               " typedesc,agents.title AS agtitle ".
	       " FROM items,users,itemtypes,agents WHERE itemtypes.id=items.itemtypeid AND userid=users.id AND agents.id=items.manufacturerid ".
               " AND items.id not in (SELECT * FROM tt) ".
	       " order by itemtypeid,items.id DESC, manufacturerid, model, dnsname";

	  $sth=db_execute($dbh,$sql);

	  while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
	    echo "\n  <tr><td><input name='itlnk[]' value='".$r['id'].
	     "' type='checkbox' /></td>".
	     "<td style='white-space:nowrap'><a title='Edit item {$r['id']} in new window' ".
	     "target=_blank href='$scriptname?action=edititem&id=".$r['id']."'><img src='images/edit.png'>".
	     $r['id']."</a>&nbsp;</td>".
	     "<td>".$r['typedesc']."</td>".
	     "<td>".$r['agtitle']."&nbsp;".$r['model']."</td>".
	     "<td>".$r['label']."&nbsp;</td>".
	     "<td >".$r['dnsname']."&nbsp;</td>".
	     "<td >".$r['username']."&nbsp;</td>".
	     "<td >".$r['sn']."&nbsp;</td></tr>\n";
	  }
	?>

	</tbody>
	</table>
	</div>
	</td>
	</tr>
      </table><!-- /connect to other items -->

<?php  } 
  elseif (($id!="new") && ($mytype=="invoice")) { 
     echo "<br>-Files of type 'invoice' can be associated only with invoices and only using the 'invoice' menu ";
  }
?>

</div><!-- item associations -->


<div id="tab2" class="tab_content"><!-- software associations -->
<?php   if (($id!="new") && ($mytype!="invoice")) { ?>
      <table border='0' class=tbl2 style='width:100%;border-bottom:1px solid #cecece'><!-- connect to software -->
	<tr><td colspan=2 >Associate file with the following software:
	<input class='filter' style='color:#909090' id="softwarefilter" 
               name="softwarefilter" value='Filter' onclick='this.style.color="#000"; this.value=""' size="20"><br>
	</td></tr>
	<tr><td colspan=2>

	  <div class='scrltblcontainer' style='height:35em'>
	  <table width='100%' class='sortable brdr' id='softwarelisttbl'>
	  <thead><tr><th><?php te("Rel");?></th><th><?php te("ID");?></th><th><?php te("Manufacturer");?></th>
                     <th><?php te("Title-Version");?></th></tr></thead>
	  <tbody>
	  <?php 
	  //////////////////////////////////////////////
	  //connect to other Items
	  //insert into tt ids that link to $id. It will remain empty if $id not defined
	  $dbh->exec("CREATE TEMP TABLE IF NOT EXISTS tt (ids integer)");
	  $error = $dbh->errorInfo();if($error[0] && isset($error[2])) echo "Error ei0:".$error[2]."<br>";
	  $dbh->exec("DELETE FROM tt");
	  $error = $dbh->errorInfo();if($error[0] && isset($error[2])) echo "Error ei0:".$error[2]."<br>";

	  if (isset($id) && strlen($id)) { //software links
	    //fill in tt with linked software
	    $sql="INSERT INTO tt SELECT id from software WHERE id IN ".
		  "(SELECT softwareid FROM software2file WHERE fileid=$id )";
	    db_exec($dbh,$sql);
	  }

	  //linked software
	  $sql="SELECT software.id,manufacturerid,stitle,sversion, agents.title AS agtitle ".
	       " FROM software,agents WHERE agents.id=software.manufacturerid AND  ".
               " software.id in (SELECT * from tt) ".
	       " order by agtitle, software.id DESC, stitle";
	  $sth=db_execute($dbh,$sql);

	  while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
	    if (isset($id) && ($r['id']==$id)) continue; //dont link recursively 
	    echo "\n <tr><td><input name='softlnk[]' value='".$r['id'].
	     "' checked type='checkbox' /></td>".
	     "<td class='bld' style='white-space:nowrap'><a title='Edit Software {$r['id']} in new window' ".
	     "target=_blank href='$scriptname?action=editsoftware&id=".$r['id']."'><img src='images/edit.png'>".
	     $r['id']."</a></b></td>".
	     "<td class='bld'>".$r['agtitle']."&nbsp;</td>".
	     "<td class='bld'>".$r['stitle']."&nbsp;".$r['sversion']."&nbsp;</td>";
	  }

	  //not linked software
	  $sql="SELECT software.id,manufacturerid,stitle,sversion, agents.title AS agtitle ".
	       " FROM software,agents WHERE agents.id=software.manufacturerid AND  ".
               " software.id not in (SELECT * from tt) ".
	       " order by agtitle, software.id DESC, stitle";
	  $sth=db_execute($dbh,$sql);

	  while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
	    if (isset($id) && ($r['id']==$id)) continue; //dont link recursively 
	    echo "\n  <tr><td><input name='softlnk[]' value='".$r['id'].
	     "' type='checkbox' /></td>".
	     "<td style='white-space:nowrap'><a title='Edit Software {$r['id']} in new window' ".
	     "target=_blank href='$scriptname?action=editsoftware&id=".$r['id']."'><img src='images/edit.png'>".
	     $r['id']."</a>&nbsp;</td>".
	     "<td>".$r['agtitle']."&nbsp;</td>".
	     "<td>".$r['stitle']."&nbsp;".$r['sversion']."&nbsp;</td>";
	  }
	?>

	</tbody>
	</table>
	</div>
	</td>
	</tr>
      </table><!-- /connect to other items -->


<?php   }

  elseif (($id!="new") && ($mytype=="invoice")) { 
     echo t("<br>-Files of type 'invoice' can be associated only with invoices and only using the 'invoice' menu ");
  }
?>

</div><!-- tab2 software associations -->

<div id="tab3" class="tab_content"><!-- contracts associations -->
<?php   if (($id!="new") && ($mytype!="invoice")) { ?>
      <table border='0' class=tbl2 style='width:100%;border-bottom:1px solid #cecece'><!-- connect to contracts -->
	<tr><td colspan=2><?php te("Associate file with the following contracts");?>:
             <input class='filter' style='color:#909090' id="contractsfilter" 
               name="contractsfilter" value='Filter' onclick='this.style.color="#000"; this.value=""' size="20"><br>
	</td></tr>
	<tr><td colspan=2>

	  <div class='scrltblcontainer' style='height:35em'>
	  <table width='100%' class='sortable brdr' id='contractslisttbl'>
	  <thead><tr><th><?php te("Rel");?></th><th><?php te("ID");?></th><th><?php te("Contractor");?></th>
                     <th><?php te("Title-Version");?></th></tr></thead>
	  <tbody>
	  <?php 
	  //////////////////////////////////////////////
	  //connect to other Items
	  //insert into tt ids that link to $id. It will remain empty if $id not defined
	  $dbh->exec("CREATE TEMP TABLE IF NOT EXISTS tt (ids integer)");
	  $error = $dbh->errorInfo();if($error[0] && isset($error[2])) echo "Error ei0:".$error[2]."<br>";
	  $dbh->exec("DELETE FROM tt");
	  $error = $dbh->errorInfo();if($error[0] && isset($error[2])) echo "Error ei0:".$error[2]."<br>";

	  if (isset($id) && strlen($id)) { //contracts links
	    //fill in tt with linked contracts
	    $sql="INSERT INTO tt SELECT id from contracts WHERE id IN ".
		  "(SELECT contractid FROM contract2file WHERE fileid=$id )";
	    db_exec($dbh,$sql);
	  }

	  //linked contract
	  $sql="SELECT contracts.id,contractorid,contracts.title as ctitle, agents.title AS agtitle ".
	       " FROM contracts,agents WHERE agents.id=contracts.contractorid AND  ".
               " contracts.id in (SELECT * from tt) ".
	       " order by agtitle, contracts.id DESC, ctitle";
	  $sth=db_execute($dbh,$sql);

	  while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
	    if (isset($id) && ($r['id']==$id)) continue; //dont link recursively 
	    echo "\n <tr><td><input name='contrlnk[]' value='".$r['id'].
	     "' checked type='checkbox' /></td>".
	     "<td class='bld' style='white-space:nowrap'><a title='Edit Contract {$r['id']} in new window' ".
	     "target=_blank href='$scriptname?action=editcontract&id=".$r['id']."'><img src='images/edit.png'>".
	     $r['id']."</a></b></td>".
	     "<td class='bld'>".$r['agtitle']."&nbsp;</td>".
	     "<td class='bld'>".$r['ctitle']."&nbsp;".$r['sversion']."&nbsp;</td>";
	  }

	  //not linked contracts
	  $sql="SELECT contracts.id,contractorid,contracts.title AS ctitle, agents.title AS agtitle ".
	       " FROM contracts,agents WHERE agents.id=contracts.contractorid AND  ".
               " contracts.id not in (SELECT * from tt) ".
	       " order by agtitle, contracts.id DESC, ctitle";
	  $sth=db_execute($dbh,$sql);

	  while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
	    if (isset($id) && ($r['id']==$id)) continue; //dont link recursively 
	    echo "\n  <tr><td><input name='contrlnk[]' value='".$r['id'].
	     "' type='checkbox' /></td>".
	     "<td style='white-space:nowrap'><a title='Edit Contract {$r['id']} in new window' ".
	     "target=_blank href='$scriptname?action=editcontract&id=".$r['id']."'><img src='images/edit.png'>".
	     $r['id']."</a>&nbsp;</td>".
	     "<td>".$r['agtitle']."&nbsp;</td>".
	     "<td>".$r['ctitle']."&nbsp;".$r['sversion']."&nbsp;</td>";
	  }
	?>

	</tbody>
	</table>
	</div>
	</td>
	</tr>
      </table><!-- /connect to other items -->


<?php   }

  elseif (($id!="new") && ($mytype=="invoice")) { 
     echo t("<br>-Files of type 'invoice' can be associated only with invoices and only using the 'invoice' menu ");
  }
?>





</div><!-- tab3 contracts associations -->

</div><!-- tab container -->



</td></tr>

<tr><td colspan=1><button type="submit"><img src="images/save.png" alt="Save"> <?php te("Save");?></button></td>

<?php 
echo "\n<td style='text-align:right'><button type='button' onclick='javascript:delconfirm2(\"{$r['id']}\",\"$scriptname?action=$action&amp;delid=$id\");'>".
     "<img title='delete' src='images/delete.png' border=0>".t("Delete"). "</button></td>\n</tr>\n";

// end of item links
//////////////////////////////////////////////
echo "\n</table>\n";
echo "\n<input type=hidden name='action' value='$action'>";
echo "\n<input type=hidden name='id' value='$id'>";

?>

</form>
</body>
</html>
