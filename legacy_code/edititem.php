<?php 

/* Spiros Ioannou 2009 , sivann _at_ gmail.com */
if (!isset($initok)) {echo "do not run this script directly";exit;}

//form variables
$formvars=array("item_type_id","function","manufacturer_id","label",
  "warranty_info","model","sn","sn2","sn3","location_id","location_area_id",
  "origin","warranty_months","purchase_date","purchase_price","dns_name","user_id",
  "comments","maintenance_info","is_part","hd",
  "cpu","cpu_count","cores_per_cpu", "ram", "is_rack_mountable", "rack_id","rack_position","rack_position_depth","usize","status",
  "macs","ipv4","ipv6","remote_admin_ip","panel_port","switch_id","switch_port","ports");

/* delete item */
if (isset($_GET['delid'])) { 
  //first handle file associations
  //get a list of files associated with us
  $f=itemid2files($delid,$dbh);
  for ($fids=array(),$c=0;$c<count($f);$c++) {
    array_push($fids,$f[$c]['id']);
  }

  //remove file links
  $sql="DELETE from item2file where item_id=$delid";
  $sth=db_exec($dbh,$sql);

  //for each file: check if others link to it, and if not remove it:
  for ($c=0;$c<count($fids);$c++) {
    $nlinks=countfileidlinks($fids[$c],$dbh);
    if ($nlinks==0) delfile($fids[$c],$dbh);
  }

  //delete invoice links
  $sql="DELETE from item2inv where item_id=".$_GET['delid'];
  $sth=db_exec($dbh,$sql);

  //delete software links
  $sql="DELETE from item2soft where item_id=".$_GET['delid'];
  $sth=db_exec($dbh,$sql);

  //delete inter-item links
  $sql="DELETE from itemlink where itemid1=".$_GET['delid']." or itemid2=".$_GET['delid'];
  $sth=db_exec($dbh,$sql);

  //nullify TAGS
  $sql="UPDATE tag2item set item_id=null where item_id=".$_GET['delid'];
  $sth=db_exec($dbh,$sql);

  //delete item 
  $sql="DELETE from items where id=".$_GET['delid'];
  $sth=db_exec($dbh,$sql);

  echo "<script>document.location='$scriptname?action=listitems'</script>";
  echo "<a href='$scriptname?action=listitems'>Go here</a></body></html>"; 
  exit;
}

if (isset($_GET['cloneid'])) { 
  $cols="item_type_id , function, manufacturer_id ,model,origin,warranty_months ,purchase_date ,purchase_price, maintenance_info,".
        "comments,is_part ,hd,cpu,ram,location_id ,usize ,is_rack_mountable ,label,status,cpu_count , cores_per_cpu , warranty_info";

  $sql="insert into items ($cols) ".
     " select $cols from items ".
     " where id={$_GET['cloneid']}";
  $sth=db_exec($dbh,$sql);

  $lastid=$dbh->lastInsertId();
  $newid=$lastid;
  echo "<script>document.location='$scriptname?action=edititem&id=$newid'</script>";
  echo "<a href='$scriptname?action=edititem&amp;id=$newid'>Go here</a></body></html>"; 
  exit;
}








/* delete associated file */
if (isset($_GET['delfid'])) { /* displayed from showfiles() */

  //remove file link
  $sql="DELETE from item2file where item_id=$id AND file_id=".$_GET['delfid'];
  $sth=db_exec($dbh,$sql);

  //check if others point to this file
  $nlinks=countfileidlinks($_GET['delfid'],$dbh);
  if ($nlinks==0) delfile($_GET['delfid'],$dbh);
  //echo "$nlinks DELETED ".$_GET['delfid'];
  echo "<script>window.location='$scriptname?action=$action&id=$id'</script> ";
  echo "<br><a href='$scriptname?action=$action&id=$id'>Go here</a></body></html>";
  exit;
}

//check for arguments
if (!isset($_GET['id'])) {echo "edititem:missing arguments";exit;}

/* update item data */
//if came here from a form post, update db with new values
if (isset($_POST['item_type_id']) && ($_GET['id']!="new") && isvalidfrm()) {
//get form post variables and create the sql query
  $set="";
  $c=count($formvars);$i=0;
  foreach ($formvars as $formvar){
    if (isset($_POST[$formvar]))
      $$formvar=trim($_POST[$formvar]);//create $sn from $_POST['sn']
    else {$i++;continue;} //for files which are in _FILES not in _POST

    if ($formvar == "purchase_date") $$formvar=ymd2sec($$formvar);
    if ($formvar == "maintend") $$formvar=ymd2sec($$formvar);
    if ($formvar == "warranty_months") {
		if ($$formvar=="") 
		  $$formvar="NULL";
		else
		  $$formvar=(int)($$formvar);
      $set.="$formvar=".($$formvar).""; //without quotes for integer
    }
    else {
      $set.="$formvar='".htmlspecialchars($$formvar,ENT_QUOTES,"UTF-8")."'";
	}
    $set.=", ";
    $i++;
  }
  $set[strlen($set)-2]=" ";
  if (!isset($_POST['itlnk'])) $itlnk=array();
  if (!isset($_POST['invlnk'])) $invlnk=array();



  $sql="SELECT items.user_id,users.username from users,items where user_id=users.id and items.id='$id'";
  $sth=db_execute($dbh,$sql);
  $curruser=$sth->fetchAll(PDO::FETCH_ASSOC);
  $curruser=$curruser[0];

  $sql="SELECT username from users where id=$user_id";
  $sth=db_execute($dbh,$sql);
  $newuser=$sth->fetchAll(PDO::FETCH_ASSOC);
  $newuser=$newuser[0];

  if ($user_id!=$curruser['user_id']) { //changed user
    $str="Updated user from {$curruser['username']} to {$newuser['username']}";
    $sql="INSERT into actions (item_id, actiondate,description,invoiceinfo,isauto,entrydate) values ".
	 "($id,".time().",'$str' , '',1,".time().")";
    db_exec($dbh,$sql);
  }

  $sql="UPDATE items set $set WHERE id=$id";
  db_exec($dbh,$sql); 

  //Add new action entry
  //if not exists already for today
  $sql="SELECT item_id,entrydate,description, isauto FROM actions WHERE item_id='$id' ORDER BY entrydate DESC LIMIT 1";
  $sth=db_execute($dbh,$sql);
  $laction=$sth->fetchAll(PDO::FETCH_ASSOC);

  $upstr="Updated by {$_COOKIE["itdbuser"]}";
  $ldesc=$laction[0]['description'];
  $ldate=date("Ymd",$laction[0]['entrydate']);
  $ndate=date("Ymd",time());


  if (($upstr != $ldesc) && ($ldate != $ndate) ) {
    //add new action entry
    $sql="INSERT into actions (item_id, actiondate,description,invoiceinfo,isauto,entrydate) values ".
	 "($id,".time().",'$upstr' , '',1,".time().")";
    db_exec($dbh,$sql);
//echo "HERE:($upstr,$ldesc), ($ldate,$ndate);";
  }

  //update item links
  //remove old links for this object
  $dbh->beginTransaction();
  $sql="delete from itemlink where itemid1=$id";
  db_exec($dbh,$sql);
  //add new links for each checked checkbox
  for ($i=0;$i<count($itlnk);$i++) {
    $sql="INSERT into itemlink (itemid1, itemid2) values ($id,".$itlnk[$i].")";
    db_exec($dbh,$sql);
  }
  //update invoice links
  //remove old links for this object
  $sql="delete from item2inv where item_id=$id";
  db_exec($dbh,$sql);
  //add new links for each checked checkbox
  for ($i=0;$i<count($invlnk);$i++) {
    $sql="INSERT into item2inv (item_id, invoice_id) values ($id,".$invlnk[$i].")";
    db_exec($dbh,$sql);
  }
  $dbh->commit();

  //update software - item links 
  //remove old links for this object
  $sql="delete from item2soft where item_id=$id";
  db_exec($dbh,$sql);
  //add new links for each checked checkbox
  for ($i=0;$i<count($softlnk);$i++) {
    $sql="INSERT into item2soft (item_id,software_id) values ($id,".$softlnk[$i].")";
    db_exec($dbh,$sql);
  }

  //update contract - item links 
  //remove old links for this object
  $sql="delete from contract2item where item_id=$id";
  db_exec($dbh,$sql);
  //add new links for each checked checkbox
  for ($i=0;$i<count($contrlnk);$i++) {
    $sql="INSERT into contract2item (item_id,contract_id) values ($id,".$contrlnk[$i].")";
    db_exec($dbh,$sql);
  }
} //if updating
/* add new item */
elseif (isset($_POST['item_type_id']) && ($_GET['id']=="new")&&isvalidfrm()) {

  //ok, save new item
  //find a new ID 
  //handle file uploads
  $photofn="";
  $manualfn="";

  foreach($_POST as $k => $v) { if (!is_array($v)) ${$k} = (trim($v));}
  $purchase_date2=ymd2sec($purchase_date);// mktime(0, 0, 0, $x[1], $x[0], $x[2]);

  $mend=ymd2sec($maintend);

  if ($switch_id=="") $switch_id="NULL";
  if ($usize=="") $usize="NULL";
  if ($location_id=="") $location_id="NULL";
  if ($location_area_id=="") $location_area_id="NULL";
  if ($rack_id=="") $rack_id="NULL";
  if ($rack_position=="") $rack_position="NULL";
  if ($user_id=="") $user_id="NULL";
  $warranty_months=(int)$warranty_months;
  if (!$warranty_months || !strlen($warranty_months) || !is_integer($warranty_months)) $warranty_months="NULL";




  //// STORE DATA
  $sql="INSERT into items (label, item_type_id, function, manufacturer_id, ".
  " warranty_info, model, sn, sn2, sn3, origin, warranty_months, purchase_date, purchase_price, ".
  " dns_name, user_id, location_id,location_area_id, maintenance_info,  ".
  " comments,is_part, rack_id, rack_position,rack_position_depth, is_rack_mountable, ".
  " usize, status, macs, ipv4, ipv6, remote_admin_ip, ".
  " hd, cpu,cpu_count,cores_per_cpu, ram, ".
  " panel_port, switch_id, switch_port, ports) VALUES ".
  " ('$label', '$item_type_id', '$function', '$manufacturer_id', ".
  " '$warranty_info', '$model', '$sn', '$sn2', '$sn3', '$origin', ".
  "  $warranty_months, '$purchase_date2', ".
  " '$purchase_price', '$dns_name', $user_id, $location_id,$location_area_id, '$maintenance_info', ".
  " '". htmlspecialchars($comments,ENT_QUOTES,'UTF-8')  ."',$is_part, $rack_id, $rack_position,$rack_position_depth, $is_rack_mountable, " .
  "  $usize, $status, '$macs', '$ipv4', '$ipv6', '$remote_admin_ip', ".
  " '$hd', '$cpu', '$cpu_count', '$cores_per_cpu', '$ram', ".
  " '$panel_port', $switch_id,  '$switch_port', '$ports' ) ";

  //echo $sql."<br>";
  db_exec($dbh,$sql);

  $lastid=$dbh->lastInsertId();
  $id=$lastid;

  //add new links for each checked checkbox
  if (isset($_POST['itlnk'])) {
    $itlnk=$_POST['itlnk'];
    for ($i=0;$i<count($itlnk);$i++) {
      $sql="INSERT into itemlink (itemid1, itemid2) values ($lastid,".$itlnk[$i].")";
      db_exec($dbh,$sql);
    }
  }//add item links

  //add new links for each checked checkbox
  if (isset($_POST['invlnk'])) {
    $itlnk=$_POST['invlnk'];
    for ($i=0;$i<count($invlnk);$i++) {
      $sql="INSERT into item2inv (item_id, invoice_id) values ($lastid,".$invlnk[$i].")";
      db_exec($dbh,$sql);
    }
  }//add invoice links

  //update software - item links 
  //remove old links for this object
  $sql="DELETE from item2soft where item_id=$lastid";
  db_exec($dbh,$sql);
  //add new links for each checked checkbox
  for ($i=0;$i<count($softlnk);$i++) {
    $sql="INSERT into item2soft (item_id,software_id) values ($lastid,".$softlnk[$i].")";
    db_exec($dbh,$sql);
  }

  //update contract - item links 
  //remove old links for this object
  $sql="DELETE from contract2item where item_id=$lastid";
  db_exec($dbh,$sql);
  //add new links for each checked checkbox
  for ($i=0;$i<count($contrlnk);$i++) {
    $sql="INSERT into contract2item (item_id,contract_id) values ($lastid,".$contrlnk[$i].")";
    db_exec($dbh,$sql);
  }


  //add new action entry
  $sql="INSERT into actions (item_id, actiondate,description,invoiceinfo,isauto,entrydate) values ".
       "($lastid,".time().",'Added by {$_COOKIE["itdbuser"]}' , '',1,".time().")";
  db_exec($dbh,$sql);

  print "\n<br><b>Added item <a href='$scriptname?action=edititem&amp;id=$lastid'>$lastid</a></b><br>\n";
  if ($lastid) echo "<script>window.location='$scriptname?action=edititem&id=$lastid'</script> "; //go to the new item

}//xxxadd new item

function isvalidfrm() {
global $dbh,$disperr,$err,$_POST;
  //check for mandatory fields
  $err="";
  $disperr="";
  if ($_POST['item_type_id']=="") $err.="Missing Item Type<br>";
  if ($_POST['user_id']=="") $err.="Missing User<br>";
  if ($_POST['manufacturer_id']=="") $err.="Missing manufacturer<br>";
  if (!isset($_POST['is_rack_mountable'])) $err.="Missing 'Rackmountable' classification<br>";
  if (!isset($_POST['is_part'])) $err.="Missing 'Part' classification<br>";
  if (!isset($_POST['status'])) $err.="Missing 'Status' classification<br>";
  if ($_POST['model']=="") $err.="Missing model<br>";


  $myid=$_GET['id'];
  if ($myid != "new" && is_numeric($myid) && (strlen(trim($_POST['sn'])) || strlen(trim($_POST['sn2'])))) {
	  $sql="SELECT id from items where  id <> $myid AND ((length(sn)>0 AND serial_number in ('{$_POST['sn']}', '{$_POST['sn2']}')) OR (length(sn2)>0 AND sn2 in ('{$_POST['sn']}', '{$_POST['sn2']}')))  LIMIT 1";
	  $sth=db_execute($dbh,$sql);
	  $dups=$sth->fetchAll(PDO::FETCH_ASSOC);
	  if (count($dups[0])) {
		  $err.="Duplicate SN with id <a href='$scriptname?action=edititem&amp;id={$dups[0]['id']}'><b><u>{$dups[0]['id']}</u></b></a>";
	  }
  }




  if (strlen($err)) {
      $disperr= "
      <div class='ui-state-error ui-corner-all' style='padding: 0 .7em;width:300px;margin-bottom:3px;'> 
	      <p><span class='ui-icon ui-icon-alert' style='float: left; margin-right: .3em;'></span>
	      <strong>Error: Item not saved, correct these errors:</strong><br><div style='text-align:left'>$err</div></p>
      </div>
      ";
    return 0;
  }
  return 1;
}

require('itemform.php');
?>
