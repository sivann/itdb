<SCRIPT LANGUAGE="JavaScript"> 

 $(document).ready(function() {
    $('input#itemsfilter').quicksearch('table#itemslisttbl tbody tr');
    $('input#invfilter').quicksearch('table#invlisttbl tbody tr');
    $('input#contrfilter').quicksearch('table#contrlisttbl tbody tr');

    $("#tabs").tabs();
    $("#tabs").show();

 });


</SCRIPT>

<?php 
if (!isset($initok)) {echo "do not run this script directly";exit;}

/* Spiros Ioannou 2009-2010 , sivann _at_ gmail.com */


$sql="SELECT id,title,type FROM agents";
$sth=db_execute($dbh,$sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $agents[$r['id']]=$r;


//delete software
if (isset($_GET['delid'])) { //if we came from a post (save) the update software 
  $delid=$_GET['delid'];
  
  //first handle file associations

  //get a list of files associated with us
  $f=softid2files($delid,$dbh);
  for ($fids=array(),$c=0;$c<count($f);$c++) {
    array_push($fids,$f[$c]['id']);
  }

  //remove file links
  $sql="DELETE from software2file where softwareid=$delid";
  $sth=db_exec($dbh,$sql);

  //for each file: check if others link to it, and if not remove it:
  for ($c=0;$c<count($fids);$c++) {
    $nlinks=countfileidlinks($fids[$c],$dbh);
    if ($nlinks==0) delfile($fids[$c],$dbh);
  }

  //delete entry
  $sql="DELETE from software where id=".$_GET['delid'];
  $sth=db_exec($dbh,$sql);

  $sql="DELETE from item2soft where softid=".$_GET['delid'];
  $sth=db_exec($dbh,$sql);

  echo "<script>document.location='$scriptname?action=listsoftware'</script>";
  echo "<a href='$scriptname?action=listsoftware'>Go here</a></body></html>"; 
  exit;

}

//remove association and delete file
if (isset($_GET['delfid'])) {

  //remove file link
  $sql="DELETE from software2file where softwareid=$id AND fileid=".$_GET['delfid'];
  $sth=db_exec($dbh,$sql);

  //check if others point to this file
  $nlinks=countfileidlinks($_GET['delfid'],$dbh);
  if ($nlinks==0) delfile($_GET['delfid'],$dbh);
  //echo "$nlinks DELETED ".$_GET['delfid'];
  echo "<script>window.location='$scriptname?action=$action&id=$id'</script> ";
  echo "<br><a href='$scriptname?action=$action&id=$id'>Go here</a></body></html>"; 
  exit;
}

if (isset($_POST['id'])) { //if we came from a post (save) the update software 
  $id=$_POST['id'];

  //don't accept empty fields
  if ((empty($stitle))|| (empty($version))|| (!strlen($manufacturerid)) || (!strlen($purchdate)) ) {
    echo "<br><b>".t("Some <span class='mandatory'> mandatory</span> fields are missing").".</b><br><a href='javascript:history.go(-1);'>Go back</a></body></html>";
    exit;
  }

  $slicenseinfo=$_POST['slicenseinfo'];
  $manufacturerid=$_POST['manufacturerid'];
  $sversion=$_POST['sversion'];
  $sinfo=$_POST['sinfo'];
  $stitle=$_POST['stitle'];

  $pd=ymd2sec($purchdate);
  $mend=ymd2sec($maintend);

  if ($_POST['id']=="new")  {//if we came from a post (save) the add software 
    $sql="INSERT into software (invoiceid,slicenseinfo,manufacturerid,stitle,sversion,sinfo,purchdate,licqty,lictype)".
         " VALUEs ('$invoiceid','$slicenseinfo','$manufacturerid','$stitle','$sversion','$sinfo','$pd','$licqty','$lictype')";
    db_exec($dbh,$sql,0,0,$lastid);
    $lastid=$dbh->lastInsertId();
    print "<br><b>Added Software <a href='$scriptname?action=$action&amp;id=$lastid'>$lastid</a></b><br>";
    echo "<script>window.location='$scriptname?action=$action&id=$lastid'</script> "; //go to the new item
    $id=$lastid;
  }
  else {
    $sql="UPDATE software set invoiceid='$invoiceid', slicenseinfo='$slicenseinfo', ".
       " manufacturerid='$manufacturerid', stitle='$stitle', sversion='$sversion', ".
       " sinfo='$sinfo', purchdate='$pd', licqty='$licqty', lictype='$lictype'  ".
       " WHERE id=$id";
    db_exec($dbh,$sql);
  }

  //RELATIONS

  if (!isset($_POST['softlnk'])) $softlnk=array();
  //update software - item links (installed into)
  //remove old links for this object
  $sql="delete from item2soft where softid=$id";
  db_exec($dbh,$sql);
  //add new links for each checked checkbox
  for ($i=0;$i<count($softlnk);$i++) {
    $sql="INSERT into item2soft (softid,itemid) values ($id,".$softlnk[$i].")";
    db_exec($dbh,$sql);
  }


  //invoice relations
  if (!isset($_POST['invlnk'])) $invlnk=array();
  else $invlnk=$_POST['invlnk'];
  //update software - invlnk links
  //remove old links for this object
  $sql="DELETE FROM soft2inv WHERE softid=$id";
  db_exec($dbh,$sql);
  //add new links for each checked checkbox
  for ($i=0;$i<count($invlnk);$i++) {
    $sql="INSERT INTO soft2inv (softid,invid) values ($id,".$invlnk[$i].")";
    db_exec($dbh,$sql);
  }

  //contract relations
  if (!isset($_POST['contrlnk'])) $contrlnk=array();
  else $contrlnk=$_POST['contrlnk'];

  //update software - contract links
  //remove old links for this object
  $sql="DELETE FROM contract2soft WHERE softid=$id";
  db_exec($dbh,$sql);
  //add new links for each checked checkbox
  for ($i=0;$i<count($contrlnk);$i++) {
    $sql="INSERT INTO contract2soft (softid,contractid) values ($id,".$contrlnk[$i].")";
    db_exec($dbh,$sql);
  }


}//save pressed

/////////////////////////////
//// display data now


if (!isset($_REQUEST['id'])) {echo "ERROR:ID not defined";exit;}
$id=$_REQUEST['id'];

$sql="SELECT * FROM software where id='$id'";
$sth=db_execute($dbh,$sql);
$r=$sth->fetch(PDO::FETCH_ASSOC);
if (($id !="new") && (count($r)<5)) {echo "ERROR: non-existent ID";exit;}

$manufacturerid=$r['manufacturerid'];
$stitle=$r['stitle'];
$sversion=$r['sversion'];
$purchdate=$r['purchdate'];
$slicensefile=$r['slicensefile'];
$slicenseinfo=$r['slicenseinfo'];
$sinfo=$r['sinfo'];
$invoiceid=$r['invoiceid'];
$licqty=$r['licqty'];
$lictype=$r['lictype'];


echo "\n<form id='mainform' method=post  action='$scriptname?action=$action&amp;id=$id' enctype='multipart/form-data'  name='addfrm'>\n";
?>



<?php 
if ($id=="new")
  echo "\n<h1>".t("Add Software")."</h1>\n";
else
  echo "\n<h1>".t("Edit Software")." ($id)</h1>\n";


?>

<!-- error errcontainer -->
<div class='errcontainer ui-state-error ui-corner-all' style='padding: 0 .7em;width:700px;margin-bottom:3px;'>
        <p><span class='ui-icon ui-icon-alert' style='float: left; margin-right: .3em;'></span>
        <h4>There are errors in your form submission, please see below for details.</h4>
        <ol>
                <li><label for="manufacturerid" class="error"><?php te("S/W Manufacturer is missing");?></label></li>
                <li><label for="stitle" class="error"><?php te("Software title is missing");?></label></li>
                <li><label for="sversion" class="error"><?php te("Software Version is missing");?></label></li>
                <li><label for="purchdate" class="error"><?php te("Date of purhcase is missing");?></label></li>
        </ol>
</div>


<div id="tabs">
  <ul>
  <li><a href="#tab1"><?php te("Software Data");?></a></li>
  <li><a href="#tab2"><?php te("Item Associations");?></a></li>
  <li><a href="#tab3"><?php te("Invoice Associations");?></a></li>
  <li><a href="#tab4"><?php te("Contract Associations");?></a></li>
  <li><a href="#tab5"><?php te("Upload Files");?></a></li>
  </ul>


<div id="tab1" class="tab_content">
  <?php 
  echo "<table class=tbl1 border=0>";

  $sql="SELECT * FROM itemtypes";
  $sth=$dbh->query($sql);
  $fixtypes=$sth->fetchAll(PDO::FETCH_ASSOC);

  for ($i=0;$i<count($fixtypes);$i++) {
    $typeid2name[$fixtypes[$i]['id']]=$fixtypes[$i]['typedesc'];
  }


  $qtsel="<select name='licqty'>\n";
  for ($i=1;$i<=400;$i++) {
    if ($licqty==$i) $s="SELECTED";
    else $s="";
    $qtsel.= "<option $s value='$i'>$i</option>\n";
  }
  $qtsel.="</select>\n";


  //
  //Associated files

  $f=softid2files($id,$dbh);
  $flnk=showfiles($f);

  $f2=softid2invoicefiles($id,$dbh);
  $flnk.=showfiles($f2,'fileslist2',0,'File of related invoice');

  $f3=softid2contractfiles($id,$dbh);
  $flnk.=showfiles($f3,'fileslist3',0,'File of related contract');

  $d=strlen($purchdate)?date($dateparam,$purchdate):"";

  ?>

  <tr>
  <td class="tdtop">

      <table class="tbl2" style='width:300px;'>
      <tr><td colspan=2><h3>Software Properties</h3></td></tr>
      <tr><td class="tdt">ID:</td> <td><input  class='input2' type=text name='id' value='<?php echo $id?>' readonly size=3></td></tr>
      <tr><td class="tdt">
     <?php   if (is_numeric($manufacturerid))
       echo "<a title='edit manufacturer (agent)' href='$scriptname?action=editagent&amp;id=$manufacturerid'><img src='images/edit.png'></a> "; ?>
      
      <?php te('Manufacturer');?>:</td> <td title='Add more manufacturers at the "Agents" menu'>
	   <select validate='required:true' class='mandatory' name='manufacturerid'>
	   <option value=''>Select</option>
	  <?php 
	    foreach ($agents as $a) {
	      if (!($a['type']&2)) continue; //show only manufacturers
	      $dbid=$a['id'];
	      $atype=$a['title']; $s="";
	      if (isset($manufacturerid) && $manufacturerid==$a['id']) $s=" SELECTED ";
	      echo "<option $s value='$dbid' title='$dbid'>$atype</option>\n";
	    }
	    echo "</select>\n";
	  ?>
	  
	  
      </td></tr>
      <tr><td class="tdt"><?php te("Title");?>:</td> <td><input  validate='required:true' class='input2 mandatory' size=20 type=text name='stitle' value="<?php echo $stitle?>"></td></tr>
      <tr><td class="tdt"><?php te("Version");?>:</td> <td><input  validate='required:true' class='input2 mandatory' size=20 type=text name='sversion' value="<?php echo $sversion?>"></td></tr>
      <tr>
	  <td class="tdt"><?php te("Purchase Date");?>:</td> <td><input  validate='required:true' class='mandatory dateinp' size=10 title='<?php echo $datetitle?>' type=text name='purchdate' id='purchdate' value='<?php echo $d?>'>
	  </td>
      </tr>


      <tr><td class="tdt"><?php te("Quantity");?>:</td> <td><?php echo $qtsel?> </td></tr>

  <?php 
  //licensetype
  $t0="";$t1="";$t2="";
  if (empty ($lictype) || $lictype=="0") {$t0="checked";$t1="";$t2="";}
  if ($lictype=="1") {$t1="checked";$t0="";$t2="";}
  if ($lictype=="2") {$t2="checked";$t0="";$t1="";}
  ?>
    <tr>
    <td class='tdt'>License Per:</td>
    <td>
    <input style='width:10%' type=radio <?php echo $t0?> name='lictype' value='0'>Box
    <input style='width:10%' type=radio <?php echo $t1?> name='lictype' value='1'>CPU
    <input style='width:10%' type=radio <?php echo $t2?> name='lictype' value='2'>Core
    </td>
    </tr>



      <tr><td class="tdt"><?php te("Licencing Info");?>:</td> <td colspan=2>
	      <textarea name='slicenseinfo' class='tarea2' wrap='soft'><?php echo $slicenseinfo?></textarea></td></tr>
      <tr><td class="tdt"><?php te("Other Info");?>:</td> <td colspan=2> <textarea name='sinfo' class='tarea2' wrap='soft'><?php echo $sinfo?></textarea> </td></tr>
      </table>
  </td>

  <td rowspan=1 class="tdtop"> <!-- related start -->
    <h3><?php te("Associations Overview");?></h3>
    <div style='text-align:center'>
      <span class="tita" onclick='showid("items");'><?php te("Items");?></span> |
      <span class="tita" onclick='showid("invoices1");'><?php te("Invoices");?></span> |
      <span class="tita" onclick='showid("contracts");'><?php te("Contracts");?></span>
    </div>

    <div class="scrltblcontainer4" >

    <div  id='items' class='relatedlist'>ITEMS</div>
    <?php 
    if (is_numeric($id)) {
      $sql="SELECT items.id, agents.title || ' ' || items.model || ' [' || itemtypes.typedesc || ', ID:' || items.id || ']' as txt ".
           "FROM agents,items,itemtypes,item2soft WHERE ".
           " agents.id=items.manufacturerid AND items.itemtypeid=itemtypes.id AND ".
           " item2soft.itemid=items.id AND item2soft.softid=$id";
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

   <div id='invoices1' class='relatedlist'>INVOICES</div>
    <?php 
    if (is_numeric($id)) {
      //print a table row
      $sql="SELECT invoices.id, invoices.number, invoices.date FROM invoices,soft2inv ".
           " WHERE soft2inv.invid=invoices.id AND soft2inv.softid=$id";
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

   <div id='contracts' class='relatedlist'>CONTRACTS</div>
    <?php 
    if (is_numeric($id)) {
      //print a table row
      $sql="SELECT contracts.id, type,title,number,startdate,currentenddate FROM contracts,contract2soft ".
           " WHERE contract2soft.contractid=contracts.id AND contract2soft.softid=$id";
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


  <td rowspan=1 class="tdtop"> <!-- related start -->
    <h3>Tags <span title='<?php te("Changes are saved immediately.<br>Removing tags removes associations not Tags. Use the Tags menu for that.");?>' style='font-weight:normal;font-size:70%'>(<a class="edit-tags" href=""><?php te("edit tags");?></a>)</span></h3>



      <?php 
      echo showtags("software",$id);
      ?>
      <script>
        ajaxtagscript="php/tag2software_ajaxedit.php?id=<?php echo $id?>";
        <?php 
        require_once('js/jquery.tag.front.js');
        ?>
      </script>
              <br>
              <div style='clear:both;height:20px;'></div>
              <div style='font-style:italic' id='result'></div>

  </td>


  <!-- upload -->
  </tr>

  <tr><td colspan=3 class="tdtop">

    <h3><?php te("Associated Files ");?><img onclick='window.location.href=window.location.href;' title='Refresh' src='images/refresh.png'></h3>
    <br>
	<?php echo $flnk?>

  </td>
  </tr>
  </table>
</div>

<div id='tab2' class='tab_content'>
  <table>
  <tr>
    <td colspan=3><h2> <?php te("Installed Into");?><sup>1</sup>
	<input style='color:#909090' id="itemsfilter" name="itemsfilter" class='filter' 
	       value='Filter' onclick='this.style.color="#000"; this.value=""' size="20">
	 <span style='font-weight:normal;' class='nres'></span>
    </h2>
    </td></tr>
    <tr><td colspan=3 class='tdc' >
    <?php 

    //////////////////////////////////////////////
    //connect to Items
    $sql=" SELECT COALESCE((SELECT itemid from item2soft where softid='$id'  AND itemid=items.id ),0) islinked , ".
	 " items.id,status,manufacturerid,model,itemtypeid,sn || ' '||sn2 ||' ' || sn3 as sn,dnsname,users.username ,label ".
	 " FROM items,itemtypes,users  WHERE items.itemtypeid=itemtypes.id AND itemtypes.hassoftware=1 AND users.id=userid ".
	 " order by islinked desc,itemtypeid,items.id desc, manufacturerid,model, dnsname ";
    $sth=db_execute($dbh,$sql);

    ?>
    <div style='margin-left:auto;margin-right:auto;' class='scrltblcontainer2'>
       <table width='100%' class='sortable'  id='itemslisttbl'>
	 <thead>
	    <tr><th><?php te("Installed");?></th><th style='width:70px'><?php te("ID");?></th><th><?php te("Type");?></th>
                <th><?php te("Manufacturer");?></th><th><?php te("Model");?></th>
	        <th><?php te("Label");?></th><th><?php te("DNS");?></th><th><?php te("User");?></th><th><?php te("S/N");?></th>
	    </tr>
	  </thead>
	  <tbody>
    <?php 

    while ($ir=$sth->fetch(PDO::FETCH_ASSOC)) {
      if ($ir['islinked']) {
	$cls="class='bld'";
      }
      else 
	$cls="";


      $x=attrofstatus((int)$ir['status'],$dbh);
      $attr=$x[0];
      $statustxt=$x[1];

      echo "\n <tr><td><input name='softlnk[]' value='".$ir['id']."' ";
      if ($ir['islinked']) echo " checked ";
      echo  " type='checkbox' /></td>".
       "<td nowrap $cls style='white-space: nowrap;'><span $attr>&nbsp;</span><a title='Edit item {$ir['id']} in a new window' ".
       "target=_blank href='$scriptname?action=edititem&id=".$ir['id']."'><div class='editid'>".
       $ir['id'].
       "</div></a></td>";
       echo "<td $attr $cls>".$typeid2name[$ir['itemtypeid']].
       "<td $cls>".$agents[$ir['manufacturerid']]['title']. "&nbsp;</td>".
       "<td $cls>".$ir['model'].  "&nbsp;</td>".
       "<td $cls>".$ir['label']."&nbsp;</td>".
       "<td $cls>".$ir['dnsname']."&nbsp;</td>".
       "<td $cls>".$ir['username']."&nbsp;</td>".
       "<td $cls>".$ir['sn']."&nbsp;</td></tr>\n";
    }
    echo "\n</tbody></table>\n";
    echo "</div>\n";
    ?>

    <sup>1</sup><?php te("Select systems where this software is currently installed. Only items with 'software support' in their item type are shown.");?>
    </td>

  </tr>
  </table>

</div><!-- tab2 -->


<div id='tab3' class='tab_content'>

  <h2>
      <input style='color:#909090' id="invfilter" name="invfilter" class='filter' 
             value='Filter' onclick='this.style.color="#000"; this.value=""' size="20">
	 <span style='font-weight:normal;' class='nres'></span>
  </h2>

  <?php 
  //////////////////////////////////////////////
  //connect to Items
  $sql=" SELECT COALESCE((SELECT invid from soft2inv WHERE softid='$id' AND invid=invoices.id ),0) islinked , ".
       " invoices.id, number,date,invoices.description as invdesc, agents.title AS agtitle  ".
       " FROM invoices,agents WHERE agents.id=invoices.vendorid ".
       " ORDER BY islinked desc,date,agtitle";
  $sth=db_execute($dbh,$sql);
  ?>
  <div style='margin-left:auto;margin-right:auto;' class='scrltblcontainer2'>
     <table width='100%' class='tbl2 brdr sortable'  id='invlisttbl'>
       <thead>
          <tr><th width='5%'><?php te("Associated");?></th><th><?php te("ID");?></th><th><?php te("Vendor");?></th>
              <th><?php te("Number");?></th><th><?php te("Title");?></th><th><?php te("Date");?></th>
          </tr>
        </thead>
        <tbody>
  <?php 

  while ($ir=$sth->fetch(PDO::FETCH_ASSOC)) {
    if ($ir['islinked']) {
      $cls="class='bld'";
    }
    else
      $cls="";

    echo "<tr><td><input name='invlnk[]' value='".$ir['id']."' ";
    if ($ir['islinked']) echo " checked ";
    echo  " type='checkbox' /></td>".
     "<td $cls><a title='Edit invoice {$ir['id']} in a new window' ".
     "target=_blank href='$scriptname?action=editinvoice&amp;id=".$ir['id']."'><div class='editid'>";
    echo $ir['id'];
    echo "</div></a></td>".
     "<td $cls>".$ir['agtitle'].  "&nbsp;</td>".
     "<td $cls>".$ir['number'].  "&nbsp;</td>".
     "<td $cls>".$ir['invdesc'].  "&nbsp;</td>".
     "<td $cls>". date("Y-m-d",$ir['date'])."&nbsp;</td></tr>\n";
  }
  ?>
  </tbody>
  </table>
  </div>

</div><!-- tab3-->

<div id='tab4' class='tab_content'>

  <h2><input style='color:#909090' id="contrfilter" name="contrfilter" class='filter' 
             value='Filter' onclick='this.style.color="#000"; this.value=""' size="20">
	 <span style='font-weight:normal;' class='nres'></span>
  </h2>

  <?php 
  //////////////////////////////////////////////
  //connect to Items
  $sql=" SELECT COALESCE((SELECT contractid FROM contract2soft WHERE softid='$id' AND contractid=contracts.id ),0) islinked , ".
       " contracts.id, contracts.title AS ctitle, agents.title AS agtitle  ".
       " FROM contracts,agents WHERE agents.id=contracts.contractorid ".
       " ORDER BY islinked desc,contractorid,ctitle";
  $sth=db_execute($dbh,$sql);
  ?>
  <div style='margin-left:auto;margin-right:auto;' class='scrltblcontainer2'>
     <table width='100%' class='tbl2 brdr sortable'  id='contrlisttbl'>
       <thead>
          <tr><th width='5%'><?php te("Associated");?></th><th><?php te("ID");?></th><th><?php te("Contractor");?></th><th><?php te("Title");?></th>
          </tr>
        </thead>
        <tbody>
  <?php 

  while ($ir=$sth->fetch(PDO::FETCH_ASSOC)) {
    if ($ir['islinked']) {
      $cls="class='bld'";
    }
    else
      $cls="";

    echo "<tr><td><input name='contrlnk[]' value='".$ir['id']."' ";
    if ($ir['islinked']) echo " checked ";
    echo  " type='checkbox' /></td>".
     "<td $cls><a title='Edit Contract {$ir['id']} in a new window' ".
     "target=_blank href='$scriptname?action=editcontract&amp;id=".$ir['id']."'><div class='editid'>";
    echo $ir['id'];
    echo "</div></a></td>".
     "<td $cls>".$ir['agtitle'].  "&nbsp;</td>".
     "<td $cls>".$ir['ctitle']."&nbsp;</td></tr>\n";
  }
  ?>
  </tbody>
  </table>
  </div>


</div><!-- tab4-->

<div id='tab5' class='tab_content'>
      <table class="tbl2" width='100%'>
      <tr><td colspan=2><h2>Upload a File</h2></td></tr>
      <!-- file upload -->
      <tr><td class="tdc">
      <iframe class="upload_frame" name="upload_frame" 
	    src="php/uploadframe.php?id=<?php echo $id?>&amp;assoctable=software2file&amp;colname=softwareid"  
	    frameborder="0" allowtransparency="true"></iframe>
      </td>
      </tr>
      </table>

</div>


</div> <!-- tab container -->

<table>
<tr><td colspan=2><button type="submit"><img src="images/save.png" alt="Save"> <?php te("Save");?></button></td>

<?php 
echo "\n<td><button type='button' onclick='javascript:delconfirm2(\"{$r['id']}\",\"$scriptname?action=$action&amp;delid={$r['id']}\");'>".
     "<img title='delete' src='images/delete.png' border=0> ".t("Delete")."</button></td>\n</tr>\n";
?>

</table>
<input type=hidden name='action' value='<?php echo $action?>'>
<input type=hidden name='id' value='<?php echo $id?>'>
</form>


</body>
</html>
