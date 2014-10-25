<?php 

/* Spiros Ioannou 2009 , sivann _at_ gmail.com */
if (!isset($initok)) {echo "do not run this script directly";exit;}

//form variables
$formvars=array("itemtypeid","function","manufacturerid","label",
  "warrinfo","model","sn","sn2","sn3","locationid","locareaid",
  "origin","warrantymonths","purchasedate","purchprice","dnsname","userid",
  "comments","maintenanceinfo","ispart","hd",
  "cpu","cpuno","corespercpu", "ram", "rackmountable", "rackid","rackposition","rackposdepth","usize","status",
  "macs","ipv4","ipv6","remadmip","panelport","switchid","switchport","ports");

/* delete item */
if (isset($_GET['delid'])) { 
  //first handle file associations
  //get a list of files associated with us
  $f=itemid2files($delid,$dbh);
  for ($fids=array(),$c=0;$c<count($f);$c++) {
    array_push($fids,$f[$c]['id']);
  }

  //remove file links
  $sql="DELETE from item2file where itemid=$delid";
  $sth=db_exec($dbh,$sql);

  //for each file: check if others link to it, and if not remove it:
  for ($c=0;$c<count($fids);$c++) {
    $nlinks=countfileidlinks($fids[$c],$dbh);
    if ($nlinks==0) delfile($fids[$c],$dbh);
  }

  //delete invoice links
  $sql="DELETE from item2inv where itemid=".$_GET['delid'];
  $sth=db_exec($dbh,$sql);

  //delete software links
  $sql="DELETE from item2soft where itemid=".$_GET['delid'];
  $sth=db_exec($dbh,$sql);

  //delete inter-item links
  $sql="DELETE from itemlink where itemid1=".$_GET['delid']." or itemid2=".$_GET['delid'];
  $sth=db_exec($dbh,$sql);

  //nullify TAGS
  $sql="UPDATE tag2item set itemid=null where itemid=".$_GET['delid'];
  $sth=db_exec($dbh,$sql);

  //delete item 
  $sql="DELETE from items where id=".$_GET['delid'];
  $sth=db_exec($dbh,$sql);

  echo "<script>document.location='$scriptname?action=listitems'</script>";
  echo "<a href='$scriptname?action=listitems'>Go here</a></body></html>"; 
  exit;
}

if (isset($_GET['cloneid'])) { 
  $cols="itemtypeid , function, manufacturerid ,model,origin,warrantymonths ,purchasedate ,purchprice, maintenanceinfo,".
        "comments,ispart ,hd,cpu,ram,locationid ,usize ,rackmountable ,label,status,cpuno , corespercpu , warrinfo";

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
  $sql="DELETE from item2file where itemid=$id AND fileid=".$_GET['delfid'];
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
if (isset($_POST['itemtypeid']) && ($_GET['id']!="new") && isvalidfrm()) {
//get form post variables and create the sql query
  $set="";
  $c=count($formvars);$i=0;
  foreach ($formvars as $formvar){
    if (isset($_POST[$formvar]))
      $$formvar=trim($_POST[$formvar]);//create $sn from $_POST['sn']
    else {$i++;continue;} //for files which are in _FILES not in _POST

    if ($formvar == "purchasedate") $$formvar=ymd2sec($$formvar);
    if ($formvar == "maintend") $$formvar=ymd2sec($$formvar);
    if ($formvar == "warrantymonths") {
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



  $sql="SELECT items.userid,users.username from users,items where userid=users.id and items.id='$id'";
  $sth=db_execute($dbh,$sql);
  $curruser=$sth->fetchAll(PDO::FETCH_ASSOC);
  $curruser=$curruser[0];

  $sql="SELECT username from users where id=$userid";
  $sth=db_execute($dbh,$sql);
  $newuser=$sth->fetchAll(PDO::FETCH_ASSOC);
  $newuser=$newuser[0];

  if ($userid!=$curruser['userid']) { //changed user
    $str="Updated user from {$curruser['username']} to {$newuser['username']}";
    $sql="INSERT into actions (itemid, actiondate,description,invoiceinfo,isauto,entrydate) values ".
	 "($id,".time().",'$str' , '',1,".time().")";
    db_exec($dbh,$sql);
  }

  $sql="UPDATE items set $set WHERE id=$id";
  db_exec($dbh,$sql); 

  //Add new action entry
  //if not exists already for today
  $sql="SELECT itemid,entrydate,description, isauto FROM actions WHERE itemid='$id' ORDER BY entrydate DESC LIMIT 1";
  $sth=db_execute($dbh,$sql);
  $laction=$sth->fetchAll(PDO::FETCH_ASSOC);

  $upstr="Updated by {$_COOKIE["itdbuser"]}";
  $ldesc=$laction[0]['description'];
  $ldate=date("Ymd",$laction[0]['entrydate']);
  $ndate=date("Ymd",time());


  if (($upstr != $ldesc) && ($ldate != $ndate) ) {
    //add new action entry
    $sql="INSERT into actions (itemid, actiondate,description,invoiceinfo,isauto,entrydate) values ".
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
  $sql="delete from item2inv where itemid=$id";
  db_exec($dbh,$sql);
  //add new links for each checked checkbox
  for ($i=0;$i<count($invlnk);$i++) {
    $sql="INSERT into item2inv (itemid, invid) values ($id,".$invlnk[$i].")";
    db_exec($dbh,$sql);
  }
  $dbh->commit();

  //update software - item links 
  //remove old links for this object
  $sql="delete from item2soft where itemid=$id";
  db_exec($dbh,$sql);
  //add new links for each checked checkbox
  for ($i=0;$i<count($softlnk);$i++) {
    $sql="INSERT into item2soft (itemid,softid) values ($id,".$softlnk[$i].")";
    db_exec($dbh,$sql);
  }

  //update contract - item links 
  //remove old links for this object
  $sql="delete from contract2item where itemid=$id";
  db_exec($dbh,$sql);
  //add new links for each checked checkbox
  for ($i=0;$i<count($contrlnk);$i++) {
    $sql="INSERT into contract2item (itemid,contractid) values ($id,".$contrlnk[$i].")";
    db_exec($dbh,$sql);
  }
} //if updating
/* add new item */
elseif (isset($_POST['itemtypeid']) && ($_GET['id']=="new")&&isvalidfrm()) {

  //ok, save new item
  //find a new ID 
  //handle file uploads
  $photofn="";
  $manualfn="";

  foreach($_POST as $k => $v) { if (!is_array($v)) ${$k} = (trim($v));}
  $purchasedate2=ymd2sec($purchasedate);// mktime(0, 0, 0, $x[1], $x[0], $x[2]);

  $mend=ymd2sec($maintend);

  if ($switchid=="") $switchid="NULL";
  if ($usize=="") $usize="NULL";
  if ($locationid=="") $locationid="NULL";
  if ($locareaid=="") $locareaid="NULL";
  if ($rackid=="") $rackid="NULL";
  if ($rackposition=="") $rackposition="NULL";
  if ($userid=="") $userid="NULL";
  $warrantymonths=(int)$warrantymonths;
  if (!$warrantymonths || !strlen($warrantymonths) || !is_integer($warrantymonths)) $warrantymonths="NULL";




  //// STORE DATA
  $sql="INSERT into items (label, itemtypeid, function, manufacturerid, ".
  " warrinfo, model, sn, sn2, sn3, origin, warrantymonths, purchasedate, purchprice, ".
  " dnsname, userid, locationid,locareaid, maintenanceinfo,  ".
  " comments,ispart, rackid, rackposition,rackposdepth, rackmountable, ".
  " usize, status, macs, ipv4, ipv6, remadmip, ".
  " hd, cpu,cpuno,corespercpu, ram, ".
  " panelport, switchid, switchport, ports) VALUES ".
  " ('$label', '$itemtypeid', '$function', '$manufacturerid', ".
  " '$warrinfo', '$model', '$sn', '$sn2', '$sn3', '$origin', ".
  "  $warrantymonths, '$purchasedate2', ".
  " '$purchprice', '$dnsname', $userid, $locationid,$locareaid, '$maintenanceinfo', ".
  " '". htmlspecialchars($comments,ENT_QUOTES,'UTF-8')  ."',$ispart, $rackid, $rackposition,$rackposdepth, $rackmountable, " .
  "  $usize, $status, '$macs', '$ipv4', '$ipv6', '$remadmip', ".
  " '$hd', '$cpu', '$cpuno', '$corespercpu', '$ram', ".
  " '$panelport', $switchid,  '$switchport', '$ports' ) ";

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
      $sql="INSERT into item2inv (itemid, invid) values ($lastid,".$invlnk[$i].")";
      db_exec($dbh,$sql);
    }
  }//add invoice links

  //update software - item links 
  //remove old links for this object
  $sql="DELETE from item2soft where itemid=$lastid";
  db_exec($dbh,$sql);
  //add new links for each checked checkbox
  for ($i=0;$i<count($softlnk);$i++) {
    $sql="INSERT into item2soft (itemid,softid) values ($lastid,".$softlnk[$i].")";
    db_exec($dbh,$sql);
  }

  //update contract - item links 
  //remove old links for this object
  $sql="DELETE from contract2item where itemid=$lastid";
  db_exec($dbh,$sql);
  //add new links for each checked checkbox
  for ($i=0;$i<count($contrlnk);$i++) {
    $sql="INSERT into contract2item (itemid,contractid) values ($lastid,".$contrlnk[$i].")";
    db_exec($dbh,$sql);
  }


  //add new action entry
  $sql="INSERT into actions (itemid, actiondate,description,invoiceinfo,isauto,entrydate) values ".
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
  if ($_POST['itemtypeid']=="") $err.="Missing Item Type<br>";
  if ($_POST['userid']=="") $err.="Missing User<br>";
  if ($_POST['manufacturerid']=="") $err.="Missing manufacturer<br>";
  if (!isset($_POST['rackmountable'])) $err.="Missing 'Rackmountable' classification<br>";
  if (!isset($_POST['ispart'])) $err.="Missing 'Part' classification<br>";
  if (!isset($_POST['status'])) $err.="Missing 'Status' classification<br>";
  if ($_POST['model']=="") $err.="Missing model<br>";


  $myid=$_GET['id'];
  if ($myid != "new" && is_numeric($myid) && (strlen($_POST['sn']) || strlen($_POST['sn2']))) {
	  $sql="SELECT id from items where  id <> $myid AND ((length(sn)>0 AND sn in ('{$_POST['sn']}', '{$_POST['sn2']}')) OR (length(sn2)>0 AND sn2 in ('{$_POST['sn']}', '{$_POST['sn2']}')))  LIMIT 1";
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
